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

        <h2 style="margin-top:2em;"><?php esc_html_e( 'Scener', 'vupvup-qa' ); ?></h2>
        <p class="description" style="margin-bottom:1em;">
            <?php esc_html_e( 'Tilføj én eller flere scener. Hver scene får sin egen Q&A-strøm, et unikt deltagerlink og en dedikeret facilitator.', 'vupvup-qa' ); ?>
        </p>

        <div id="vupvup-scenes-list">
            <div class="vupvup-scene-row" style="border:1px solid #ddd;padding:1em;margin-bottom:1em;border-radius:4px;">
                <table class="form-table" style="margin:0;">
                    <tr>
                        <th style="width:200px;"><label><?php esc_html_e( 'Scene-navn', 'vupvup-qa' ); ?> <span aria-hidden="true">*</span></label></th>
                        <td><input type="text" name="vupvup_scenes[0][name]" class="regular-text"
                                   placeholder="<?php esc_attr_e( 'F.eks. Plenum', 'vupvup-qa' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Facilitator navn', 'vupvup-qa' ); ?></label></th>
                        <td><input type="text" name="vupvup_scenes[0][facilitator_name]" class="regular-text"
                                   placeholder="<?php esc_attr_e( 'F.eks. Anna Nielsen', 'vupvup-qa' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Facilitator e-mail', 'vupvup-qa' ); ?></label></th>
                        <td>
                            <input type="email" name="vupvup_scenes[0][facilitator_email]" class="regular-text"
                                   placeholder="anna@virksomhed.dk">
                            <p class="description"><?php esc_html_e( 'Ny konto oprettes automatisk og facilitatoren modtager velkomstmail med login.', 'vupvup-qa' ); ?></p>
                        </td>
                    </tr>
                </table>
                <button type="button" class="button vupvup-remove-scene" disabled style="margin-top:.5em;">
                    <?php esc_html_e( 'Fjern scene', 'vupvup-qa' ); ?>
                </button>
            </div>
        </div>

        <button type="button" id="vupvup-add-scene" class="button" style="margin-bottom:1.5em;">
            + <?php esc_html_e( 'Tilføj scene', 'vupvup-qa' ); ?>
        </button>

        <template id="vupvup-scene-template">
            <div class="vupvup-scene-row" style="border:1px solid #ddd;padding:1em;margin-bottom:1em;border-radius:4px;">
                <table class="form-table" style="margin:0;">
                    <tr>
                        <th style="width:200px;"><label><?php esc_html_e( 'Scene-navn', 'vupvup-qa' ); ?> <span aria-hidden="true">*</span></label></th>
                        <td><input type="text" name="vupvup_scenes[__INDEX__][name]" class="regular-text"
                                   placeholder="<?php esc_attr_e( 'F.eks. Sal B', 'vupvup-qa' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Facilitator navn', 'vupvup-qa' ); ?></label></th>
                        <td><input type="text" name="vupvup_scenes[__INDEX__][facilitator_name]" class="regular-text"
                                   placeholder="<?php esc_attr_e( 'F.eks. Bo Sørensen', 'vupvup-qa' ); ?>"></td>
                    </tr>
                    <tr>
                        <th><label><?php esc_html_e( 'Facilitator e-mail', 'vupvup-qa' ); ?></label></th>
                        <td>
                            <input type="email" name="vupvup_scenes[__INDEX__][facilitator_email]" class="regular-text"
                                   placeholder="bo@virksomhed.dk">
                            <p class="description"><?php esc_html_e( 'Ny konto oprettes automatisk og facilitatoren modtager velkomstmail med login.', 'vupvup-qa' ); ?></p>
                        </td>
                    </tr>
                </table>
                <button type="button" class="button vupvup-remove-scene" style="margin-top:.5em;">
                    <?php esc_html_e( 'Fjern scene', 'vupvup-qa' ); ?>
                </button>
            </div>
        </template>

        <?php submit_button( __( 'Opret event', 'vupvup-qa' ) ); ?>
    </form>
</div>

<script>
(function () {
    var list     = document.getElementById('vupvup-scenes-list');
    var tmpl     = document.getElementById('vupvup-scene-template');
    var addBtn   = document.getElementById('vupvup-add-scene');
    var counter  = list.querySelectorAll('.vupvup-scene-row').length;

    addBtn.addEventListener('click', function () {
        var html = tmpl.innerHTML.replace(/__INDEX__/g, counter++);
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        list.appendChild(wrap.firstElementChild);
        syncRemoveButtons();
    });

    list.addEventListener('click', function (e) {
        if (e.target.classList.contains('vupvup-remove-scene')) {
            e.target.closest('.vupvup-scene-row').remove();
            syncRemoveButtons();
        }
    });

    function syncRemoveButtons() {
        var rows = list.querySelectorAll('.vupvup-scene-row');
        rows.forEach(function (row) {
            row.querySelector('.vupvup-remove-scene').disabled = (rows.length <= 1);
        });
    }
}());
</script>
