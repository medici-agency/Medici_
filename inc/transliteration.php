<?php
declare(strict_types=1);

/**
 * Transliteration Module - Cyrillic to Latin
 *
 * Автоматична транслітерація кирилиці у латиницю для URL-адрес (slug)
 * та назв файлів. Підтримує українську та російську мови.
 *
 * @package    Medici
 * @subpackage Core
 * @since      1.0.17
 * @version    1.0.1
 *
 * Використання:
 * - Автоматично транслітерує заголовки постів при створенні slug
 * - Автоматично транслітерує назви файлів при завантаженні
 *
 * Приклад:
 * "Реклама лікарських засобів" → "reklama-likarskykh-zasobiv"
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Транслітерація кирилиці у латиницю
 *
 * Конвертує українські та російські літери у латинський алфавіт.
 * Це спрощена схема для SEO‑дружніх slug-ів, а не офіційний стандарт.
 *
 * @since 1.0.17
 *
 * @param string $text Текст для транслітерації.
 * @return string Транслітерований текст.
 */
function medici_transliterate( string $text ): string {
	// Таблиця транслітерації (Українська + Російська)
	$transliteration_table = array(
		// Українські літери (великі)
		'А' => 'A',
		'Б' => 'B',
		'В' => 'V',
		'Г' => 'H',
		'Ґ' => 'G',
		'Д' => 'D',
		'Е' => 'E',
		'Є' => 'Ye',
		'Ж' => 'Zh',
		'З' => 'Z',
		'И' => 'Y',
		'І' => 'I',
		'Ї' => 'Yi',
		'Й' => 'Y',
		'К' => 'K',
		'Л' => 'L',
		'М' => 'M',
		'Н' => 'N',
		'О' => 'O',
		'П' => 'P',
		'Р' => 'R',
		'С' => 'S',
		'Т' => 'T',
		'У' => 'U',
		'Ф' => 'F',
		'Х' => 'Kh',
		'Ц' => 'Ts',
		'Ч' => 'Ch',
		'Ш' => 'Sh',
		'Щ' => 'Shch',
		'Ь' => '',
		'Ю' => 'Yu',
		'Я' => 'Ya',

		// Українські літери (малі)
		'а' => 'a',
		'б' => 'b',
		'в' => 'v',
		'г' => 'h',
		'ґ' => 'g',
		'д' => 'd',
		'е' => 'e',
		'є' => 'ye',
		'ж' => 'zh',
		'з' => 'z',
		'и' => 'y',
		'і' => 'i',
		'ї' => 'yi',
		'й' => 'y',
		'к' => 'k',
		'л' => 'l',
		'м' => 'm',
		'н' => 'n',
		'о' => 'o',
		'п' => 'p',
		'р' => 'r',
		'с' => 's',
		'т' => 't',
		'у' => 'u',
		'ф' => 'f',
		'х' => 'kh',
		'ц' => 'ts',
		'ч' => 'ch',
		'ш' => 'sh',
		'щ' => 'shch',
		'ь' => '',
		'ю' => 'yu',
		'я' => 'ya',

		// Російські літери (додаткові)
		'Ё' => 'Yo',
		'Ы' => 'Y',
		'Э' => 'E',
		'Ъ' => '',
		'ё' => 'yo',
		'ы' => 'y',
		'э' => 'e',
		'ъ' => '',

		// Спеціальні символи
		'№' => '',
		'—' => '-',
		'–' => '-',
		'«' => '',
		'»' => '',
		'"' => '',
		"'" => '',
	);

	// Виконати транслітерацію
	$text = strtr( $text, $transliteration_table );

	return $text;
}

/**
 * Фільтр для транслітерації заголовків постів (slug)
 *
 * Автоматично конвертує кирилицю у латиницю при створенні URL-адреси поста.
 *
 * @since 1.0.17
 *
 * @param string $title     Заголовок для sanitize.
 * @param string $raw_title Оригінальний заголовок (необов'язковий).
 * @param string $context   Контекст виклику (save, query, display).
 * @return string Транслітерований заголовок.
 */
function medici_transliterate_sanitize_title( string $title, string $raw_title = '', string $context = 'save' ): string {
	// Транслітерувати тільки при збереженні (створення slug)
	if ( 'save' !== $context ) {
		return $title;
	}

	// Якщо є оригінальний заголовок - використати його
	$text = $raw_title ?: $title;

	// Перевірити чи є кирилиця в тексті (усі мови на кирилиці)
	if ( ! preg_match( '/\p{Cyrillic}/u', $text ) ) {
		return $title;
	}

	// Транслітерувати
	$transliterated = medici_transliterate( $text );

	// Конвертувати у lowercase та нормалізувати slug
	$transliterated = mb_strtolower( $transliterated, 'UTF-8' );
	$transliterated = preg_replace( '/\s+/u', '-', $transliterated );
	$transliterated = preg_replace( '/[^a-z0-9\-]/u', '', $transliterated );
	$transliterated = preg_replace( '/-+/', '-', $transliterated );
	$transliterated = trim( $transliterated, '-' );

	return $transliterated;
}

/**
 * Фільтр для транслітерації назв файлів
 *
 * Автоматично конвертує кирилицю у латиницю при завантаженні файлів.
 *
 * @since 1.0.17
 *
 * @param string $filename Назва файлу для sanitize.
 * @return string Транслітерована назва файлу.
 */
function medici_transliterate_sanitize_file_name( string $filename ): string {
	// Отримати розширення файлу
	$pathinfo  = pathinfo( $filename );
	$extension = isset( $pathinfo['extension'] ) ? '.' . $pathinfo['extension'] : '';
	$basename  = isset( $pathinfo['filename'] ) ? $pathinfo['filename'] : $filename;

	// Перевірити чи є кирилиця
	if ( ! preg_match( '/\p{Cyrillic}/u', $basename ) ) {
		return $filename;
	}

	// Транслітерувати
	$transliterated = medici_transliterate( $basename );

	// Конвертувати у lowercase та замінити пробіли на дефіси
	$transliterated = mb_strtolower( $transliterated, 'UTF-8' );
	$transliterated = preg_replace( '/\s+/u', '-', $transliterated );
	$transliterated = preg_replace( '/[^a-z0-9\-_]/u', '', $transliterated );
	$transliterated = preg_replace( '/-+/', '-', $transliterated );
	$transliterated = trim( $transliterated, '-' );

	return $transliterated . $extension;
}

/**
 * =====================================================
 * WordPress HOOKS
 * =====================================================
 */

// Транслітерація заголовків постів (slug)
add_filter( 'sanitize_title', 'medici_transliterate_sanitize_title', 10, 3 );

// Транслітерація назв файлів
add_filter( 'sanitize_file_name', 'medici_transliterate_sanitize_file_name', 10, 1 );
