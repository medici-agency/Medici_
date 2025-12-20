cat > scripts/install-hooks.sh << 'EOF'
#!/bin/bash

# Medici Theme - Install Git Hooks
# Run this script once after cloning the repository
# Usage: ./scripts/install-hooks.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ROOT_DIR="$(dirname "$SCRIPT_DIR")"
HOOKS_DIR="$ROOT_DIR/.git/hooks"

echo "🔧 Installing Git hooks for Medici Theme..."
echo ""

# Check if we're in a git repository
if [ ! -d "$ROOT_DIR/.git" ]; then
    echo "❌ Error: Not a git repository"
    echo "   Run this script from the theme root directory"
    exit 1
fi

# Create hooks directory if it doesn't exist
mkdir -p "$HOOKS_DIR"

# Install pre-commit hook
if [ -f "$SCRIPT_DIR/pre-commit" ]; then
    # Create symlink to pre-commit script
    ln -sf "../../scripts/pre-commit" "$HOOKS_DIR/pre-commit"
    chmod +x "$SCRIPT_DIR/pre-commit"
    echo "✅ Pre-commit hook installed"
else
    echo "❌ Error: pre-commit script not found"
    exit 1
fi

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✅ Git hooks installed successfully!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "The pre-commit hook will now run automatically before each commit."
echo "To skip the hook temporarily: git commit --no-verify"
echo ""
echo "To uninstall hooks: rm .git/hooks/pre-commit"
EOF