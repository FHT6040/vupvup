<?php defined( 'ABSPATH' ) || exit; ?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php esc_html_e( 'Nyt event', 'vupvup-qa' ); ?> — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-dash-body">

<?php include __DIR__ . '/partials/dashboard-nav.php'; ?>

<div class="vupvup-dash-wrap vupvup-dash-wrap-narrow">

    <div class="vupvup-dash-header">
        <div>
            <a href="<?php echo esc_url( home_url( 'dashboard/' ) ); ?>" class="vupvup-back">
                ← <?php esc_html_e( 'Tilbage', 'vupvup-qa' ); ?>
            </a>
            <h1><?php esc_html_e( 'Opret nyt event', 'vupvup-qa' ); ?></h1>
        </div>
    </div>

    <div id="vupvup-new-error"   class="vupvup-notice vupvup-notice-error   vupvup-hidden"></div>
    <div id="vupvup-new-success" class="vupvup-notice vupvup-notice-success vupvup-hidden"></div>

    <form id="vupvup-new-event-form" class="vupvup-dash-form" novalidate>
        <?php wp_nonce_field( 'vupvup_new_event', 'vupvup_event_nonce' ); ?>

        <div class="vupvup-form-section">
            <h2><?php esc_html_e( 'Grundoplysninger', 'vupvup-qa' ); ?></h2>

            <div class="vupvup-field">
                <label for="ev-title"><?php esc_html_e( 'Eventtitel', 'vupvup-qa' ); ?> *</label>
                <input type="text" id="ev-title" name="title" class="vupvup-input"
                       placeholder="<?php esc_attr_e( 'F.eks. Årskonference 2026', 'vupvup-qa' ); ?>" required>
            </div>

            <div class="vupvup-form-row vupvup-form-row-split">
                <div class="vupvup-field">
                    <label for="ev-start"><?php esc_html_e( 'Starttidspunkt', 'vupvup-qa' ); ?></label>
                    <input type="datetime-local" id="ev-start" name="start_time" class="vupvup-input">
                </div>
                <div class="vupvup-field">
                    <label for="ev-end"><?php esc_html_e( 'Sluttidspunkt', 'vupvup-qa' ); ?></label>
                    <input type="datetime-local" id="ev-end" name="end_time" class="vupvup-input">
                    <span class="vupvup-field-hint"><?php esc_html_e( 'Lukkes automatisk', 'vupvup-qa' ); ?></span>
                </div>
            </div>

            <div class="vupvup-field">
                <label for="ev-location"><?php esc_html_e( 'Sted', 'vupvup-qa' ); ?></label>
                <input type="text" id="ev-location" name="location" class="vupvup-input"
                       placeholder="<?php esc_attr_e( 'F.eks. Radisson Blu, København', 'vupvup-qa' ); ?>">
            </div>
        </div>

        <div class="vupvup-form-section">
            <h2><?php esc_html_e( 'Talere', 'vupvup-qa' ); ?></h2>
            <div class="vupvup-field">
                <label for="ev-speakers"><?php esc_html_e( 'Én taler pr. linje', 'vupvup-qa' ); ?></label>
                <textarea id="ev-speakers" name="speakers" class="vupvup-textarea" rows="4"
                          placeholder="<?php esc_attr_e( "Anna Nielsen\nBo Sørensen\nCaroline Holm", 'vupvup-qa' ); ?>"></textarea>
                <span class="vupvup-field-hint"><?php esc_html_e( 'Deltagere kan adressere spørgsmål til en bestemt taler.', 'vupvup-qa' ); ?></span>
            </div>
        </div>

        <div class="vupvup-form-section">
            <h2><?php esc_html_e( 'Adgang', 'vupvup-qa' ); ?></h2>
            <label class="vupvup-toggle-label">
                <input type="checkbox" id="ev-guest" name="guest_allowed" value="1" checked>
                <span class="vupvup-toggle-text">
                    <strong><?php esc_html_e( 'Tillad gæster uden login', 'vupvup-qa' ); ?></strong>
                    <span><?php esc_html_e( 'Deltagere behøver kun et navn for at stille spørgsmål', 'vupvup-qa' ); ?></span>
                </span>
            </label>

            <label class="vupvup-toggle-label" style="margin-top:12px;">
                <input type="checkbox" id="ev-activate" name="activate_now" value="1">
                <span class="vupvup-toggle-text">
                    <strong><?php esc_html_e( 'Aktivér eventet med det samme', 'vupvup-qa' ); ?></strong>
                    <span><?php esc_html_e( 'Deltagere kan stille spørgsmål så snart de scanner QR-koden', 'vupvup-qa' ); ?></span>
                </span>
            </label>
        </div>

        <div class="vupvup-form-actions">
            <button type="submit" id="vupvup-new-submit" class="vupvup-btn vupvup-btn-primary vupvup-btn-lg">
                <?php esc_html_e( 'Opret event', 'vupvup-qa' ); ?>
            </button>
            <a href="<?php echo esc_url( home_url( 'dashboard/' ) ); ?>"
               class="vupvup-btn vupvup-btn-ghost">
                <?php esc_html_e( 'Annullér', 'vupvup-qa' ); ?>
            </a>
        </div>

    </form>
</div>

<?php wp_footer(); ?>
</body>
</html>
