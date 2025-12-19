#!/usr/bin/env python3
"""Перевірка CSS змінних у FAQ.html на відповідність з реальними змінними теми."""

import re
from pathlib import Path

# Читаємо FAQ.html
faq_file = Path("/home/user/medici/gutenberg/FAQ.html")
content = faq_file.read_text()

# Знайти всі CSS змінні у "styles" атрибутах
styles_vars = set()
pattern = r'"styles":\{[^}]+?"color":"var\(\\\\u002d\\\\u002d([^)]+)\)'
for match in re.finditer(pattern, content):
    var_name = match.group(1).replace('\\u002d', '-')
    styles_vars.add(f"--{var_name}")

# Знайти всі CSS змінні у "css" атрибутах  
css_vars = set()
pattern = r'"css":"[^"]*var\(--([^)]+)\)'
for match in re.finditer(pattern, content):
    css_vars.add(f"--{match.group(1)}")

print("📊 АНАЛІЗ CSS ЗМІННИХ У FAQ.HTML\n")
print(f"Змінних у 'styles': {len(styles_vars)}")
for var in sorted(styles_vars):
    print(f"  - {var}")

print(f"\nЗмінних у 'css': {len(css_vars)}")
for var in sorted(css_vars):
    print(f"  - {var}")

# Перевірка чи всі змінні з styles існують в css
print("\n" + "="*60)
mismatch = styles_vars - css_vars
if mismatch:
    print("❌ НЕУЗГОДЖЕНІСТЬ: змінні в 'styles' відсутні в 'css':")
    for var in sorted(mismatch):
        print(f"  - {var}")
else:
    print("✅ Всі змінні узгоджені")
