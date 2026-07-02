#!/usr/bin/env python3
"""
LTR currency scanner — ensure all displayed amounts use &lrm; prefix
so currency symbol ($ / ل.س) appears LEFT of number in RTL pages.

Usage:
  python scripts/check-ltr-currency.py              # scan project
  python scripts/check-ltr-currency.py --json       # JSON report
  python scripts/check-ltr-currency.py --path resources/views/admin/transactions/  # scan directory
  python scripts/check-ltr-currency.py --verbose    # show why matches were skipped

Reference: &lrm; = U+200E Left-to-Right Mark. Forces LTR direction for the
following text, so "$100" displays as "$100" (not "100$") in Arabic/RTL pages.
"""

import os, re, sys, json, argparse
from pathlib import Path
from datetime import datetime

# ─── Patterns that DEFINITELY need &lrm; ───
# These match actual user-visible amount display patterns found in the codebase.

NEEDS_LRM = [
    # Blade/PHP: {{ $sym }}{{ number_format( ... ) }}  (no &lrm; before {{)
    (r'{{ \$\s*sym\s*}}{{ number_format\(',
     '{{ $sym }}{{ number_format( — add &lrm; before {{'),

    # Blade/PHP: number_format(...) . ' ل.س'  (PHP string concat in views)
    (r"number_format\([^)]+\)\s*\.\s*['\"]\s*ل\.س",
     'number_format() . "ل.س" — add &lrm; before number_format'),

    # Blade/PHP: 'ل.س ' . number_format(...)  (PHP string concat in views)
    (r"['\"]\s*ل\.س\s*['\"]\s*\.\s*number_format",
     '"ل.س" . number_format() — add &lrm; before ل.س'),

    # Amount with currency label: {{ number_format(amount, 2) }} {{ $transaction->currency }}
    # Need to check on case-by-case basis — flag for manual review
    (r'\{\{ number_format\([^)]+\) \}\} \{\{ \$transaction->currency \}\}',
     '{{ number_format() }} {{ $transaction->currency }} — add &lrm; before number_format'),
]

# ─── Lines to SKIP entirely (false positive categories) ───
SKIP_LINE_RE = [
    # CSS / style blocks
    r'<style>', r'</style>', r'px;', r'rem;', r'em;', r'vw', r'vh', r'%\);',
    r'font-size', r'border-radius', r'padding', r'margin',
    r'min-width', r'max-width', r'line-height', r'opacity',
    r'box-shadow', r'transform', r'transition',

    # Comments
    r'^\s*//', r'^\s*/\*', r'\*/',
    r'{{--', r'--}}',

    # Code/class/function definitions
    r'^\s*(public|private|protected|static|function|class|interface|trait|enum|const|var)\s',
    r'case\s+\w+\s*=', r"'\$\d'", r'%\d+\$', r'\\\$',

    # Variable assignments (not display)
    r'\$[a-zA-Z_]+\s*=', r'collect\(', r'Config::',

    # Array/string definitions (not display)
    r"['\"].*['\"]\s*=>", r"['\"](fee|amount|balance|price|rate|commission|min_amount|max_amount)['\"]",

    # Blade directives
    r'@php', r'@endphp', r'@if', r'@endif', r'@foreach', r'@endfor',
    r'@section', r'@endsection', r'@extends', r'@include', r'@props',
    r'@push', r'@endpush', r'@stack',

    # Rate/unit labels (not displayed amounts)
    r'/\s*\$1\b', r'/\s*\$100\b', r'ل\.س\s*/\s*\$', r'الربح\s*/\s*\$',

    # JS/TS
    r'\.js\(', r'\.ts\(', r'function\s*\(', r'=>',
    r'regex', r'\.replace\(', r'\.match\(',

    # Tooltip/attribute content (has own dir attribute)
    r'title=', r'aria-label=', r'placeholder=',

    # file info comments
    r'Copyright', r'License', r'@package',
]


def line_should_skip(line: str) -> bool:
    """Return True if line should not be checked."""
    for pat in SKIP_LINE_RE:
        if re.search(pat, line):
            return True
    return False


