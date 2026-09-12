<?php
/**
 * Core install/activate engine. Reuses WordPress's own Plugin_Upgrader —
 * the exact same class core WordPress uses for Plugins > Add New > Upload
 * Plugin — so a bundled zip is installed identically to how it would be
 * if uploaded by hand. This file has no Carzuri-specific logic in it at
 * all; it just consumes whatever list Carzuri_Installer_Manifest gives it,
 * so it can be reused as-is for a future project's installer.
 *
 * @package CarzuriInstaller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carzuri_Installer_Engine {

	/**
	 * Silent skin: suppresses Plugin_Upgrader's normal HTML progress output
	 * (designed for wp-admin's own screen), since this runs inside our own
	 * admin page instead. We collect results ourselves and render them.
	 */
	private static function get_silent_skin() {
		if ( ! class_exists( 'WP_Upgrader_Skin' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader-skin.php';
		}

		if ( ! class_exists( 'Carzuri_Installer_Silent_Skin' ) ) {
			/**
			 * Anonymous-style silent skin, defined once on first use.
			 */
			eval( 'class Carzuri_Installer_Silent_Skin extends WP_Upgrader_Skin {
				public function feedback( $string, ...$args ) {}
				public function header() {}
				public function footer() {}
				public function error( $errors ) {}
			}' );
		}

		return new Carzuri_Installer_Silent_Skin();
	}

	/**
	 * Run the full install: every plugin in the manifest, in order.
	 * Stops immediately if a 'required' plugin (Core) fails, since nothing
	 * else can safely proceed without it.
	 *
	 * @return array List of per-plugin result rows for display.
	 */
	public static function install_all() {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/misc.php';
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

		// Uploads/installs can be slow on shared hosting with 19 plugins
		// in one request — give this run more room than PHP's default.
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 300 );
		}

		$results = array();

		foreach ( Carzuri_Installer_Manifest::get_plugins() as $plugin ) {
			$row = self::install_or_activate_one( $plugin );
			$results[] = $row;

			if ( 'error' === $row['status'] && ! empty( $plugin['required'] ) ) {
				$row['message'] .= ' ' . __( '(This plugin is required — stopping here since nothing else can work without it.)', 'carzuri-installer' );
				break;
			}
		}

		return $results;
	}

	/**
	 * Handle a single plugin: skip if already active, activate if already
	 * installed but inactive, or install fresh from its bundled zip and
	 * then activate.
	 *
	 * @param array $plugin One manifest entry.
	 * @return array Result row: slug, label, status ('already_active'|
	 *               'activated'|'installed'|'error'), message.
	 */
	private static function install_or_activate_one( $plugin ) {
		$row = array(
			'slug'    => $plugin['slug'],
			'label'   => $plugin['label'],
			'status'  => 'error',
			'message' => '',
		);

		// 1. Already active? Nothing to do.
		if ( is_plugin_active( $plugin['main_file'] ) ) {
			$row['status']  = 'already_active';
			$row['message'] = __( 'Already installed and active.', 'carzuri-installer' );
			return $row;
		}

		// 2. Installed but inactive? Just activate it.
		$installed_plugins = get_plugins();
		if ( isset( $installed_plugins[ $plugin['main_file'] ] ) ) {
			$activated = activate_plugin( $plugin['main_file'] );
			if ( is_wp_error( $activated ) ) {
				$row['message'] = sprintf(
					__( 'Found installed but activation failed: %s', 'carzuri-installer' ),
					$activated->get_error_message()
				);
				return $row;
			}
			$row['status']  = 'activated';
			$row['message'] = __( 'Was already installed — activated now.', 'carzuri-installer' );
			return $row;
		}

		// 3. Not installed at all — install from the bundled zip.
		$zip_path = CARZURI_INSTALLER_BUNDLE_DIR . $plugin['slug'] . '.zip';

		if ( ! file_exists( $zip_path ) ) {
			$row['message'] = sprintf(
				__( 'Bundled zip not found at %s — was it included when this installer was packaged?', 'carzuri-installer' ),
				'bundled-plugins/' . $plugin['slug'] . '.zip'
			);
			return $row;
		}

		$upgrader = new Plugin_Upgrader( self::get_silent_skin() );

		// Plugin_Upgrader::install() accepts a local file path directly —
		// WP_Upgrader::download_package() returns local paths as-is when
		// they don't start with http(s):// or ftp://, so no download step
		// happens; it just unzips the file that's already sitting here.
		$install_result = $upgrader->install( $zip_path );

		if ( is_wp_error( $install_result ) ) {
			$row['message'] = sprintf(
				__( 'Install failed: %s', 'carzuri-installer' ),
				$install_result->get_error_message()
			);
			return $row;
		}

		if ( ! $install_result ) {
			$row['message'] = __( 'Install failed for an unknown reason.', 'carzuri-installer' );
			return $row;
		}

		// Confirm WordPress actually sees the expected main file now —
		// catches a mismatched zip/manifest entry rather than silently
		// reporting success for the wrong thing.
		$installed_plugins = get_plugins();
		if ( ! isset( $installed_plugins[ $plugin['main_file'] ] ) ) {
			$row['message'] = sprintf(
				__( 'Installed, but WordPress did not find the expected file %s afterward — check the zip\'s internal folder name matches the manifest.', 'carzuri-installer' ),
				$plugin['main_file']
			);
			return $row;
		}

		$activated = activate_plugin( $plugin['main_file'] );
		if ( is_wp_error( $activated ) ) {
			$row['message'] = sprintf(
				__( 'Installed, but activation failed: %s', 'carzuri-installer' ),
				$activated->get_error_message()
			);
			return $row;
		}

		$row['status']  = 'installed';
		$row['message'] = __( 'Installed and activated successfully.', 'carzuri-installer' );
		return $row;
	}
}
