<?php
declare(strict_types=1);
/**
 * ============================================================================
 * MEDICI.AGENCY - SECURITY OPTIMIZATIONS
 * File: inc/security.php
 * ============================================================================
 *
 * Handles:
 * • XML-RPC disabling (prevents brute force attacks)
 * • X-Pingback header removal (version disclosure prevention)
 * • Pingback/RSD discovery hardening
 * • WordPress version hiding
 *
 * Depends: None (works independently)
 * Used by: functions.php
 *
 * ✅ CRITICAL:
 * • XML-RPC disabled via proper WordPress filter
 * • Prevents brute force password attacks
 * • Blocks pingback DDoS reflection attacks
 * • Reduces WordPress fingerprinting
 *
 * @version 1.5.3
 * @since   1.3.3
 * @updated 1.5.3 - Fixed CSP for Google services + AJAX compatibility
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ============================================================================
// DISABLE XML-RPC ENDPOINT (CRITICAL SECURITY FIX)
// ============================================================================

/**
 * Disable XML-RPC endpoint
 *
 * Uses official WordPress filter xmlrpc_enabled.
 *
 * Prevents:
 * • Brute force password attacks via /xmlrpc.php
 * • Pingback reflection DDoS attacks
 * • XML-RPC based login attempts and pings
 *
 * Note:
 * • This will break Jetpack, official WP mobile apps
 *   and other clients that rely on XML-RPC.
 *
 * @since 1.3.3
 * @return false Always disable XML-RPC
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

// ============================================================================
// REMOVE X-PINGBACK HEADER (VERSION / FOOTPRINT HIDING)
// ============================================================================

/**
 * Remove X-Pingback header from responses
 *
 * Prevents disclosure of XML-RPC availability and WordPress presence.
 * Reduces attack surface for automated scanners.
 *
 * @since 1.3.3
 * @param array $headers HTTP response headers.
 * @return array Headers without X-Pingback
 */
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		if ( isset( $headers['X-Pingback'] ) ) {
			unset( $headers['X-Pingback'] );
		}

		return $headers;
	}
);

// ============================================================================
// REMOVE RSD LINK (XML-RPC DISCOVERY LINK)
// ============================================================================

/**
 * Remove Really Simple Discovery (RSD) link from <head>.
 *
 * Default WordPress output adds a link to xmlrpc.php?rsd
 * which exposes XML-RPC endpoint even if the file exists
 * only for compatibility.
 *
 * @since 1.3.4
 */
remove_action( 'wp_head', 'rsd_link' );

// ============================================================================
// REMOVE WordPress VERSION DISCLOSURE (VERSION HIDING)
// ============================================================================

/**
 * Remove WordPress version from HTML head
 *
 * Removes the meta generator tag from <head>.
 * This makes version-based automated scanning slightly harder.
 *
 * @since 1.3.3
 */
remove_action( 'wp_head', 'wp_generator' );

/**
 * Remove WordPress version from RSS feeds and other generators
 *
 * Filters all generator strings (RSS, Atom and others)
 * and replaces them with an empty string.
 *
 * @since 1.3.3
 * @return string Empty string (no version info)
 */
add_filter( 'the_generator', '__return_empty_string' );

// ============================================================================
// CONTENT SECURITY POLICY (CSP) HEADERS
// ============================================================================

/**
 * Add Content Security Policy headers
 *
 * CSP headers mitigate XSS, clickjacking, and other code injection attacks.
 * This is a fallback if Cloudflare CSP is not configured.
 *
 * Policy (updated for Google Analytics, GTM, Fonts, Cloudflare Zaraz):
 * • default-src 'self' - Only load resources from same origin by default
 * • script-src - Allow scripts from self, Google Analytics, GTM, Cloudflare
 * • style-src - Allow styles from self, Google Fonts
 * • img-src - Allow images from self, data URIs, HTTPS, Google Analytics
 * • font-src - Allow fonts from self, data URIs, Google Fonts
 * • connect-src - Allow AJAX to self, Google Analytics, Cloudflare
 * • frame-src - Allow frames from YouTube, Vimeo (embedded videos)
 * • frame-ancestors 'none' - Prevent clickjacking (X-Frame-Options alternative)
 * • base-uri 'self' - Restrict <base> tag URLs
 * • form-action 'self' - Restrict form submissions to same origin
 *
 * Note:
 * • 'unsafe-inline' is required for WordPress inline styles/scripts
 * • 'unsafe-eval' is required for some WordPress plugins (GenerateBlocks)
 * • For stricter policy, implement nonce-based CSP in the future
 * • Testing: Check browser console for CSP violations
 *
 * @since 1.4.0
 * @updated 1.5.1 - Fixed CSP to allow Google services and Cloudflare Zaraz
 * @updated 1.5.2 - Added CSP Report Endpoint (Cloudflare Worker)
 * @updated 1.5.3 - Added accounts.google.com, *.gstatic.com + Skip CSP for AJAX
 * @return void
 */
