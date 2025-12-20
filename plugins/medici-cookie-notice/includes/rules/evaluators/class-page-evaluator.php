<?php
/**
 * Page Type Evaluator
 *
 * @package Medici_Cookie_Notice
 * @since 1.3.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice\Rules\Evaluators;

use Medici\CookieNotice\Rules\Rule_Evaluator_Interface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Type Evaluator
 *
 * Evaluates rules based on WordPress page/post type.
 */
class Page_Evaluator implements Rule_Evaluator_Interface {

	/**
	 * Get evaluator type
	 *
	 * @return string
	 */
	public function get_type(): string {
		return 'page_type';
	}

	/**
	 * Get available operators
	 *
	 * @return array<string, string>
	 */
	public function get_operators(): array {
		return [
			'is'     => __( 'є', 'medici-cookie-notice' ),
			'is_not' => __( 'не є', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Evaluate the rule
	 *
	 * @param string $operator Operator.
	 * @param mixed  $value Value to compare.
	 * @return bool
	 */
	public function evaluate( string $operator, mixed $value ): bool {
		$is_match = match ( (string) $value ) {
			'front_page'        => is_front_page(),
			'home'              => is_home(),
			'single'            => is_single(),
			'page'              => is_page(),
			'archive'           => is_archive(),
			'search'            => is_search(),
			'404'               => is_404(),
			'category'          => is_category(),
			'tag'               => is_tag(),
			'author'            => is_author(),
			'date'              => is_date(),
			'attachment'        => is_attachment(),
			'singular'          => is_singular(),
			'post_type_archive' => is_post_type_archive(),
			default             => false,
		};

		return 'is' === $operator ? $is_match : ! $is_match;
	}

	/**
	 * Get label
	 *
	 * @return string
	 */
	public function get_label(): string {
		return __( 'Тип сторінки', 'medici-cookie-notice' );
	}

	/**
	 * Get value field type
	 *
	 * @return string
	 */
	public function get_value_field_type(): string {
		return 'select';
	}

	/**
	 * Get value options
	 *
	 * @return array<string, string>
	 */
	public function get_value_options(): array {
		return [
			'front_page'        => __( 'Головна сторінка', 'medici-cookie-notice' ),
			'home'              => __( 'Сторінка блогу', 'medici-cookie-notice' ),
			'single'            => __( 'Окремий запис', 'medici-cookie-notice' ),
			'page'              => __( 'Сторінка', 'medici-cookie-notice' ),
			'archive'           => __( 'Архів', 'medici-cookie-notice' ),
			'search'            => __( 'Результати пошуку', 'medici-cookie-notice' ),
			'404'               => __( '404 сторінка', 'medici-cookie-notice' ),
			'category'          => __( 'Категорія', 'medici-cookie-notice' ),
			'tag'               => __( 'Тег', 'medici-cookie-notice' ),
			'author'            => __( 'Автор', 'medici-cookie-notice' ),
			'date'              => __( 'Архів по даті', 'medici-cookie-notice' ),
			'singular'          => __( 'Будь-який окремий контент', 'medici-cookie-notice' ),
			'post_type_archive' => __( 'Архів типу запису', 'medici-cookie-notice' ),
		];
	}
}
