<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Admin {

    public function register(): void {
        add_action( 'admin_menu', [ $this, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_vupvup_regenerate_qr', [ $this, 'ajax_regenerate_qr' ] );
        add_action( 'admin_post_vupvup_new_event', [ $this, 'handle_new_event_form' ] );
        add_action( 'admin_notices', [ $this, 'show_admin_notices' ] );
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
            'vupvup_manage_all_events',
            'vupvup-new-event',
            [ $this, 'render_new_event_page' ]
        );

    }

    public function enqueue_scripts( string $hook ): void {
        $allowed_hooks = [
            'toplevel_page_vupvup-qa',
            'vupvup-qa_page_vupvup-new-event',
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

    public function render_new_event_page(): void {
        if ( ! current_user_can( 'vupvup_manage_all_events' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
        $facilitators = get_users( [
            'role'   => 'event_facilitator',
            'fields' => [ 'ID', 'display_name', 'user_email' ],
        ] );
        include VUPVUP_QA_DIR . 'admin/views/new-event.php';
    }

    public function handle_new_event_form(): void {
        global $wpdb;
        if ( ! current_user_can( 'vupvup_manage_all_events' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ?? '' ) ), 'vupvup_admin_new_event' ) ) {
            wp_die( esc_html__( 'Ugyldig anmodning.', 'vupvup-qa' ) );
        }

        $error_url = admin_url( 'admin.php?page=vupvup-new-event' );

        $title = sanitize_text_field( $_POST['vupvup_event_title'] ?? '' );
        if ( ! $title ) {
            wp_redirect( add_query_arg( 'vupvup_error', 'no_title', $error_url ) );
            exit;
        }

        $facilitator_id = (int) ( $_POST['vupvup_facilitator_id'] ?? 0 );
        $facilitator    = $facilitator_id ? get_userdata( $facilitator_id ) : null;
        if ( ! $facilitator ) {
            wp_redirect( add_query_arg( 'vupvup_error', 'no_facilitator', $error_url ) );
            exit;
        }

        $allowed_statuses = [ 'draft', 'active' ];
        $status           = sanitize_text_field( $_POST['vupvup_event_status'] ?? 'draft' );
        if ( ! in_array( $status, $allowed_statuses, true ) ) {
            $status = 'draft';
        }

        $post_id = wp_insert_post( [
            'post_title'  => $title,
            'post_type'   => 'event_qna',
            'post_status' => 'publish',
            'post_author' => $facilitator_id,
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_redirect( add_query_arg( 'vupvup_error', 'create_failed', $error_url ) );
            exit;
        }

        update_post_meta( $post_id, '_vupvup_event_status',        $status );
        update_post_meta( $post_id, '_vupvup_event_start_time',    sanitize_text_field( $_POST['vupvup_event_start_time'] ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_end_time',      sanitize_text_field( $_POST['vupvup_event_end_time']   ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_location',      sanitize_text_field( $_POST['vupvup_event_location']   ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_speakers',      sanitize_textarea_field( $_POST['vupvup_event_speakers'] ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_guest_allowed', ! empty( $_POST['vupvup_guest_allowed'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_vupvup_facilitator_id',      $facilitator_id );

        $token       = wp_generate_password( 12, false );
        $landing_url = home_url( 'qa/' . $token . '/' );
        update_post_meta( $post_id, '_vupvup_event_token', $token );
        $qr_url = VupVup_QA_QR_Code::generate( $landing_url, $post_id );
        if ( $qr_url ) {
            update_post_meta( $post_id, '_vupvup_event_qr_url', $qr_url );
        }

        if ( $status === 'active' ) {
            $end = sanitize_text_field( $_POST['vupvup_event_end_time'] ?? '' );
            if ( $end ) {
                VupVup_QA_Cron::schedule_close( $post_id, $end );
            }
        }

        set_transient(
            'vupvup_admin_notice_' . get_current_user_id(),
            /* translators: 1: event title, 2: facilitator display name */
            sprintf( __( 'Eventet "%1$s" er oprettet og tildelt %2$s.', 'vupvup-qa' ), $title, $facilitator->display_name ),
            60
        );
        wp_redirect( admin_url( 'admin.php?page=vupvup-qa' ) );
        exit;
    }

    public function show_admin_notices(): void {
        $key     = 'vupvup_admin_notice_' . get_current_user_id();
        $message = get_transient( $key );
        if ( ! $message ) {
            return;
        }
        delete_transient( $key );
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
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