add_action(
	'send_headers',
	static function (): void {
		// Skip CSP headers if already sent, in admin area, or during AJAX requests
		// AJAX requests (admin-ajax.php) need relaxed CSP for WordPress functionality
		if ( headers_sent() || is_admin() || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			return;
		}

		// Google Analytics and Tag Manager domains
		$google_scripts = implode(
			' ',
			array(
				'https://*.googletagmanager.com',
				'https://*.google-analytics.com',
				'https://www.google.com',
				'https://www.googleadservices.com',
				'https://accounts.google.com', // Google Sign-In
				'https://*.gstatic.com', // Google static resources
			)
		);

		// Cloudflare domains (Zaraz analytics)
		$cloudflare = 'https://*.cloudflare.com';

		// CSP Report Endpoint (Cloudflare Worker)
		$csp_report_uri = 'https://csp-report-endpoint.moto08405.workers.dev';

		// Google Fonts domains
		$google_fonts_css  = 'https://fonts.googleapis.com';
		$google_fonts_woff = 'https://fonts.gstatic.com';

		// Analytics connect domains (for beacons and data collection)
		$analytics_connect = implode(
			' ',
			array(
				'https://*.google-analytics.com',
				'https://*.analytics.google.com',
				'https://stats.g.doubleclick.net',
				'https://*.doubleclick.net',
				'https://*.cloudflare.com',
				$csp_report_uri, // CSP violation reports
			)
		);

		// Video embed domains
		$video_frames = implode(
			' ',
			array(
				'https://www.youtube.com',
				'https://www.youtube-nocookie.com',
				'https://player.vimeo.com',
			)
		);

		$csp_directives = array(
			"default-src 'self'",
			"script-src 'self' 'unsafe-inline' 'unsafe-eval' {$google_scripts} {$cloudflare}",
			"style-src 'self' 'unsafe-inline' {$google_fonts_css}",
			"img-src 'self' data: https: blob:",
			"font-src 'self' data: {$google_fonts_woff}",
			"connect-src 'self' {$analytics_connect}",
			"frame-src 'self' {$video_frames}",
			"frame-ancestors 'none'",
			"base-uri 'self'",
			"form-action 'self'",
			"object-src 'none'",
			'upgrade-insecure-requests',
			"report-uri {$csp_report_uri}",
		);

		$csp_policy = implode( '; ', $csp_directives );

		// Report-To header for Reporting API (newer browsers)
		$report_to = wp_json_encode(
			array(
				'group'     => 'csp-endpoint',
				'max_age'   => 10886400, // 126 days
				'endpoints' => array(
					array( 'url' => $csp_report_uri ),
				),
			)
		);
		header( 'Report-To: ' . $report_to );

		// Content Security Policy
		header( 'Content-Security-Policy: ' . $csp_policy );

		// Cross-Origin headers (CORP, COOP, COEP)
		// Using permissive values for compatibility with Google services
		header( 'Cross-Origin-Resource-Policy: cross-origin' );
		header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );
		// COEP: unsafe-none allows loading cross-origin resources without CORS
		// Required for Google Fonts, Analytics, etc.
		header( 'Cross-Origin-Embedder-Policy: unsafe-none' );

		// Additional security headers (defense in depth)
		header( 'X-Frame-Options: DENY' );
		header( 'X-Content-Type-Options: nosniff' );
		header( 'X-XSS-Protection: 1; mode=block' );
		header( 'Referrer-Policy: strict-origin-when-cross-origin' );
		header( 'Permissions-Policy: geolocation=(), microphone=(), camera=()' );
	},
	1
);

// ============================================================================
// ADDITIONAL SECURITY NOTES
// ============================================================================

/**
 * SECURITY CHECKLIST:
 *
 * ✅ XML-RPC disabled (this file)
 * ✅ X-Pingback header removed (this file)
 * ✅ RSD link removed (this file)
 * ✅ WordPress version hidden (this file)
 * ✅ CSP policy enforced (this file + Cloudflare fallback)
 *    → Allows Google Analytics, GTM, Google Fonts, Cloudflare Zaraz
 *    → Allows YouTube/Vimeo embeds via frame-src
 *    → Blocks object-src (Flash, Java applets)
 *    → Forces HTTPS via upgrade-insecure-requests
 *    → Reports violations to Cloudflare Worker (report-uri + Report-To)
 * ✅ Security headers (X-Frame-Options, X-Content-Type-Options, etc.)
 * ✅ Cross-Origin headers (CORP, COOP, COEP) for Google services compatibility
 * ✅ Font preload fixed (assets.php)
 * ✅ .htaccess WordPress routing (root .htaccess)
 * ✅ wp-config.php hardened (production settings)
 *
 * ALSO in Cloudflare (defense in depth):
 * • Security headers (duplicated for fallback)
 * • HTTPS redirect (Cloudflare Always Use HTTPS)
 * • WAF rules (Cloudflare WAF)
 * • DDoS protection (Cloudflare DDoS Protection)
 *
 * DEPLOYMENT NOTES:
 *
 * 1. Runtime impact is negligible:
 *    → A few lightweight filters only
 *    → No database queries
 *    → No extra HTTP requests
 *
 * 2. Compatibility:
 *    ✓ Works with all modern WordPress versions
 *    ✓ Compatible with GeneratePress
 *    ✓ May break services depending on XML-RPC
 *      (Jetpack, mobile app, remote publishing)
 *
 * 3. Testing XML-RPC is disabled:
 *    Option A (HTTP client):
 *      • Send a POST request to https://example.com/xmlrpc.php
 *      • Expected body contains a message like:
 *        "XML-RPC services are disabled on this site."
 *        or HTTP 403/404 depending on server/WAF
 *
 *    Option B (online validator):
 *      • Use an XML-RPC validation service and confirm
 *        that XML-RPC calls fail / are reported as disabled.
 *
 * 4. Testing X-Pingback and RSD are removed:
 *    $ curl -I https://example.com/
 *    Expected:
 *      • No X-Pingback header
 *      • No <link rel="EditURI" ... xmlrpc.php?rsd> in page source
 */

// ============================================================================
// END OF SECURITY.PHP
// ============================================================================
