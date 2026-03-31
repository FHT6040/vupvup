<?php
defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator — singleton.
 */
class VupVup_QA_Plugin {

    private static ?self $instance = null;

    public static function instance(): self {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        load_plugin_textdomain( 'vupvup-qa', false, dirname( plugin_basename( VUPVUP_QA_FILE ) ) . '/languages' );

        VupVup_QA_Installer::maybe_upgrade();

        ( new VupVup_QA_CPT() )->register();
        ( new VupVup_QA_REST_API() )->register();
        ( new VupVup_QA_Cron() )->register();
        ( new VupVup_QA_Frontend() )->register();

        if ( is_admin() ) {
            ( new VupVup_QA_Admin() )->register();
        }
    }
}
