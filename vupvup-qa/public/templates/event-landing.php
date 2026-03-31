<?php
defined( 'ABSPATH' ) || exit;
/** @var int $event_id, string $status, string $event_title, bool $guest_allowed, array $speakers, bool $is_logged_in, string $guest_name */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html( $event_title ); ?> — Q&amp;A</title>

    <!-- PWA / mobile app feel -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $event_title ); ?>">
    <meta name="theme-color" content="#5B21B6">
    <?php wp_head(); ?>
</head>
<body class="vupvup-landing-body">

<div class="vupvup-landing">

    <!-- Header -->
    <header class="vupvup-header">
        <div class="vupvup-logo">VupVup</div>
        <h1 class="vupvup-event-title"><?php echo esc_html( $event_title ); ?></h1>
        <div class="vupvup-event-status-pill vupvup-status-<?php echo esc_attr( $status ); ?>">
            <?php
            $labels = [
                'active' => __( 'Live', 'vupvup-qa' ),
                'draft'  => __( 'Ikke startet', 'vupvup-qa' ),
                'closed' => __( 'Afsluttet', 'vupvup-qa' ),
            ];
            echo esc_html( $labels[ $status ] ?? $status );
            ?>
        </div>
    </header>

    <main class="vupvup-main">

        <?php if ( $status === 'closed' ) : ?>
            <div class="vupvup-notice vupvup-notice-info">
                <p><?php esc_html_e( 'Dette event er afsluttet. Du kan ikke stille spørgsmål længere.', 'vupvup-qa' ); ?></p>
            </div>

        <?php elseif ( $status === 'draft' ) : ?>
            <div class="vupvup-notice vupvup-notice-info">
                <p><?php esc_html_e( 'Q&A er endnu ikke åbnet. Prøv igen om lidt.', 'vupvup-qa' ); ?></p>
            </div>

        <?php elseif ( ! $is_logged_in && ! $guest_allowed ) : ?>
            <!-- Login required -->
            <div class="vupvup-notice vupvup-notice-warning">
                <p><?php esc_html_e( 'Du skal logge ind for at stille spørgsmål til dette event.', 'vupvup-qa' ); ?></p>
                <a href="<?php echo esc_url( wp_login_url( get_query_var( 'vupvup_event_token' ) ? home_url( 'qa/' . get_query_var( 'vupvup_event_token' ) . '/' ) : home_url() ) ); ?>" class="vupvup-btn vupvup-btn-primary">
                    <?php esc_html_e( 'Log ind', 'vupvup-qa' ); ?>
                </a>
                <?php if ( get_option( 'users_can_register' ) ) : ?>
                <a href="<?php echo esc_url( wp_registration_url() ); ?>" class="vupvup-btn vupvup-btn-secondary">
                    <?php esc_html_e( 'Opret konto', 'vupvup-qa' ); ?>
                </a>
                <?php endif; ?>
            </div>

        <?php else : ?>

            <?php if ( ! $is_logged_in && $guest_allowed && ! $guest_name ) : ?>
            <!-- Guest name form -->
            <div id="vupvup-guest-form" class="vupvup-card">
                <h2><?php esc_html_e( 'Hvad hedder du?', 'vupvup-qa' ); ?></h2>
                <p class="vupvup-hint"><?php esc_html_e( 'Dit navn vises ved dit spørgsmål. Du kan bruge et kaldenavn.', 'vupvup-qa' ); ?></p>
                <div class="vupvup-field">
                    <input type="text" id="vupvup-guest-name-input" class="vupvup-input"
                           placeholder="<?php esc_attr_e( 'Dit navn', 'vupvup-qa' ); ?>"
                           maxlength="60" autocomplete="nickname">
                </div>
                <button id="vupvup-guest-submit" class="vupvup-btn vupvup-btn-primary" data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_guest_login' ) ); ?>">
                    <?php esc_html_e( 'Fortsæt', 'vupvup-qa' ); ?>
                </button>
            </div>
            <?php endif; ?>

            <!-- Question form -->
            <div id="vupvup-question-section" class="vupvup-card<?php echo ( ! $is_logged_in && $guest_allowed && ! $guest_name ) ? ' vupvup-hidden' : ''; ?>">
                <h2><?php esc_html_e( 'Stil et spørgsmål', 'vupvup-qa' ); ?></h2>

                <?php if ( $is_logged_in ) : ?>
                    <p class="vupvup-hint">
                        <?php printf(
                            esc_html__( 'Logget ind som %s.', 'vupvup-qa' ),
                            '<strong>' . esc_html( wp_get_current_user()->display_name ) . '</strong>'
                        ); ?>
                    </p>
                <?php else : ?>
                    <p class="vupvup-hint" id="vupvup-greeting">
                        <?php esc_html_e( 'Klar til at stille et spørgsmål!', 'vupvup-qa' ); ?>
                    </p>
                <?php endif; ?>

                <div id="vupvup-form-success" class="vupvup-notice vupvup-notice-success vupvup-hidden"></div>
                <div id="vupvup-form-error"   class="vupvup-notice vupvup-notice-error   vupvup-hidden"></div>

                <?php if ( ! empty( $speakers ) ) : ?>
                <div class="vupvup-field">
                    <label for="vupvup-speaker-select"><?php esc_html_e( 'Til taler (valgfrit):', 'vupvup-qa' ); ?></label>
                    <select id="vupvup-speaker-select" class="vupvup-select">
                        <option value=""><?php esc_html_e( 'Alle talere / generelt', 'vupvup-qa' ); ?></option>
                        <?php foreach ( $speakers as $idx => $sp ) : ?>
                            <option value="<?php echo esc_attr( $idx + 1 ); ?>"><?php echo esc_html( $sp ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>

                <div class="vupvup-field">
                    <textarea id="vupvup-question-input" class="vupvup-textarea"
                              rows="4" maxlength="1000"
                              placeholder="<?php esc_attr_e( 'Skriv dit spørgsmål her…', 'vupvup-qa' ); ?>"></textarea>
                    <div class="vupvup-char-count"><span id="vupvup-chars">0</span>/1000</div>
                </div>

                <button id="vupvup-submit-btn" class="vupvup-btn vupvup-btn-primary vupvup-btn-lg">
                    <?php esc_html_e( 'Send spørgsmål', 'vupvup-qa' ); ?>
                </button>
            </div>

        <?php endif; ?>

    </main>

    <footer class="vupvup-footer">
        <p><?php esc_html_e( 'Powered by VupVup Live Q&A', 'vupvup-qa' ); ?></p>
    </footer>

</div>

<?php wp_footer(); ?>
</body>
</html>
