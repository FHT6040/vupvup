<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Log ind', 'vupvup-qa' ); ?> — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-auth-body">
<div class="vupvup-auth-wrap">

    <div class="vupvup-auth-card">
        <div class="vupvup-auth-logo">
            <a href="<?php echo esc_url( home_url() ); ?>">VupVup</a>
        </div>
        <h1><?php esc_html_e( 'Log ind', 'vupvup-qa' ); ?></h1>

        <div id="vupvup-login-error" class="vupvup-notice vupvup-notice-error vupvup-hidden"></div>

        <form id="vupvup-login-form" novalidate>

            <div class="vupvup-field">
                <label for="login-email"><?php esc_html_e( 'E-mail', 'vupvup-qa' ); ?></label>
                <input type="email" id="login-email" name="email" class="vupvup-input"
                       autocomplete="email" required>
            </div>

            <div class="vupvup-field">
                <label for="login-password"><?php esc_html_e( 'Adgangskode', 'vupvup-qa' ); ?></label>
                <input type="password" id="login-password" name="password" class="vupvup-input"
                       autocomplete="current-password" required>
            </div>

            <div class="vupvup-field vupvup-field-inline">
                <label>
                    <input type="checkbox" id="login-remember" name="remember">
                    <?php esc_html_e( 'Husk mig', 'vupvup-qa' ); ?>
                </label>
                <a href="<?php echo esc_url( wp_lostpassword_url() ); ?>" class="vupvup-forgot">
                    <?php esc_html_e( 'Glemt adgangskode?', 'vupvup-qa' ); ?>
                </a>
            </div>

            <button type="submit" id="vupvup-login-submit" class="vupvup-btn vupvup-btn-primary vupvup-btn-lg">
                <?php esc_html_e( 'Log ind', 'vupvup-qa' ); ?>
            </button>

        </form>

        <p class="vupvup-auth-switch">
            <?php esc_html_e( 'Ny bruger?', 'vupvup-qa' ); ?>
            <a href="<?php echo esc_url( home_url( 'vupvup/register/' ) ); ?>">
                <?php esc_html_e( 'Opret gratis konto', 'vupvup-qa' ); ?>
            </a>
        </p>
    </div>

</div>
<?php wp_footer(); ?>
</body>
</html>
