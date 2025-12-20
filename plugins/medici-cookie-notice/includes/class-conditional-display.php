<?php
/**
 * Conditional Display Class
 *
 * Керує умовним показом Cookie Notice на основі різних правил
 *
 * @package Medici_Cookie_Notice
 * @since 1.2.0
 */

declare(strict_types=1);

namespace Medici\CookieNotice;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Conditional_Display class
 */
class Conditional_Display {

	/**
	 * Посилання на головний клас
	 *
	 * @var Cookie_Notice
	 */
	private Cookie_Notice $plugin;

	/**
	 * Class constructor
	 *
	 * @param Cookie_Notice $plugin Main plugin instance.
	 */
	public function __construct( Cookie_Notice $plugin ) {
		$this->plugin = $plugin;
	}

	/**
	 * Initialize conditional display
	 *
	 * Called on after_setup_theme hook
	 *
	 * @return void
	 */
	public function init(): void {
		// Skip on admin side
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}
	}

	/**
	 * Перевірка чи потрібно показувати cookie notice
	 *
	 * @return bool True якщо потрібно показати, false якщо приховати.
	 */
	public function should_display(): bool {
		// Перевірка типу користувача
		if ( ! $this->check_user_type() ) {
			return false;
		}

		// Перевірка ролі користувача
		if ( ! $this->check_user_role() ) {
			return false;
		}

		// Перевірка типу сторінки
		if ( ! $this->check_page_type() ) {
			return false;
		}

		// Перевірка конкретних сторінок/постів
		if ( ! $this->check_page_ids() ) {
			return false;
		}

		// Всі перевірки пройдені
		return true;
	}

	/**
	 * Перевірка типу користувача
	 *
	 * @return bool True якщо показати, false якщо приховати.
	 */
	private function check_user_type(): bool {
		$user_type = $this->plugin->get_option( 'user_type' );

		// Якщо не встановлено - показувати всім
		if ( empty( $user_type ) || 'all' === $user_type ) {
			return true;
		}

		$is_logged_in = is_user_logged_in();

		// Тільки для залогінених
		if ( 'logged_in' === $user_type && ! $is_logged_in ) {
			return false;
		}

		// Тільки для гостей
		if ( 'guest' === $user_type && $is_logged_in ) {
			return false;
		}

		return true;
	}

	/**
	 * Перевірка ролі користувача
	 *
	 * @return bool True якщо показати, false якщо приховати.
	 */
	private function check_user_role(): bool {
		$excluded_roles = $this->plugin->get_option( 'excluded_roles' );

		// Якщо не встановлено виключених ролей - показувати всім
		if ( empty( $excluded_roles ) || ! is_array( $excluded_roles ) ) {
			return true;
		}

		// Якщо користувач не залогінений - показувати
		if ( ! is_user_logged_in() ) {
			return true;
		}

		$user = wp_get_current_user();

		// Перевірка чи є роль користувача у виключених
		foreach ( $excluded_roles as $excluded_role ) {
			if ( in_array( $excluded_role, (array) $user->roles, true ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Перевірка типу сторінки
	 *
	 * @return bool True якщо показати, false якщо приховати.
	 */
	private function check_page_type(): bool {
		$excluded_page_types = $this->plugin->get_option( 'excluded_page_types' );

		// Якщо не встановлено виключених типів - показувати на всіх
		if ( empty( $excluded_page_types ) || ! is_array( $excluded_page_types ) ) {
			return true;
		}

		// Перевірка кожного типу сторінки
		foreach ( $excluded_page_types as $page_type ) {
			switch ( $page_type ) {
				case 'front_page':
					if ( is_front_page() ) {
						return false;
					}
					break;

				case 'home':
					if ( is_home() ) {
						return false;
					}
					break;

				case 'single':
					if ( is_single() ) {
						return false;
					}
					break;

				case 'page':
					if ( is_page() ) {
						return false;
					}
					break;

				case 'archive':
					if ( is_archive() ) {
						return false;
					}
					break;

				case 'search':
					if ( is_search() ) {
						return false;
					}
					break;

				case '404':
					if ( is_404() ) {
						return false;
					}
					break;
			}
		}

		return true;
	}

	/**
	 * Перевірка конкретних сторінок/постів за ID
	 *
	 * @return bool True якщо показати, false якщо приховати.
	 */
	private function check_page_ids(): bool {
		$excluded_ids = $this->plugin->get_option( 'excluded_page_ids' );

		// Якщо не встановлено виключених ID - показувати на всіх
		if ( empty( $excluded_ids ) ) {
			return true;
		}

		// Конвертуємо в масив якщо це string (напр. "1,2,3")
		if ( is_string( $excluded_ids ) ) {
			$excluded_ids = array_map( 'intval', explode( ',', $excluded_ids ) );
		}

		if ( ! is_array( $excluded_ids ) ) {
			return true;
		}

		// Отримуємо поточний ID сторінки/поста
		$current_id = get_queried_object_id();

		// Якщо поточний ID у списку виключених - приховати
		if ( in_array( $current_id, $excluded_ids, true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Отримати доступні типи користувачів
	 *
	 * @return array<string, string>
	 */
	public function get_user_types(): array {
		return [
			'all'        => __( 'Всі користувачі', 'medici-cookie-notice' ),
			'logged_in'  => __( 'Тільки залогінені', 'medici-cookie-notice' ),
			'guest'      => __( 'Тільки гості', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Отримати доступні типи сторінок
	 *
	 * @return array<string, string>
	 */
	public function get_page_types(): array {
		return [
			'front_page' => __( 'Головна сторінка', 'medici-cookie-notice' ),
			'home'       => __( 'Блог (Home)', 'medici-cookie-notice' ),
			'single'     => __( 'Окремий пост', 'medici-cookie-notice' ),
			'page'       => __( 'Сторінка', 'medici-cookie-notice' ),
			'archive'    => __( 'Архів', 'medici-cookie-notice' ),
			'search'     => __( 'Пошук', 'medici-cookie-notice' ),
			'404'        => __( 'Сторінка 404', 'medici-cookie-notice' ),
		];
	}

	/**
	 * Отримати доступні ролі користувачів
	 *
	 * @return array<string, string>
	 */
	public function get_user_roles(): array {
		global $wp_roles;

		if ( ! isset( $wp_roles ) ) {
			$wp_roles = new \WP_Roles();
		}

		$roles = [];

		foreach ( $wp_roles->roles as $role_slug => $role_data ) {
			$roles[ $role_slug ] = $role_data['name'];
		}

		return $roles;
	}
}
