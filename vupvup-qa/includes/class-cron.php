<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Cron {

    public function register(): void {
        add_action( 'vupvup_qa_close_event', [ $this, 'close_event' ] );
        add_action( 'vupvup_qa_auto_close_events', [ $this, 'check_and_close_events' ] );

        if ( ! wp_next_scheduled( 'vupvup_qa_auto_close_events' ) ) {
            wp_schedule_event( time(), 'hourly', 'vupvup_qa_auto_close_events' );
        }
    }

    /**
     * Schedule closing a single event at its end time.
     */
    public static function schedule_close( int $event_id, string $end_time_local ): void {
        $timestamp = strtotime( $end_time_local );
        if ( ! $timestamp || $timestamp <= time() ) {
            return;
        }

        $hook = 'vupvup_qa_close_event';
        wp_clear_scheduled_hook( $hook, [ $event_id ] );
        wp_schedule_single_event( $timestamp, $hook, [ $event_id ] );
    }

    /**
     * Close a single event.
     */
    public function close_event( int $event_id ): void {
        $current = get_post_meta( $event_id, '_vupvup_event_status', true );
        if ( $current === 'active' ) {
            update_post_meta( $event_id, '_vupvup_event_status', 'closed' );
        }
    }

    /**
     * Hourly sweep: close any events whose end time has passed.
     */
    public function check_and_close_events(): void {
        global $wpdb;

        $now = current_time( 'mysql' );

        $event_ids = $wpdb->get_col(
            $wpdb->prepare(
                "SELECT post_id
                 FROM {$wpdb->postmeta}
                 WHERE meta_key = '_vupvup_event_status' AND meta_value = 'active'
                   AND post_id IN (
                       SELECT post_id FROM {$wpdb->postmeta}
                       WHERE meta_key = '_vupvup_event_end_time' AND meta_value <= %s
                   )",
                $now
            )
        );

        foreach ( $event_ids as $event_id ) {
            update_post_meta( (int) $event_id, '_vupvup_event_status', 'closed' );
        }
    }
}
