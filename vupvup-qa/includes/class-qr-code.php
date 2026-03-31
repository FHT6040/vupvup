<?php
defined( 'ABSPATH' ) || exit;

class VupVup_QA_QR_Code {

    /**
     * Generate a QR code PNG for the given URL and return the file URL.
     * Uses endroid/qr-code if composer vendor is available, otherwise falls back
     * to an external API (goqr.me) — no sensitive data is sent, only the landing URL.
     */
    public static function generate( string $url, int $event_id ): ?string {
        if ( class_exists( '\Endroid\QrCode\QrCode' ) ) {
            return self::generate_endroid( $url, $event_id );
        }
        return self::generate_external( $url, $event_id );
    }

    /**
     * Generate using endroid/qr-code (recommended, requires composer install).
     */
    private static function generate_endroid( string $url, int $event_id ): ?string {
        try {
            $qr = \Endroid\QrCode\QrCode::create( $url )
                ->setEncoding( new \Endroid\QrCode\Encoding\Encoding( 'UTF-8' ) )
                ->setErrorCorrectionLevel( \Endroid\QrCode\ErrorCorrectionLevel::High )
                ->setSize( 400 )
                ->setMargin( 20 );

            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write( $qr );

            return self::save_png( $result->getString(), $event_id );
        } catch ( \Throwable $e ) {
            error_log( '[VupVup QA] QR generation failed: ' . $e->getMessage() );
            return null;
        }
    }

    /**
     * Fallback: download QR image from qrserver.com (public, no auth required).
     * Only the landing page URL is sent — no sensitive data.
     */
    private static function generate_external( string $url, int $event_id ): ?string {
        $api_url  = add_query_arg( [
            'size' => '400x400',
            'data' => rawurlencode( $url ),
        ], 'https://api.qrserver.com/v1/create-qr-code/' );

        $response = wp_remote_get( $api_url, [ 'timeout' => 15 ] );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            return null;
        }

        return self::save_png( wp_remote_retrieve_body( $response ), $event_id );
    }

    /**
     * Save raw PNG bytes into the uploads directory and return the URL.
     */
    private static function save_png( string $png_data, int $event_id ): ?string {
        $upload_dir = wp_upload_dir();
        $sub_dir    = $upload_dir['basedir'] . '/vupvup-qa/qr/';

        if ( ! wp_mkdir_p( $sub_dir ) ) {
            return null;
        }

        $filename = 'event-' . $event_id . '-' . wp_generate_password( 8, false ) . '.png';
        $filepath = $sub_dir . $filename;

        if ( file_put_contents( $filepath, $png_data ) === false ) {
            return null;
        }

        return $upload_dir['baseurl'] . '/vupvup-qa/qr/' . $filename;
    }

    /**
     * Regenerate QR code for an existing event.
     */
    public static function regenerate( int $event_id ): ?string {
        $token = get_post_meta( $event_id, '_vupvup_event_token', true );
        if ( ! $token ) {
            return null;
        }
        $url = home_url( 'qa/' . $token . '/' );
        $qr_url = self::generate( $url, $event_id );
        if ( $qr_url ) {
            update_post_meta( $event_id, '_vupvup_event_qr_url', $qr_url );
        }
        return $qr_url;
    }
}
