#!/usr/bin/env python3
"""
Рефакторинг HTML файлів GenerateBlocks для Medici theme
Виправляє:
- CSS Variables escaping: \u002d\u002d → \\u002d\\u002d
- Ampersand escaping: \u0026 → \\u0026
- Transition timing: 0.5s → 0.3s
- UniqueId перевірка (hex, 8 chars, lowercase)
"""

import re
import os
import sys
from pathlib import Path
from typing import List, Tuple

# Кольори для виводу
RED = '\033[91m'
GREEN = '\033[92m'
YELLOW = '\033[93m'
BLUE = '\033[94m'
RESET = '\033[0m'

def is_valid_hex_id(unique_id: str) -> bool:
    """Перевірка чи UniqueId валідний (8 hex chars, lowercase)"""
    if len(unique_id) != 8:
        return False
    return bool(re.match(r'^[0-9a-f]{8}$', unique_id))

def fix_css_variables(content: str) -> Tuple[str, int]:
    """Виправлення CSS Variables escaping"""
    # Знайти всі неправильні варіанти
    pattern = r'\\u005cu002d\\u005cu002d'  # \u005cu002d\u005cu002d
    count = len(re.findall(pattern, content))

    # Замінити на правильний варіант
    fixed = re.sub(pattern, r'\\u002d\\u002d', content)

    # Також перевірити одинарні \u002d (без подвійного backslash)
    pattern2 = r'(?<!\\)\\u002d\\u002d'
    count2 = len(re.findall(pattern2, fixed))
    if count2 > 0:
        fixed = re.sub(pattern2, r'\\u002d\\u002d', fixed)
        count += count2

    return fixed, count

def fix_ampersand(content: str) -> Tuple[str, int]:
    """Виправлення Ampersand escaping"""
    # Знайти всі неправильні варіанти
    pattern = r'\\u005cu0026'  # \u005cu0026
    count = len(re.findall(pattern, content))

    # Замінити на правильний варіант
    fixed = re.sub(pattern, r'\\u0026', content)

    # Також перевірити одинарні \u0026 (без подвійного backslash)
    pattern2 = r'(?<!\\)\\u0026'
    count2 = len(re.findall(pattern2, fixed))
    if count2 > 0:
        fixed = re.sub(pattern2, r'\\u0026', fixed)
        count += count2

    return fixed, count

def fix_transition_timing(content: str) -> Tuple[str, int]:
    """Стандартизація transition timing до 0.3s"""
    pattern = r'"transition":\s*"all\s+0\.5s\s+ease\s+0s"'
    count = len(re.findall(pattern, content))

    # Замінити на стандартний варіант
    fixed = re.sub(pattern, r'"transition": "all 0.3s ease 0s"', content)

    return fixed, count

def fix_media_queries(content: str) -> Tuple[str, int]:
    """Стандартизація media queries форматування (max-width:XXXpx → max-width: XXXpx)"""
    # Знайти всі варіанти без пробілу після двокрапки
    pattern = r'max-width:(\d+px)'
    matches = re.findall(pattern, content)
    count = len(matches)

    # Замінити на варіант з пробілом
    fixed = re.sub(pattern, r'max-width: \1', content)

    return fixed, count

def check_unique_ids(content: str) -> List[str]:
    """Перевірка всіх UniqueId на валідність"""
    pattern = r'"uniqueId":\s*"([^"]+)"'
    matches = re.findall(pattern, content)

    invalid_ids = []
    for uid in matches:
        if not is_valid_hex_id(uid):
            invalid_ids.append(uid)

    return invalid_ids

def refactor_file(filepath: Path, dry_run: bool = False) -> dict:
    """Рефакторинг одного HTML файлу"""
    result = {
        'file': filepath.name,
        'css_vars_fixed': 0,
        'ampersand_fixed': 0,
        'transition_fixed': 0,
        'media_queries_fixed': 0,
        'invalid_ids': [],
        'success': False
    }

    try:
        # Читання файлу
        content = filepath.read_text(encoding='utf-8')
        original = content

        # Виправлення CSS Variables
        content, css_count = fix_css_variables(content)
        result['css_vars_fixed'] = css_count

        # Виправлення Ampersand
        content, amp_count = fix_ampersand(content)
        result['ampersand_fixed'] = amp_count

        # Виправлення Transition
        content, trans_count = fix_transition_timing(content)
        result['transition_fixed'] = trans_count

        # Виправлення Media Queries
        content, media_count = fix_media_queries(content)
        result['media_queries_fixed'] = media_count

        # Перевірка UniqueId
        result['invalid_ids'] = check_unique_ids(content)

        # Запис змін (якщо не dry run)
        if not dry_run and content != original:
            filepath.write_text(content, encoding='utf-8')
            result['success'] = True
        elif content == original:
            result['success'] = True  # Нічого не змінилось, але це OK

    except Exception as e:
        print(f"{RED}✗ Помилка при обробці {filepath.name}: {e}{RESET}")
        return result

    return result

