<?php
/**
 * Activation code gate. Blocks the installer's admin page behind a
 * one-time code entry, checked against a SHA-256 hash defined in
 * carzuri-installer-license-config.php (set per-client before packaging).
 * Once entered correctly on a given WordPress site, that site remembers
 * it (via an option) so the code isn't needed again on every visit.
 *
 * @package CarzuriInstaller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carzuri_Installer_License {

	const OPTION_KEY = 'carzuri_installer_activated';

	/**
	 * Has this WordPress site already unlocked the installer?
	 *
	 * @return bool
	 */
	public static function is_activated() {
		return 'yes' === get_option( self::OPTION_KEY );
	}

	/**
	 * Check a submitted code against the configured hash. On success,
	 * marks this site as activated so the gate doesn't reappear.
	 *
	 * @param string $submitted_code Raw code the user typed in.
	 * @return bool True if it matched.
	 */
	public static function try_activate( $submitted_code ) {
		if ( ! defined( 'CARZURI_INSTALLER_ACTIVATION_HASH' ) || empty( CARZURI_INSTALLER_ACTIVATION_HASH ) ) {
			// Misconfigured package (config file wasn't set up correctly)
			// — fail closed rather than silently allowing everything through.
			return false;
		}

		$submitted_hash = hash( 'sha256', trim( (string) $submitted_code ) );

		if ( hash_equals( CARZURI_INSTALLER_ACTIVATION_HASH, $submitted_hash ) ) {
			update_option( self::OPTION_KEY, 'yes' );
			return true;
		}

		return false;
	}

	/**
	 * Renders the activation-code entry screen (shown instead of the
	 * normal installer UI until a correct code is entered).
	 */
	public static function render_activation_screen() {
		$error = false;

		if ( isset( $_POST['carzuri_installer_activate'] ) && check_admin_referer( 'carzuri_installer_activate_action', 'carzuri_installer_activate_nonce' ) ) {
			$code = isset( $_POST['carzuri_activation_code'] ) ? sanitize_text_field( wp_unslash( $_POST['carzuri_activation_code'] ) ) : '';
			if ( self::try_activate( $code ) ) {
				// Reload so the rest of the page renders in its unlocked state.
				echo '<div class="notice notice-success"><p>' . esc_html__( 'Activated! Loading installer...', 'carzuri-installer' ) . '</p></div>';
				echo '<script>window.location.reload();</script>';
				return;
			}
			$error = true;
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Carzuri Installer — Activation Required', 'carzuri-installer' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'Enter the activation code you were given for this deployment to continue.', 'carzuri-installer' ); ?>
			</p>

			<?php if ( $error ) : ?>
				<div class="notice notice-error"><p><?php esc_html_e( 'That code is not correct. Please check it and try again.', 'carzuri-installer' ); ?></p></div>
			<?php endif; ?>

			<form method="post" style="max-width: 420px;">
				<?php wp_nonce_field( 'carzuri_installer_activate_action', 'carzuri_installer_activate_nonce' ); ?>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="carzuri_activation_code"><?php esc_html_e( 'Activation Code', 'carzuri-installer' ); ?></label></th>
						<td><input type="text" id="carzuri_activation_code" name="carzuri_activation_code" class="regular-text" autocomplete="off" required></td>
					</tr>
				</table>
				<button type="submit" name="carzuri_installer_activate" value="1" class="button button-primary">
					<?php esc_html_e( 'Activate', 'carzuri-installer' ); ?>
				</button>
			</form>
		</div>
		<?php
	}
}
