<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Frontend {

    public function register(): void {
        add_action( 'init',                           [ $this, 'add_rewrite_rules' ] );
        add_filter( 'query_vars',                     [ $this, 'add_query_vars' ] );
        add_action( 'template_redirect',              [ $this, 'route_request' ] );
        add_action( 'wp_ajax_vupvup_guest_login',     [ $this, 'ajax_guest_login' ] );
        add_action( 'wp_ajax_nopriv_vupvup_guest_login', [ $this, 'ajax_guest_login' ] );
        add_action( 'wp_ajax_vupvup_create_event',        [ $this, 'ajax_create_event' ] );
        add_action( 'wp_ajax_vupvup_update_event',        [ $this, 'ajax_update_event' ] );
        add_action( 'wp_ajax_vupvup_update_event_status', [ $this, 'ajax_update_event_status' ] );
    }

    // ── Rewrite rules ──────────────────────────────────────────────────────────

    public function add_rewrite_rules(): void {
        // QR landing page + big screen
        add_rewrite_rule( '^qa/([a-zA-Z0-9_-]+)/storskarm/?$', 'index.php?vupvup_event_token=$matches[1]&vupvup_page=bigscreen', 'top' );
        add_rewrite_rule( '^qa/([a-zA-Z0-9_-]+)/?$',           'index.php?vupvup_event_token=$matches[1]', 'top' );

        // Auth
        add_rewrite_rule( '^register/?$', 'index.php?vupvup_page=register', 'top' );
        add_rewrite_rule( '^login/?$',    'index.php?vupvup_page=login',    'top' );
        add_rewrite_rule( '^verify/?$',   'index.php?vupvup_page=verify',   'top' );

        // Dashboard
        add_rewrite_rule( '^dashboard/?$',                        'index.php?vupvup_page=dashboard',      'top' );
        add_rewrite_rule( '^dashboard/event/ny/?$',               'index.php?vupvup_page=event-new',      'top' );
        add_rewrite_rule( '^dashboard/event/([0-9]+)/?$',         'index.php?vupvup_page=event-live&vupvup_event_id=$matches[1]', 'top' );
        add_rewrite_rule( '^dashboard/event/([0-9]+)/rediger/?$', 'index.php?vupvup_page=event-edit&vupvup_event_id=$matches[1]', 'top' );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'vupvup_event_token';
        $vars[] = 'vupvup_page';
        $vars[] = 'vupvup_event_id';
        return $vars;
    }

    // ── Route dispatcher ───────────────────────────────────────────────────────

    public function route_request(): void {
        $page  = get_query_var( 'vupvup_page' );
        $token = get_query_var( 'vupvup_event_token' );

        if ( $token ) {
            if ( $page === 'bigscreen' ) { $this->render_bigscreen( sanitize_text_field( $token ) ); exit; }
            $this->render_qa_landing( sanitize_text_field( $token ) ); exit;
        }

        if ( ! $page ) return;

        match ( $page ) {
            'register'   => $this->render_register(),
            'login'      => $this->render_login(),
            'verify'     => $this->render_verify(),
            'dashboard'  => $this->render_dashboard_home(),
            'event-new'  => $this->render_event_new(),
            'event-live' => $this->render_event_live(),
            'event-edit' => $this->render_event_edit(),
            default      => null,
        };

        if ( $page ) exit;
    }

    // ── Page renderers ─────────────────────────────────────────────────────────

    private function render_register(): void {
        if ( is_user_logged_in() ) {
            wp_redirect( home_url( 'dashboard/' ) ); exit;
        }
        $this->enqueue_dashboard_assets();
        include VUPVUP_QA_DIR . 'public/templates/register.php';
    }

    private function render_login(): void {
        if ( is_user_logged_in() ) {
            wp_redirect( home_url( 'dashboard/' ) ); exit;
        }
        $this->enqueue_dashboard_assets();
        include VUPVUP_QA_DIR . 'public/templates/login.php';
    }

    private function render_verify(): void {
        $token   = sanitize_text_field( $_GET['token'] ?? '' );
        $user_id = (int) ( $_GET['uid'] ?? 0 );
        $success = false;
        $message = '';

        if ( $token && $user_id ) {
            $stored = get_user_meta( $user_id, 'vupvup_email_verify_token', true );
            if ( $stored && hash_equals( $stored, $token ) ) {
                update_user_meta( $user_id, 'vupvup_email_verified', 1 );
                delete_user_meta( $user_id, 'vupvup_email_verify_token' );
                $success = true;
                $message = __( 'Din e-mail er bekræftet! Du kan nu logge ind.', 'vupvup-qa' );
            } else {
                $message = __( 'Ugyldig eller udløbet bekræftelseslink.', 'vupvup-qa' );
            }
        } else {
            $message = __( 'Mangler bekræftelsestoken.', 'vupvup-qa' );
        }

        $this->enqueue_dashboard_assets();
        include VUPVUP_QA_DIR . 'public/templates/verify.php';
    }

    private function render_dashboard_home(): void {
        $this->require_login();
        $current_user = wp_get_current_user();
        $events       = VupVup_QA_CPT::get_facilitator_events( $current_user->ID );
        $this->enqueue_dashboard_assets();
        include VUPVUP_QA_DIR . 'public/templates/dashboard-home.php';
    }

    private function render_event_new(): void {
        $this->require_login();
        $this->enqueue_dashboard_assets();
        include VUPVUP_QA_DIR . 'public/templates/dashboard-event-new.php';
    }

    private function render_event_live(): void {
        $this->require_login();
        $event_id = (int) get_query_var( 'vupvup_event_id' );
        if ( ! $event_id || ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            wp_redirect( home_url( 'dashboard/' ) ); exit;
        }
        $event   = get_post( $event_id );
        $status  = get_post_meta( $event_id, '_vupvup_event_status', true ) ?: 'draft';
        $qr_url  = get_post_meta( $event_id, '_vupvup_event_qr_url', true );
        $token   = get_post_meta( $event_id, '_vupvup_event_token', true );
        $landing   = $token ? home_url( 'qa/' . $token . '/' ) : '';
        $bigscreen = $token ? home_url( 'qa/' . $token . '/storskarm/' ) : '';
        $this->enqueue_dashboard_assets( $event_id );
        include VUPVUP_QA_DIR . 'public/templates/dashboard-event-live.php';
    }

    private function render_event_edit(): void {
        $this->require_login();
        $event_id = (int) get_query_var( 'vupvup_event_id' );
        if ( ! $event_id || ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            wp_redirect( home_url( 'dashboard/' ) ); exit;
        }
        $event    = get_post( $event_id );
        $status   = get_post_meta( $event_id, '_vupvup_event_status', true ) ?: 'draft';
        $start    = get_post_meta( $event_id, '_vupvup_event_start_time', true );
        $end      = get_post_meta( $event_id, '_vupvup_event_end_time', true );
        $location = get_post_meta( $event_id, '_vupvup_event_location', true );
        $speakers = get_post_meta( $event_id, '_vupvup_event_speakers', true );
        $guest    = (bool) get_post_meta( $event_id, '_vupvup_event_guest_allowed', true );
        $this->enqueue_dashboard_assets( $event_id );
        include VUPVUP_QA_DIR . 'public/templates/dashboard-event-edit.php';
    }

    private function render_qa_landing( string $token ): void {
        $event_id = VupVup_QA_CPT::get_event_by_token( $token );
        if ( ! $event_id ) {
            wp_die( esc_html__( 'Dette event findes ikke.', 'vupvup-qa' ), '', [ 'response' => 404 ] );
        }
        $status        = get_post_meta( $event_id, '_vupvup_event_status', true );
        $event_title   = get_the_title( $event_id );
        $guest_allowed = (bool) get_post_meta( $event_id, '_vupvup_event_guest_allowed', true );
        $speakers_raw  = get_post_meta( $event_id, '_vupvup_event_speakers', true );
        $speakers      = $speakers_raw
            ? array_values( array_filter( array_map( 'trim', explode( "\n", $speakers_raw ) ) ) )
            : [];
        $is_logged_in  = is_user_logged_in();
        $guest_name    = '';
        if ( ! $is_logged_in && $guest_allowed ) {
            if ( session_status() === PHP_SESSION_NONE ) session_start();
            $guest_name = sanitize_text_field( $_SESSION['vupvup_guest_name'] ?? '' );
        }

        wp_enqueue_style(  'vupvup-attendee', VUPVUP_QA_URL . 'public/css/attendee.css', [], VUPVUP_QA_VERSION );
        wp_enqueue_script( 'vupvup-attendee', VUPVUP_QA_URL . 'public/js/attendee.js',   [], VUPVUP_QA_VERSION, true );
        wp_localize_script( 'vupvup-attendee', 'vupvupData', [
            'restUrl'      => rest_url( 'vupvup-qa/v1' ),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
            'eventId'      => $event_id,
            'eventStatus'  => $status,
            'guestAllowed' => $guest_allowed,
            'isLoggedIn'   => $is_logged_in,
            'guestName'    => $guest_name,
            'speakers'     => $speakers,
            'loginUrl'     => home_url( 'login/' ),
            'registerUrl'  => home_url( 'register/' ),
            'i18n'         => [
                'submit'          => __( 'Send spørgsmål', 'vupvup-qa' ),
                'submitting'      => __( 'Sender…', 'vupvup-qa' ),
                'success'         => __( 'Dit spørgsmål er modtaget!', 'vupvup-qa' ),
                'placeholder'     => __( 'Skriv dit spørgsmål her…', 'vupvup-qa' ),
                'namePlaceholder' => __( 'Dit navn', 'vupvup-qa' ),
            ],
        ] );
        include VUPVUP_QA_DIR . 'public/templates/event-landing.php';
    }

    private function render_bigscreen( string $token ): void {
        $event_id = VupVup_QA_CPT::get_event_by_token( $token );
        if ( ! $event_id ) {
            wp_die( esc_html__( 'Dette event findes ikke.', 'vupvup-qa' ), '', [ 'response' => 404 ] );
        }
        $status      = get_post_meta( $event_id, '_vupvup_event_status', true );
        $event_title = get_the_title( $event_id );

        wp_enqueue_style(  'vupvup-bigscreen', VUPVUP_QA_URL . 'public/css/bigscreen.css', [], VUPVUP_QA_VERSION );
        wp_enqueue_script( 'vupvup-bigscreen', VUPVUP_QA_URL . 'public/js/bigscreen.js',   [], VUPVUP_QA_VERSION, true );
        wp_localize_script( 'vupvup-bigscreen', 'vupvupBig', [
            'restUrl'     => rest_url( 'vupvup-qa/v1' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'eventId'     => $event_id,
            'eventStatus' => $status,
        ] );
        include VUPVUP_QA_DIR . 'public/templates/bigscreen.php';
    }

    // ── AJAX handlers ──────────────────────────────────────────────────────────

    public function ajax_guest_login(): void {
        if ( ! isset( $_POST['nonce'] ) ||
             ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vupvup_guest_login' ) ) {
            wp_send_json_error( 'Ugyldig anmodning.' );
        }
        if ( session_status() === PHP_SESSION_NONE ) session_start();
        $name = sanitize_text_field( wp_unslash( $_POST['guest_name'] ?? '' ) );
        if ( strlen( $name ) < 2 ) { wp_send_json_error( 'Navn for kort.' ); }
        $_SESSION['vupvup_guest_name'] = $name;
        wp_send_json_success( [
            'guest_name' => $name,
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }

    public function ajax_create_event(): void {
        if ( ! is_user_logged_in() || ! current_user_can( 'vupvup_manage_own_events' ) ) {
            wp_send_json_error( 'Adgang nægtet.' );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'vupvup_new_event' ) ) {
            wp_send_json_error( 'Ugyldig anmodning.' );
        }

        $title = sanitize_text_field( $_POST['title'] ?? '' );
        if ( ! $title ) { wp_send_json_error( 'Titel er påkrævet.' ); }

        $status  = ! empty( $_POST['activate_now'] ) ? 'active' : 'draft';
        $post_id = wp_insert_post( [
            'post_title'  => $title,
            'post_type'   => 'event_qna',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
        ] );

        if ( is_wp_error( $post_id ) ) { wp_send_json_error( $post_id->get_error_message() ); }

        update_post_meta( $post_id, '_vupvup_event_status',       $status );
        update_post_meta( $post_id, '_vupvup_event_start_time',   sanitize_text_field( $_POST['start_time'] ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_end_time',     sanitize_text_field( $_POST['end_time']   ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_location',     sanitize_text_field( $_POST['location']   ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_speakers',     sanitize_textarea_field( $_POST['speakers'] ?? '' ) );
        update_post_meta( $post_id, '_vupvup_event_guest_allowed', ! empty( $_POST['guest_allowed'] ) ? 1 : 0 );
        update_post_meta( $post_id, '_vupvup_facilitator_id',     get_current_user_id() );

        // Generate token + QR.
        $token = wp_generate_password( 12, false );
        update_post_meta( $post_id, '_vupvup_event_token', $token );
        $landing_url = home_url( 'qa/' . $token . '/' );
        $qr_url      = VupVup_QA_QR_Code::generate( $landing_url, $post_id );
        if ( $qr_url ) update_post_meta( $post_id, '_vupvup_event_qr_url', $qr_url );

        if ( $status === 'active' ) {
            $end = sanitize_text_field( $_POST['end_time'] ?? '' );
            if ( $end ) VupVup_QA_Cron::schedule_close( $post_id, $end );
        }

        wp_send_json_success( [
            'redirect_url' => home_url( 'dashboard/event/' . $post_id . '/' ),
        ] );
    }

    public function ajax_update_event(): void {
        if ( ! is_user_logged_in() || ! current_user_can( 'vupvup_manage_own_events' ) ) {
            wp_send_json_error( 'Adgang nægtet.' );
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ?? '' ) ), 'vupvup_edit_event' ) ) {
            wp_send_json_error( 'Ugyldig anmodning.' );
        }
        $event_id = (int) ( $_POST['event_id'] ?? 0 );
        if ( ! $event_id || ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            wp_send_json_error( 'Adgang nægtet.' );
        }
        $title = sanitize_text_field( $_POST['title'] ?? '' );
        if ( ! $title ) { wp_send_json_error( 'Titel er påkrævet.' ); }

        wp_update_post( [ 'ID' => $event_id, 'post_title' => $title ] );
        update_post_meta( $event_id, '_vupvup_event_start_time',    sanitize_text_field( $_POST['start_time'] ?? '' ) );
        update_post_meta( $event_id, '_vupvup_event_end_time',      sanitize_text_field( $_POST['end_time']   ?? '' ) );
        update_post_meta( $event_id, '_vupvup_event_location',      sanitize_text_field( $_POST['location']   ?? '' ) );
        update_post_meta( $event_id, '_vupvup_event_speakers',      sanitize_textarea_field( $_POST['speakers'] ?? '' ) );
        update_post_meta( $event_id, '_vupvup_event_guest_allowed', ! empty( $_POST['guest_allowed'] ) ? 1 : 0 );

        wp_send_json_success( [
            'redirect_url' => home_url( 'vupvup/dashboard/event/' . $event_id . '/' ),
        ] );
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

    // ── Assets ─────────────────────────────────────────────────────────────────

    private function enqueue_dashboard_assets( int $event_id = 0 ): void {
        wp_enqueue_style(  'vupvup-dashboard', VUPVUP_QA_URL . 'public/css/dashboard.css', [], VUPVUP_QA_VERSION );
        wp_enqueue_script( 'vupvup-dashboard', VUPVUP_QA_URL . 'public/js/dashboard.js',   [], VUPVUP_QA_VERSION, true );
        wp_localize_script( 'vupvup-dashboard', 'vupvupDash', [
            'restUrl' => rest_url( 'vupvup-qa/v1' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'eventId' => $event_id,
        ] );
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function require_login(): void {
        if ( ! is_user_logged_in() ) {
            wp_redirect( home_url( 'vupvup/login/' ) ); exit;
        }
        if ( ! current_user_can( 'vupvup_view_dashboard' ) ) {
            wp_die( esc_html__( 'Adgang nægtet.', 'vupvup-qa' ) );
        }
    }

    public static function get_question_count( int $event_id ): int {
        global $wpdb;
        return (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->prefix}vupvup_questions WHERE event_id = %d",
                $event_id
            )
        );
    }
}
