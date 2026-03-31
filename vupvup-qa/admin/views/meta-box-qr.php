<?php defined( 'ABSPATH' ) || exit;
/** @var int $post->ID, string $token, string $qr_url, string $landing */
?>
<?php if ( ! $token ) : ?>
    <p><?php esc_html_e( 'Gem eventet for at generere QR-kode og link.', 'vupvup-qa' ); ?></p>
<?php else : ?>
    <p>
        <strong><?php esc_html_e( 'Deltagerlink:', 'vupvup-qa' ); ?></strong><br>
        <a href="<?php echo esc_url( $landing ); ?>" target="_blank"><?php echo esc_html( $landing ); ?></a>
    </p>

    <?php if ( $qr_url ) : ?>
        <p>
            <img src="<?php echo esc_url( $qr_url ); ?>"
                 alt="<?php esc_attr_e( 'QR-kode', 'vupvup-qa' ); ?>"
                 style="max-width:200px;height:auto;display:block;margin-bottom:8px;">
            <a href="<?php echo esc_url( $qr_url ); ?>" download class="button button-small">
                <?php esc_html_e( 'Download QR (PNG)', 'vupvup-qa' ); ?>
            </a>
        </p>
    <?php else : ?>
        <p class="description"><?php esc_html_e( 'QR-kode ikke genereret endnu.', 'vupvup-qa' ); ?></p>
    <?php endif; ?>

    <p>
        <button type="button" class="button button-small"
                id="vupvup-regen-qr"
                data-event-id="<?php echo esc_attr( $post->ID ); ?>"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
            <?php esc_html_e( 'Regenerér QR-kode', 'vupvup-qa' ); ?>
        </button>
    </p>
<?php endif; ?>
