<?php defined( 'ABSPATH' ) || exit;
/** @var object[] $facilitators */
$error_messages = [
    'no_title'       => __( 'Titel er påkrævet.', 'vupvup-qa' ),
    'no_facilitator' => __( 'Vælg en gyldig facilitator.', 'vupvup-qa' ),
    'create_failed'  => __( 'Eventet kunne ikke oprettes. Prøv igen.', 'vupvup-qa' ),
];
?>
<div class="wrap vupvup-dashboard-wrap">
    <h1><?php esc_html_e( 'Nyt event', 'vupvup-qa' ); ?></h1>

    <?php if ( ! empty( $_GET['vupvup_error'] ) ) :
        $key = sanitize_key( wp_unslash( $_GET['vupvup_error'] ) );
    ?>
    <div class="notice notice-error">
        <p><?php echo esc_html( $error_messages[ $key ] ?? __( 'Der opstod en fejl.', 'vupvup-qa' ) ); ?></p>
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <?php wp_nonce_field( 'vupvup_admin_new_event' ); ?>
        <input type="hidden" name="action" value="vupvup_new_event">

        <table class="form-table">
            <tr>
                <th><label for="vupvup_event_title"><?php esc_html_e( 'Titel', 'vupvup-qa' ); ?> <span aria-hidden="true">*</span></label></th>
                <td>
                    <input type="text" name="vupvup_event_title" id="vupvup_event_title"
                           class="regular-text" required
                           placeholder="<?php esc_attr_e( 'F.eks. Årskonference 2026', 'vupvup-qa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_facilitator_id"><?php esc_html_e( 'Facilitator', 'vupvup-qa' ); ?> <span aria-hidden="true">*</span></label></th>
                <td>
                    <select name="vupvup_facilitator_id" id="vupvup_facilitator_id" required>
                        <option value=""><?php esc_html_e( '— Vælg facilitator —', 'vupvup-qa' ); ?></option>
                        <?php foreach ( $facilitators as $f ) : ?>
                        <option value="<?php echo esc_attr( $f->ID ); ?>">
                            <?php echo esc_html( $f->display_name . ' (' . $f->user_email . ')' ); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ( empty( $facilitators ) ) : ?>
                    <p class="description">
                        <?php esc_html_e( 'Ingen facilitatorer fundet. Opret en facilitatorkonto via /register/ først.', 'vupvup-qa' ); ?>
                    </p>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_event_status"><?php esc_html_e( 'Status', 'vupvup-qa' ); ?></label></th>
                <td>
                    <select name="vupvup_event_status" id="vupvup_event_status">
                        <option value="draft"><?php esc_html_e( 'Kladde', 'vupvup-qa' ); ?></option>
                        <option value="active"><?php esc_html_e( 'Aktiv', 'vupvup-qa' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_event_location"><?php esc_html_e( 'Sted', 'vupvup-qa' ); ?></label></th>
                <td>
                    <input type="text" name="vupvup_event_location" id="vupvup_event_location"
                           class="regular-text"
                           placeholder="<?php esc_attr_e( 'F.eks. Radisson Blu, København', 'vupvup-qa' ); ?>">
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_event_start_time"><?php esc_html_e( 'Starttidspunkt', 'vupvup-qa' ); ?></label></th>
                <td>
                    <input type="datetime-local" name="vupvup_event_start_time" id="vupvup_event_start_time">
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_event_end_time"><?php esc_html_e( 'Sluttidspunkt', 'vupvup-qa' ); ?></label></th>
                <td>
                    <input type="datetime-local" name="vupvup_event_end_time" id="vupvup_event_end_time">
                    <p class="description"><?php esc_html_e( 'Eventet lukkes automatisk på dette tidspunkt.', 'vupvup-qa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><label for="vupvup_event_speakers"><?php esc_html_e( 'Talere', 'vupvup-qa' ); ?></label></th>
                <td>
                    <textarea name="vupvup_event_speakers" id="vupvup_event_speakers"
                              rows="4" class="large-text"
                              placeholder="<?php esc_attr_e( "Én taler pr. linje, f.eks.:\nAnna Nielsen\nBo Sørensen", 'vupvup-qa' ); ?>"
                    ></textarea>
                    <p class="description"><?php esc_html_e( 'Deltagere kan adressere spørgsmål til en bestemt taler.', 'vupvup-qa' ); ?></p>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Gæster tilladt', 'vupvup-qa' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="vupvup_guest_allowed" value="1">
                        <?php esc_html_e( 'Tillad deltagere at stille spørgsmål uden at logge ind (kun navn kræves)', 'vupvup-qa' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <?php submit_button( __( 'Opret event', 'vupvup-qa' ) ); ?>
    </form>
</div>
