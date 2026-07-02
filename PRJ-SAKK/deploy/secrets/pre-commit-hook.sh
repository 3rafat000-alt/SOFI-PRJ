#!/bin/bash
# Pre-commit hook to detect secrets
# Install: ln -s ../../deploy/secrets/pre-commit-hook.sh .git/hooks/pre-commit

echo "🔍 Scanning for secrets..."

# Detect potential secrets in staged files
STAGED_FILES=$(git diff --cached --name-only)
SECRET_PATTERNS=(
    "AKIA[0-9A-Z]{16}"  # AWS Access Key
    "sk_live_[0-9a-zA-Z]+"  # Stripe Live Secret
    "pk_live_[0-9a-zA-Z]+"  # Stripe Live Publishable
    "-----BEGIN PRIVATE KEY-----"
    "-----BEGIN RSA PRIVATE KEY-----"
    "APP_KEY=base64:"  # Should be placeholder only
    "DB_PASSWORD=[^$]"  # Non-empty DB password
    "password\s*=\s*[^'\"\s]"  # Password with value
    "secret\s*=\s*[^'\"\s]"  # Secret with value
    "api_key\s*=\s*[^'\"\s]"  # API key with value
)

has_secrets=false
for file in $STAGED_FILES; do
    if [ -f "$file" ]; then
        for pattern in "${SECRET_PATTERNS[@]}"; do
            if grep -qE "$pattern" "$file" 2>/dev/null; then
                echo "❌ SECRET FOUND in $file (pattern: $pattern)"
                has_secrets=true
            fi
        done
    fi
done

if [ "$has_secrets" = true ]; then
    echo "❌ Commit blocked! Remove secrets before committing."
    exit 1
fi

echo "✅ No secrets detected."
exit 0
