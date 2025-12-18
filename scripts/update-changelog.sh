#!/bin/bash
#
# CHANGELOG Update Helper
# Автоматичний помічник для оновлення CHANGELOG.md
#
# Використання:
#   ./scripts/update-changelog.sh
#

set -e

# Colors
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

CHANGELOG_FILE="CHANGELOG.md"
TODO_FILE="TODO.md"
TODAY=$(date +%Y-%m-%d)

echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${BLUE}📝 CHANGELOG Update Helper${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo ""

# Check if TODO.md has been modified
if git diff --name-only HEAD~1 HEAD 2>/dev/null | grep -q "$TODO_FILE"; then
    echo -e "${GREEN}✓ TODO.md був змінений у останньому коміті${NC}"
    echo ""

    # Extract completed items from TODO.md
    echo -e "${YELLOW}Виконані завдання з TODO.md:${NC}"
    grep -E "^- \[x\]" "$TODO_FILE" | tail -n 10 | sed 's/^/  /'
    echo ""
else
    echo -e "${YELLOW}⚠ TODO.md не був змінений у останньому коміті${NC}"
    echo ""
fi

# Show current CHANGELOG version
CURRENT_VERSION=$(grep -m 1 "^## \[" "$CHANGELOG_FILE" | sed 's/^## \[\(.*\)\] - .*/\1/')
echo -e "${BLUE}Поточна версія в CHANGELOG: ${GREEN}$CURRENT_VERSION${NC}"
echo ""

# Interactive mode
echo -e "${YELLOW}Що ви хочете додати до CHANGELOG?${NC}"
echo ""
echo "1. Added (нові функції)"
echo "2. Changed (зміни у функціоналі)"
echo "3. Fixed (виправлення)"
echo "4. Performance (оптимізації)"
echo "5. Показати шаблон"
echo "6. Вихід"
echo ""

read -p "Виберіть опцію (1-6): " choice

case $choice in
    1)
        echo ""
        read -p "Опишіть нову функцію: " feature
        echo -e "${GREEN}Додайте до CHANGELOG в секцію Added:${NC}"
        echo "- $feature"
        ;;
    2)
        echo ""
        read -p "Опишіть зміну: " change
        echo -e "${GREEN}Додайте до CHANGELOG в секцію Changed:${NC}"
        echo "- $change"
        ;;
    3)
        echo ""
        read -p "Опишіть виправлення: " fix
        echo -e "${GREEN}Додайте до CHANGELOG в секцію Fixed:${NC}"
        echo "- $fix"
        ;;
    4)
        echo ""
        read -p "Опишіть оптимізацію: " perf
        echo -e "${GREEN}Додайте до CHANGELOG в секцію Performance:${NC}"
        echo "- $perf"
        ;;
    5)
        echo ""
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        echo -e "${GREEN}Шаблон для CHANGELOG.md:${NC}"
        echo -e "${GREEN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
        cat << 'EOF'

## [1.0.12] - 2025-11-26

### Added
- **Назва функції**: Короткий опис
  - Деталь 1
  - Деталь 2

### Changed
- Опис змін в існуючому функціоналі
- Оновлення файлів

### Fixed
- Виправлення бага або проблеми

### Performance
- Оптимізації та покращення продуктивності

---

EOF
        ;;
    6)
        echo -e "${YELLOW}Вихід${NC}"
        exit 0
        ;;
    *)
        echo -e "${YELLOW}Невірний вибір${NC}"
        exit 1
        ;;
esac

echo ""
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
echo -e "${GREEN}✓ Готово!${NC}"
echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
