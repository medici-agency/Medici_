<?php
/**
 * Bot Detection Class.
 *
 * Based on CrawlerDetect - detects bots/crawlers for spam prevention.
 *
 * @package MediciForms
 * @since   1.1.0
 */

declare(strict_types=1);

namespace MediciForms;

/**
 * Bot_Detect Class.
 *
 * @since 1.1.0
 */
class Bot_Detect {

	/**
	 * The user agent.
	 *
	 * @var string|null
	 */
	private ?string $user_agent = null;

	/**
	 * HTTP headers.
	 *
	 * @var array<string, string>
	 */
	private array $http_headers = array();

	/**
	 * Regex matches.
	 *
	 * @var array<int, string>
	 */
	private array $matches = array();

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->set_http_headers();
		$this->set_user_agent();
	}

	/**
	 * Set HTTP headers.
	 *
	 * @param array<string, string>|null $http_headers Headers.
	 */
	public function set_http_headers( ?array $http_headers = null ): void {
		if ( ! is_array( $http_headers ) || empty( $http_headers ) ) {
			$http_headers = $_SERVER; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		}

		$this->http_headers = array();

		foreach ( $http_headers as $key => $value ) {
			if ( str_starts_with( (string) $key, 'HTTP_' ) ) {
				$this->http_headers[ $key ] = (string) $value;
			}
		}
	}

	/**
	 * Get user agent.
	 *
	 * @return string|null
	 */
	public function get_user_agent(): ?string {
		return $this->user_agent;
	}

	/**
	 * Set user agent.
	 *
	 * @param string|null $user_agent User agent.
	 */
	public function set_user_agent( ?string $user_agent = null ): void {
		if ( ! empty( $user_agent ) ) {
			$this->user_agent = $user_agent;
			return;
		}

		$this->user_agent = null;
		$ua_headers       = $this->get_ua_headers();

		foreach ( $ua_headers as $header ) {
			if ( ! empty( $this->http_headers[ $header ] ) ) {
				$this->user_agent .= $this->http_headers[ $header ] . ' ';
			}
		}

		$this->user_agent = ! empty( $this->user_agent ) ? trim( $this->user_agent ) : null;
	}

	/**
	 * Check if current visitor is a bot/crawler.
	 *
	 * @param string|null $user_agent User agent to check.
	 * @return bool
	 */
	public function is_bot( ?string $user_agent = null ): bool {
		$agent = $user_agent ?? $this->user_agent;

		if ( empty( $agent ) ) {
			return false;
		}

		// Remove exclusions.
		$agent = (string) preg_replace( '/' . $this->get_exclusions_regex() . '/i', '', $agent );

		if ( empty( trim( $agent ) ) ) {
			return false;
		}

		$result = preg_match( '/' . $this->get_crawlers_regex() . '/i', trim( $agent ), $matches );

		if ( $matches ) {
			$this->matches = $matches;
		}

		return (bool) $result;
	}

	/**
	 * Get matched bot name.
	 *
	 * @return string|null
	 */
	public function get_match(): ?string {
		return $this->matches[0] ?? null;
	}

	/**
	 * Get UA headers list.
	 *
	 * @return array<int, string>
	 */
	private function get_ua_headers(): array {
		return array(
			'HTTP_USER_AGENT',
			'HTTP_X_OPERAMINI_PHONE_UA',
			'HTTP_X_DEVICE_USER_AGENT',
			'HTTP_X_ORIGINAL_USER_AGENT',
			'HTTP_FROM',
		);
	}

	/**
	 * Get crawlers regex.
	 *
	 * @return string
	 */
	private function get_crawlers_regex(): string {
		$crawlers = array(
			'curl',
			'wget',
			'python-requests',
			'Python-urllib',
			'python-httpx',
			'Go-http-client',
			'Java\/',
			'Apache-HttpClient',
			'axios\/',
			'node-fetch',
			'PostmanRuntime',
			'insomnia\/',
			'httpie',
			'bot',
			'crawl',
			'spider',
			'slurp',
			'scraper',
			'scanner',
			'Googlebot',
			'bingbot',
			'yandex',
			'baidu',
			'facebookexternalhit',
			'Twitterbot',
			'LinkedInBot',
			'WhatsApp',
			'TelegramBot',
			'Discordbot',
			'Slackbot',
			'HeadlessChrome',
			'PhantomJS',
			'Selenium',
			'puppeteer',
			'playwright',
			'scrapy',
			'aiohttp',
			'httpx',
			'requests\/',
			'libwww-perl',
			'Mechanize',
			'LWP::',
			'WWW-Mechanize',
			'GuzzleHttp',
			'fasthttp',
			'okhttp',
			'Acunetix',
			'Nessus',
			'Nikto',
			'sqlmap',
			'ZmEu',
			'masscan',
			'nmap',
			'Havij',
			'w3af',
			'Zgrab',
			'dirbuster',
			'gobuster',
			'nuclei',
			'semrush',
			'ahrefs',
			'moz\.com',
			'majestic',
			'SEO',
			'DataForSeoBot',
			'PetalBot',
			'AhrefsBot',
			'SemrushBot',
			'MJ12bot',
			'DotBot',
			'BLEXBot',
			'Screaming Frog',
		);

		return '(' . implode( '|', $crawlers ) . ')';
	}

	/**
	 * Get exclusions regex.
	 *
	 * @return string
	 */
	private function get_exclusions_regex(): string {
		$exclusions = array(
			'Safari\.[\d\.]*',
			'Firefox\.[\d\.]*',
			'Chrome\.[\d\.]*',
			'MSIE\.[\d\.]',
			'Opera\/[\d\.]*',
			'Mozilla\.[\d\.]*',
			'AppleWebKit\.[\d\.]*',
			'Windows NT\.[\d\.]*',
			'Android [\d\.]*',
			'Macintosh\.',
			'Linux',
			'like Gecko',
		);

		return '(' . implode( '|', $exclusions ) . ')';
	}
}
