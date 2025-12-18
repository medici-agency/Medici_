#!/usr/bin/env python3
"""Автоматичне виправлення escaping в HTML файлах."""

import re
from pathlib import Path

def fix_html_file(file_path):
    """Виправлення escaping в HTML файлі."""
    content = file_path.read_text(encoding='utf-8')
    original = content
    changes = []

    # 1. Виправити CSS Variables: \u002d\u002d → \\u002d\\u002d
    # Знайти всі var(\u002d\u002d...) та замінити на var(\\u002d\\u002d...)
    pattern1 = r'var\(\\u002d\\u002d([^)]+)\)'
    matches = list(re.finditer(pattern1, content))
    if matches:
        # Replace with escaped version
        for match in matches:
            old = match.group(0)
            new = old.replace('\\u002d\\u002d', '\\\\u002d\\\\u002d')
            content = content.replace(old, new, 1)
        changes.append(f"✅ CSS Variables: {len(matches)} виправлено")

    # 2. Виправити Ampersand: \u0026 → \\u0026
    pattern2 = r'(?<!\\)\\u0026(:is\([^)]+\))'
    matches2 = list(re.finditer(pattern2, content))
    if matches2:
        for match in matches2:
            old = match.group(0)
            new = old.replace('\\u0026', '\\\\u0026')
            content = content.replace(old, new, 1)
        changes.append(f"✅ Ampersand: {len(matches2)} виправлено")

    # Записати якщо були зміни
    if content != original:
        file_path.write_text(content, encoding='utf-8')
        print(f"\n{'='*60}")
        print(f"📝 {file_path.name}")
        print(f"{'='*60}")
        for change in changes:
            print(change)
        return True
    return False

def main():
    """Головна функція."""
    gutenberg_dir = Path("/home/user/medici/gutenberg")
    files_fixed = 0

    print("🚀 Початок автоматичного виправлення HTML файлів...")

    for html_file in sorted(gutenberg_dir.glob('*.html')):
        if fix_html_file(html_file):
            files_fixed += 1

    print(f"\n{'='*60}")
    print(f"✅ ГОТОВО! Виправлено {files_fixed} файлів")
    print(f"{'='*60}")

if __name__ == "__main__":
    main()
