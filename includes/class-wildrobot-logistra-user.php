<?php

class Wildrobot_Logistra_User
{

	private $plugin_name;
	private $version;

	public function __construct($plugin_name, $version)
	{

		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	public function user_printer($user)
	{
		$printers = get_option('wildrobot_logistra_printers', []);
?>
		<br />
		<h2><?php _e("Bruker printer for Wildrobot fraktintegrasjon.", "wildrobot-logistra"); ?></h2>

		<table class="form-table">
			<tr>
				<th><label for="wildrobot-user-printer"><?php _e("Printer"); ?></label></th>
				<td>
					<select id="wildrobot-user-printer" name="wildrobot-user-printer">
						<option value="" selected="selected" disabled="disabled">Velg overstyrings printer for bruker&hellip;</option>
						<?php
						foreach ($printers as $printer) {
							printf('<option value="%1$s" %2$s>%3$s</option>', $printer["id"], selected(esc_attr(get_user_option("wildrobot_logistra_user_printer", $user->ID)), $printer["id"], false), $printer["name"]);
						}
						?>
					</select>
					<br />
					<span class="description"><?php _e("Kun velg en printer her om du vil at alle etikett utskrifter fra denne brukeren skal gå via printer valgt her."); ?></span>
				</td>
			</tr>
		</table>
<?php }

	function save_user_printer($user_id)
	{
		if (empty($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'update-user_' . $user_id)) {
			return;
		}

		if (!current_user_can('edit_user', $user_id)) {
			return false;
		}
		update_user_option($user_id, "wildrobot_logistra_user_printer", $_POST['wildrobot-user-printer']);
	}
}
// Wildrobot_Logistra_User::init();
