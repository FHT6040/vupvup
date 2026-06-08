<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Opret arrangørkonto', 'vupvup-qa' ); ?> — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-auth-body">
<div class="vupvup-auth-wrap">

    <div class="vupvup-auth-card">
        <div class="vupvup-auth-logo">
            <a href="<?php echo esc_url( home_url() ); ?>">VupVup</a>
        </div>
        <h1><?php esc_html_e( 'Opret arrangørkonto', 'vupvup-qa' ); ?></h1>
        <p class="vupvup-auth-sub"><?php esc_html_e( 'Administrer events og scener for dine arrangementer.', 'vupvup-qa' ); ?></p>

        <div id="vupvup-reg-organizer-error" class="vupvup-notice vupvup-notice-error vupvup-hidden"></div>

        <form id="vupvup-register-organizer-form" novalidate>

            <div class="vupvup-form-row vupvup-form-row-split">
                <div class="vupvup-field">
                    <label for="reg-org-first"><?php esc_html_e( 'Fornavn', 'vupvup-qa' ); ?> *</label>
                    <input type="text" id="reg-org-first" name="first_name" class="vupvup-input"
                           autocomplete="given-name" required>
                </div>
                <div class="vupvup-field">
                    <label for="reg-org-last"><?php esc_html_e( 'Efternavn', 'vupvup-qa' ); ?></label>
                    <input type="text" id="reg-org-last" name="last_name" class="vupvup-input"
                           autocomplete="family-name">
                </div>
            </div>

            <div class="vupvup-field">
                <label for="reg-org-company"><?php esc_html_e( 'Virksomhed / Organisation', 'vupvup-qa' ); ?></label>
                <input type="text" id="reg-org-company" name="company" class="vupvup-input"
                       autocomplete="organization">
            </div>

            <div class="vupvup-field">
                <label for="reg-org-email"><?php esc_html_e( 'E-mail', 'vupvup-qa' ); ?> *</label>
                <input type="email" id="reg-org-email" name="email" class="vupvup-input"
                       autocomplete="email" required>
            </div>

            <div class="vupvup-field">
                <label for="reg-org-password"><?php esc_html_e( 'Adgangskode', 'vupvup-qa' ); ?> *</label>
                <input type="password" id="reg-org-password" name="password" class="vupvup-input"
                       autocomplete="new-password" required minlength="8">
                <span class="vupvup-field-hint"><?php esc_html_e( 'Mindst 8 tegn', 'vupvup-qa' ); ?></span>
            </div>

            <button type="submit" id="vupvup-reg-organizer-submit" class="vupvup-btn vupvup-btn-primary vupvup-btn-lg">
                <?php esc_html_e( 'Opret arrangørkonto', 'vupvup-qa' ); ?>
            </button>

        </form>

        <p class="vupvup-auth-switch">
            <?php esc_html_e( 'Har du allerede en konto?', 'vupvup-qa' ); ?>
            <a href="<?php echo esc_url( home_url( 'login/' ) ); ?>">
                <?php esc_html_e( 'Log ind her', 'vupvup-qa' ); ?>
            </a>
        </p>
    </div>

</div>
<?php wp_footer(); ?>
</body>
</html>
