#!/bin/bash
# Documentation Validation Script
# Перевіряє документацію на compliance з CLAUDE.md правилами
# Usage: ./scripts/validate-docs.sh

set -e

echo "🔍 Validating documentation files..."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

failed=0

# 1. Перевірка розміру критичних файлів
check_file_size() {
    file=$1
    max_lines=$2
    current_lines=$(wc -l < "$file" 2>/dev/null || echo "0")

    if [ ! -f "$file" ]; then
        echo -e "${YELLOW}⚠️  SKIP: $file (file not found)${NC}"
        return 0
    fi

    if [ "$current_lines" -gt "$max_lines" ]; then
        echo -e "${RED}❌ FAIL: $file has $current_lines lines (max: $max_lines)${NC}"
        return 1
    fi
    echo -e "${GREEN}✅ PASS: $file size OK ($current_lines/$max_lines lines)${NC}"
    return 0
}

# 2. Перевірка заборонених keywords
check_forbidden_keywords() {
    file=$1

    if [ ! -f "$file" ]; then
        echo -e "${YELLOW}⚠️  SKIP: $file (file not found)${NC}"
        return 0
    fi

    # Keywords що вказують на tutorials/troubleshooting
    forbidden="step by step|how to use|walkthrough"

    matches=$(grep -iE "$forbidden" "$file" 2>/dev/null | head -n 5)

    if [ -n "$matches" ]; then
        echo -e "${RED}❌ FAIL: $file contains forbidden keywords:${NC}"
        echo "$matches"
        return 1
    fi
    echo -e "${GREEN}✅ PASS: $file keywords OK${NC}"
    return 0
}

# 3. Перевірка дублікатів з CHANGELOG
check_changelog_duplicates() {
    if git rev-parse --is-inside-work-tree > /dev/null 2>&1; then
        if git diff --cached CLAUDE.md 2>/dev/null | grep -q "^+.*\[1\.\|^+.*\[2\."; then
            echo -e "${RED}❌ FAIL: CLAUDE.md contains version history (use CHANGELOG.md)${NC}"
            return 1
        fi
    fi
    echo -e "${GREEN}✅ PASS: No changelog in CLAUDE.md${NC}"
    return 0
}

# 4. Перевірка наявності archive references
check_archive_references() {
    file=$1

    if [ ! -f "$file" ]; then
        return 0
    fi

    # Шукаємо посилання на archive/ або старі дати
    if grep -qE "docs/archive/|Archive|застарілі|deprecated.*202[0-3]" "$file" 2>/dev/null; then
        echo -e "${YELLOW}⚠️  WARNING: $file may contain archive references${NC}"
        # Not failing, just warning
    fi
    return 0
}

echo "=== File Size Checks ==="
check_file_size "CLAUDE.md" 2500 || failed=1
check_file_size "CHANGELOG.md" 1000 || failed=1
check_file_size "TODO.md" 300 || failed=1
check_file_size "docs/api/EVENTS-API.md" 400 || failed=1
check_file_size "docs/LEAD-TRACKING-RULES.md" 300 || failed=1
check_file_size "docs/DOCS-INDEX.md" 250 || failed=1

echo ""
echo "=== Forbidden Keywords Checks ==="
check_forbidden_keywords "CLAUDE.md" || failed=1
check_forbidden_keywords "docs/api/EVENTS-API.md" || failed=1
check_forbidden_keywords "docs/LEAD-TRACKING-RULES.md" || failed=1

echo ""
echo "=== Duplicate Content Checks ==="
check_changelog_duplicates || failed=1

echo ""
echo "=== Archive References Checks ==="
check_archive_references "CLAUDE.md"
check_archive_references "docs/DOCS-INDEX.md"

echo ""
echo "=================================================="

if [ $failed -eq 1 ]; then
    echo -e "${RED}❌ Documentation validation FAILED!${NC}"
    echo "Fix issues above before committing."
    exit 1
fi

echo -e "${GREEN}✅ All documentation checks PASSED!${NC}"
exit 0
