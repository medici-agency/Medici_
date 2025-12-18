<?php
/**
 * Validation Result
 *
 * Value object containing validation results.
 *
 * @package    Medici_Agency
 * @subpackage Lead\Validation
 * @since      2.0.0
 * @version    1.0.0
 */

declare(strict_types=1);

namespace Medici\Lead;

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validation Result Class
 *
 * Immutable value object for validation results.
 *
 * @since 2.0.0
 */
final class ValidationResult {

	/**
	 * Is valid
	 *
	 * @var bool
	 */
	private bool $valid;

	/**
	 * Error messages
	 *
	 * @var array<string>
	 */
	private array $errors;

	/**
	 * Warning messages
	 *
	 * @var array<string>
	 */
	private array $warnings;

	/**
	 * Quality score modifier
	 *
	 * @var int
	 */
	private int $scoreModifier;

	/**
	 * Validated/sanitized data
	 *
	 * @var array<string, mixed>
	 */
	private array $data;

	/**
	 * Constructor
	 *
	 * @param bool                 $valid         Is valid.
	 * @param array<string>        $errors        Error messages.
	 * @param array<string>        $warnings      Warning messages.
	 * @param int                  $scoreModifier Quality score modifier.
	 * @param array<string, mixed> $data          Sanitized data.
	 */
	public function __construct(
		bool $valid = true,
		array $errors = array(),
		array $warnings = array(),
		int $scoreModifier = 0,
		array $data = array()
	) {
		$this->valid         = $valid;
		$this->errors        = $errors;
		$this->warnings      = $warnings;
		$this->scoreModifier = $scoreModifier;
		$this->data          = $data;
	}

	/**
	 * Create success result
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data          Sanitized data.
	 * @param int                  $scoreModifier Score bonus.
	 * @param array<string>        $warnings      Optional warnings.
	 * @return self
	 */
	public static function success( array $data = array(), int $scoreModifier = 0, array $warnings = array() ): self {
		return new self( true, array(), $warnings, $scoreModifier, $data );
	}

	/**
	 * Create error result
	 *
	 * @since 2.0.0
	 * @param array<string> $errors        Error messages.
	 * @param int           $scoreModifier Score penalty.
	 * @return self
	 */
	public static function error( array $errors, int $scoreModifier = 0 ): self {
		return new self( false, $errors, array(), $scoreModifier, array() );
	}

	/**
	 * Create warning result (valid but with concerns)
	 *
	 * @since 2.0.0
	 * @param array<string, mixed> $data          Sanitized data.
	 * @param array<string>        $warnings      Warning messages.
	 * @param int                  $scoreModifier Score modifier.
	 * @return self
	 */
	public static function warning( array $data, array $warnings, int $scoreModifier = 0 ): self {
		return new self( true, array(), $warnings, $scoreModifier, $data );
	}

	/**
	 * Is valid
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function isValid(): bool {
		return $this->valid;
	}

	/**
	 * Has errors
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function hasErrors(): bool {
		return ! empty( $this->errors );
	}

	/**
	 * Has warnings
	 *
	 * @since 2.0.0
	 * @return bool
	 */
	public function hasWarnings(): bool {
		return ! empty( $this->warnings );
	}

	/**
	 * Get errors
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function getErrors(): array {
		return $this->errors;
	}

	/**
	 * Get warnings
	 *
	 * @since 2.0.0
	 * @return array<string>
	 */
	public function getWarnings(): array {
		return $this->warnings;
	}

	/**
	 * Get score modifier
	 *
	 * @since 2.0.0
	 * @return int
	 */
	public function getScoreModifier(): int {
		return $this->scoreModifier;
	}

	/**
	 * Get sanitized data
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>
	 */
	public function getData(): array {
		return $this->data;
	}

	/**
	 * Merge with another result
	 *
	 * @since 2.0.0
	 * @param self $other Other result.
	 * @return self Merged result.
	 */
	public function merge( self $other ): self {
		return new self(
			$this->valid && $other->valid,
			array_merge( $this->errors, $other->errors ),
			array_merge( $this->warnings, $other->warnings ),
			$this->scoreModifier + $other->scoreModifier,
			array_merge( $this->data, $other->data )
		);
	}

	/**
	 * Convert to array
	 *
	 * @since 2.0.0
	 * @return array<string, mixed>
	 */
	public function toArray(): array {
		return array(
			'valid'         => $this->valid,
			'errors'        => $this->errors,
			'warnings'      => $this->warnings,
			'quality_score' => max( 0, min( 100, 50 + $this->scoreModifier ) ),
			'data'          => $this->data,
		);
	}
}
