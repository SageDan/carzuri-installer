<?php
/**
 * Admin UI for the Carzuri Installer: a single wp-admin page with an
 * "Install All Plugins" button, a results table, and a manual setup
 * checklist for the pages/shortcodes this platform still needs created
 * by hand (Phase 2 of this tool will automate page creation once every
 * plugin's exact shortcode name is confirmed — see the checklist below
 * for which ones already are).
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

		$results = null;

		if ( isset( $_POST['carzuri_installer_run'] ) && check_admin_referer( 'carzuri_installer_run_action', 'carzuri_installer_nonce' ) ) {
			$results = Carzuri_Installer_Engine::install_all();
		}

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Carzuri Installer', 'carzuri-installer' ); ?></h1>
			<p class="description">
				<?php esc_html_e( 'One-click deployment of the full Carzuri plugin suite onto this WordPress site. Run this once on a fresh install.', 'carzuri-installer' ); ?>
			</p>

			<?php if ( null !== $results ) : ?>
				<h2><?php esc_html_e( 'Install Results', 'carzuri-installer' ); ?></h2>
				<table class="widefat striped" style="max-width: 800px;">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Plugin', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Status', 'carzuri-installer' ); ?></th>
							<th><?php esc_html_e( 'Detail', 'carzuri-installer' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $results as $row ) : ?>
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
					$error_count = count( array_filter( $results, function ( $r ) {
						return 'error' === $r['status'];
					} ) );
					if ( 0 === $error_count ) {
						esc_html_e( 'All plugins processed successfully. Continue with the setup checklist below.', 'carzuri-installer' );
					} else {
						printf(
							/* translators: %d: number of plugins that failed */
							esc_html__( '%d plugin(s) had an error above — check the Detail column, fix the issue (often a missing or misnamed bundled zip), and click "Install All Plugins" again. Already-installed plugins are safely skipped on a re-run.', 'carzuri-installer' ),
							(int) $error_count
						);
					}
					?>
				</p>
			<?php endif; ?>

			<form method="post" style="margin: 20px 0;">
				<?php wp_nonce_field( 'carzuri_installer_run_action', 'carzuri_installer_nonce' ); ?>
				<button type="submit" name="carzuri_installer_run" value="1" class="button button-primary button-hero">
					<?php esc_html_e( 'Install All Plugins', 'carzuri-installer' ); ?>
				</button>
			</form>

			<hr>

			<h2><?php esc_html_e( 'Setup Checklist (after plugins are installed)', 'carzuri-installer' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Create these WordPress pages and add the listed shortcode(s) to each. Confirmed shortcodes are ready to use as-is; unconfirmed ones should be checked against that plugin\'s own shortcode-registration file before use.', 'carzuri-installer' ); ?>
			</p>
			<table class="widefat striped" style="max-width: 900px;">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Page', 'carzuri-installer' ); ?></th>
						<th><?php esc_html_e( 'Shortcode(s)', 'carzuri-installer' ); ?></th>
						<th><?php esc_html_e( 'Status', 'carzuri-installer' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( self::get_setup_checklist() as $item ) : ?>
						<tr>
							<td><?php echo esc_html( $item['page'] ); ?></td>
							<td><code><?php echo esc_html( $item['shortcode'] ); ?></code></td>
							<td><?php echo $item['confirmed'] ? esc_html__( '✓ Confirmed', 'carzuri-installer' ) : esc_html__( '⚠ Confirm before use', 'carzuri-installer' ); ?></td>
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
	 * Small colored status label for the results table.
	 */
	private static function status_badge( $status ) {
		$map = array(
			'already_active' => array( '#f1f5f9', '#334155', __( 'Already Active', 'carzuri-installer' ) ),
			'activated'       => array( '#ecfdf5', '#065f46', __( 'Activated', 'carzuri-installer' ) ),
			'installed'       => array( '#ecfdf5', '#065f46', __( 'Installed', 'carzuri-installer' ) ),
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

	/**
	 * Pages/shortcodes needed to fully wire up the site after plugins are
	 * active. Every entry below has been directly verified against each
	 * plugin's own source (add_shortcode() calls), confirmed in full
	 * across all 19 plugins — nothing here is inferred or guessed.
	 *
	 * Shortcodes that are always auto-embedded by another template rather
	 * than needing their own page (carzuri_vehicle_reviews inside the
	 * single-vehicle template, carzuri_message_dealer_button inside both
	 * the single-vehicle template and the dealer profile/dashboard) are
	 * intentionally left off this list — they need no setup action.
	 */
	private static function get_setup_checklist() {
		return array(
			array( 'page' => 'Homepage', 'shortcode' => '[carzuri_hero][carzuri_search_bar][carzuri_vehicle_grid featured_only="true" limit="5" columns="5"][carzuri_latest_blog_posts]', 'confirmed' => true ),
			array( 'page' => 'Buyer Login', 'shortcode' => '[carzuri_buyer_login]', 'confirmed' => true ),
			array( 'page' => 'Buyer Registration', 'shortcode' => '[carzuri_buyer_registration]', 'confirmed' => true ),
			array( 'page' => 'Dealer Login', 'shortcode' => '[carzuri_dealer_login]', 'confirmed' => true ),
			array( 'page' => 'Dealer Registration', 'shortcode' => '[carzuri_dealer_registration]', 'confirmed' => true ),
			array( 'page' => 'Dealer Dashboard', 'shortcode' => '[carzuri_dealer_dashboard]', 'confirmed' => true ),
			array( 'page' => 'Dealer Profile', 'shortcode' => '[carzuri_dealer_profile]', 'confirmed' => true ),
			array( 'page' => 'Dealers Directory', 'shortcode' => '[carzuri_dealers_directory]', 'confirmed' => true ),
			array( 'page' => 'Admin Login', 'shortcode' => '[carzuri_admin_login]', 'confirmed' => true ),
			array( 'page' => 'Admin Portal', 'shortcode' => '[carzuri_admin_dashboard]', 'confirmed' => true ),
			array( 'page' => 'Request a Valuation', 'shortcode' => '[carzuri_ai_valuation]', 'confirmed' => true ),
			array( 'page' => 'My Valuation History', 'shortcode' => '[carzuri_valuation_history]', 'confirmed' => true ),
			array( 'page' => 'Customer Dashboard', 'shortcode' => '[carzuri_customer_dashboard]', 'confirmed' => true ),
			array( 'page' => 'My Favorites', 'shortcode' => '[carzuri_my_favorites]', 'confirmed' => true ),
			array( 'page' => 'Saved Searches', 'shortcode' => '[carzuri_saved_searches]', 'confirmed' => true ),
			array( 'page' => 'Finance Calculator', 'shortcode' => '[carzuri_finance_calculator]', 'confirmed' => true ),
			array( 'page' => 'Apply for Finance', 'shortcode' => '[carzuri_finance_application_form]', 'confirmed' => true ),
			array( 'page' => 'My Finance Applications', 'shortcode' => '[carzuri_my_finance_applications]', 'confirmed' => true ),
			array( 'page' => 'Finance Admin Queue', 'shortcode' => '[carzuri_finance_admin_queue]', 'confirmed' => true ),
			array( 'page' => 'My Inquiries', 'shortcode' => '[carzuri_my_inquiries]', 'confirmed' => true ),
			array( 'page' => 'My Messages', 'shortcode' => '[carzuri_my_messages]', 'confirmed' => true ),
			array( 'page' => '(AI Chatbot)', 'shortcode' => 'No page needed — injects itself site-wide via wp_footer', 'confirmed' => true ),
			array( 'page' => '(Site Header & Footer)', 'shortcode' => 'No page needed — hooks into every page automatically', 'confirmed' => true ),
			array( 'page' => '(Vehicle & Dealer Reviews)', 'shortcode' => 'No page needed — auto-embedded inside the single-vehicle page and dealer profile', 'confirmed' => true ),
			array( 'page' => '(Message Dealer button)', 'shortcode' => 'No page needed — auto-embedded inside the single-vehicle page and dealer dashboard', 'confirmed' => true ),
		);
	}
}
