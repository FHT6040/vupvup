<?php defined( 'ABSPATH' ) || exit;
/** @var bool $success, string $message */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php esc_html_e( 'E-mail bekræftelse', 'vupvup-qa' ); ?> — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-auth-body">
<div class="vupvup-auth-wrap">
    <div class="vupvup-auth-card" style="text-align:center;">
        <div class="vupvup-auth-logo">
            <a href="<?php echo esc_url( home_url() ); ?>">VupVup</a>
        </div>

        <?php if ( $success ) : ?>
            <div style="font-size:3rem;margin-bottom:16px;">✅</div>
            <h1><?php esc_html_e( 'E-mail bekræftet!', 'vupvup-qa' ); ?></h1>
            <p style="color:#6B7280;margin:12px 0 24px;">
                <?php echo esc_html( $message ); ?>
            </p>
            <a href="<?php echo esc_url( home_url( 'login/' ) ); ?>"
               class="vupvup-btn vupvup-btn-primary">
                <?php esc_html_e( 'Log ind nu', 'vupvup-qa' ); ?>
            </a>
        <?php else : ?>
            <div style="font-size:3rem;margin-bottom:16px;">❌</div>
            <h1><?php esc_html_e( 'Ugyldigt link', 'vupvup-qa' ); ?></h1>
            <p style="color:#6B7280;margin:12px 0 24px;">
                <?php echo esc_html( $message ); ?>
            </p>
            <a href="<?php echo esc_url( home_url( 'dashboard/' ) ); ?>"
               class="vupvup-btn vupvup-btn-ghost">
                <?php esc_html_e( 'Gå til dashboard', 'vupvup-qa' ); ?>
            </a>
        <?php endif; ?>
    </div>
</div>
<?php wp_footer(); ?>
</body>
</html>
