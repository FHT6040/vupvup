<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Admin {

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_vupvup_regenerate_qr', [ $this, 'ajax_regenerate_qr' ] );
    }

    public function register_menus(): void {
        add_menu_page(
            __( 'VupVup Q&A', 'vupvup-qa' ),
            __( 'VupVup Q&A', 'vupvup-qa' ),
            'vupvup_view_dashboard',
            'vupvup-qa',
            [ $this, 'render_events_page' ],
            'dashicons-format-chat',
            30
        );

        add_submenu_page(
            'vupvup-qa',
            __( 'Mine Events', 'vupvup-qa' ),
            __( 'Mine Events', 'vupvup-qa' ),
            'vupvup_view_dashboard',
            'vupvup-qa',
            [ $this, 'render_events_page' ]
        );

        add_submenu_page(
            'vupvup-qa',
            __( 'Nyt Event', 'vupvup-qa' ),
            __( 'Nyt Event', 'vupvup-qa' ),
            'vupvup_manage_own_events',
            'post-new.php?post_type=event_qna'
        );

    }

    public function enqueue_scripts( string $hook ): void {
        $allowed_hooks = [
            'toplevel_page_vupvup-qa',
            'event_qna',
            'post.php',
            'post-new.php',
        ];

        $is_event_screen = in_array( $hook, $allowed_hooks, true ) ||
            ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) &&
              isset( $_GET['post_type'] ) && $_GET['post_type'] === 'event_qna' );

        if ( ! $is_event_screen ) {
            return;
        }

        wp_enqueue_style(
            'vupvup-admin',
            VUPVUP_QA_URL . 'admin/css/admin.css',
            [],
            VUPVUP_QA_VERSION
        );

    }

    public function render_events_page(): void {
        if ( ! current_user_can( 'vupvup_view_dashboard' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
        $events = VupVup_QA_CPT::get_facilitator_events( get_current_user_id() );
        include VUPVUP_QA_DIR . 'admin/views/events-list.php';
    }

    public function ajax_regenerate_qr(): void {
        check_ajax_referer( 'vupvup_admin', 'nonce' );

        $event_id = (int) ( $_POST['event_id'] ?? 0 );
        if ( ! $event_id || ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            wp_send_json_error( 'Adgang nægtet.' );
        }

        $qr_url = VupVup_QA_QR_Code::regenerate( $event_id );
        if ( $qr_url ) {
            wp_send_json_success( [ 'qr_url' => $qr_url ] );
        } else {
            wp_send_json_error( 'QR-kode kunne ikke genereres.' );
        }
    }
}
