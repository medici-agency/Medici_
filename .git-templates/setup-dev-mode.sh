#!/bin/bash
# Git Dev Mode Setup Script
# Автоматичне налаштування Git Sparse Checkout для нових розробників

set -e

YELLOW='\033[1;33m'
GREEN='\033[0;32m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🚀 Налаштування Git Dev Mode...${NC}"

# 1. Copy sparse-checkout configuration
echo -e "${YELLOW}📝 Копіюю sparse-checkout конфігурацію...${NC}"
cp .git-templates/sparse-checkout.template .git/info/sparse-checkout

# 2. Copy exclude configuration
echo -e "${YELLOW}📝 Копіюю exclude конфігурацію...${NC}"
cp .git-templates/exclude.template .git/info/exclude

# 3. Copy toggle script
echo -e "${YELLOW}📝 Копіюю toggle скрипт...${NC}"
cp .git-templates/sparse-checkout-toggle.sh .git/info/sparse-checkout-toggle.sh
chmod +x .git/info/sparse-checkout-toggle.sh

# 4. Create symlink
echo -e "${YELLOW}🔗 Створюю symlink git-dev-mode...${NC}"
ln -sf .git/info/sparse-checkout-toggle.sh git-dev-mode 2>/dev/null || true

echo -e "${GREEN}✅ Налаштування завершено!${NC}"
echo -e ""
echo -e "${YELLOW}📋 Доступні команди:${NC}"
echo -e "   ./git-dev-mode enable   - Активувати Dev режим"
echo -e "   ./git-dev-mode status   - Перевірити статус"
echo -e "   ./git-dev-mode disable  - Деактивувати"
echo -e ""
echo -e "${YELLOW}📖 Документація:${NC}"
echo -e "   docs/devops/GIT-SPARSE-CHECKOUT.md"
echo -e ""
echo -e "${YELLOW}💡 Рекомендація:${NC}"
echo -e "   Запустіть: ./git-dev-mode enable"
echo -e "   Прискорить розробку в 10-20 разів!"
