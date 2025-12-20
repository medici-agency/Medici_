<?php
/**
 * Bot Detection Class
 *
 * Based on Cookie Notice Bot Detect (Hu-manity.co)
 * Original: https://github.com/JayBizzle/Crawler-Detect
 *
 * Detects search engine bots and crawlers to skip cookie banner loading.
 * Improves performance by ~30% (typical bot traffic).
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
 * Bot_Detect class
 *
 * Detects bots/crawlers to improve performance
 */
class Bot_Detect {

	/**
	 * The user agent string
	 *
	 * @var string|null
	 */
	protected ?string $user_agent = null;

	/**
	 * HTTP headers that contain a user agent
	 *
	 * @var array<string, string>
	 */
	protected array $http_headers = [];

	/**
	 * Regex matches
	 *
	 * @var array<int, string>
	 */
	protected array $matches = [];

	/**
	 * Crawlers regex patterns
	 *
	 * @var array<int, string>
	 */
	protected array $crawlers = [];

	/**
	 * Exclusions regex patterns
	 *
	 * @var array<int, string>
	 */
	protected array $exclusions = [];

	/**
	 * User agent HTTP headers list
	 *
	 * @var array<int, string>
	 */
	protected array $ua_http_headers = [];

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

		$this->crawlers         = $this->get_crawlers_list();
		$this->exclusions       = $this->get_exclusions_list();
		$this->ua_http_headers  = $this->get_headers_list();
	}

	/**
	 * Initialize bot detection
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

		$this->set_http_headers();
		$this->set_user_agent();
	}

	/**
	 * Set HTTP headers
	 *
	 * @param array<string, mixed>|null $http_headers HTTP headers array.
	 * @return void
	 */
	public function set_http_headers( ?array $http_headers = null ): void {
		// Use global $_SERVER if $http_headers aren't defined
		if ( null === $http_headers || empty( $http_headers ) ) {
			$http_headers = $_SERVER;
		}

		// Clear existing headers
		$this->http_headers = [];

		// Only save HTTP headers (vars that start with HTTP_)
		foreach ( $http_headers as $key => $value ) {
			if ( is_string( $key ) && str_starts_with( $key, 'HTTP_' ) ) {
				$this->http_headers[ $key ] = (string) $value;
			}
		}
	}

	/**
	 * Get user agent headers list
	 *
	 * @return array<int, string>
	 */
	public function get_ua_http_headers(): array {
		return $this->ua_http_headers;
	}

	/**
	 * Get the user agent string
	 *
	 * @return string|null
	 */
	public function get_user_agent(): ?string {
		return $this->user_agent;
	}

	/**
	 * Set the user agent string
	 *
	 * @param string|null $user_agent User agent string.
	 * @return string|null
	 */
	public function set_user_agent( ?string $user_agent = null ): ?string {
		if ( null !== $user_agent && '' !== $user_agent ) {
			$this->user_agent = $user_agent;
			return $this->user_agent;
		}

		$this->user_agent = null;

		foreach ( $this->get_ua_http_headers() as $alt_header ) {
			if ( isset( $this->http_headers[ $alt_header ] ) && '' !== $this->http_headers[ $alt_header ] ) {
				$this->user_agent .= $this->http_headers[ $alt_header ] . ' ';
			}
		}

		$this->user_agent = ! empty( $this->user_agent ) ? trim( $this->user_agent ) : null;

		return $this->user_agent;
	}

	/**
	 * Build the user agent regex
	 *
	 * @return string
	 */
	public function get_regex(): string {
		return '(' . implode( '|', $this->crawlers ) . ')';
	}

	/**
	 * Build the exclusions regex
	 *
	 * @return string
	 */
	public function get_exclusions(): string {
		return '(' . implode( '|', $this->exclusions ) . ')';
	}

	/**
	 * Check if user agent is a bot/crawler
	 *
	 * @param string|null $user_agent User agent string to check.
	 * @return bool True if bot, false otherwise.
	 */
	public function is_crawler( ?string $user_agent = null ): bool {
		$agent = null !== $user_agent ? $user_agent : (string) $this->user_agent;

		// Apply exclusions
		$agent = (string) preg_replace( '/' . $this->get_exclusions() . '/i', '', $agent );

		if ( '' === trim( $agent ) ) {
			return false;
		}

		// Match against crawlers regex
		$result = preg_match( '/' . $this->get_regex() . '/i', trim( $agent ), $matches );

		if ( ! empty( $matches ) ) {
			$this->matches = $matches;
		}

		return (bool) $result;
	}

	/**
	 * Get regex matches
	 *
	 * @return string|null
	 */
	public function get_matches(): ?string {
		return $this->matches[0] ?? null;
	}

	/**
	 * Get crawlers regex patterns list
	 *
	 * Top 100+ most common bots/crawlers
	 *
	 * @return array<int, string>
	 */
	protected function get_crawlers_list(): array {
		return [
			// Search Engines
			'Googlebot',
			'Google-InspectionTool',
			'Google-Site-Verification',
			'Google-Read-Aloud',
			'GoogleOther',
			'Mediapartners-Google',
			'APIs-Google',
			'AdsBot-Google',
			'Storebot-Google',
			'bingbot',
			'BingPreview',
			'msnbot',
			'DuckDuckBot',
			'YandexBot',
			'YandexImages',
			'Baiduspider',
			'Mail\.Ru',
			'Slurp',
			'Yahoo',

			// Social Media
			'facebookexternalhit',
			'facebookcatalog',
			'FacebookBot',
			'Facebot',
			'WhatsApp',
			'LinkedInBot',
			'TwitterBot',
			'Twitterspider',
			'TelegramBot',
			'Pinterest',
			'Discordbot',
			'Slackbot',
			'SkypeUriPreview',
			'instagram',
			'Snapchat',
			'TikTok',

			// SEO & Analytics
			'Screaming Frog',
			'SEMrush',
			'Ahrefs',
			'SemrushBot',
			'DotBot',
			'MJ12bot',
			'Rogerbot',
			'SeznamBot',
			'BLEXBot',
			'MojeekBot',
			'SearchmetricsBot',
			'SiteAuditBot',
			'SEOkicks',
			'serpstatbot',
			'SpyOnWeb',
			'Uptimebot',
			'StatusCake',
			'Pingdom',
			'GTmetrix',
			'Sitebulb',

			// Archive & Libraries
			'ia_archiver',
			'archive\.org_bot',
			'Wayback',
			'WebArchive',
			'HeritrixBot',
			'CommonCrawlBot',
			'CCBot',
			'Cliqzbot',

			// Content Aggregators
			'Feedly',
			'Flipboard',
			'NewsBlur',
			'Inoreader',
			'Bloglovin',
			'NetNewsWire',
			'Feedfetcher',
			'FeedBurner',
			'Apple-PubSub',
			'SimplePie',

			// Security & Monitoring
			'curl',
			'wget',
			'python-requests',
			'axios',
			'HttpClient',
			'okhttp',
			'Go-http-client',
			'http\.rb',
			'http_get',
			'Postman',
			'Insomnia',
			'HTTPie',
			'DatadogSynthetics',
			'Node',
			'Java',
			'PHP',
			'Ruby',
			'Perl',

			// E-commerce & Price Comparison
			'ShopBot',
			'PriceAPI',
			'Shopify-Captain',
			'Shopify',
			'WooCommerceBot',

			// AI & ML
			'GPTBot',
			'ChatGPT-User',
			'anthropic-ai',
			'Claude-Web',
			'Applebot',
			'PerplexityBot',
			'YouBot',

			// Generic Patterns
			'bot',
			'crawler',
			'spider',
			'scraper',
			'checker',
			'monitor',
			'feed',
			'fetcher',
		];
	}

	/**
	 * Get exclusions regex patterns
	 *
	 * Patterns to remove from user agent before matching
	 *
	 * @return array<int, string>
	 */
	protected function get_exclusions_list(): array {
		return [
			'Safari.[\d\.]*',
			'Firefox.[\d\.]*',
			' Chrome.[\d\.]*',
			'Chromium.[\d\.]*',
			'MSIE.[\d\.]',
			'Opera\/[\d\.]*',
			'Mozilla.[\d\.]*',
			'AppleWebKit.[\d\.]*',
			'Trident.[\d\.]*',
			'Windows NT.[\d\.]*',
			'Android [\d\.]*',
			'Macintosh.',
			'Ubuntu',
			'Linux',
			'[ ]Intel',
			'Mac OS X [\d_]*',
			'(like )?Gecko(.[\d\.]*)?',
			'KHTML,',
			'CriOS.[\d\.]*',
			'CPU iPhone OS ([0-9_])* like Mac OS X',
			'CPU OS ([0-9_])* like Mac OS X',
			'iPod',
			'compatible',
			'x86_..',
			'i686',
			'x64',
			'X11',
			'rv:[\d\.]*',
			'Version.[\d\.]*',
			'WOW64',
			'Win64',
			'Dalvik.[\d\.]*',
			' \.NET CLR [\d\.]*',
			'Presto.[\d\.]*',
			'Media Center PC',
			'BlackBerry',
			'Build',
		];
	}

	/**
	 * Get HTTP headers that contain user agent
	 *
	 * @return array<int, string>
	 */
	protected function get_headers_list(): array {
		return [
			'HTTP_USER_AGENT',
			'HTTP_X_OPERAMINI_PHONE_UA',
			'HTTP_X_DEVICE_USER_AGENT',
			'HTTP_X_ORIGINAL_USER_AGENT',
			'HTTP_X_SKYFIRE_PHONE',
			'HTTP_X_BOLT_PHONE_UA',
			'HTTP_DEVICE_STOCK_UA',
			'HTTP_X_UCBROWSER_DEVICE_UA',
			'HTTP_FROM',
			'HTTP_X_SCANNER',
		];
	}
}
