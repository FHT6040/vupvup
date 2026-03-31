<?php defined( 'ABSPATH' ) || exit;
/** @var WP_User $current_user, array $events */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title>Dashboard — VupVup</title>
    <?php wp_head(); ?>
</head>
<body class="vupvup-dash-body">

<?php include __DIR__ . '/partials/dashboard-nav.php'; ?>

<?php if ( ! VupVup_QA_Registration::is_email_verified( $current_user->ID ) ) : ?>
<div class="vupvup-verify-banner">
    <span>📧 <?php esc_html_e( 'Bekræft din e-mailadresse for at sikre din konto.', 'vupvup-qa' ); ?></span>
    <button class="vupvup-resend-btn"
            data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_resend_verify' ) ); ?>">
        <?php esc_html_e( 'Send bekræftelsesmail igen', 'vupvup-qa' ); ?>
    </button>
    <span class="vupvup-resend-msg" style="display:none;color:#065F46;font-weight:600;"></span>
</div>
<?php endif; ?>

<div class="vupvup-dash-wrap">

    <div class="vupvup-dash-header">
        <div>
            <h1><?php esc_html_e( 'Mine Events', 'vupvup-qa' ); ?></h1>
            <p class="vupvup-dash-sub">
                <?php printf(
                    esc_html__( 'Hej %s — klar til dit næste event?', 'vupvup-qa' ),
                    '<strong>' . esc_html( $current_user->first_name ?: $current_user->display_name ) . '</strong>'
                ); ?>
            </p>
        </div>
        <a href="<?php echo esc_url( home_url( 'vupvup/dashboard/event/ny/' ) ); ?>"
           class="vupvup-btn vupvup-btn-primary">
            + <?php esc_html_e( 'Nyt event', 'vupvup-qa' ); ?>
        </a>
    </div>

    <?php if ( empty( $events ) ) : ?>
        <div class="vupvup-dash-empty">
            <div class="vupvup-dash-empty-icon">📋</div>
            <h2><?php esc_html_e( 'Ingen events endnu', 'vupvup-qa' ); ?></h2>
            <p><?php esc_html_e( 'Opret dit første event og del QR-koden med dit publikum.', 'vupvup-qa' ); ?></p>
            <a href="<?php echo esc_url( home_url( 'vupvup/dashboard/event/ny/' ) ); ?>"
               class="vupvup-btn vupvup-btn-primary">
                <?php esc_html_e( 'Opret første event', 'vupvup-qa' ); ?>
            </a>
        </div>
    <?php else : ?>
        <div class="vupvup-events-grid">
            <?php foreach ( $events as $event ) :
                $status      = get_post_meta( $event->ID, '_vupvup_event_status', true ) ?: 'draft';
                $start       = get_post_meta( $event->ID, '_vupvup_event_start_time', true );
                $location    = get_post_meta( $event->ID, '_vupvup_event_location', true );
                $token       = get_post_meta( $event->ID, '_vupvup_event_token', true );
                $qr_url      = get_post_meta( $event->ID, '_vupvup_event_qr_url', true );
                $landing     = $token ? home_url( 'qa/' . $token . '/' ) : '';
                $q_count     = VupVup_QA_Frontend::get_question_count( $event->ID );
                $status_map  = [
                    'draft'  => [ __( 'Kladde', 'vupvup-qa' ),  'draft' ],
                    'active' => [ __( 'Live nu', 'vupvup-qa' ),  'active' ],
                    'closed' => [ __( 'Afsluttet', 'vupvup-qa' ), 'closed' ],
                ];
                [ $status_label, $status_cls ] = $status_map[ $status ] ?? [ $status, '' ];
            ?>
            <div class="vupvup-event-card vupvup-event-<?php echo esc_attr( $status_cls ); ?>">
                <div class="vupvup-event-card-top">
                    <span class="vupvup-badge vupvup-status-<?php echo esc_attr( $status_cls ); ?>">
                        <?php echo esc_html( $status_label ); ?>
                    </span>
                    <?php if ( $status === 'active' ) : ?>
                        <span class="vupvup-live-dot"></span>
                    <?php endif; ?>
                </div>

                <h2 class="vupvup-event-card-title">
                    <?php echo esc_html( $event->post_title ); ?>
                </h2>

                <div class="vupvup-event-card-meta">
                    <?php if ( $start ) : ?>
                        <span>📅 <?php echo esc_html( wp_date( 'd. M Y, H:i', strtotime( $start ) ) ); ?></span>
                    <?php endif; ?>
                    <?php if ( $location ) : ?>
                        <span>📍 <?php echo esc_html( $location ); ?></span>
                    <?php endif; ?>
                    <span>💬 <?php echo esc_html( $q_count ); ?> <?php esc_html_e( 'spørgsmål', 'vupvup-qa' ); ?></span>
                </div>

                <div class="vupvup-event-card-actions">
                    <a href="<?php echo esc_url( home_url( 'vupvup/dashboard/event/' . $event->ID . '/' ) ); ?>"
                       class="vupvup-btn vupvup-btn-primary vupvup-btn-sm">
                        <?php echo $status === 'active'
                            ? esc_html__( 'Åbn live dashboard', 'vupvup-qa' )
                            : esc_html__( 'Se event', 'vupvup-qa' ); ?>
                    </a>
                    <?php if ( $landing ) : ?>
                    <a href="<?php echo esc_url( $landing ); ?>" target="_blank"
                       class="vupvup-btn vupvup-btn-ghost vupvup-btn-sm">
                        <?php esc_html_e( 'Deltagerlink', 'vupvup-qa' ); ?> ↗
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

</div>

<?php wp_footer(); ?>
</body>
</html>
