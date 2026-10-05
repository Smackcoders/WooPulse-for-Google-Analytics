<?php
/**
 * @license GPL-2.0-or-later
 * @link    https://www.gnu.org/licenses/gpl-2.0.html
 * Affiliate Link Tracking configuration (#39).
 *
 * @package Sm_Pulse_AnalyticsAffiliateLinkModule
 */

namespace Sm_Pulse_Analytics\AffiliateLinkModule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Affiliate_Link_Config {

	const OPTION_KEY = 'sm_pulse_analytics_affiliate_link_tracking_settings';
	const CACHE_TTL  = 900;

	/**
	 * @return array<string, mixed>
	 */
	public static function default_settings() {
		return array(
			'enabled'              => true,
			'track_affiliate'      => true,
			'track_outbound'         => true,
			'respect_consent'        => true,
			'affiliate_prefixes'     => array( '/go/', '/out/', '/recommend/', '/recommends/' ),
			'affiliate_domains'      => array(),
			'partner_map'            => array(),
			'excluded_domains'       => array( 'facebook.com', 'twitter.com', 'x.com', 'linkedin.com', 'instagram.com' ),
			'excluded_prefixes'      => array(),
			'ignored_selectors'      => array( '.ignore-pulse-tracking' ),
			'download_extensions'    => array( 'pdf', 'zip', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'mp3', 'mp4', 'epub', 'pptx', 'ppt', 'rar', 'tar', 'gz' ),
		);
	}

	/**
	 * @return array<string, mixed>
	 */
	public static function get_settings() {
		$stored = get_option( self::OPTION_KEY, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		return wp_parse_args( $stored, self::default_settings() );
	}

	/**
	 * @param array<string, mixed> $settings Settings payload.
	 */
	public static function update_settings( array $settings ) {
		$current = self::get_settings();

		foreach ( array( 'enabled', 'track_affiliate', 'track_outbound', 'respect_consent' ) as $key ) {
			if ( array_key_exists( $key, $settings ) ) {
				$current[ $key ] = (bool) $settings[ $key ];
			}
		}

		foreach ( array( 'affiliate_prefixes', 'excluded_prefixes', 'ignored_selectors', 'download_extensions' ) as $list_key ) {
			if ( isset( $settings[ $list_key ] ) && is_array( $settings[ $list_key ] ) ) {
				$current[ $list_key ] = array_values(
					array_filter(
						array_map( 'sanitize_text_field', $settings[ $list_key ] )
					)
				);
			}
		}

		foreach ( array( 'affiliate_domains', 'excluded_domains' ) as $domain_key ) {
			if ( isset( $settings[ $domain_key ] ) && is_array( $settings[ $domain_key ] ) ) {
				$current[ $domain_key ] = array_values(
					array_filter(
						array_map( array( __CLASS__, 'normalize_domain' ), $settings[ $domain_key ] )
					)
				);
			}
		}

		if ( isset( $settings['partner_map'] ) && is_array( $settings['partner_map'] ) ) {
			$map = array();
			foreach ( $settings['partner_map'] as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				$domain = self::normalize_domain( $row['domain'] ?? '' );
				$name   = sanitize_text_field( $row['name'] ?? '' );
				if ( '' !== $domain ) {
					$map[] = array(
						'domain' => $domain,
						'name'   => $name ?: $domain,
					);
				}
			}
			$current['partner_map'] = $map;
		}

		update_option( self::OPTION_KEY, $current, false );
		delete_transient( 'pulse_affiliate_link_report_cache' );
	}

	/**
	 * Clean and extract raw domain hostname (e.g. "https://www.facebook.com/page" -> "facebook.com").
	 *
	 * @param string $domain Raw domain input.
	 * @return string
	 */
	public static function normalize_domain( $domain ) {
		$domain = trim( (string) $domain );
		if ( '' === $domain ) {
			return '';
		}
		if ( false === strpos( $domain, '://' ) && 0 !== strpos( $domain, '//' ) ) {
			$domain = 'http://' . $domain;
		}
		$host = wp_parse_url( $domain, PHP_URL_HOST );
		if ( ! $host ) {
			$host = preg_replace( '/[\/\?#].*$/', '', $domain );
		}
		$host = strtolower( trim( (string) $host ) );
		return preg_replace( '/^www\./i', '', $host );
	}

	public static function is_active() {
		$settings = self::get_settings();
		return ! empty( $settings['enabled'] );
	}

	public static function is_tracking_enabled() {
		if ( ! self::is_active() ) {
			return false;
		}
		$settings = self::get_settings();
		return ! empty( $settings['track_affiliate'] ) || ! empty( $settings['track_outbound'] );
	}

	/**
	 * Frontend localization payload.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_frontend_config() {
		$settings = self::get_settings();
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			'enabled'           => self::is_tracking_enabled(),
			'track_affiliate'   => ! empty( $settings['track_affiliate'] ),
			'track_outbound'    => ! empty( $settings['track_outbound'] ),
			'respect_consent'   => ! empty( $settings['respect_consent'] ),
			'site_host'         => $site_host ? strtolower( $site_host ) : '',
			'affiliate_prefixes'=> array_values( $settings['affiliate_prefixes'] ?? array() ),
			'affiliate_domains' => array_map( 'strtolower', $settings['affiliate_domains'] ?? array() ),
			'partner_map'       => $settings['partner_map'] ?? array(),
			'excluded_domains'  => array_map( 'strtolower', $settings['excluded_domains'] ?? array() ),
			'excluded_prefixes'   => $settings['excluded_prefixes'] ?? array(),
			'ignored_selectors'   => $settings['ignored_selectors'] ?? array(),
			'download_extensions' => array_values( array_map( 'strtolower', $settings['download_extensions'] ?? array() ) ),
			'rest_url'            => rest_url( 'pulse-analytics/v1/link-click' ),
			'page_url'          => is_singular() ? get_permalink() : home_url( add_query_arg( array() ) ),
			'page_title'        => wp_get_document_title(),
			'page_path'         => wp_parse_url( home_url( add_query_arg( array() ) ), PHP_URL_PATH ) ?: '/',
		);
	}

	/**
	 * @param string $domain Domain.
	 * @return string
	 */
	public static function partner_name_for_domain( $domain ) {
		$domain = strtolower( sanitize_text_field( $domain ) );
		foreach ( self::get_settings()['partner_map'] as $row ) {
			if ( isset( $row['domain'] ) && $row['domain'] === $domain ) {
				return $row['name'] ?? $domain;
			}
		}
		return $domain;
	}

	/**
	 * @return bool
	 */
	public static function user_can_view() {
		return is_user_logged_in() && ( current_user_can( 'sm_pulse_analytics_view_reports' ) || current_user_can( 'manage_options' ) );
	}

	/**
	 * @return bool
	 */
	public static function user_can_manage() {
		return current_user_can( 'sm_pulse_analytics_manage_settings' ) || current_user_can( 'manage_options' );
	}
}
