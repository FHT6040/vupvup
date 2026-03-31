<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Registration {

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
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

        // Log the user in.
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id, true );

        // Send welcome email.
        self::send_welcome_email( $user_id );

        return new WP_REST_Response( [
            'success'      => true,
            'redirect_url' => home_url( 'vupvup/dashboard/' ),
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
            'redirect_url' => home_url( 'vupvup/dashboard/' ),
        ] );
    }

    private static function send_welcome_email( int $user_id ): void {
        $user      = get_userdata( $user_id );
        $site_name = get_bloginfo( 'name' );
        $dashboard = home_url( 'vupvup/dashboard/' );

        $subject = sprintf( __( 'Velkommen til %s', 'vupvup-qa' ), $site_name );
        $message = sprintf(
            __( "Hej %s,\n\nDin konto er oprettet. Du kan nu logge ind og oprette dit første event.\n\n%s\n\nMed venlig hilsen\n%s", 'vupvup-qa' ),
            $user->first_name ?: $user->display_name,
            $dashboard,
            $site_name
        );

        wp_mail( $user->user_email, $subject, $message );
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
