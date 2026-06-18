<?php
/**
 * Plugin Name:       Wedding Widget
 * Plugin URI:        https://example.com/wedding-widget
 * Description:       An independent Elementor widget pack for wedding invitation sites — Countdown, Cover, RSVP, Wishes, Music, Timeline, WhatsApp, Copy Text, Add to Calendar, and QR — plus a private in-editor template library with categories, search, and thumbnails. No license key required.
 * Version:           1.0.0
 * Author:            You
 * Text Domain:       wedding-widget
 * Domain Path:       /languages
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Requires Plugins:  elementor
 *
 * @package WeddingWidget
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WEDDING_WIDGET_VERSION', '1.0.0' );
define( 'WEDDING_WIDGET_FILE', __FILE__ );
define( 'WEDDING_WIDGET_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEDDING_WIDGET_URL', plugin_dir_url( __FILE__ ) );
define( 'WEDDING_WIDGET_MIN_ELEMENTOR', '3.0.0' );

/**
 * Bootstrap after all plugins are loaded so Elementor can be detected.
 */
function wedding_widget_bootstrap() {
	if ( ! did_action( 'elementor/loaded' ) ) {
		add_action( 'admin_notices', 'wedding_widget_notice_no_elementor' );
		return;
	}

	if ( defined( 'ELEMENTOR_VERSION' ) && ! version_compare( ELEMENTOR_VERSION, WEDDING_WIDGET_MIN_ELEMENTOR, '>=' ) ) {
		add_action( 'admin_notices', 'wedding_widget_notice_elementor_version' );
		return;
	}

	require_once WEDDING_WIDGET_PATH . 'includes/class-ww-loader.php';
	WeddingWidget\WW_Loader::instance();
}
add_action( 'plugins_loaded', 'wedding_widget_bootstrap' );

/**
 * Load translations.
 */
function wedding_widget_load_textdomain() {
	load_plugin_textdomain( 'wedding-widget', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'wedding_widget_load_textdomain' );

/**
 * Admin notice: Elementor missing.
 */
function wedding_widget_notice_no_elementor() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	$message = sprintf(
		/* translators: 1: plugin name, 2: Elementor */
		esc_html__( '"%1$s" requires "%2$s" to be installed and activated.', 'wedding-widget' ),
		'<strong>' . esc_html__( 'Wedding Widget', 'wedding-widget' ) . '</strong>',
		'<strong>' . esc_html__( 'Elementor', 'wedding-widget' ) . '</strong>'
	);
	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $message ) );
}

/**
 * Admin notice: Elementor too old.
 */
function wedding_widget_notice_elementor_version() {
	if ( isset( $_GET['activate'] ) ) {
		unset( $_GET['activate'] );
	}
	$message = sprintf(
		/* translators: 1: plugin name, 2: Elementor, 3: version */
		esc_html__( '"%1$s" requires "%2$s" %3$s or greater.', 'wedding-widget' ),
		'<strong>' . esc_html__( 'Wedding Widget', 'wedding-widget' ) . '</strong>',
		'<strong>' . esc_html__( 'Elementor', 'wedding-widget' ) . '</strong>',
		WEDDING_WIDGET_MIN_ELEMENTOR
	);
	printf( '<div class="notice notice-warning is-dismissible"><p>%s</p></div>', wp_kses_post( $message ) );
}
