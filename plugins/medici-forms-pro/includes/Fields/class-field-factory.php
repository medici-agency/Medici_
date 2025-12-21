<?php
/**
 * Field Factory Class.
 *
 * @package MediciForms
 * @since   1.0.0
 */

declare(strict_types=1);

namespace MediciForms\Fields;

/**
 * Field Factory Class.
 *
 * @since 1.0.0
 */
class Field_Factory {

	/**
	 * Get default field configuration.
	 *
	 * @since 1.0.0
	 * @param string $type Field type.
	 * @return array<string, mixed>
	 */
	public static function get_defaults( string $type ): array {
		$common = array(
			'id'          => '',
			'type'        => $type,
			'label'       => '',
			'placeholder' => '',
			'required'    => false,
			'css_class'   => '',
			'width'       => '100',
		);

		$type_specific = array(
			'text'     => array(),
			'email'    => array(),
			'phone'    => array(),
			'url'      => array(),
			'number'   => array(
				'min'  => '',
				'max'  => '',
				'step' => '',
			),
			'textarea' => array(
				'rows' => 5,
			),
			'select'   => array(
				'options'  => '',
				'multiple' => false,
			),
			'radio'    => array(
				'options' => '',
			),
			'checkbox' => array(
				'options' => '',
			),
			'name'     => array(
				'show_last_name' => true,
			),
			'date'     => array(
				'format' => 'Y-m-d',
			),
			'time'     => array(
				'format' => 'H:i',
			),
			'file'     => array(
				'allowed_types' => 'jpg,jpeg,png,pdf,doc,docx',
				'max_size'      => 5, // MB.
			),
			'hidden'   => array(
				'default_value' => '',
			),
			'html'     => array(
				'content' => '',
			),
			'divider'  => array(),
			'heading'  => array(
				'heading_level' => 3,
			),
		);

		return array_merge( $common, $type_specific[ $type ] ?? array() );
	}

	/**
	 * Get field type options for builder.
	 *
	 * @since 1.0.0
	 * @param string $type Field type.
	 * @return array<string, array<string, mixed>>
	 */
	public static function get_options_config( string $type ): array {
		$common_options = array(
			'label'       => array(
				'type'  => 'text',
				'label' => __( 'Назва поля', 'medici-forms-pro' ),
			),
			'placeholder' => array(
				'type'  => 'text',
				'label' => __( 'Placeholder', 'medici-forms-pro' ),
			),
			'required'    => array(
				'type'  => 'checkbox',
				'label' => __( "Обов'язкове поле", 'medici-forms-pro' ),
			),
			'css_class'   => array(
				'type'  => 'text',
				'label' => __( 'CSS клас', 'medici-forms-pro' ),
			),
			'width'       => array(
				'type'    => 'select',
				'label'   => __( 'Ширина', 'medici-forms-pro' ),
				'options' => array(
					'100' => '100%',
					'50'  => '50%',
					'33'  => '33%',
				),
			),
		);

		$type_options = array(
			'textarea' => array(
				'rows' => array(
					'type'  => 'number',
					'label' => __( 'Кількість рядків', 'medici-forms-pro' ),
					'min'   => 2,
					'max'   => 20,
				),
			),
			'select'   => array(
				'options' => array(
					'type'  => 'textarea',
					'label' => __( 'Варіанти (один на рядок)', 'medici-forms-pro' ),
				),
			),
			'radio'    => array(
				'options' => array(
					'type'  => 'textarea',
					'label' => __( 'Варіанти (один на рядок)', 'medici-forms-pro' ),
				),
			),
			'checkbox' => array(
				'options' => array(
					'type'  => 'textarea',
					'label' => __( 'Варіанти (один на рядок)', 'medici-forms-pro' ),
				),
			),
			'hidden'   => array(
				'default_value' => array(
					'type'        => 'text',
					'label'       => __( 'Значення за замовчуванням', 'medici-forms-pro' ),
					'description' => __( 'Доступні змінні: {page_url}, {page_title}, {user_ip}, {utm_source}', 'medici-forms-pro' ),
				),
			),
			'html'     => array(
				'content' => array(
					'type'  => 'textarea',
					'label' => __( 'HTML контент', 'medici-forms-pro' ),
				),
			),
			'heading'  => array(
				'heading_level' => array(
					'type'    => 'select',
					'label'   => __( 'Рівень заголовка', 'medici-forms-pro' ),
					'options' => array(
						'2' => 'H2',
						'3' => 'H3',
						'4' => 'H4',
						'5' => 'H5',
						'6' => 'H6',
					),
				),
			),
		);

		// Fields that don't need placeholder/required.
		$no_input_fields = array( 'html', 'divider', 'heading', 'hidden' );

		if ( in_array( $type, $no_input_fields, true ) ) {
			unset( $common_options['placeholder'], $common_options['required'] );
		}

		return array_merge( $common_options, $type_options[ $type ] ?? array() );
	}
}
