<?php
/**
 * Plugin Name: VupVup
 * Plugin URI:  https://vupvup.dk
 * Description: Live Q&A for physical events via QR code. Facilitators moderate questions in real time.
 * Version:     1.0.0
 * Author:      VupVup
 * Author URI:  https://vupvup.dk
 * License:     GPL-2.0+
 * Text Domain: vupvup-qa
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 8.0
 */

defined( 'ABSPATH' ) || exit;

define( 'VUPVUP_QA_VERSION', '1.0.0' );
define( 'VUPVUP_QA_FILE', __FILE__ );
define( 'VUPVUP_QA_DIR', plugin_dir_path( __FILE__ ) );
define( 'VUPVUP_QA_URL', plugin_dir_url( __FILE__ ) );
define( 'VUPVUP_QA_DB_VERSION', '2' );

// Autoload composer dependencies (QR code library etc.)
if ( file_exists( VUPVUP_QA_DIR . 'vendor/autoload.php' ) ) {
    require_once VUPVUP_QA_DIR . 'vendor/autoload.php';
}

require_once VUPVUP_QA_DIR . 'includes/class-installer.php';
require_once VUPVUP_QA_DIR . 'includes/class-roles.php';
require_once VUPVUP_QA_DIR . 'includes/class-cpt.php';
require_once VUPVUP_QA_DIR . 'includes/class-qr-code.php';
require_once VUPVUP_QA_DIR . 'includes/class-rate-limiter.php';
require_once VUPVUP_QA_DIR . 'includes/class-rest-api.php';
require_once VUPVUP_QA_DIR . 'includes/class-cron.php';
require_once VUPVUP_QA_DIR . 'includes/class-registration.php';
require_once VUPVUP_QA_DIR . 'public/class-frontend.php';
require_once VUPVUP_QA_DIR . 'admin/class-admin.php';
require_once VUPVUP_QA_DIR . 'includes/class-plugin.php';

register_activation_hook( VUPVUP_QA_FILE, [ 'VupVup_QA_Installer', 'activate' ] );
register_deactivation_hook( VUPVUP_QA_FILE, [ 'VupVup_QA_Installer', 'deactivate' ] );

add_action( 'plugins_loaded', function () {
    VupVup_QA_Plugin::instance()->init();
} );
