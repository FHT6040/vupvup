<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap vupvup-wrap">
    <h1 class="wp-heading-inline"><?php esc_html_e( 'Mine Q&A Events', 'vupvup-qa' ); ?></h1>
    <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=event_qna' ) ); ?>" class="page-title-action">
        <?php esc_html_e( 'Opret nyt event', 'vupvup-qa' ); ?>
    </a>
    <hr class="wp-header-end">

    <?php if ( empty( $events ) ) : ?>
        <div class="vupvup-empty">
            <p><?php esc_html_e( 'Du har endnu ingen events. Opret dit første event ovenfor.', 'vupvup-qa' ); ?></p>
        </div>
    <?php else : ?>
        <table class="wp-list-table widefat fixed striped vupvup-events-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Event', 'vupvup-qa' ); ?></th>
                    <th><?php esc_html_e( 'Status', 'vupvup-qa' ); ?></th>
                    <th><?php esc_html_e( 'Start', 'vupvup-qa' ); ?></th>
                    <th><?php esc_html_e( 'Slut', 'vupvup-qa' ); ?></th>
                    <th><?php esc_html_e( 'Sted', 'vupvup-qa' ); ?></th>
                    <th><?php esc_html_e( 'Handlinger', 'vupvup-qa' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ( $events as $event ) :
                    $status     = get_post_meta( $event->ID, '_vupvup_event_status', true ) ?: 'draft';
                    $start      = get_post_meta( $event->ID, '_vupvup_event_start_time', true );
                    $end        = get_post_meta( $event->ID, '_vupvup_event_end_time', true );
                    $location   = get_post_meta( $event->ID, '_vupvup_event_location', true );
                    $token      = get_post_meta( $event->ID, '_vupvup_event_token', true );
                    $landing    = $token ? home_url( 'qa/' . $token . '/' ) : '';
                    $status_labels = [
                        'draft'  => [ __( 'Kladde', 'vupvup-qa' ),  'vupvup-status-draft' ],
                        'active' => [ __( 'Aktiv', 'vupvup-qa' ),   'vupvup-status-active' ],
                        'closed' => [ __( 'Lukket', 'vupvup-qa' ),  'vupvup-status-closed' ],
                    ];
                    [ $label, $cls ] = $status_labels[ $status ] ?? [ $status, '' ];
                ?>
                <tr>
                    <td><strong><?php echo esc_html( $event->post_title ); ?></strong></td>
                    <td>
                        <span class="vupvup-badge <?php echo esc_attr( $cls ); ?>"><?php echo esc_html( $label ); ?></span>
                    </td>
                    <td><?php echo $start ? esc_html( wp_date( 'd.m.Y H:i', strtotime( $start ) ) ) : '—'; ?></td>
                    <td><?php echo $end   ? esc_html( wp_date( 'd.m.Y H:i', strtotime( $end ) ) )   : '—'; ?></td>
                    <td><?php echo $location ? esc_html( $location ) : '—'; ?></td>
                    <td class="vupvup-actions">
                        <a href="<?php echo esc_url( get_edit_post_link( $event->ID ) ); ?>">
                            <?php esc_html_e( 'Rediger', 'vupvup-qa' ); ?>
                        </a>
                        |
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=vupvup-dashboard&event_id=' . $event->ID ) ); ?>">
                            <?php esc_html_e( 'Dashboard', 'vupvup-qa' ); ?>
                        </a>
                        <?php if ( $landing ) : ?>
                        |
                        <a href="<?php echo esc_url( $landing ); ?>" target="_blank">
                            <?php esc_html_e( 'Deltagerlink', 'vupvup-qa' ); ?>
                        </a>
                        <?php endif; ?>
                        |
                        <a href="#"
                           class="vupvup-toggle-status"
                           data-event-id="<?php echo esc_attr( $event->ID ); ?>"
                           data-current-status="<?php echo esc_attr( $status ); ?>"
                           data-nonce="<?php echo esc_attr( wp_create_nonce( 'vupvup_admin' ) ); ?>">
                            <?php echo $status === 'active'
                                ? esc_html__( 'Luk', 'vupvup-qa' )
                                : esc_html__( 'Aktivér', 'vupvup-qa' ); ?>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>
