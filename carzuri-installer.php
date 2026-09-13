<?php
/**
 * Plugin Name: Carzuri Installer
 * Plugin URI:  https://carzuri.co.ke
 * Description: One-click installer that bundles and deploys the entire Carzuri plugin suite (all 19 plugins) in the correct dependency order, for fast re-deployment on new client sites reusing the Carzuri platform.
 * Version:     1.0.0
 * Author:      Carzuri Platform Team
 * Text Domain: carzuri-installer
 * License:     GPLv2 or later
 *
 * @package CarzuriInstaller
 *
 * ============================================================
 * HOW TO PACKAGE THIS FOR A NEW CLIENT SITE
 * ============================================================
 * 1. Zip each of the 19 Carzuri plugins individually (the normal
 *    zip you'd upload via Plugins > Add New > Upload Plugin).
 * 2. Set a fresh activation code for this deployment — see the header
 *    comment in carzuri-installer-license-config.php for how.
 * 3. Place all 19 zips into this plugin's own `bundled-plugins/`
 *    folder, named EXACTLY as their plugin slug, e.g.:
 *       bundled-plugins/carzuri-core.zip
 *       bundled-plugins/carzuri-listings.zip
 *       ...and so on for all 19 (see class-carzuri-installer-manifest.php
 *       for the full authoritative list and required install order).
 * 4. Zip this whole carzuri-installer folder (now containing all 19
 *    bundled zips inside it) into carzuri-installer.zip.
 * 5. On a fresh WordPress site, upload + activate ONLY this one zip.
 * 6. Go to the new "Carzuri Installer" admin menu it creates, enter
 *    the activation code from step 2, then click "Install All Plugins."
 *
 * This plugin does not need to be kept active after setup is done —
 * it's a one-time deployment tool, not a runtime dependency.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CARZURI_INSTALLER_VERSION', '1.0.0' );
define( 'CARZURI_INSTALLER_PATH', plugin_dir_path( __FILE__ ) );
define( 'CARZURI_INSTALLER_URL', plugin_dir_url( __FILE__ ) );
define( 'CARZURI_INSTALLER_BUNDLE_DIR', CARZURI_INSTALLER_PATH . 'bundled-plugins/' );

require_once CARZURI_INSTALLER_PATH . 'carzuri-installer-license-config.php';
require_once CARZURI_INSTALLER_PATH . 'includes/class-carzuri-installer-license.php';
require_once CARZURI_INSTALLER_PATH . 'includes/class-carzuri-installer-manifest.php';
require_once CARZURI_INSTALLER_PATH . 'includes/class-carzuri-installer-engine.php';
require_once CARZURI_INSTALLER_PATH . 'includes/class-carzuri-installer-pages.php';
require_once CARZURI_INSTALLER_PATH . 'includes/class-carzuri-installer-admin.php';

function carzuri_installer_init() {
	Carzuri_Installer_Admin::init();
}
add_action( 'plugins_loaded', 'carzuri_installer_init' );
