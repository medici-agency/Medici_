#!/bin/bash
# Git Sparse Checkout Toggle Script
# Перемикання між Full та Dev режимами

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

print_help() {
    cat <<EOF
🚀 Git Sparse Checkout Toggle Script

USAGE:
    $0 [enable|disable|status]

COMMANDS:
    enable   - Увімкнути Dev режим (виключити assets, bot, docs, etc.)
    disable  - Вимкнути Dev режим (повний checkout)
    status   - Показати поточний статус

EXAMPLES:
    $0 enable      # Активувати Dev режим
    $0 disable     # Повернутись до Full режиму
    $0 status      # Перевірити поточний режим

EXCLUDED in Dev mode:
    - assets/  (11MB, fonts, images)
    - bot/     (110KB, bot scripts)
    - docs/    (446KB, крім docs/coding-rules/)
    - fonts/   (191KB, web fonts)
    - scripts/ (58KB, build scripts)
    - skills/  (8.7MB, AI skills)

INCLUDED (критичні):
    - package.json, composer.json, theme.json
    - .eslintrc.json, .prettierrc.json, .stylelintrc.json
    - inc/, css/, js/, templates/, gutenberg/, plugins/
    - docs/coding-rules/ (потрібні для LLM!)
    - .github/ (CI/CD workflows)

EOF
}

enable_sparse_checkout() {
    echo -e "${YELLOW}🔧 Активую Dev режим (Sparse Checkout)...${NC}"

    # Check if sparse-checkout file exists
    if [ ! -f .git/info/sparse-checkout ]; then
        echo -e "${RED}❌ Помилка: Файл .git/info/sparse-checkout не знайдено!${NC}"
        echo -e "${YELLOW}Створіть файл спочатку або запустіть git sparse-checkout init${NC}"
        exit 1
    fi

    # Enable sparse-checkout
    git config core.sparseCheckout true

    # Apply sparse-checkout patterns (non-cone mode)
    git sparse-checkout init --no-cone
    git sparse-checkout set --stdin < .git/info/sparse-checkout

    # Refresh working tree
    echo -e "${YELLOW}🔄 Оновлюю робочу директорію...${NC}"
    git checkout HEAD -- .

    echo -e "${GREEN}✅ Dev режим активовано!${NC}"
    echo -e "${YELLOW}📊 Статистика:${NC}"
    local excluded=$(git ls-files | grep -E "^(assets|bot|skills|fonts|scripts|docs)/" | wc -l)
    local remaining=$(git ls-files | grep -v -E "^(assets|bot|skills|fonts|scripts|docs)/" | wc -l)
    echo "   Файлів виключено: $excluded"
    echo "   Файлів залишилось: $remaining"
}

disable_sparse_checkout() {
    echo -e "${YELLOW}🔧 Деактивую Dev режим (Full Checkout)...${NC}"

    # Disable sparse-checkout
    git config core.sparseCheckout false
    git sparse-checkout disable

    # Restore all files
    echo -e "${YELLOW}🔄 Відновлюю всі файли...${NC}"
    git checkout HEAD -- .

    echo -e "${GREEN}✅ Full режим активовано!${NC}"
    echo -e "${YELLOW}📊 Статистика:${NC}"
    git ls-files | wc -l | xargs echo "   Всього файлів в checkout:"
}

show_status() {
    echo -e "${YELLOW}📊 Поточний статус Git Sparse Checkout:${NC}"

    if git config core.sparseCheckout | grep -q "true"; then
        echo -e "${GREEN}✅ Dev режим АКТИВОВАНО${NC}"
        echo -e "\n${YELLOW}Виключені директорії:${NC}"
        grep "^!" .git/info/sparse-checkout 2>/dev/null | sed 's/^!/   - /' || echo "   (немає)"
        echo -e "\n${YELLOW}Файлів в checkout:${NC}"
        local excluded=$(git ls-files | grep -E "^(assets|bot|skills|fonts|scripts|docs)/" | wc -l)
        local remaining=$(git ls-files | grep -v -E "^(assets|bot|skills|fonts|scripts|docs)/" | wc -l)
        echo "   Виключено: $excluded"
        echo "   Залишилось: $remaining (активні для розробки)"
    else
        echo -e "${RED}❌ Dev режим ДЕАКТИВОВАНО (Full Checkout)${NC}"
        echo -e "\n${YELLOW}Файлів в checkout:${NC}"
        git ls-files | wc -l | xargs echo "   Всього: "
    fi

    echo -e "\n${YELLOW}Розміри директорій:${NC}"
    du -sh assets bot docs fonts scripts skills 2>/dev/null | awk '{printf "   %-10s %s\n", $2, $1}' || echo "   (директорії виключені)"
}

# Main script
case "${1:-}" in
    enable)
        enable_sparse_checkout
        ;;
    disable)
        disable_sparse_checkout
        ;;
    status)
        show_status
        ;;
    -h|--help|help|"")
        print_help
        ;;
    *)
        echo -e "${RED}❌ Невідома команда: $1${NC}"
        print_help
        exit 1
        ;;
esac
