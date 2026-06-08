<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_REST_API {

    private const NS = 'vupvup-qa/v1';

    public function register(): void {
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    public function register_routes(): void {
        // Submit question (attendee).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/questions', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'submit_question' ],
            'permission_callback' => [ $this, 'can_submit' ],
            'args'                => [
                'event_id'   => [ 'type' => 'integer', 'required' => true, 'minimum' => 1 ],
                'question'   => [ 'type' => 'string',  'required' => true, 'sanitize_callback' => 'sanitize_textarea_field' ],
                'guest_name' => [ 'type' => 'string',  'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
                'speaker_id' => [ 'type' => 'integer', 'required' => false ],
            ],
        ] );

        // List questions (facilitator).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/questions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_questions' ],
            'permission_callback' => [ $this, 'can_moderate' ],
            'args'                => [
                'event_id' => [ 'type' => 'integer', 'required' => true ],
                'status'   => [ 'type' => 'string',  'required' => false, 'default' => 'all' ],
                'orderby'  => [ 'type' => 'string',  'required' => false, 'default' => 'newest' ],
                'since_id' => [ 'type' => 'integer', 'required' => false, 'default' => 0 ],
            ],
        ] );

        // Update question status (facilitator).
        register_rest_route( self::NS, '/questions/(?P<question_id>\d+)/status', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_status' ],
            'permission_callback' => [ $this, 'can_moderate_question' ],
            'args'                => [
                'question_id' => [ 'type' => 'integer', 'required' => true ],
                'status'      => [
                    'type'     => 'string',
                    'required' => true,
                    'enum'     => [ 'pending', 'approved', 'rejected', 'hidden', 'asked' ],
                ],
            ],
        ] );

        // Upvote (attendee).
        register_rest_route( self::NS, '/questions/(?P<question_id>\d+)/upvote', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'upvote_question' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'question_id' => [ 'type' => 'integer', 'required' => true ],
            ],
        ] );

        // List questions for a scene (scene facilitator).
        register_rest_route( self::NS, '/scenes/(?P<scene_id>\d+)/questions', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_scene_questions' ],
            'permission_callback' => [ $this, 'can_moderate_scene' ],
            'args'                => [
                'scene_id' => [ 'type' => 'integer', 'required' => true ],
                'status'   => [ 'type' => 'string',  'required' => false, 'default' => 'all' ],
                'orderby'  => [ 'type' => 'string',  'required' => false, 'default' => 'newest' ],
                'since_id' => [ 'type' => 'integer', 'required' => false, 'default' => 0 ],
            ],
        ] );

        // Get event info (public, used by frontend).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_event' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'event_id' => [ 'type' => 'integer', 'required' => true ],
            ],
        ] );

        // Attendee question list (approved questions for polling).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/feed', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_feed' ],
            'permission_callback' => '__return_true',
            'args'                => [
                'event_id' => [ 'type' => 'integer', 'required' => true ],
                'since_id' => [ 'type' => 'integer', 'required' => false, 'default' => 0 ],
            ],
        ] );

        // Toggle highlight on a question (facilitator).
        register_rest_route( self::NS, '/questions/(?P<question_id>\d+)/highlight', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_highlight' ],
            'permission_callback' => [ $this, 'can_moderate_question' ],
            'args'                => [
                'question_id' => [ 'type' => 'integer', 'required' => true ],
                'highlighted' => [ 'type' => 'boolean', 'required' => true ],
            ],
        ] );

        // Get/set bigscreen display mode (facilitator write, public read).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/bigscreen-state', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_bigscreen_state' ],
                'permission_callback' => '__return_true',
                'args'                => [ 'event_id' => [ 'type' => 'integer', 'required' => true ] ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'set_bigscreen_state' ],
                'permission_callback' => [ $this, 'can_moderate' ],
                'args'                => [
                    'event_id' => [ 'type' => 'integer', 'required' => true ],
                    'mode'     => [ 'type' => 'string', 'required' => true, 'enum' => [ 'all', 'highlighted' ] ],
                ],
            ],
        ] );

        // Scene CRUD (organizer/admin only).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/scenes', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_scenes' ],
                'permission_callback' => [ $this, 'can_moderate' ],
                'args'                => [
                    'event_id' => [ 'type' => 'integer', 'required' => true ],
                ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_scene' ],
                'permission_callback' => [ $this, 'can_moderate' ],
                'args'                => [
                    'event_id'          => [ 'type' => 'integer', 'required' => true ],
                    'name'              => [ 'type' => 'string',  'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                    'facilitator_email' => [ 'type' => 'string',  'required' => false, 'sanitize_callback' => 'sanitize_email' ],
                ],
            ],
        ] );

        register_rest_route( self::NS, '/scenes/(?P<scene_id>\d+)', [
            'methods'             => 'DELETE',
            'callback'            => [ $this, 'delete_scene' ],
            'permission_callback' => [ $this, 'can_delete_scene' ],
            'args'                => [
                'scene_id' => [ 'type' => 'integer', 'required' => true ],
            ],
        ] );

        // Create facilitator account (organizer/admin only).
        register_rest_route( self::NS, '/facilitators', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'create_facilitator' ],
            'permission_callback' => [ $this, 'can_manage_facilitators' ],
            'args'                => [
                'first_name' => [ 'type' => 'string', 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ],
                'last_name'  => [ 'type' => 'string', 'required' => false, 'sanitize_callback' => 'sanitize_text_field' ],
                'email'      => [ 'type' => 'string', 'required' => true,  'sanitize_callback' => 'sanitize_email' ],
                'password'   => [ 'type' => 'string', 'required' => true ],
            ],
        ] );

        // Get/set active speaker slot (facilitator write, public read).
        register_rest_route( self::NS, '/events/(?P<event_id>\d+)/active-slot', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_active_slot' ],
                'permission_callback' => '__return_true',
                'args'                => [ 'event_id' => [ 'type' => 'integer', 'required' => true ] ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'set_active_slot' ],
                'permission_callback' => [ $this, 'can_moderate' ],
                'args'                => [
                    'event_id'   => [ 'type' => 'integer', 'required' => true ],
                    'slot_index' => [ 'type' => 'integer', 'required' => true, 'minimum' => -1 ],
                ],
            ],
        ] );
    }

    // ---------------------------------------------------------------------------
    // Permission callbacks
    // ---------------------------------------------------------------------------

    public function can_submit( WP_REST_Request $request ): bool|WP_Error {
        $event_id = (int) $request->get_param( 'event_id' );
        if ( ! VupVup_QA_Roles::can_submit_question( $event_id ) ) {
            return new WP_Error( 'forbidden', __( 'Du skal logge ind for at stille spørgsmål.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }
        $status = get_post_meta( $event_id, '_vupvup_event_status', true );
        if ( $status !== 'active' ) {
            return new WP_Error( 'event_closed', __( 'Dette event accepterer ikke spørgsmål i øjeblikket.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }
        return true;
    }

    public function can_moderate( WP_REST_Request $request ): bool|WP_Error {
        $event_id = (int) $request->get_param( 'event_id' );
        if ( ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }
        return true;
    }

    public function can_moderate_scene( WP_REST_Request $request ): bool|WP_Error {
        $scene_id = (int) $request->get_param( 'scene_id' );
        if ( VupVup_QA_Roles::can_moderate_scene( $scene_id ) ) {
            return true;
        }
        return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
    }

    public function can_moderate_question( WP_REST_Request $request ): bool|WP_Error {
        $question_id = (int) $request->get_param( 'question_id' );
        $question    = $this->fetch_question( $question_id );
        if ( ! $question ) {
            return new WP_Error( 'not_found', __( 'Spørgsmål ikke fundet.', 'vupvup-qa' ), [ 'status' => 404 ] );
        }
        if ( ! VupVup_QA_Roles::can_moderate( (int) $question->event_id ) ) {
            return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }
        return true;
    }

    // ---------------------------------------------------------------------------
    // Route handlers
    // ---------------------------------------------------------------------------

    public function submit_question( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $event_id = (int) $request->get_param( 'event_id' );

        if ( ! VupVup_QA_Rate_Limiter::check( $event_id ) ) {
            return new WP_Error(
                'rate_limited',
                __( 'Du har stillet for mange spørgsmål på det seneste. Prøv igen om lidt.', 'vupvup-qa' ),
                [ 'status' => 429 ]
            );
        }

        $question_text = trim( $request->get_param( 'question' ) );
        if ( strlen( $question_text ) < 5 ) {
            return new WP_Error( 'too_short', __( 'Spørgsmålet er for kort.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }
        if ( strlen( $question_text ) > 1000 ) {
            return new WP_Error( 'too_long', __( 'Spørgsmålet er for langt (max 1000 tegn).', 'vupvup-qa' ), [ 'status' => 400 ] );
        }

        $author_id  = is_user_logged_in() ? get_current_user_id() : 0;
        $guest_name = null;

        if ( ! $author_id ) {
            $guest_name = trim( $request->get_param( 'guest_name' ) ?? '' );
            if ( empty( $guest_name ) ) {
                $guest_name = __( 'Anonym', 'vupvup-qa' );
            }
        }

        global $wpdb;
        $inserted = $wpdb->insert(
            $wpdb->prefix . 'vupvup_questions',
            [
                'event_id'   => $event_id,
                'author_id'  => $author_id,
                'guest_name' => $guest_name,
                'question'   => $question_text,
                'status'     => 'pending',
                'speaker_id' => $request->get_param( 'speaker_id' ) ?: null,
                'ip_hash'    => VupVup_QA_Rate_Limiter::get_ip_hash(),
                'created_at' => current_time( 'mysql' ),
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' ]
        );

        if ( ! $inserted ) {
            return new WP_Error( 'db_error', __( 'Spørgsmålet kunne ikke gemmes. Prøv igen.', 'vupvup-qa' ), [ 'status' => 500 ] );
        }

        VupVup_QA_Rate_Limiter::increment( $event_id );

        return new WP_REST_Response( [
            'success' => true,
            'id'      => $wpdb->insert_id,
            'message' => __( 'Dit spørgsmål er modtaget!', 'vupvup-qa' ),
        ], 201 );
    }

    public function get_questions( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $event_id = (int) $request->get_param( 'event_id' );
        $status   = $request->get_param( 'status' );
        $orderby  = $request->get_param( 'orderby' );
        $since_id = (int) $request->get_param( 'since_id' );

        $where = $wpdb->prepare( 'WHERE event_id = %d', $event_id );

        if ( $status !== 'all' ) {
            $where .= $wpdb->prepare( ' AND status = %s', $status );
        }
        if ( $since_id > 0 ) {
            $where .= $wpdb->prepare( ' AND id > %d', $since_id );
        }

        $order = match ( $orderby ) {
            'upvotes' => 'upvotes DESC, created_at DESC',
            'oldest'  => 'created_at ASC',
            default   => 'created_at DESC',
        };

        $questions = $wpdb->get_results(
            "SELECT q.*, u.display_name as author_name
             FROM {$wpdb->prefix}vupvup_questions q
             LEFT JOIN {$wpdb->users} u ON q.author_id = u.ID
             {$where}
             ORDER BY {$order}
             LIMIT 200"
        );

        return new WP_REST_Response( array_map( [ $this, 'format_question' ], $questions ) );
    }

    public function get_scene_questions( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $scene_id = (int) $request->get_param( 'scene_id' );
        $status   = $request->get_param( 'status' );
        $orderby  = $request->get_param( 'orderby' );
        $since_id = (int) $request->get_param( 'since_id' );

        $where = $wpdb->prepare( 'WHERE scene_id = %d', $scene_id );

        if ( $status !== 'all' ) {
            $where .= $wpdb->prepare( ' AND status = %s', $status );
        }
        if ( $since_id > 0 ) {
            $where .= $wpdb->prepare( ' AND id > %d', $since_id );
        }

        $order = match ( $orderby ) {
            'upvotes' => 'upvotes DESC, created_at DESC',
            'oldest'  => 'created_at ASC',
            default   => 'created_at DESC',
        };

        $questions = $wpdb->get_results(
            "SELECT q.*, u.display_name as author_name
             FROM {$wpdb->prefix}vupvup_questions q
             LEFT JOIN {$wpdb->users} u ON q.author_id = u.ID
             {$where}
             ORDER BY {$order}
             LIMIT 200"
        );

        return new WP_REST_Response( array_map( [ $this, 'format_question' ], $questions ) );
    }

    public function get_feed( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $event_id = (int) $request->get_param( 'event_id' );
        $since_id = (int) $request->get_param( 'since_id' );

        $where = $wpdb->prepare(
            "WHERE q.event_id = %d AND q.status IN ('approved','asked')",
            $event_id
        );
        if ( $since_id > 0 ) {
            $where .= $wpdb->prepare( ' AND q.id > %d', $since_id );
        }

        $questions = $wpdb->get_results(
            "SELECT q.id, q.event_id, q.question, q.status, q.upvotes, q.highlighted, q.author_id, q.guest_name, q.speaker_id, q.created_at, u.display_name as author_name
             FROM {$wpdb->prefix}vupvup_questions q
             LEFT JOIN {$wpdb->users} u ON q.author_id = u.ID
             {$where}
             ORDER BY q.created_at DESC
             LIMIT 100"
        );

        return new WP_REST_Response( array_map( [ $this, 'format_question' ], $questions ) );
    }

    public function update_status( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;

        $question_id = (int) $request->get_param( 'question_id' );
        $new_status  = $request->get_param( 'status' );

        $updated = $wpdb->update(
            $wpdb->prefix . 'vupvup_questions',
            [ 'status' => $new_status ],
            [ 'id'     => $question_id ],
            [ '%s' ],
            [ '%d' ]
        );

        if ( $updated === false ) {
            return new WP_Error( 'db_error', __( 'Status kunne ikke opdateres.', 'vupvup-qa' ), [ 'status' => 500 ] );
        }

        return new WP_REST_Response( [ 'success' => true, 'status' => $new_status ] );
    }

    public function upvote_question( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;

        $question_id = (int) $request->get_param( 'question_id' );

        // Prevent double upvoting via transient per IP + question.
        $key = 'vupvup_upvote_' . $question_id . '_' . substr( VupVup_QA_Rate_Limiter::get_ip_hash(), 0, 12 );
        if ( get_transient( $key ) ) {
            return new WP_Error( 'already_voted', __( 'Du har allerede stemt på dette spørgsmål.', 'vupvup-qa' ), [ 'status' => 400 ] );
        }

        $wpdb->query(
            $wpdb->prepare(
                "UPDATE {$wpdb->prefix}vupvup_questions SET upvotes = upvotes + 1 WHERE id = %d",
                $question_id
            )
        );

        set_transient( $key, 1, DAY_IN_SECONDS );

        $upvotes = (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT upvotes FROM {$wpdb->prefix}vupvup_questions WHERE id = %d", $question_id )
        );

        return new WP_REST_Response( [ 'success' => true, 'upvotes' => $upvotes ] );
    }

    public function get_event( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $event_id = (int) $request->get_param( 'event_id' );
        $post     = get_post( $event_id );

        if ( ! $post || $post->post_type !== 'event_qna' ) {
            return new WP_Error( 'not_found', __( 'Event ikke fundet.', 'vupvup-qa' ), [ 'status' => 404 ] );
        }

        return new WP_REST_Response( [
            'id'            => $event_id,
            'title'         => get_the_title( $event_id ),
            'status'        => get_post_meta( $event_id, '_vupvup_event_status', true ),
            'location'      => get_post_meta( $event_id, '_vupvup_event_location', true ),
            'start_time'    => get_post_meta( $event_id, '_vupvup_event_start_time', true ),
            'end_time'      => get_post_meta( $event_id, '_vupvup_event_end_time', true ),
            'guest_allowed' => (bool) get_post_meta( $event_id, '_vupvup_event_guest_allowed', true ),
            'speakers'      => get_post_meta( $event_id, '_vupvup_event_speakers', true ),
        ] );
    }

    public function toggle_highlight( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;

        $question_id = (int) $request->get_param( 'question_id' );
        $highlighted = $request->get_param( 'highlighted' ) ? 1 : 0;

        // If highlighting, clear all other highlights for same event first.
        if ( $highlighted ) {
            $q = $this->fetch_question( $question_id );
            if ( $q ) {
                $wpdb->update(
                    $wpdb->prefix . 'vupvup_questions',
                    [ 'highlighted' => 0 ],
                    [ 'event_id' => (int) $q->event_id ],
                    [ '%d' ], [ '%d' ]
                );
            }
        }

        $wpdb->update(
            $wpdb->prefix . 'vupvup_questions',
            [ 'highlighted' => $highlighted ],
            [ 'id' => $question_id ],
            [ '%d' ], [ '%d' ]
        );

        return new WP_REST_Response( [ 'success' => true, 'highlighted' => (bool) $highlighted ] );
    }

    public function get_bigscreen_state( WP_REST_Request $request ): WP_REST_Response {
        $event_id = (int) $request->get_param( 'event_id' );
        $mode     = get_transient( 'vupvup_bigscreen_mode_' . $event_id ) ?: 'all';
        $slot     = (int) get_transient( 'vupvup_active_slot_' . $event_id );
        return new WP_REST_Response( [ 'mode' => $mode, 'active_slot' => $slot ] );
    }

    public function set_bigscreen_state( WP_REST_Request $request ): WP_REST_Response {
        $event_id = (int) $request->get_param( 'event_id' );
        $mode     = $request->get_param( 'mode' );
        set_transient( 'vupvup_bigscreen_mode_' . $event_id, $mode, DAY_IN_SECONDS );
        return new WP_REST_Response( [ 'success' => true, 'mode' => $mode ] );
    }

    public function get_active_slot( WP_REST_Request $request ): WP_REST_Response {
        $event_id = (int) $request->get_param( 'event_id' );
        $slot     = get_transient( 'vupvup_active_slot_' . $event_id );
        return new WP_REST_Response( [ 'active_slot' => $slot !== false ? (int) $slot : -1 ] );
    }

    public function set_active_slot( WP_REST_Request $request ): WP_REST_Response {
        $event_id   = (int) $request->get_param( 'event_id' );
        $slot_index = (int) $request->get_param( 'slot_index' );
        if ( $slot_index < 0 ) {
            delete_transient( 'vupvup_active_slot_' . $event_id );
        } else {
            set_transient( 'vupvup_active_slot_' . $event_id, $slot_index, DAY_IN_SECONDS );
        }
        return new WP_REST_Response( [ 'success' => true, 'active_slot' => $slot_index ] );
    }

    public function get_scenes( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $event_id = (int) $request->get_param( 'event_id' );
        $scenes   = $wpdb->get_results( $wpdb->prepare(
            "SELECT s.id, s.name, s.token, s.qr_url, s.facilitator_id, u.display_name AS facilitator_name
             FROM {$wpdb->prefix}vupvup_scenes s
             LEFT JOIN {$wpdb->users} u ON s.facilitator_id = u.ID
             WHERE s.event_id = %d
             ORDER BY s.sort_order ASC, s.id ASC",
            $event_id
        ) );
        return new WP_REST_Response( array_map( function ( $s ) {
            return [
                'id'               => (int) $s->id,
                'name'             => $s->name,
                'qr_url'           => $s->qr_url ?: '',
                'facilitator_id'   => $s->facilitator_id ? (int) $s->facilitator_id : null,
                'facilitator_name' => $s->facilitator_name ?: '',
                'dashboard_url'    => home_url( 'dashboard/scene/' . $s->id . '/' ),
            ];
        }, $scenes ) );
    }

    public function create_scene( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        global $wpdb;
        $event_id          = (int) $request->get_param( 'event_id' );
        $name              = $request->get_param( 'name' );
        $facilitator_email = $request->get_param( 'facilitator_email' ) ?: '';

        $facilitator_id = null;
        if ( $facilitator_email ) {
            $user = get_user_by( 'email', $facilitator_email );
            if ( ! $user ) {
                return new WP_Error( 'user_not_found', __( 'Ingen bruger fundet med denne e-mailadresse.', 'vupvup-qa' ), [ 'status' => 404 ] );
            }
            $facilitator_id = (int) $user->ID;
        }

        $token  = wp_generate_password( 12, false );
        $qr_url = VupVup_QA_QR_Code::generate( home_url( 'qa/' . $token . '/' ), $event_id );

        $data    = [ 'event_id' => $event_id, 'name' => $name, 'token' => $token, 'sort_order' => 0, 'created_at' => current_time( 'mysql' ) ];
        $formats = [ '%d', '%s', '%s', '%d', '%s' ];
        if ( $qr_url ) { $data['qr_url'] = $qr_url; $formats[] = '%s'; }
        if ( $facilitator_id !== null ) { $data['facilitator_id'] = $facilitator_id; $formats[] = '%d'; }

        if ( ! $wpdb->insert( $wpdb->prefix . 'vupvup_scenes', $data, $formats ) ) {
            return new WP_Error( 'db_error', __( 'Scenen kunne ikke oprettes.', 'vupvup-qa' ), [ 'status' => 500 ] );
        }

        $scene_id         = $wpdb->insert_id;
        $facilitator_name = $facilitator_id ? ( get_user_by( 'id', $facilitator_id )->display_name ?? '' ) : '';

        return new WP_REST_Response( [
            'id'               => $scene_id,
            'name'             => $name,
            'qr_url'           => $qr_url ?: '',
            'facilitator_id'   => $facilitator_id,
            'facilitator_name' => $facilitator_name,
            'dashboard_url'    => home_url( 'dashboard/scene/' . $scene_id . '/' ),
        ], 201 );
    }

    public function delete_scene( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;
        $wpdb->delete( $wpdb->prefix . 'vupvup_scenes', [ 'id' => (int) $request->get_param( 'scene_id' ) ], [ '%d' ] );
        return new WP_REST_Response( [ 'success' => true ] );
    }

    public function create_facilitator( WP_REST_Request $request ): WP_REST_Response|WP_Error {
        $email      = $request->get_param( 'email' );
        $password   = $request->get_param( 'password' );
        $first_name = $request->get_param( 'first_name' );
        $last_name  = $request->get_param( 'last_name' ) ?? '';

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

        wp_update_user( [
            'ID'           => $user_id,
            'first_name'   => $first_name,
            'last_name'    => $last_name,
            'display_name' => trim( $first_name . ' ' . $last_name ),
            'role'         => 'event_facilitator',
        ] );

        return new WP_REST_Response( [
            'id'           => $user_id,
            'display_name' => trim( $first_name . ' ' . $last_name ),
            'email'        => $email,
        ], 201 );
    }

    public function can_delete_scene( WP_REST_Request $request ): bool|WP_Error {
        global $wpdb;
        $scene_id = (int) $request->get_param( 'scene_id' );
        $event_id = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT event_id FROM {$wpdb->prefix}vupvup_scenes WHERE id = %d",
            $scene_id
        ) );
        if ( ! $event_id ) {
            return new WP_Error( 'not_found', __( 'Scene ikke fundet.', 'vupvup-qa' ), [ 'status' => 404 ] );
        }
        if ( ! VupVup_QA_Roles::can_moderate( $event_id ) ) {
            return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }
        return true;
    }

    public function can_manage_facilitators(): bool|WP_Error {
        if ( ! is_user_logged_in() ) {
            return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
        }
        if ( current_user_can( 'vupvup_manage_scenes' ) || current_user_can( 'vupvup_manage_all_events' ) ) {
            return true;
        }
        return new WP_Error( 'forbidden', __( 'Adgang nægtet.', 'vupvup-qa' ), [ 'status' => 403 ] );
    }

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    private function fetch_question( int $id ): ?object {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}vupvup_questions WHERE id = %d",
            $id
        ) );
    }

    private function format_question( object $q ): array {
        $name = $q->author_id > 0
            ? ( $q->author_name ?: __( 'Bruger', 'vupvup-qa' ) )
            : ( $q->guest_name  ?: __( 'Anonym', 'vupvup-qa' ) );

        return [
            'id'          => (int) $q->id,
            'event_id'    => (int) $q->event_id,
            'author'      => $name,
            'question'    => $q->question,
            'status'      => $q->status,
            'speaker_id'  => $q->speaker_id ? (int) $q->speaker_id : null,
            'upvotes'     => (int) $q->upvotes,
            'highlighted' => (bool) $q->highlighted,
            'created_at'  => $q->created_at,
        ];
    }
}
