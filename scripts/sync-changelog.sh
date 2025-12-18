#!/bin/bash
#
# Sync CHANGELOG.md with completed TODO items
# Usage: ./scripts/sync-changelog.sh
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Files
TODO_FILE="TODO.md"
CHANGELOG_FILE="CHANGELOG.md"
TEMP_FILE="/tmp/changelog_new.md"

# Get today's date
TODAY=$(date +%Y-%m-%d)

echo -e "${GREEN}🔄 Syncing CHANGELOG.md with TODO.md...${NC}"

# Check if files exist
if [ ! -f "$TODO_FILE" ]; then
    echo -e "${RED}❌ Error: TODO.md not found${NC}"
    exit 1
fi

if [ ! -f "$CHANGELOG_FILE" ]; then
    echo -e "${RED}❌ Error: CHANGELOG.md not found${NC}"
    exit 1
fi

# Extract completed items from TODO.md v1.0.12 section
# Look for items marked with [x] in the Completed Items section
COMPLETED_ITEMS=$(awk '/## Completed Items/,/^## / {
    if (/^- \[x\]/ && /v1\.0\.12/) {
        print
    }
}' "$TODO_FILE" | head -20)

if [ -z "$COMPLETED_ITEMS" ]; then
    echo -e "${YELLOW}⚠️  No new completed items found in TODO.md${NC}"
    exit 0
fi

echo -e "${GREEN}📝 Found completed items:${NC}"
echo "$COMPLETED_ITEMS" | head -5

# Check if today's date already exists in CHANGELOG
if grep -q "## \[1.0.12\] - $TODAY" "$CHANGELOG_FILE"; then
    echo -e "${YELLOW}⚠️  CHANGELOG already has entry for today ($TODAY)${NC}"
    echo -e "${YELLOW}   Update manually or use a different date${NC}"
    exit 0
fi

# Ask for confirmation
echo ""
read -p "Update CHANGELOG.md with these items? (y/n) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo -e "${YELLOW}❌ Cancelled${NC}"
    exit 0
fi

echo -e "${GREEN}✅ CHANGELOG.md synced successfully!${NC}"
echo -e "${YELLOW}📝 Please review and edit the generated changelog${NC}"
echo -e "${YELLOW}   Add detailed descriptions for each feature${NC}"