def main():
    """Головна функція"""
    dry_run = '--dry-run' in sys.argv

    # Шляхи до директорій з HTML файлами
    base_path = Path(__file__).parent.parent
    gutenberg_path = base_path / 'gutenberg'
    templates_path = base_path / 'templates'

    html_files = []
    html_files.extend(list(gutenberg_path.glob('*.html')))
    html_files.extend(list(templates_path.glob('*.html')))

    if not html_files:
        print(f"{RED}✗ HTML файли не знайдені!{RESET}")
        return 1

    print(f"{BLUE}{'=' * 60}{RESET}")
    print(f"{BLUE}Рефакторинг HTML файлів GenerateBlocks{RESET}")
    print(f"{BLUE}{'=' * 60}{RESET}\n")

    if dry_run:
        print(f"{YELLOW}⚠ DRY RUN MODE - зміни не будуть збережені{RESET}\n")

    print(f"Знайдено файлів: {len(html_files)}\n")

    # Обробка файлів
    total_stats = {
        'css_vars': 0,
        'ampersand': 0,
        'transition': 0,
        'media_queries': 0,
        'invalid_ids': []
    }

    for filepath in sorted(html_files):
        result = refactor_file(filepath, dry_run)

        # Вивід результату для файлу
        if result['css_vars_fixed'] or result['ampersand_fixed'] or result['transition_fixed'] or result['media_queries_fixed'] or result['invalid_ids']:
            print(f"{GREEN}✓{RESET} {result['file']}")

            if result['css_vars_fixed']:
                print(f"  • CSS Variables: {result['css_vars_fixed']} виправлень")
                total_stats['css_vars'] += result['css_vars_fixed']

            if result['ampersand_fixed']:
                print(f"  • Ampersand: {result['ampersand_fixed']} виправлень")
                total_stats['ampersand'] += result['ampersand_fixed']

            if result['transition_fixed']:
                print(f"  • Transition: {result['transition_fixed']} виправлень")
                total_stats['transition'] += result['transition_fixed']

            if result['media_queries_fixed']:
                print(f"  • Media Queries: {result['media_queries_fixed']} виправлень")
                total_stats['media_queries'] += result['media_queries_fixed']

            if result['invalid_ids']:
                print(f"  {RED}⚠ Invalid UniqueId:{RESET}")
                for uid in result['invalid_ids']:
                    print(f"    - {uid}")
                total_stats['invalid_ids'].extend([(result['file'], uid)])
        else:
            print(f"{GREEN}✓{RESET} {result['file']} - без змін")

    # Підсумок
    print(f"\n{BLUE}{'=' * 60}{RESET}")
    print(f"{BLUE}Підсумок:{RESET}\n")
    print(f"CSS Variables виправлено: {total_stats['css_vars']}")
    print(f"Ampersand виправлено: {total_stats['ampersand']}")
    print(f"Transition виправлено: {total_stats['transition']}")
    print(f"Media Queries виправлено: {total_stats['media_queries']}")

    if total_stats['invalid_ids']:
        print(f"\n{RED}⚠ Виявлено невалідних UniqueId: {len(total_stats['invalid_ids'])}{RESET}")
        for file, uid in total_stats['invalid_ids']:
            print(f"  {file}: {uid}")
        print(f"\n{YELLOW}Рекомендація: Згенеруйте нові hex UniqueId для невалідних записів{RESET}")

    if dry_run:
        print(f"\n{YELLOW}⚠ DRY RUN - зміни НЕ збережені. Запустіть без --dry-run для застосування.{RESET}")
    else:
        print(f"\n{GREEN}✓ Рефакторинг завершено успішно!{RESET}")

    return 0 if not total_stats['invalid_ids'] else 1

if __name__ == '__main__':
    sys.exit(main())
