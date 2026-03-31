<?php
defined( 'ABSPATH' ) || exit;
/** @var int $event_id, string $status, string $event_title, bool $guest_allowed, array $speakers, bool $is_logged_in, string $guest_name */
$needs_name  = ! $is_logged_in && $guest_allowed && ! $guest_name;
$needs_login = ! $is_logged_in && ! $guest_allowed;
$is_active   = $status === 'active';
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html( $event_title ); ?> — Q&amp;A</title>
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="<?php echo esc_attr( $event_title ); ?>">
    <meta name="theme-color" content="#4F46E5">
    <?php wp_head(); ?>
</head>
<body class="vupvup-landing-body">

<!-- ── Top navbar ─────────────────────────────────────────────── -->
<header class="vv-nav">
    <div class="vv-nav-logo">
        <span class="vv-logo-badge">V</span>
        <span class="vv-logo-name">VupVup</span>
    </div>
    <div class="vv-nav-meta">
        <span class="vv-nav-stat">
            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
            <span id="vv-attendee-count">—</span>
        </span>
        <span class="vv-nav-dot"></span>
        <span class="vv-nav-stat vv-status-pill vv-status-<?php echo esc_attr( $status ); ?>">
            <?php
            echo esc_html( [ 'active' => 'Live', 'draft' => 'Ikke startet', 'closed' => 'Afsluttet' ][ $status ] ?? $status );
            ?>
        </span>
    </div>
</header>

<!-- ── Two-column layout ──────────────────────────────────────── -->
<div class="vv-layout">

    <!-- Left sidebar -->
    <aside class="vv-sidebar">

        <div class="vv-card vv-event-card">
            <div class="vv-event-icon-area">
                <svg width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity=".5"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/><line x1="8" y1="23" x2="16" y2="23"/></svg>
            </div>
            <h1 class="vv-event-title"><?php echo esc_html( $event_title ); ?></h1>
            <?php if ( ! empty( $speakers ) ) : ?>
            <div class="vv-speakers-list">
                <?php foreach ( array_slice( $speakers, 0, 3 ) as $sp ) : ?>
                <div class="vv-speaker">
                    <span class="vv-avatar"><?php echo esc_html( mb_substr( $sp, 0, 1 ) ); ?></span>
                    <span class="vv-speaker-name"><?php echo esc_html( $sp ); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <div class="vv-card vv-stats-card">
            <div class="vv-stats-header">
                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Live Stats
            </div>
            <div class="vv-stats-grid">
                <div class="vv-stat">
                    <span class="vv-stat-num" id="vv-q-count">0</span>
                    <span class="vv-stat-label">Spørgsmål</span>
                </div>
                <div class="vv-stat">
                    <span class="vv-stat-num" id="vv-vote-count">0</span>
                    <span class="vv-stat-label">Stemmer</span>
                </div>
            </div>
        </div>

    </aside>

    <!-- Main questions feed -->
    <main class="vv-main">

        <?php if ( $status === 'closed' ) : ?>
        <div class="vv-feed-header">
            <span class="vv-feed-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/></svg>
                <?php esc_html_e( 'Spørgsmål', 'vupvup-qa' ); ?>
            </span>
        </div>
        <div class="vv-status-banner vv-banner-closed">
            <?php esc_html_e( 'Dette event er afsluttet. Tak for din deltagelse!', 'vupvup-qa' ); ?>
        </div>

        <?php elseif ( $status === 'draft' ) : ?>
        <div class="vv-status-banner vv-banner-draft">
            <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            <?php esc_html_e( 'Q&A er endnu ikke åbnet. Prøv igen om lidt.', 'vupvup-qa' ); ?>
        </div>

        <?php else : ?>
        <div class="vv-feed-header">
            <span class="vv-feed-title">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1a3 3 0 0 0-3 3v8a3 3 0 0 0 6 0V4a3 3 0 0 0-3-3z"/><path d="M19 10v2a7 7 0 0 1-14 0v-2"/><line x1="12" y1="19" x2="12" y2="23"/></svg>
                <?php esc_html_e( 'Live spørgsmål', 'vupvup-qa' ); ?>
            </span>
            <span class="vv-feed-sort"><?php esc_html_e( 'Sorteret efter popularitet', 'vupvup-qa' ); ?></span>
        </div>
        <div id="vv-questions-list" class="vv-questions-list">
            <div class="vv-empty-feed">
                <svg width="40" height="40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24" opacity=".3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <p><?php esc_html_e( 'Ingen spørgsmål endnu — vær den første!', 'vupvup-qa' ); ?></p>
            </div>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- ── Fixed bottom input bar ─────────────────────────────────── -->
<?php if ( $status === 'active' ) : ?>
<div class="vv-input-bar" id="vv-input-bar">

    <?php if ( $needs_login ) : ?>
    <div class="vv-input-login">
        <span><?php esc_html_e( 'Log ind for at stille spørgsmål', 'vupvup-qa' ); ?></span>
        <a href="<?php echo esc_url( home_url( 'vupvup/login/' ) ); ?>" class="vv-btn-send">
            <?php esc_html_e( 'Log ind', 'vupvup-qa' ); ?>
        </a>
    </div>

    <?php elseif ( $needs_name ) : ?>
    <div class="vv-input-guest" id="vv-guest-bar">
        <input type="text" id="vupvup-guest-name-input" class="vv-input"
               placeholder="<?php esc_attr_e( 'Hvad hedder du?', 'vupvup-qa' ); ?>"
               maxlength="60" autocomplete="nickname">
        <button id="vupvup-guest-submit" class="vv-btn-send"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_guest_login' ) ); ?>">
            <?php esc_html_e( 'Fortsæt', 'vupvup-qa' ); ?>
        </button>
    </div>

    <?php else : ?>
    <div class="vv-input-wrap">
        <?php if ( ! empty( $speakers ) ) : ?>
        <div class="vv-speaker-row">
            <select id="vupvup-speaker-select" class="vv-select-speaker">
                <option value=""><?php esc_html_e( 'Alle talere', 'vupvup-qa' ); ?></option>
                <?php foreach ( $speakers as $idx => $sp ) : ?>
                <option value="<?php echo esc_attr( $idx + 1 ); ?>"><?php echo esc_html( $sp ); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="vv-input-row">
            <div class="vv-textarea-wrap">
                <textarea id="vupvup-question-input" class="vv-input" rows="1"
                          maxlength="200"
                          placeholder="<?php esc_attr_e( 'Stil et spørgsmål…', 'vupvup-qa' ); ?>"></textarea>
                <span class="vv-char-count"><span id="vupvup-chars">0</span>/200</span>
            </div>
            <button id="vupvup-submit-btn" class="vv-btn-send">
                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <?php esc_html_e( 'Send', 'vupvup-qa' ); ?>
            </button>
        </div>
        <div id="vv-form-feedback" class="vv-feedback vupvup-hidden"></div>
        <p class="vv-moderation-note">
            <?php esc_html_e( 'Spørgsmål modereres inden de vises for alle', 'vupvup-qa' ); ?>
        </p>
    </div>
    <?php endif; ?>

</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
