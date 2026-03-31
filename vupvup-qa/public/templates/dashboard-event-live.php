<?php defined( 'ABSPATH' ) || exit;
/** @var WP_Post $event, int $event_id, string $status, string $qr_url, string $landing */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php echo esc_html( $event->post_title ); ?> — VupVup Dashboard</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-dash-body">

<?php include __DIR__ . '/partials/dashboard-nav.php'; ?>

<div class="vupvup-dash-wrap">

    <!-- Event header -->
    <div class="vupvup-dash-header vupvup-event-header">
        <div>
            <a href="<?php echo esc_url( home_url( 'vupvup/dashboard/' ) ); ?>" class="vupvup-back">
                ← <?php esc_html_e( 'Mine events', 'vupvup-qa' ); ?>
            </a>
            <h1><?php echo esc_html( $event->post_title ); ?></h1>
        </div>
        <div class="vupvup-event-controls">
            <span id="vupvup-status-badge"
                  class="vupvup-badge vupvup-status-<?php echo esc_attr( $status ); ?>">
                <?php
                $labels = [ 'draft' => 'Kladde', 'active' => 'Live', 'closed' => 'Afsluttet' ];
                echo esc_html( $labels[ $status ] ?? $status );
                ?>
            </span>
            <?php if ( $status !== 'active' ) : ?>
            <button id="vupvup-activate-btn"
                    class="vupvup-btn vupvup-btn-primary vupvup-btn-sm"
                    data-event-id="<?php echo esc_attr( $event_id ); ?>"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
                <?php esc_html_e( 'Aktivér event', 'vupvup-qa' ); ?>
            </button>
            <?php endif; ?>
            <?php if ( $status === 'active' ) : ?>
            <button id="vupvup-close-btn"
                    class="vupvup-btn vupvup-btn-danger vupvup-btn-sm"
                    data-event-id="<?php echo esc_attr( $event_id ); ?>"
                    data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
                <?php esc_html_e( 'Luk event', 'vupvup-qa' ); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>

    <div class="vupvup-live-layout">

        <!-- Left: QR + info -->
        <aside class="vupvup-live-sidebar">
            <?php if ( $qr_url ) : ?>
            <div class="vupvup-sidebar-card">
                <h3><?php esc_html_e( 'QR-kode', 'vupvup-qa' ); ?></h3>
                <img src="<?php echo esc_url( $qr_url ); ?>"
                     alt="QR" class="vupvup-qr-img">
                <a href="<?php echo esc_url( $qr_url ); ?>" download
                   class="vupvup-btn vupvup-btn-ghost vupvup-btn-sm vupvup-btn-full">
                    ⬇ <?php esc_html_e( 'Download QR', 'vupvup-qa' ); ?>
                </a>
            </div>
            <?php endif; ?>

            <?php if ( $landing ) : ?>
            <div class="vupvup-sidebar-card">
                <h3><?php esc_html_e( 'Deltagerlink', 'vupvup-qa' ); ?></h3>
                <div class="vupvup-link-box">
                    <span id="vupvup-landing-url"><?php echo esc_html( $landing ); ?></span>
                    <button class="vupvup-copy-link" data-url="<?php echo esc_attr( $landing ); ?>">
                        <?php esc_html_e( 'Kopiér', 'vupvup-qa' ); ?>
                    </button>
                </div>
            </div>
            <?php endif; ?>

            <div class="vupvup-sidebar-card">
                <h3><?php esc_html_e( 'Statistik', 'vupvup-qa' ); ?></h3>
                <div class="vupvup-stats">
                    <div class="vupvup-stat">
                        <span id="stat-total" class="vupvup-stat-num">0</span>
                        <span><?php esc_html_e( 'Total', 'vupvup-qa' ); ?></span>
                    </div>
                    <div class="vupvup-stat">
                        <span id="stat-pending" class="vupvup-stat-num">0</span>
                        <span><?php esc_html_e( 'Afventer', 'vupvup-qa' ); ?></span>
                    </div>
                    <div class="vupvup-stat">
                        <span id="stat-asked" class="vupvup-stat-num">0</span>
                        <span><?php esc_html_e( 'Stillet', 'vupvup-qa' ); ?></span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Right: Live question feed -->
        <main class="vupvup-live-main">
            <div class="vupvup-live-toolbar">
                <div class="vupvup-filter-group">
                    <button class="vupvup-filter-btn active" data-status="pending">
                        <?php esc_html_e( 'Afventer', 'vupvup-qa' ); ?>
                        <span class="vupvup-filter-count" id="count-pending">0</span>
                    </button>
                    <button class="vupvup-filter-btn" data-status="approved">
                        <?php esc_html_e( 'Godkendt', 'vupvup-qa' ); ?>
                    </button>
                    <button class="vupvup-filter-btn" data-status="asked">
                        <?php esc_html_e( 'Stillet', 'vupvup-qa' ); ?>
                    </button>
                    <button class="vupvup-filter-btn" data-status="all">
                        <?php esc_html_e( 'Alle', 'vupvup-qa' ); ?>
                    </button>
                </div>
                <div class="vupvup-sort-group">
                    <select id="vupvup-sort" class="vupvup-select-sm">
                        <option value="newest"><?php esc_html_e( 'Nyeste', 'vupvup-qa' ); ?></option>
                        <option value="upvotes"><?php esc_html_e( 'Flest stemmer', 'vupvup-qa' ); ?></option>
                        <option value="oldest"><?php esc_html_e( 'Ældste', 'vupvup-qa' ); ?></option>
                    </select>
                    <span class="vupvup-live-dot" title="Live"></span>
                </div>
            </div>

            <div id="vupvup-questions-list" class="vupvup-questions-list">
                <div class="vupvup-questions-empty">
                    <?php esc_html_e( 'Ingen spørgsmål endnu. Del QR-koden med dit publikum.', 'vupvup-qa' ); ?>
                </div>
            </div>
        </main>

    </div>
</div>

<!-- Question card template -->
<template id="vupvup-q-tpl">
    <div class="vupvup-q-card" data-id="" data-status="">
        <div class="vupvup-q-header">
            <span class="vupvup-q-author"></span>
            <span class="vupvup-q-time"></span>
            <span class="vupvup-q-upvotes">▲ <span class="up-num">0</span></span>
            <span class="vupvup-q-badge"></span>
        </div>
        <p class="vupvup-q-text"></p>
        <div class="vupvup-q-actions">
            <button class="vupvup-btn vupvup-btn-sm vupvup-btn-success  btn-approve"><?php esc_html_e( 'Godkend', 'vupvup-qa' ); ?></button>
            <button class="vupvup-btn vupvup-btn-sm vupvup-btn-primary  btn-asked"><?php esc_html_e( 'Stillet ✓', 'vupvup-qa' ); ?></button>
            <button class="vupvup-btn vupvup-btn-sm vupvup-btn-ghost    btn-copy"><?php esc_html_e( 'Kopiér', 'vupvup-qa' ); ?></button>
            <button class="vupvup-btn vupvup-btn-sm vupvup-btn-danger   btn-reject"><?php esc_html_e( 'Afvis', 'vupvup-qa' ); ?></button>
        </div>
    </div>
</template>

<?php wp_footer(); ?>
</body>
</html>
