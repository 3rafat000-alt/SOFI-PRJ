<?php
/**
 * CSS Audit Tool for SAKK Admin Dashboard
 * Scans all blade files and reports CSS inconsistencies
 */

$bladeFiles = glob(__DIR__ . '/resources/views/admin/**/*.blade.php');
$issues = [];

// Patterns to check
$patterns = [
    'bg-white' => 'Use "card" class instead of bg-white for containers',
    'svg class=' => 'Use Material Icons instead of inline SVG',
    'bg-gray-50' => 'Use "input" class for form elements',
    'bg-blue-500' => 'Use "btn btn-primary" for buttons',
    'bg-green-50' => 'Use "badge badge-success" for status badges',
    'bg-red-50' => 'Use "badge badge-danger" for status badges',
    'bg-amber-50' => 'Use "badge badge-warning" for status badges',
    'rounded-full' => 'Use "badge" class for rounded badges',
    'text-gray-500 text-sm' => 'Use "stat-label" for stats',
    'text-3xl font-bold' => 'Use "stat-value" for stats',
    'w-full.*table' => 'Use "table-container" + "table" classes',
    'px-4 py-2\.5' => 'Use "input" class for form elements',
    'px-6 py-2\.5 bg-blue-500' => 'Use "btn btn-primary" for buttons',
    'px-4 py-2\.5 bg-emerald-50' => 'Use "btn btn-success" for buttons',
    'px-4 py-2\.5 bg-gray-100' => 'Use "btn btn-secondary" for buttons',
    'px-4 py-2 bg-white' => 'Use "btn btn-secondary" for buttons',
    'text-blue-500 hover:underline' => 'Use "link" class for links',
    'text-gray-900 text-sm' => 'Inconsistent font size usage',
    'hover:bg-blue-600' => 'Hover should be handled by btn class',
    'transition-all' => 'Use specific transition classes from design system',
    'border-gray-200' => 'Use var(--border) or border class from design system',
    'shadow-sm' => 'Use var(--shadow-sm) or card class',
    'text-gray-400' => 'Use text-muted or text-secondary',
    'text-gray-700' => 'Use text-secondary or text-primary',
    'text-gray-900' => 'Use text-primary',
    'font-medium' => 'Use font-bold or font-normal',
    'font-semibold' => 'Use font-bold or font-normal',
    'rounded-xl' => 'Use card or specific radius from design system',
    'rounded-lg' => 'Use var(--radius-md) or specific component',
    'rounded-2xl' => 'Use var(--radius-lg) or card class',
    'p-4' => 'Use spacing from design system',
    'p-5' => 'Use spacing from design system',
    'p-6' => 'Use spacing from design system',
    'mb-6' => 'Use spacing from design system',
    'gap-5' => 'Use spacing from design system',
    'gap-3' => 'Use spacing from design system',
];

foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    $relativePath = str_replace(__DIR__ . '/resources/views/admin/', '', $file);
    
    foreach ($patterns as $pattern => $message) {
        if (preg_match('/' . preg_quote($pattern, '/') . '/i', $content)) {
            $issues[$relativePath][] = $message;
        }
    }
}

// Report
echo "=== CSS AUDIT REPORT ===\n\n";
echo "Total files scanned: " . count($bladeFiles) . "\n";
echo "Files with issues: " . count($issues) . "\n\n";

foreach ($issues as $file => $fileIssues) {
    echo "📄 $file\n";
    $uniqueIssues = array_unique($fileIssues);
    foreach ($uniqueIssues as $issue) {
        echo "   ⚠️  $issue\n";
    }
    echo "\n";
}

// Summary
echo "=== SUMMARY ===\n";
$allIssues = [];
foreach ($issues as $fileIssues) {
    $allIssues = array_merge($allIssues, $fileIssues);
}
$issueCounts = array_count_values($allIssues);
arsort($issueCounts);

echo "\nMost common issues:\n";
foreach ($issueCounts as $issue => $count) {
    echo "  $count× $issue\n";
}

// Save report
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'total_files' => count($bladeFiles),
    'files_with_issues' => count($issues),
    'issues' => $issues,
    'summary' => $issueCounts,
];

file_put_contents(__DIR__ . '/css-audit-report.json', json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n📁 Report saved to: css-audit-report.json\n";
