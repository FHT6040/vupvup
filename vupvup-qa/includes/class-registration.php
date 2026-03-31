<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Registration {

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
        add_action( 'wp_ajax_vupvup_resend_verification', [ $this, 'ajax_resend_verification' ] );
    }

    public function register_routes(): void {
        register_rest_route( 'vupvup-qa/v1', '/register', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_register' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'first_name' => [ 'type' => 'string', 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'last_name'  => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
                'email'      => [ 'type' => 'string', 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
                'password'   => [ 'type' => 'string', 'required' => true ],
                'company'    => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
            ],
        ] );

        register_rest_route( 'vupvup-qa/v1', '/login', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'handle_login' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'email'    => [ 'type' => 'string', 'required' => true, 'sanitize_callback' => 'sanitize_email' ],
                'password' => [ 'type' => 'string', 'required' => true ],
            ],
        ] );
    }

    public function handle_register( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $email      = $request->get_param( 'email' );
        $password   = $request->get_param( 'password' );
        $first_name = $request->get_param( 'first_name' );
        $last_name  = $request->get_param( 'last_name' ) ?? '';
        $company    = $request->get_param( 'company' ) ?? '';

        if ( ! is_email( $email ) ) {
            return new WP_Error( 'invalid_email', __( 'Ugyldig e-mailadresse.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }
        if ( email_exists( $email ) ) {
            return new WP_Error( 'email_exists', __( 'Der findes allerede en konto med denne e-mail.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }
        if ( strlen( $password ) < 8 ) {
            return new WP_Error( 'weak_password', __( 'Adgangskoden skal være mindst 8 tegn.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }

        $username = sanitize_user( strstr( $email, '@', true ) . '_' . wp_generate_password( 4, false ) );
        while ( username_exists( $username ) ) {
            $username = sanitize_user( strstr( $email, '@', true ) . '_' . wp_generate_password( 4, false ) );
        }

        $user_id = wp_create_user( $username, $password, $email );
        if ( is_wp_error( $user_id ) ) {
            return $user_id;
        }

        // Set name and role.
        wp_update_user( [
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( $first_name . ' ' . $last_name ),
            'role'         => 'event_facilitator',
        ] );

        // Store extra meta.
        update_user_meta( $user_id, 'vupvup_company', $company );
        update_user_meta( $user_id, 'vupvup_plan', 'free' );
        update_user_meta( $user_id, 'vupvup_plan_since', current_time( 'mysql' ) );

        // Log the user in immediately — no waiting for email verification.
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        // Send verification email in the background (non-blocking).
        self::send_verification_email( $user_id );

        return new WP_REST_Response( [
            'success'      => true,
            'redirect_url' => home_url( 'dashboard/' ),
        ], 201 );
    }

    public function handle_login( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $email    = $request->get_param( 'email' );
        $password = $request->get_param( 'password' );

        $user = get_user_by( 'email', $email );
        if ( ! $user || ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
            return new WP_Error( 'invalid_credentials', __( 'Forkert e-mail eller adgangskode.', 'vupvup-qa' ), [ 'status' => 401 ] );
        }

        if ( ! user_can( $user, 'vupvup_view_dashboard' ) ) {
            return new WP_Error( 'no_access', __( 'Denne konto har ikke adgang til facilitator-dashboardet.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }

        wp_set_current_user( $user->ID );
        wp_set_auth_cookie( $user->ID, true );

        return new WP_REST_Response( [
            'success'      => true,
            'redirect_url' => home_url( 'dashboard/' ),
        ] );
    }

    /**
     * Send verification email. User is already logged in — this is just a soft nudge.
     */
    public static function send_verification_email( int $user_id ): void {
        $user  = get_userdata( $user_id );
        $token = wp_generate_password( 32, false );

        update_user_meta( $user_id, 'vupvup_email_verify_token', $token );

        $verify_url = add_query_arg( [
            'token' => $token,
            'uid'   => $user_id,
        ], home_url( 'verify/' ) );

        $site_name = get_bloginfo( 'name' );
        $subject   = sprintf( __( 'Bekræft din e-mail — %s', 'vupvup-qa' ), $site_name );
        $message   = sprintf(
            /* translators: 1: first name, 2: verify URL, 3: site name */
            __( "Hej %1\$s,\n\nKlik på linket nedenfor for at bekræfte din e-mailadresse:\n\n%2\$s\n\nLinket udløber ikke — du kan bruge det når det passer dig.\n\nMed venlig hilsen\n%3\$s", 'vupvup-qa' ),
            $user->first_name ?: $user->display_name,
            $verify_url,
            $site_name
        );

        wp_mail( $user->user_email, $subject, $message );
    }

    /**
     * Check if the current user has verified their email.
     */
    public static function is_email_verified( int $user_id ): bool {
        return (bool) get_user_meta( $user_id, 'vupvup_email_verified', true );
    }

    /**
     * Resend verification email via AJAX.
     */
    public static function ajax_resend_verification(): void {
        check_ajax_referer( 'vupvup_resend_verify', 'nonce' );
        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Ikke logget ind.' );
        }
        self::send_verification_email( get_current_user_id() );
        wp_send_json_success( __( 'E-mail sendt! Tjek din indbakke.', 'vupvup-qa' ) );
    }

    /**
     * Check if a user has an active plan.
     * Future: validate Stripe subscription here.
     */
    public static function has_active_plan( int $user_id ): bool {
        $plan = get_user_meta( $user_id, 'vupvup_plan', true );
        // Free plan is always active. Stripe plans validated here later.
        return in_array( $plan, [ 'free', 'pro', 'enterprise' ], true );
    }

    /**
     * Placeholder for Stripe webhook handling (future).
     * Called when a Stripe subscription is created/cancelled.
     */
    public static function update_plan( int $user_id, string $plan, string $stripe_customer_id = '' ): void {
        update_user_meta( $user_id, 'vupvup_plan', $plan );
        if ( $stripe_customer_id ) {
            update_user_meta( $user_id, 'vupvup_stripe_customer_id', $stripe_customer_id );
        }
    }
}
