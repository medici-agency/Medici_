#!/usr/bin/env python3
"""Виправлення Ampersand escaping в TEAM та TEAM_FIXED файлах."""

import re
from pathlib import Path

def fix_ampersand_file(file_path):
    """Виправити Ampersand escaping."""
    content = file_path.read_text(encoding='utf-8')
    original = content

    # Виправити: "\u0026:is(:hover, :focus)" → "\\u0026:is(:hover, :focus)"
    # Шукаємо pattern з одинарним backslash перед u0026
    pattern = r'"([^"]*?)(\\u0026:is\([^)]+\))"'

    def replace_ampersand(match):
        prefix = match.group(1)
        ampersand_part = match.group(2)
        # Додати другий backslash
        new_ampersand = ampersand_part.replace('\\u0026', '\\\\u0026')
        return f'"{prefix}{new_ampersand}"'

    content = re.sub(pattern, replace_ampersand, content)

    if content != original:
        file_path.write_text(content, encoding='utf-8')
        print(f"✅ {file_path.name} - Ampersand виправлено")
        return True
    return False

def main():
    """Головна функція."""
    gutenberg_dir = Path("/home/user/medici/gutenberg")

    print("🔧 Виправлення Ampersand escaping...")

    for filename in ['TEAM.html', 'TEAM_FIXED.html']:
        file_path = gutenberg_dir / filename
        if file_path.exists():
            fix_ampersand_file(file_path)

if __name__ == "__main__":
    main()
