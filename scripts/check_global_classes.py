#!/usr/bin/env python3
"""Перевірка узгодження Global Classes між HTML та CSS файлами."""

import re
import json
from pathlib import Path
from collections import defaultdict

def extract_global_classes_from_html(file_path):
    """Витягти всі globalClasses з HTML файлу."""
    content = file_path.read_text(encoding='utf-8')

    # Знайти всі "globalClasses":[...] блоки
    pattern = r'"globalClasses":\s*\[([^\]]+)\]'
    matches = re.findall(pattern, content)

    classes = set()
    for match in matches:
        # Очистити класи від лапок та пробілів
        class_list = re.findall(r'"([^"]+)"', match)

        # Декодувати Unicode escaping: \u002d\u002d → --
        decoded_classes = []
        for cls in class_list:
            # Замінити \u002d на - (hyphen)
            decoded = cls.replace('\\u002d', '-')
            decoded_classes.append(decoded)

        classes.update(decoded_classes)

    return classes

def extract_css_classes_from_file(file_path):
    """Витягти всі класи .gbp-* з CSS файлу."""
    content = file_path.read_text(encoding='utf-8')

    # Знайти всі .gbp-* класи (включаючи модифікатори та елементи)
    pattern = r'\.gbp-[\w-]+(?:__[\w-]+)?(?:--[\w-]+)?'
    matches = re.findall(pattern, content)

    # Прибрати крапку на початку
    classes = {cls[1:] for cls in matches}

    return classes

def main():
    """Головна функція."""
    gutenberg_dir = Path("/home/user/medici/gutenberg")
    css_dir = Path("/home/user/medici/css")

    print("🔍 Збір Global Classes з HTML файлів...")
    html_classes = set()
    html_files_classes = {}

    for html_file in sorted(gutenberg_dir.glob('*.html')):
        classes = extract_global_classes_from_html(html_file)
        if classes:
            html_files_classes[html_file.name] = classes
            html_classes.update(classes)

    print(f"✅ Знайдено {len(html_classes)} унікальних Global Classes в HTML файлах\n")

    print("🔍 Збір класів з CSS файлів...")
    css_classes = set()
    css_files = [
        css_dir / "components/sections.css",
        css_dir / "components/cards.css",
        css_dir / "components/buttons.css",
        css_dir / "components/navigation.css",
        css_dir / "components/forms.css",
        css_dir / "components/faq.css",
        css_dir / "layout/layout.css",
    ]

    for css_file in css_files:
        if css_file.exists():
            classes = extract_css_classes_from_file(css_file)
            css_classes.update(classes)
            print(f"  {css_file.name}: {len(classes)} класів")

    print(f"\n✅ Знайдено {len(css_classes)} унікальних .gbp-* класів в CSS файлах\n")

    # Перевірка узгодження
    print(f"{'='*60}")
    print("📊 АНАЛІЗ УЗГОДЖЕННЯ")
    print(f"{'='*60}\n")

    # Класи в HTML які НЕ визначені в CSS
    missing_in_css = html_classes - css_classes
    if missing_in_css:
        print("❌ КЛАСИ В HTML БЕЗ CSS ВИЗНАЧЕННЯ:")
        for cls in sorted(missing_in_css):
            print(f"  - {cls}")
            # Знайти в яких файлах використовується
            for filename, classes in html_files_classes.items():
                if cls in classes:
                    print(f"    Використовується в: {filename}")
        print()
    else:
        print("✅ Всі класи з HTML визначені в CSS\n")

    # Класи в CSS які НЕ використовуються в HTML
    unused_in_html = css_classes - html_classes
    if unused_in_html:
        print("⚠️  КЛАСИ В CSS БЕЗ ВИКОРИСТАННЯ В HTML:")
        print(f"   (це нормально якщо класи використовуються в темплейтах або JS)")
        for cls in sorted(unused_in_html):
            print(f"  - {cls}")
        print()

    # Статистика по файлах
    print(f"{'='*60}")
    print("📋 СТАТИСТИКА ПО HTML ФАЙЛАХ")
    print(f"{'='*60}\n")

    for filename in sorted(html_files_classes.keys()):
        classes = html_files_classes[filename]
        print(f"\n{filename}: {len(classes)} класів")
        for cls in sorted(classes):
            status = "✅" if cls in css_classes else "❌"
            print(f"  {status} {cls}")

    # Підсумок
    print(f"\n{'='*60}")
    print("✅ ПІДСУМОК")
    print(f"{'='*60}")
    print(f"HTML файлів проаналізовано: {len(html_files_classes)}")
    print(f"CSS файлів проаналізовано: {len([f for f in css_files if f.exists()])}")
    print(f"Global Classes в HTML: {len(html_classes)}")
    print(f"Класів .gbp-* в CSS: {len(css_classes)}")
    print(f"Класів без CSS визначення: {len(missing_in_css)}")
    print(f"Невикористаних класів в CSS: {len(unused_in_html)}")

    if not missing_in_css:
        print("\n🎉 УЗГОДЖЕННЯ ІДЕАЛЬНЕ - всі HTML класи визначені в CSS!")
    else:
        print(f"\n⚠️  ПОТРІБНО ДОДАТИ {len(missing_in_css)} класів до CSS файлів")

if __name__ == "__main__":
    main()
