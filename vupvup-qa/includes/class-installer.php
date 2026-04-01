<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Installer {

    /**
     * Run on plugin activation.
     */
    public static function activate(): void {
        self::create_tables();
        VupVup_QA_Roles::add_roles();
        flush_rewrite_rules();
        update_option( 'vupvup_qa_db_version', VUPVUP_QA_DB_VERSION );
    }

    /**
     * Run on plugin deactivation.
     */
    public static function deactivate(): void {
        wp_clear_scheduled_hook( 'vupvup_qa_auto_close_events' );
        flush_rewrite_rules();
    }

    /**
     * Create the questions table.
     */
    public static function create_tables(): void {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table           = $wpdb->prefix . 'vupvup_questions';

        $sql = "CREATE TABLE {$table} (
            id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            event_id    BIGINT UNSIGNED NOT NULL,
            author_id   BIGINT UNSIGNED NOT NULL DEFAULT 0,
            guest_name  VARCHAR(100)    DEFAULT NULL,
            question    TEXT            NOT NULL,
            status      ENUM('pending','approved','rejected','hidden','asked') NOT NULL DEFAULT 'pending',
            speaker_id  BIGINT UNSIGNED DEFAULT NULL,
            upvotes     INT UNSIGNED    NOT NULL DEFAULT 0,
            highlighted TINYINT(1)      NOT NULL DEFAULT 0,
            ip_hash     VARCHAR(64)     DEFAULT NULL,
            created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY event_id    (event_id),
            KEY status      (status),
            KEY highlighted (highlighted),
            KEY created_at  (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta( $sql );
    }

    /**
     * Run DB upgrades when version changes.
     */
    public static function maybe_upgrade(): void {
        $installed = get_option( 'vupvup_qa_db_version', '0' );
        if ( version_compare( $installed, VUPVUP_QA_DB_VERSION, '<' ) ) {
            self::create_tables();
            update_option( 'vupvup_qa_db_version', VUPVUP_QA_DB_VERSION );
        }
    }
}
