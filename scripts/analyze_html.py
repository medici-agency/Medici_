#!/usr/bin/env python3
"""Аналіз HTML файлів проекту Medici на відповідність CODING-RULES."""

import re
import json
from pathlib import Path
from collections import defaultdict

def analyze_html_files(directory):
    """Аналіз всіх HTML файлів."""
    issues = defaultdict(list)
    stats = defaultdict(int)

    for html_file in Path(directory).glob('*.html'):
        print(f"\n{'='*60}")
        print(f"Аналіз: {html_file.name}")
        print(f"{'='*60}")

        content = html_file.read_text(encoding='utf-8')

        # 1. UniqueId формат - 8 hex символів, lowercase
        unique_ids = re.findall(r'"uniqueId":"([^"]+)"', content)
        for uid in unique_ids:
            stats['total_uniqueIds'] += 1
            if not re.match(r'^[0-9a-f]{8}$', uid):
                issues[html_file.name].append(f"❌ UniqueId НЕПРАВИЛЬНИЙ: '{uid}' (має бути 8 hex lowercase)")
            else:
                stats['valid_uniqueIds'] += 1

        # 2. CSS Variables - має бути \\u002d\\u002d (подвійний backslash)
        css_vars_single = re.findall(r'var\(\\u002d\\u002d([^)]+)\)', content)
        css_vars_double = re.findall(r'var\(\\\\u002d\\\\u002d([^)]+)\)', content)

        if css_vars_single:
            stats['css_vars_single_backslash'] += len(css_vars_single)
            issues[html_file.name].append(f"❌ CSS Variables з ОДИНАРНИМ backslash: {len(css_vars_single)} шт. (має бути подвійний!)")
            print(f"Приклад: var(\\u002d\\u002d{css_vars_single[0]})")

        if css_vars_double:
            stats['css_vars_double_backslash'] += len(css_vars_double)
            print(f"✅ CSS Variables з ПОДВІЙНИМ backslash: {len(css_vars_double)} шт.")

        # 3. Ampersand escaping - має бути \\u0026
        ampersand_single = re.findall(r'\\u0026:is\(([^)]+)\)', content)
        ampersand_double = re.findall(r'\\\\u0026:is\(([^)]+)\)', content)

        if ampersand_single:
            stats['ampersand_single'] += len(ampersand_single)
            issues[html_file.name].append(f"❌ Ampersand з ОДИНАРНИМ backslash: {len(ampersand_single)} шт.")

        if ampersand_double:
            stats['ampersand_double'] += len(ampersand_double)
            print(f"✅ Ampersand з ПОДВІЙНИМ backslash: {len(ampersand_double)} шт.")

        # 4. Responsive breakpoints
        breakpoint_768 = re.findall(r'@media \(max-width:\s*767?px\)', content)
        breakpoint_1024 = re.findall(r'@media \(max-width:\s*1024px\)', content)

        stats['breakpoint_768'] += len(breakpoint_768)
        stats['breakpoint_1024'] += len(breakpoint_1024)

        if breakpoint_768 or breakpoint_1024:
            print(f"✅ Responsive: 768px ({len(breakpoint_768)}), 1024px ({len(breakpoint_1024)})")
        else:
            issues[html_file.name].append("⚠️  Responsive breakpoints відсутні")

        # 5. Global Classes
        global_classes = re.findall(r'"globalClasses":\s*\[([^\]]+)\]', content)
        if global_classes:
            stats['has_global_classes'] += 1
            print(f"✅ Global Classes використовуються")

        # 6. Accessibility attributes
        aria_labels = re.findall(r'"aria-label":"([^"]+)"', content)
        if aria_labels:
            stats['has_aria_labels'] += 1
            print(f"✅ ARIA labels: {len(aria_labels)} шт.")

        stats['total_files'] += 1

    return issues, stats

def print_summary(issues, stats):
    """Вивід підсумку."""
    print(f"\n\n{'='*60}")
    print("📊 ПІДСУМОК АНАЛІЗУ")
    print(f"{'='*60}")

    print(f"\n✅ Статистика:")
    print(f"- Всього файлів: {stats['total_files']}")
    print(f"- Всього UniqueIds: {stats['total_uniqueIds']}")
    print(f"- Правильних UniqueIds: {stats['valid_uniqueIds']}")
    print(f"- CSS Vars (одинарний backslash): {stats['css_vars_single_backslash']}")
    print(f"- CSS Vars (подвійний backslash): {stats['css_vars_double_backslash']}")
    print(f"- Ampersand (одинарний): {stats['ampersand_single']}")
    print(f"- Ampersand (подвійний): {stats['ampersand_double']}")
    print(f"- Breakpoints 768px: {stats['breakpoint_768']}")
    print(f"- Breakpoints 1024px: {stats['breakpoint_1024']}")
    print(f"- Файлів з Global Classes: {stats['has_global_classes']}")
    print(f"- Файлів з ARIA labels: {stats['has_aria_labels']}")

    print(f"\n❌ Проблеми знайдені в {len(issues)} файлах:")
    for filename, file_issues in sorted(issues.items()):
        print(f"\n{filename}:")
        for issue in file_issues:
            print(f"  {issue}")

if __name__ == "__main__":
    gutenberg_dir = Path("/home/user/medici/gutenberg")
    issues, stats = analyze_html_files(gutenberg_dir)
    print_summary(issues, stats)
