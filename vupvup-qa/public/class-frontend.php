<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Frontend {

    public function register(): void {
        add_action( 'template_redirect',        [ $this, 'handle_event_landing' ] );
        add_action( 'wp_enqueue_scripts',        [ $this, 'enqueue_scripts' ] );
        add_action( 'wp_ajax_vupvup_guest_login', [ $this, 'ajax_guest_login' ] );
        add_action( 'wp_ajax_nopriv_vupvup_guest_login', [ $this, 'ajax_guest_login' ] );
    }

    /**
     * Intercept requests to /qa/{token}/ and render event landing page.
     */
    public function handle_event_landing(): void {
        $token = get_query_var( 'vupvup_event_token' );
        if ( ! $token ) {
            return;
        }

        $event_id = VupVup_QA_CPT::get_event_by_token( sanitize_text_field( $token ) );
        if ( ! $event_id ) {
            wp_die( esc_html__( 'Dette event findes ikke eller er blevet slettet.', 'vupvup-qa' ), '', [ 'response' => 404 ] );
        }

        $status = get_post_meta( $event_id, '_vupvup_event_status', true );

        // Load our custom template, bypassing the theme.
        $this->render_landing_page( $event_id, $status );
        exit;
    }

    private function render_landing_page( int $event_id, string $status ): void {
        $this->enqueue_attendee_assets();

        $event_title   = get_the_title( $event_id );
        $guest_allowed = (bool) get_post_meta( $event_id, '_vupvup_event_guest_allowed', true );
        $speakers_raw  = get_post_meta( $event_id, '_vupvup_event_speakers', true );
        $speakers      = $speakers_raw
            ? array_filter( array_map( 'trim', explode( "\n", $speakers_raw ) ) )
            : [];

        // Determine current user / guest state.
        $is_logged_in  = is_user_logged_in();
        $guest_name    = '';
        if ( ! $is_logged_in && $guest_allowed ) {
            $guest_name = sanitize_text_field( $_SESSION['vupvup_guest_name'] ?? '' );
        }

        wp_localize_script( 'vupvup-attendee', 'vupvupData', [
            'restUrl'       => rest_url( 'vupvup-qa/v1' ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'eventId'       => $event_id,
            'eventStatus'   => $status,
            'guestAllowed'  => $guest_allowed,
            'isLoggedIn'    => $is_logged_in,
            'guestName'     => $guest_name,
            'speakers'      => array_values( $speakers ),
            'loginUrl'      => wp_login_url( home_url( 'qa/' . get_post_meta( $event_id, '_vupvup_event_token', true ) . '/' ) ),
            'registerUrl'   => wp_registration_url(),
            'i18n'          => [
                'submit'        => __( 'Send spørgsmål', 'vupvup-qa' ),
                'submitting'    => __( 'Sender…', 'vupvup-qa' ),
                'success'       => __( 'Dit spørgsmål er modtaget!', 'vupvup-qa' ),
                'rateLimited'   => __( 'Du har stillet for mange spørgsmål. Vent lidt.', 'vupvup-qa' ),
                'eventClosed'   => __( 'Dette event accepterer ikke spørgsmål i øjeblikket.', 'vupvup-qa' ),
                'loginRequired' => __( 'Du skal logge ind for at stille spørgsmål.', 'vupvup-qa' ),
                'placeholder'   => __( 'Skriv dit spørgsmål her…', 'vupvup-qa' ),
                'namePlaceholder' => __( 'Dit navn', 'vupvup-qa' ),
                'upvote'        => __( 'Stem', 'vupvup-qa' ),
                'voted'         => __( 'Stemt!', 'vupvup-qa' ),
            ],
        ] );

        include VUPVUP_QA_DIR . 'public/templates/event-landing.php';
    }

    private function enqueue_attendee_assets(): void {
        wp_enqueue_style(
            'vupvup-attendee',
            VUPVUP_QA_URL . 'public/css/attendee.css',
            [],
            VUPVUP_QA_VERSION
        );
        wp_enqueue_script(
            'vupvup-attendee',
            VUPVUP_QA_URL . 'public/js/attendee.js',
            [],
            VUPVUP_QA_VERSION,
            true
        );
    }

    public function enqueue_scripts(): void {
        // Only enqueue globally if needed by shortcodes etc.
    }

    /**
     * AJAX: store guest name in session and return nonce for subsequent REST calls.
     */
    public function ajax_guest_login(): void {
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'vupvup_guest_login' ) ) {
            wp_send_json_error( 'Ugyldig anmodning.' );
        }

        if ( session_status() === PHP_SESSION_NONE ) {
            session_start();
        }

        $name = sanitize_text_field( wp_unslash( $_POST['guest_name'] ?? '' ) );
        if ( strlen( $name ) < 2 ) {
            wp_send_json_error( 'Navn for kort.' );
        }

        $_SESSION['vupvup_guest_name'] = $name;

        wp_send_json_success( [
            'guest_name' => $name,
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
        ] );
    }
}
