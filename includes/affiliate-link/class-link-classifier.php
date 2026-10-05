<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Link classification engine (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Link_Classifier {

	const TYPE_AFFILIATE = 'affiliate';
	const TYPE_OUTBOUND  = 'outbound';
	const TYPE_INTERNAL  = 'internal';
	const TYPE_IGNORED   = 'ignored';

	/**
	 * @param string               $href     Link URL.
	 * @param array<string, mixed> $settings Config.
	 * @return string affiliate|outbound|internal|ignored
	 */
	public static function classify( $href, array $settings = null ) {
		$href = trim( (string) $href );
		if ( '' === $href ) {
			return self::TYPE_IGNORED;
		}

		if ( preg_match( '/^(mailto:|tel:|javascript:|#)/i', $href ) ) {
			return self::TYPE_IGNORED;
		}

		if ( null === $settings ) {
			$settings = Affiliate_Link_Config::get_settings();
		}

		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );
		$site_host = $site_host ? strtolower( $site_host ) : '';

		$parsed = wp_parse_url( $href );
		$path   = strtolower( $parsed['path'] ?? $href );
		$host   = isset( $parsed['host'] ) ? strtolower( $parsed['host'] ) : '';

		foreach ( $settings['excluded_prefixes'] ?? array() as $prefix ) {
			if ( '' !== $prefix && 0 === strpos( $path, strtolower( $prefix ) ) ) {
				return self::TYPE_IGNORED;
			}
		}

		if ( '' === $host || ( $site_host && $host === $site_host ) ) {
			foreach ( $settings['affiliate_prefixes'] ?? array() as $prefix ) {
				$prefix = strtolower( $prefix );
				if ( '' !== $prefix && false !== strpos( $path, $prefix ) ) {
					return self::TYPE_AFFILIATE;
				}
			}
			return self::TYPE_INTERNAL;
		}

		foreach ( $settings['excluded_domains'] ?? array() as $excluded ) {
			if ( self::host_matches( $host, strtolower( $excluded ) ) ) {
				return self::TYPE_IGNORED;
			}
		}

		foreach ( $settings['affiliate_domains'] ?? array() as $domain ) {
			if ( self::host_matches( $host, strtolower( $domain ) ) ) {
				return self::TYPE_AFFILIATE;
			}
		}

		foreach ( $settings['affiliate_prefixes'] ?? array() as $prefix ) {
			if ( false !== strpos( strtolower( $href ), strtolower( $prefix ) ) ) {
				return self::TYPE_AFFILIATE;
			}
		}

		if ( preg_match( '/(ref=|affiliate|aff_id|partner=|tag=)/i', $href ) ) {
			return self::TYPE_AFFILIATE;
		}

		return self::TYPE_OUTBOUND;
	}

	/**
	 * @param string $host   Link host.
	 * @param string $needle Domain or host fragment.
	 * @return bool
	 */
	private static function host_matches( $host, $needle ) {
		if ( '' === $needle || '' === $host ) {
			return false;
		}
		$host   = Affiliate_Link_Config::normalize_domain( $host );
		$needle = Affiliate_Link_Config::normalize_domain( $needle );
		if ( '' === $host || '' === $needle ) {
			return false;
		}
		return $host === $needle || substr( $host, -strlen( $needle ) - 1 ) === '.' . $needle;
	}

	/**
	 * Sanitize URL for reports — strip sensitive query params.
	 *
	 * @param string $url Raw URL.
	 * @return string
	 */
	public static function sanitize_report_url( $url ) {
		$parts = wp_parse_url( $url );
		if ( empty( $parts['host'] ) ) {
			return sanitize_text_field( $url );
		}
		$path = $parts['path'] ?? '/';
		return ( $parts['scheme'] ?? 'https' ) . '://' . $parts['host'] . $path;
	}

	/**
	 * @param string $url URL.
	 * @return string
	 */
	public static function extract_domain( $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		return $host ? strtolower( $host ) : '';
	}
}
