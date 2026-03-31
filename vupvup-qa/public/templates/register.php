<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Opret konto', 'vupvup-qa' ); ?> — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-auth-body">
<div class="vupvup-auth-wrap">

    <div class="vupvup-auth-card">
        <div class="vupvup-auth-logo">
            <a href="<?php echo esc_url( home_url() ); ?>">VupVup</a>
        </div>
        <h1><?php esc_html_e( 'Opret gratis konto', 'vupvup-qa' ); ?></h1>
        <p class="vupvup-auth-sub"><?php esc_html_e( 'Start med at afholde live Q&A på dine events.', 'vupvup-qa' ); ?></p>

        <div id="vupvup-reg-error" class="vupvup-notice vupvup-notice-error vupvup-hidden"></div>

        <form id="vupvup-register-form" novalidate>

            <div class="vupvup-form-row vupvup-form-row-split">
                <div class="vupvup-field">
                    <label for="reg-first"><?php esc_html_e( 'Fornavn', 'vupvup-qa' ); ?> *</label>
                    <input type="text" id="reg-first" name="first_name" class="vupvup-input"
                           autocomplete="given-name" required>
                </div>
                <div class="vupvup-field">
                    <label for="reg-last"><?php esc_html_e( 'Efternavn', 'vupvup-qa' ); ?></label>
                    <input type="text" id="reg-last" name="last_name" class="vupvup-input"
                           autocomplete="family-name">
                </div>
            </div>

            <div class="vupvup-field">
                <label for="reg-company"><?php esc_html_e( 'Virksomhed / Organisation', 'vupvup-qa' ); ?></label>
                <input type="text" id="reg-company" name="company" class="vupvup-input"
                       autocomplete="organization">
            </div>

            <div class="vupvup-field">
                <label for="reg-email"><?php esc_html_e( 'E-mail', 'vupvup-qa' ); ?> *</label>
                <input type="email" id="reg-email" name="email" class="vupvup-input"
                       autocomplete="email" required>
            </div>

            <div class="vupvup-field">
                <label for="reg-password"><?php esc_html_e( 'Adgangskode', 'vupvup-qa' ); ?> *</label>
                <input type="password" id="reg-password" name="password" class="vupvup-input"
                       autocomplete="new-password" required minlength="8">
                <span class="vupvup-field-hint"><?php esc_html_e( 'Mindst 8 tegn', 'vupvup-qa' ); ?></span>
            </div>

            <button type="submit" id="vupvup-reg-submit" class="vupvup-btn vupvup-btn-primary vupvup-btn-lg">
                <?php esc_html_e( 'Opret konto gratis', 'vupvup-qa' ); ?>
            </button>

        </form>

        <p class="vupvup-auth-switch">
            <?php esc_html_e( 'Har du allerede en konto?', 'vupvup-qa' ); ?>
            <a href="<?php echo esc_url( home_url( 'vupvup/login/' ) ); ?>">
                <?php esc_html_e( 'Log ind her', 'vupvup-qa' ); ?>
            </a>
        </p>
    </div>

</div>
<?php wp_footer(); ?>
</body>
</html>