def line_already_has_lrm(line: str, match_start: int, match_end: int) -> bool:
    """Check if &lrm; or ‎ (LRM) appears before the match on same line."""
    before = line[:match_start]
    # Check for LRM as HTML entity, hex entity, or Unicode char
    if '&lrm;' in before or '&#x200E;' in before or '&#8206;' in before:
        return True
    if '\u200E' in before:
        return True
    # Also check if the match is inside an already-LRM'd expression
    return False


def check_file(filepath: Path, verbose: bool = False) -> list[dict]:
    """Check a single file for currency display issues."""
    issues = []
    try:
        content = filepath.read_text(encoding='utf-8')
    except Exception as e:
        if verbose:
            print(f"  ⚠  skip {filepath.name}: {e}")
        return issues

    lines = content.split('\n')
    ext = filepath.suffix

    for i, line in enumerate(lines, 1):
        if line_should_skip(line):
            continue

        for pat, desc in NEEDS_LRM:
            for m in re.finditer(pat, line):
                if line_already_has_lrm(line, m.start(), m.end()):
                    if verbose:
                        print(f"  ✓ {filepath.name}:{i} already has &lrm; ✓")
                    continue
                issues.append({
                    'file': str(filepath),
                    'line': i,
                    'column': m.start() + 1,
                    'match': m.group().strip()[:100],
                    'description': desc,
                })
                if verbose:
                    print(f"  ✗ {filepath.name}:{i} — {desc}")
                    print(f"    › {m.group().strip()[:100]}")

    return issues


EXTENSIONS = {'.php', '.blade.php'}
EXCLUDE_DIRS = {'vendor', 'node_modules', '.git', 'storage', 'bootstrap/cache', 'docs'}


def scan(path: str, verbose: bool = False, json_output: bool = False):
    """Recursively scan directory."""
    base = Path(path).resolve()
    all_issues = []
    scanned = 0
    with_issues = 0

    for fp in base.rglob('*'):
        rel = fp.relative_to(base)
        if any(part in EXCLUDE_DIRS for part in rel.parts):
            continue
        if fp.suffix not in EXTENSIONS:
            continue

        issues = check_file(fp, verbose=verbose)
        scanned += 1
        if issues:
            all_issues.extend(issues)
            with_issues += 1
            if not json_output:
                print(f"\n  📄 {rel}")
                for iss in issues:
                    print(f"    ⚠️  L{iss['line']}:{iss['column']} — {iss['description']}")
                    print(f"       › {iss['match']}")

    return all_issues, scanned, with_issues


def main():
    parser = argparse.ArgumentParser(
        description='Check currency amounts have &lrm; prefix in RTL Laravel project',
    )
    parser.add_argument('--path', help='Scan specific path (default: project root)')
    parser.add_argument('--json', action='store_true', help='Output JSON report')
    parser.add_argument('--verbose', action='store_true', help='Show skipped lines too')
    args = parser.parse_args()

    # Auto-detect project root
    script_dir = Path(__file__).resolve().parent  # backend/scripts/
    if (script_dir.parent / 'artisan').exists():
        project = script_dir.parent
    else:
        project = script_dir

    scan_path = project / args.path if args.path else project

    if not scan_path.exists():
        print(f"❌ Path not found: {scan_path}")
        sys.exit(1)

    print(f"🔍 LTR currency scan")
    print(f"   Path: {scan_path}")
    print(f"   {'─' * 50}")

    start = datetime.now()
    issues, scanned, with_issues = scan(
        str(scan_path), verbose=args.verbose, json_output=args.json
    )
    elapsed = (datetime.now() - start).total_seconds()

    if args.json:
        print(json.dumps({
            'scanned_at': start.isoformat(),
            'project': str(project),
            'files_scanned': scanned,
            'files_with_issues': with_issues,
            'total_issues': len(issues),
            'issues': issues,
            'duration_seconds': elapsed,
        }, ensure_ascii=False, indent=2))
    else:
        print(f"\n{'─' * 50}")
        print(f"📊 {len(issues)} issues in {with_issues}/{scanned} files ({elapsed:.2f}s)")
        if not issues:
            print("✅ All amounts have &lrm; prefix — clean!")
        else:
            print(f"⚠️  {len(issues)} issue(s) remaining — fix manually")

    return 0 if not issues else 1


if __name__ == '__main__':
    main()
