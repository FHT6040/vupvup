<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap vupvup-wrap vupvup-dashboard-wrap">
    <h1><?php esc_html_e( 'Facilitator Dashboard', 'vupvup-qa' ); ?></h1>

    <?php if ( empty( $events ) ) : ?>
        <p><?php esc_html_e( 'Du har ingen events endnu.', 'vupvup-qa' ); ?></p>
        <?php return; ?>
    <?php endif; ?>

    <!-- Event selector -->
    <div class="vupvup-event-selector">
        <label for="vupvup-event-select"><strong><?php esc_html_e( 'Vælg event:', 'vupvup-qa' ); ?></strong></label>
        <select id="vupvup-event-select">
            <option value=""><?php esc_html_e( '— Vælg et event —', 'vupvup-qa' ); ?></option>
            <?php foreach ( $events as $ev ) :
                $ev_status = get_post_meta( $ev->ID, '_vupvup_event_status', true );
            ?>
            <option value="<?php echo esc_attr( $ev->ID ); ?>"
                    data-status="<?php echo esc_attr( $ev_status ); ?>"
                    <?php selected( $ev->ID, $event_id ); ?>>
                <?php echo esc_html( $ev->post_title ); ?>
                (<?php echo esc_html( $ev_status ?: 'draft' ); ?>)
            </option>
            <?php endforeach; ?>
        </select>

        <button id="vupvup-activate-btn" class="button button-primary" style="display:none;"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
            <?php esc_html_e( 'Aktivér event', 'vupvup-qa' ); ?>
        </button>
        <button id="vupvup-close-btn" class="button button-secondary" style="display:none;"
                data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
            <?php esc_html_e( 'Luk event', 'vupvup-qa' ); ?>
        </button>
        <span id="vupvup-status-badge" class="vupvup-badge"></span>
    </div>

    <!-- Filter & sort bar -->
    <div id="vupvup-filter-bar" class="vupvup-filter-bar" style="display:none;">
        <label><?php esc_html_e( 'Vis:', 'vupvup-qa' ); ?>
            <select id="vupvup-filter-status">
                <option value="all"><?php esc_html_e( 'Alle', 'vupvup-qa' ); ?></option>
                <option value="pending" selected><?php esc_html_e( 'Afventer', 'vupvup-qa' ); ?></option>
                <option value="approved"><?php esc_html_e( 'Godkendte', 'vupvup-qa' ); ?></option>
                <option value="asked"><?php esc_html_e( 'Stillede', 'vupvup-qa' ); ?></option>
                <option value="rejected"><?php esc_html_e( 'Afviste', 'vupvup-qa' ); ?></option>
            </select>
        </label>
        <label><?php esc_html_e( 'Sorter:', 'vupvup-qa' ); ?>
            <select id="vupvup-filter-order">
                <option value="newest"><?php esc_html_e( 'Nyeste', 'vupvup-qa' ); ?></option>
                <option value="oldest"><?php esc_html_e( 'Ældste', 'vupvup-qa' ); ?></option>
                <option value="upvotes"><?php esc_html_e( 'Flest stemmer', 'vupvup-qa' ); ?></option>
            </select>
        </label>
        <span id="vupvup-live-indicator" class="vupvup-live-dot" title="<?php esc_attr_e( 'Live opdatering aktiv', 'vupvup-qa' ); ?>"></span>
        <span id="vupvup-question-count" class="vupvup-count"></span>
    </div>

    <!-- Questions list -->
    <div id="vupvup-questions-container" class="vupvup-questions-container" style="display:none;">
        <div id="vupvup-questions-list"></div>
    </div>

    <div id="vupvup-no-event-msg" class="vupvup-placeholder">
        <?php esc_html_e( 'Vælg et event ovenfor for at se spørgsmål.', 'vupvup-qa' ); ?>
    </div>
</div>

<!-- Question card template (cloned by JS) -->
<template id="vupvup-question-tpl">
    <div class="vupvup-question-card" data-id="">
        <div class="vupvup-question-meta">
            <span class="vupvup-q-author"></span>
            <span class="vupvup-q-time"></span>
            <span class="vupvup-q-upvotes">▲ <span class="upvote-count">0</span></span>
            <span class="vupvup-q-status-badge"></span>
        </div>
        <div class="vupvup-q-text"></div>
        <div class="vupvup-q-actions">
            <button class="button button-small vupvup-btn-approve"><?php esc_html_e( 'Godkend', 'vupvup-qa' ); ?></button>
            <button class="button button-small vupvup-btn-reject"><?php esc_html_e( 'Afvis', 'vupvup-qa' ); ?></button>
            <button class="button button-small vupvup-btn-asked"><?php esc_html_e( 'Stillet', 'vupvup-qa' ); ?></button>
            <button class="button button-small vupvup-btn-copy"><?php esc_html_e( 'Kopiér', 'vupvup-qa' ); ?></button>
        </div>
    </div>
</template>
