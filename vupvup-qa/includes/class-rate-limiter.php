<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_Rate_Limiter {

    private const MAX_PER_EVENT  = 5;   // questions per user per event
    private const WINDOW_SECONDS = 300; // 5-minute window

    /**
     * Check if this request is allowed. Returns true if allowed, false if rate-limited.
     */
    public static function check( int $event_id ): bool {
        $key   = self::transient_key( $event_id );
        $count = (int) get_transient( $key );

        return $count < self::MAX_PER_EVENT;
    }

    /**
     * Increment the counter after a successful submission.
     */
    public static function increment( int $event_id ): void {
        $key   = self::transient_key( $event_id );
        $count = (int) get_transient( $key );

        set_transient( $key, $count + 1, self::WINDOW_SECONDS );
    }

    private static function transient_key( int $event_id ): string {
        // Key by IP hash + event ID so different events have separate limits.
        $ip   = self::get_ip();
        $hash = substr( hash( 'sha256', $ip . NONCE_SALT ), 0, 16 );
        return 'vupvup_rl_' . $event_id . '_' . $hash;
    }

    private static function get_ip(): string {
        // Prefer forwarded IP if behind a trusted proxy, fall back to REMOTE_ADDR.
        $candidates = [
            'HTTP_CF_CONNECTING_IP', // Cloudflare
            'HTTP_X_FORWARDED_FOR',
            'REMOTE_ADDR',
        ];
        foreach ( $candidates as $key ) {
            if ( ! empty( $_SERVER[ $key ] ) ) {
                $ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $key ] ) ) )[0] );
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }
        return '0.0.0.0';
    }

    /**
     * Return hashed IP for storage (never store raw IPs).
     */
    public static function get_ip_hash(): string {
        return hash( 'sha256', self::get_ip() . NONCE_SALT );
    }
}
