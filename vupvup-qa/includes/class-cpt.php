<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_CPT {

    public function register(): void {
        add_action( 'init', [ $this, 'register_post_type' ] );
        add_action( 'add_meta_boxes', [ $this, 'add_meta_boxes' ] );
        add_action( 'save_post_event_qna', [ $this, 'save_meta' ], 10, 2 );
        add_action( 'wp_insert_post', [ $this, 'generate_token_and_qr' ], 10, 3 );
    }

    public function register_post_type(): void {
        $labels = [
            'name'               => __( 'Events', 'vupvup-qa' ),
            'singular_name'      => __( 'Event', 'vupvup-qa' ),
            'add_new'            => __( 'Add New Event', 'vupvup-qa' ),
            'add_new_item'       => __( 'Add New Event', 'vupvup-qa' ),
            'edit_item'          => __( 'Edit Event', 'vupvup-qa' ),
            'menu_name'          => __( 'Q&A Events', 'vupvup-qa' ),
        ];

        register_post_type( 'event_qna', [
            'labels'             => $labels,
            'public'             => false,
            'show_ui'            => true,
            'show_in_menu'       => false, // Shown under our custom menu
            'show_in_rest'       => true,
            'supports'           => [ 'title', 'editor', 'author' ],
            'capability_type'    => 'post',
            'map_meta_cap'       => true,
            'rewrite'            => false,
            'has_archive'        => false,
        ] );
    }

    public function register_rewrite_rules(): void {
        add_rewrite_rule(
            '^qa/([a-zA-Z0-9_-]+)/?$',
            'index.php?vupvup_event_token=$matches[1]',
            'top'
        );
    }

    public function add_query_vars( array $vars ): array {
        $vars[] = 'vupvup_event_token';
        return $vars;
    }

    public function add_meta_boxes(): void {
        add_meta_box(
            'vupvup_event_details',
            __( 'Event Details', 'vupvup-qa' ),
            [ $this, 'render_meta_box' ],
            'event_qna',
            'normal',
            'high'
        );
        add_meta_box(
            'vupvup_event_qr',
            __( 'QR Code & Link', 'vupvup-qa' ),
            [ $this, 'render_qr_meta_box' ],
            'event_qna',
            'side',
            'high'
        );
    }

    public function render_meta_box( WP_Post $post ): void {
        wp_nonce_field( 'vupvup_save_event', 'vupvup_event_nonce' );

        $start_time    = get_post_meta( $post->ID, '_vupvup_event_start_time', true );
        $end_time      = get_post_meta( $post->ID, '_vupvup_event_end_time', true );
        $location      = get_post_meta( $post->ID, '_vupvup_event_location', true );
        $status        = get_post_meta( $post->ID, '_vupvup_event_status', true ) ?: 'draft';
        $guest_allowed = get_post_meta( $post->ID, '_vupvup_event_guest_allowed', true );
        $speakers      = get_post_meta( $post->ID, '_vupvup_event_speakers', true ) ?: '';

        include VUPVUP_QA_DIR . 'admin/views/meta-box-details.php';
    }

    public function render_qr_meta_box( WP_Post $post ): void {
        $token   = get_post_meta( $post->ID, '_vupvup_event_token', true );
        $qr_url  = get_post_meta( $post->ID, '_vupvup_event_qr_url', true );
        $landing = $token ? home_url( 'qa/' . $token . '/' ) : '';

        include VUPVUP_QA_DIR . 'admin/views/meta-box-qr.php';
    }

    public function save_meta( int $post_id, WP_Post $post ): void {
        if (
            ! isset( $_POST['vupvup_event_nonce'] ) ||
            ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['vupvup_event_nonce'] ) ), 'vupvup_save_event' ) ||
            defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ||
            ! current_user_can( 'edit_post', $post_id )
        ) {
            return;
        }

        $fields = [
            '_vupvup_event_start_time'    => 'sanitize_text_field',
            '_vupvup_event_end_time'      => 'sanitize_text_field',
            '_vupvup_event_location'      => 'sanitize_text_field',
            '_vupvup_event_status'        => 'sanitize_text_field',
            '_vupvup_event_speakers'      => 'sanitize_textarea_field',
            '_vupvup_facilitator_id'      => 'intval',
        ];

        foreach ( $fields as $key => $sanitizer ) {
            $raw = $_POST[ ltrim( $key, '_' ) ] ?? $_POST[ str_replace( '_vupvup_event_', 'vupvup_', $key ) ] ?? null;
            if ( $raw !== null ) {
                update_post_meta( $post_id, $key, $sanitizer( $raw ) );
            }
        }

        $guest = isset( $_POST['vupvup_guest_allowed'] ) ? 1 : 0;
        update_post_meta( $post_id, '_vupvup_event_guest_allowed', $guest );

        // Set facilitator to post author if not explicitly set.
        if ( ! get_post_meta( $post_id, '_vupvup_facilitator_id', true ) ) {
            update_post_meta( $post_id, '_vupvup_facilitator_id', $post->post_author );
        }

        // Schedule auto-close if end time is set.
        $end_time = get_post_meta( $post_id, '_vupvup_event_end_time', true );
        if ( $end_time ) {
            VupVup_QA_Cron::schedule_close( $post_id, $end_time );
        }
    }

    public function generate_token_and_qr( int $post_id, WP_Post $post, bool $update ): void {
        if ( $post->post_type !== 'event_qna' ) {
            return;
        }
        $existing_token = get_post_meta( $post_id, '_vupvup_event_token', true );
        if ( $existing_token ) {
            return; // Already generated.
        }

        $token = wp_generate_password( 12, false );
        update_post_meta( $post_id, '_vupvup_event_token', $token );

        // Generate QR code.
        $landing_url = home_url( 'qa/' . $token . '/' );
        $qr_url      = VupVup_QA_QR_Code::generate( $landing_url, $post_id );
        if ( $qr_url ) {
            update_post_meta( $post_id, '_vupvup_event_qr_url', $qr_url );
        }
    }

    /**
     * Get event ID from token.
     */
    public static function get_event_by_token( string $token ): ?int {
        global $wpdb;
        $post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta}
                 WHERE meta_key = '_vupvup_event_token' AND meta_value = %s
                 LIMIT 1",
                $token
            )
        );
        return $post_id ? (int) $post_id : null;
    }

    /**
     * Get all events for a facilitator.
     */
    public static function get_facilitator_events( int $user_id ): array {
        $args = [
            'post_type'      => 'event_qna',
            'posts_per_page' => -1,
            'post_status'    => [ 'publish', 'draft' ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        if ( ! current_user_can( 'vupvup_manage_all_events' ) ) {
            $args['meta_query'] = [
                [
                    'key'   => '_vupvup_facilitator_id',
                    'value' => $user_id,
                    'type'  => 'NUMERIC',
                ],
            ];
        }

        return get_posts( $args );
    }
}
