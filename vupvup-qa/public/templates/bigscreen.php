<?php
defined( 'ABSPATH' ) || exit;
/** @var int $event_id, string $status, string $event_title */
?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html( $event_title ); ?> — Storskærm</title>
    <?php wp_head(); ?>
</head>
<body class="vv-big-body">

<div class="vv-big-header">
    <div class="vv-big-logo">
        <span class="vv-big-badge">V</span>
        <span class="vv-big-name">VupVup</span>
    </div>
    <div class="vv-big-event"><?php echo esc_html( $event_title ); ?></div>
    <div class="vv-big-meta">
        <span class="vv-big-status vv-big-status-<?php echo esc_attr( $status ); ?>">
            <?php echo esc_html( [ 'active' => 'Live', 'draft' => 'Ikke startet', 'closed' => 'Afsluttet' ][ $status ] ?? $status ); ?>
        </span>
        <span class="vv-big-count"><span id="vv-big-q-count">0</span> spørgsmål</span>
    </div>
</div>

<div id="vv-big-slot-banner" class="vv-big-slot-banner vv-hidden"></div>

<div id="vv-big-list" class="vv-big-list">
    <div class="vv-big-empty" id="vv-big-empty">
        <svg width="64" height="64" fill="none" stroke="currentColor" stroke-width="1.2" viewBox="0 0 24 24" opacity=".3">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
        <p>Afventer spørgsmål…</p>
    </div>
</div>

<?php wp_footer(); ?>
</body>
</html>
