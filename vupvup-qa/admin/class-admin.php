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

        add_submenu_page(
            'vupvup-qa',
            __( 'Facilitator Dashboard', 'vupvup-qa' ),
            __( 'Dashboard', 'vupvup-qa' ),
            'vupvup_moderate_questions',
            'vupvup-dashboard',
            [ $this, 'render_facilitator_dashboard' ]
        );
    }

    public function enqueue_scripts( string $hook ): void {
        $allowed_hooks = [
            'toplevel_page_vupvup-qa',
            'vupvup-q-a_page_vupvup-dashboard',
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

        wp_enqueue_script(
            'vupvup-admin-dashboard',
            VUPVUP_QA_URL . 'admin/js/admin-dashboard.js',
            [ 'wp-api-fetch', 'wp-i18n' ],
            VUPVUP_QA_VERSION,
            true
        );

        wp_localize_script( 'vupvup-admin-dashboard', 'vupvupAdminData', [
            'restUrl'   => rest_url( 'vupvup-qa/v1' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
            'i18n'      => [
                'approve'  => __( 'Godkend', 'vupvup-qa' ),
                'reject'   => __( 'Afvis', 'vupvup-qa' ),
                'hide'     => __( 'Skjul', 'vupvup-qa' ),
                'asked'    => __( 'Stillet', 'vupvup-qa' ),
                'copy'     => __( 'Kopiér', 'vupvup-qa' ),
                'copied'   => __( 'Kopieret!', 'vupvup-qa' ),
                'newQ'     => __( 'Nyt spørgsmål!', 'vupvup-qa' ),
                'loading'  => __( 'Henter…', 'vupvup-qa' ),
                'error'    => __( 'Noget gik galt. Prøv igen.', 'vupvup-qa' ),
            ],
        ] );
    }

    public function render_events_page(): void {
        if ( ! current_user_can( 'vupvup_view_dashboard' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
        $events = VupVup_QA_CPT::get_facilitator_events( get_current_user_id() );
        include VUPVUP_QA_DIR . 'admin/views/events-list.php';
    }

    public function render_facilitator_dashboard(): void {
        if ( ! current_user_can( 'vupvup_moderate_questions' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
        $event_id = isset( $_GET['event_id'] ) ? (int) $_GET['event_id'] : 0;
        $events   = VupVup_QA_CPT::get_facilitator_events( get_current_user_id() );
        $event    = $event_id ? get_post( $event_id ) : null;
        include VUPVUP_QA_DIR . 'admin/views/facilitator-dashboard.php';
    }

    public function ajax_update_event_status(): void {
        check_ajax_referer( 'vupvup_admin', 'nonce' );

        $event_id   = (int) ( $_POST['event_id'] ?? 0 );
        $new_status = sanitize_text_field( $_POST['status'] ?? '' );

        if ( ! $event_id || ! in_array( $new_status, [ 'draft', 'active', 'closed' ], true ) ) {
            wp_send_json_error( 'Ugyldige data.' );
        }

        if ( ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            wp_send_json_error( 'Adgang nægtet.' );
        }

        update_post_meta( $event_id, '_vupvup_event_status', $new_status );
        wp_send_json_success( [ 'status' => $new_status ] );
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
