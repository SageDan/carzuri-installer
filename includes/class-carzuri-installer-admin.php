<?php
/**
 * Admin UI for the Carzuri Installer: a single wp-admin page with an
 * "Install Plugins & Create Pages" button that runs both Phase 1 (plugin
 * install/activate) and Phase 2 (page creation) in one click, plus a
 * live-status checklist that reflects what's actually in the database at
 * any time — not just right after clicking the button.
 *
 * @package CarzuriInstaller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carzuri_Installer_Admin {

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'register_menu' ) );
	}

	public static function register_menu() {
		add_menu_page(
			__( 'Carzuri Installer', 'carzuri-installer' ),
			__( 'Carzuri Installer', 'carzuri-installer' ),
			'manage_options',
			'carzuri-installer',
			array( __CLASS__, 'render_page' ),
			'dashicons-download',
			2 // High in the menu — this is a one-time setup tool, easy to find on a brand-new site.
		);
	}

	public static function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Activation gate: nothing below this renders until the correct
		// code has been entered once on this site.
		if ( ! Carzuri_Installer_License::is_activated() ) {
			Carzuri_Installer_License::render_activation_screen();
			return;
		}

		$plugin_results = null;
		$page_results   = null;

		if ( isset( $_POST['carzuri_installer_run'] ) && check_admin_referer( 'carzuri_installer_run_action', 'carzuri_installer_nonce' ) ) {
			$plugin_results = Carzuri_Installer_Engine::install_all();

			// Only attempt page creation if Core (the one 'required'
			// plugin) actually succeeded — every shortcode depends on its
			// CPT/taxonomies existing, so creating pages before that would
			// just mean empty, non-functional shortcodes on the page.
			$core_failed = false;
			foreach ( $plugin_results as $row ) {
				if ( 'carzuri-core' === $row['slug'] && 'error' === $row['status'] ) {
					$core_failed = true;
					break;
				}
			}

			if ( ! $core_failed ) {
				$set_front_page = ! empty( $_POST['carzuri_set_front_page'] );
				$page_results   = Carzuri_Installer_Pages::create_all_pages( $set_front_page );
			}
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Carzuri Installer', 'carzuri-installer' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'One-click deployment of the full Carzuri plugin suite onto this WordPress site — installs and activates all 19 plugins, then creates every needed page with its shortcode already inserted. Run this once on a fresh install.', 'carzuri-installer' ); ?>
			</p>

			<?php if ( null !== $plugin_results ) : ?>
				<h2><?php esc_html_e( 'Plugin Install Results', 'carzuri-installer' ); ?></h2>
				<table class="widefat striped" style="max-width: 800px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Plugin', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Status', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Detail', 'carzuri-installer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $plugin_results as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['label'] ); ?></strong></td>
								<td><?php echo self::status_badge( $row['status'] ); ?></td>
								<td><?php echo esc_html( $row['message'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p>
					<?php
					$error_count = count( array_filter( $plugin_results, function ( $r ) {
						return 'error' === $r['status'];
					} ) );
					if ( 0 === $error_count ) {
						esc_html_e( 'All plugins processed successfully.', 'carzuri-installer' );
					} else {
						printf(
							/* translators: %d: number of plugins that failed */
							esc_html__( '%d plugin(s) had an error above — check the Detail column, fix the issue (often a missing or misnamed bundled zip), and click the button again. Already-installed plugins are safely skipped on a re-run.', 'carzuri-installer' ),
							(int) $error_count
						);
					}
					?>
				</p>
			<?php endif; ?>

			<?php if ( null !== $page_results ) : ?>
				<h2><?php esc_html_e( 'Page Creation Results', 'carzuri-installer' ); ?></h2>
				<table class="widefat striped" style="max-width: 800px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Page', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Status', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Detail', 'carzuri-installer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $page_results as $row ) : ?>
							<tr>
								<td><strong><?php echo esc_html( $row['title'] ); ?></strong></td>
								<td><?php echo self::status_badge( $row['status'] ); ?></td>
								<td>
									<?php echo esc_html( $row['message'] ); ?>
									<?php if ( ! empty( $row['edit_link'] ) ) : ?>
										— <a href="<?php echo esc_url( $row['edit_link'] ); ?>" target="_blank"><?php esc_html_e( 'Edit page', 'carzuri-installer' ); ?></a>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php elseif ( null !== $plugin_results ) : ?>
				<div class="notice notice-warning"><p>
					<?php esc_html_e( 'Page creation was skipped because Carzuri Core failed to install — fix that first, then run this again.', 'carzuri-installer' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" style="margin: 20px 0;">
				<?php wp_nonce_field( 'carzuri_installer_run_action', 'carzuri_installer_nonce' ); ?>
				<p>
					<label>
						<input type="checkbox" name="carzuri_set_front_page" value="1" checked>
						<?php esc_html_e( 'Set the Home page as this site\'s static front page', 'carzuri-installer' ); ?>
					</label>
				</p>
				<button type="submit" name="carzuri_installer_run" value="1" class="button button-primary button-hero">
					<?php esc_html_e( 'Install Plugins & Create Pages', 'carzuri-installer' ); ?>
				</button>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Setup Checklist — Live Status', 'carzuri-installer' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'This reflects what actually exists in this site\'s database right now, whether or not you\'ve clicked the button above.', 'carzuri-installer' ); ?>
			</p>
			<table class="widefat striped" style="max-width: 900px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'carzuri-installer' ); ?></th>
						<th><?php esc_html_e( 'Shortcode', 'carzuri-installer' ); ?></th>
						<th><?php esc_html_e( 'Status', 'carzuri-installer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( Carzuri_Installer_Pages::get_page_manifest() as $item ) : ?>
						<?php $existing_id = Carzuri_Installer_Pages::find_existing_page_id( $item['check_shortcode'] ); ?>
						<tr>
							<td><?php echo esc_html( $item['title'] ); ?></td>
							<td><code><?php echo esc_html( $item['check_shortcode'] ); ?></code></td>
							<td>
								<?php if ( $existing_id ) : ?>
									✓ <a href="<?php echo esc_url( get_edit_post_link( $existing_id, '' ) ); ?>" target="_blank"><?php esc_html_e( 'Created', 'carzuri-installer' ); ?></a>
								<?php else : ?>
									<?php esc_html_e( 'Not created yet', 'carzuri-installer' ); ?>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					<?php foreach ( Carzuri_Installer_Pages::get_no_page_needed_items() as $item ) : ?>
						<tr style="color:#6b7280;">
							<td><?php echo esc_html( $item['title'] ); ?></td>
							<td colspan="2"><?php echo esc_html( $item['note'] ); ?></td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<p style="margin-top: 20px;">
				<?php esc_html_e( 'Once pages are created, finish configuration in Carzuri → Settings (currency, supported locations, Gemini API key, payment gateway key).', 'carzuri-installer' ); ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=carzuri-settings' ) ); ?>"><?php esc_html_e( 'Go to Carzuri Settings →', 'carzuri-installer' ); ?></a>
			</p>
		</div>
		<?php
	}

	/**
	 * Small colored status label, shared by both results tables.
	 */
	private static function status_badge( $status ) {
		$map = array(
			'already_active' => array( '#f1f5f9', '#334155', __( 'Already Active', 'carzuri-installer' ) ),
			'activated'       => array( '#ecfdf5', '#065f46', __( 'Activated', 'carzuri-installer' ) ),
			'installed'       => array( '#ecfdf5', '#065f46', __( 'Installed', 'carzuri-installer' ) ),
			'created'         => array( '#ecfdf5', '#065f46', __( 'Created', 'carzuri-installer' ) ),
			'exists'          => array( '#f1f5f9', '#334155', __( 'Already Existed', 'carzuri-installer' ) ),
			'error'           => array( '#fef2f2', '#991b1b', __( 'Error', 'carzuri-installer' ) ),
		);
		$style = isset( $map[ $status ] ) ? $map[ $status ] : array( '#f1f5f9', '#334155', $status );

		return sprintf(
			'<span style="display:inline-block; padding:2px 10px; border-radius:12px; font-size:12px; font-weight:600; background:%s; color:%s;">%s</span>',
			esc_attr( $style[0] ),
			esc_attr( $style[1] ),
			esc_html( $style[2] )
		);
	}
}
