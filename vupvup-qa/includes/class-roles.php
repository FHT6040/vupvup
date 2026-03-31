<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Roles {

    public static function add_roles(): void {
        add_role(
            'event_facilitator',
            __( 'Event Facilitator', 'vupvup-qa' ),
            [
                'read'                          => true,
                'vupvup_manage_own_events'      => true,
                'vupvup_moderate_questions'     => true,
                'vupvup_view_dashboard'         => true,
            ]
        );

        add_role(
            'event_participant',
            __( 'Event Participant', 'vupvup-qa' ),
            [
                'read'                      => true,
                'vupvup_submit_question'    => true,
                'vupvup_upvote_question'    => true,
            ]
        );

        // Give admins and editors all capabilities.
        foreach ( [ 'administrator', 'editor' ] as $role_name ) {
            $role = get_role( $role_name );
            if ( $role ) {
                $role->add_cap( 'vupvup_manage_own_events' );
                $role->add_cap( 'vupvup_manage_all_events' );
                $role->add_cap( 'vupvup_moderate_questions' );
                $role->add_cap( 'vupvup_view_dashboard' );
                $role->add_cap( 'vupvup_submit_question' );
                $role->add_cap( 'vupvup_upvote_question' );
            }
        }
    }

    /**
     * Prevent custom roles from accessing wp-admin and hide the admin bar.
     * Hooked on 'admin_init' and 'show_admin_bar'.
     */
    public static function restrict_admin_access(): void {
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        if ( ! is_user_logged_in() ) {
            return;
        }
        $user = wp_get_current_user();
        $vupvup_roles = [ 'event_facilitator', 'event_participant' ];
        if ( empty( array_intersect( $vupvup_roles, (array) $user->roles ) ) ) {
            return;
        }
        if ( in_array( 'event_facilitator', (array) $user->roles, true ) ) {
            wp_safe_redirect( home_url( '/vupvup/dashboard/' ) );
        } else {
            wp_safe_redirect( home_url( '/' ) );
        }
        exit;
    }

    /**
     * Hide the WordPress admin bar for custom plugin roles.
     * Hooked on 'show_admin_bar'.
     */
    public static function hide_admin_bar( bool $show ): bool {
        if ( ! is_user_logged_in() ) {
            return $show;
        }
        $user = wp_get_current_user();
        $vupvup_roles = [ 'event_facilitator', 'event_participant' ];
        if ( ! empty( array_intersect( $vupvup_roles, (array) $user->roles ) ) ) {
            return false;
        }
        return $show;
    }

    /**
     * Check if current user can moderate questions for a given event.
     */
    public static function can_moderate( int $event_id ): bool {
        if ( ! is_user_logged_in() ) {
            return false;
        }
        if ( current_user_can( 'vupvup_manage_all_events' ) ) {
            return true;
        }
        if ( ! current_user_can( 'vupvup_moderate_questions' ) ) {
            return false;
        }
        $facilitator_id = (int) get_post_meta( $event_id, '_vupvup_facilitator_id', true );
        return $facilitator_id === get_current_user_id();
    }

    /**
     * Check if current user can submit a question.
     */
    public static function can_submit_question( int $event_id ): bool {
        $guest_allowed = get_post_meta( $event_id, '_vupvup_event_guest_allowed', true );
        if ( $guest_allowed ) {
            return true;
        }
        return is_user_logged_in() && current_user_can( 'vupvup_submit_question' );
    }
}
