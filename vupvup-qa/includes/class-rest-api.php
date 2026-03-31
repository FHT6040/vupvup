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

    public function get_feed( WP_REST_Request $request ): WP_REST_Response {
        global $wpdb;

        $event_id = (int) $request->get_param( 'event_id' );
        $since_id = (int) $request->get_param( 'since_id' );

        $where = $wpdb->prepare(
            "WHERE event_id = %d AND status IN ('approved','asked')",
            $event_id
        );
        if ( $since_id > 0 ) {
            $where .= $wpdb->prepare( ' AND id > %d', $since_id );
        }

        $questions = $wpdb->get_results(
            "SELECT id, question, status, upvotes, created_at
             FROM {$wpdb->prefix}vupvup_questions
             {$where}
             ORDER BY created_at DESC
             LIMIT 100"
        );

        return new WP_REST_Response( $questions );
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
            'id'         => (int) $q->id,
            'event_id'   => (int) $q->event_id,
            'author'     => $name,
            'question'   => $q->question,
            'status'     => $q->status,
            'speaker_id' => $q->speaker_id ? (int) $q->speaker_id : null,
            'upvotes'    => (int) $q->upvotes,
            'created_at' => $q->created_at,
        ];
    }
}
