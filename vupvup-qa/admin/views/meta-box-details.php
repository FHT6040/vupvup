<?php defined( 'ABSPATH' ) || exit;
/** @var WP_Post $post */
/** @var string $start_time, $end_time, $location, $status, $guest_allowed, $speakers */
?>
<table class="form-table">
    <tr>
        <th><label for="vupvup_event_status"><?php esc_html_e( 'Status', 'vupvup-qa' ); ?></label></th>
        <td>
            <select name="vupvup_event_status" id="vupvup_event_status">
                <option value="draft"  <?php selected( $status, 'draft'  ); ?>><?php esc_html_e( 'Kladde',  'vupvup-qa' ); ?></option>
                <option value="active" <?php selected( $status, 'active' ); ?>><?php esc_html_e( 'Aktiv',   'vupvup-qa' ); ?></option>
                <option value="closed" <?php selected( $status, 'closed' ); ?>><?php esc_html_e( 'Lukket',  'vupvup-qa' ); ?></option>
            </select>
        </td>
    </tr>
    <tr>
        <th><label for="vupvup_event_location"><?php esc_html_e( 'Sted', 'vupvup-qa' ); ?></label></th>
        <td>
            <input type="text" name="vupvup_event_location" id="vupvup_event_location"
                   value="<?php echo esc_attr( $location ); ?>" class="regular-text"
                   placeholder="<?php esc_attr_e( 'F.eks. Radisson Blu, København', 'vupvup-qa' ); ?>">
        </td>
    </tr>
    <tr>
        <th><label for="vupvup_event_start_time"><?php esc_html_e( 'Starttidspunkt', 'vupvup-qa' ); ?></label></th>
        <td>
            <input type="datetime-local" name="vupvup_event_start_time" id="vupvup_event_start_time"
                   value="<?php echo esc_attr( $start_time ); ?>">
        </td>
    </tr>
    <tr>
        <th><label for="vupvup_event_end_time"><?php esc_html_e( 'Sluttidspunkt', 'vupvup-qa' ); ?></label></th>
        <td>
            <input type="datetime-local" name="vupvup_event_end_time" id="vupvup_event_end_time"
                   value="<?php echo esc_attr( $end_time ); ?>">
            <p class="description"><?php esc_html_e( 'Eventet lukkes automatisk på dette tidspunkt.', 'vupvup-qa' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><label for="vupvup_event_speakers"><?php esc_html_e( 'Talere', 'vupvup-qa' ); ?></label></th>
        <td>
            <textarea name="vupvup_event_speakers" id="vupvup_event_speakers"
                      rows="4" class="large-text"
                      placeholder="<?php esc_attr_e( 'Én taler pr. linje, f.eks.:\nAnna Nielsen\nBo Sørensen', 'vupvup-qa' ); ?>"
            ><?php echo esc_textarea( $speakers ); ?></textarea>
            <p class="description"><?php esc_html_e( 'Deltagere kan adressere spørgsmål til en bestemt taler.', 'vupvup-qa' ); ?></p>
        </td>
    </tr>
    <tr>
        <th><?php esc_html_e( 'Gæster tilladt', 'vupvup-qa' ); ?></th>
        <td>
            <label>
                <input type="checkbox" name="vupvup_guest_allowed" value="1"
                       <?php checked( $guest_allowed, 1 ); ?>>
                <?php esc_html_e( 'Tillad deltagere at stille spørgsmål uden at logge ind (kun navn kræves)', 'vupvup-qa' ); ?>
            </label>
        </td>
    </tr>
</table>
