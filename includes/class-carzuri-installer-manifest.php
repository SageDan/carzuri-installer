<?php
/**
 * Authoritative, dependency-ordered list of every plugin in the Carzuri
 * suite. Core MUST install first (every other plugin depends on its CPT,
 * taxonomies, DB tables, REST namespace, and design system). The rest are
 * ordered to roughly match the platform's original build order, which has
 * already proven to be a safe activation sequence in production.
 *
 * To reuse this installer for a different project (not Carzuri), this is
 * the one file that needs rewriting — swap in that project's own plugin
 * slugs in their dependency order. Nothing else in this installer is
 * Carzuri-specific.
 *
 * @package CarzuriInstaller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carzuri_Installer_Manifest {

	/**
	 * Returns the ordered plugin list.
	 *
	 * Each entry:
	 *   'slug'       => the plugin's folder/zip name (no .zip extension)
	 *   'main_file'  => the relative path WordPress uses to identify this
	 *                   plugin once installed, e.g. 'slug/slug.php'
	 *   'label'      => human-readable name, shown in the admin UI
	 *   'required'   => true if the whole platform is broken without it
	 *                   (currently just Core) — installer treats a
	 *                   failure here as fatal and stops the run
	 *
	 * @return array[]
	 */
	public static function get_plugins() {
		return array(
			array(
				'slug'      => 'carzuri-core',
				'main_file' => 'carzuri-core/carzuri-core.php',
				'label'     => 'Carzuri Core',
				'required'  => true,
			),
			array(
				'slug'      => 'carzuri-listings',
				'main_file' => 'carzuri-listings/carzuri-listings.php',
				'label'     => 'Carzuri Listings',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-favorites',
				'main_file' => 'carzuri-favorites/carzuri-favorites.php',
				'label'     => 'Carzuri Favorites & Saved Searches',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-dealer-marketplace',
				'main_file' => 'carzuri-dealer-marketplace/carzuri-dealer-marketplace.php',
				'label'     => 'Carzuri Dealer Marketplace',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-inquiry-lead-system',
				'main_file' => 'carzuri-inquiry-lead-system/carzuri-inquiry-lead-system.php',
				'label'     => 'Carzuri Inquiry & Lead System',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-notifications',
				'main_file' => 'carzuri-notifications/carzuri-notifications.php',
				'label'     => 'Carzuri Notifications',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-finance-calculator',
				'main_file' => 'carzuri-finance-calculator/carzuri-finance-calculator.php',
				'label'     => 'Carzuri Finance Calculator',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-finance-applications',
				'main_file' => 'carzuri-finance-applications/carzuri-finance-applications.php',
				'label'     => 'Carzuri Finance Applications',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-ai-valuation',
				'main_file' => 'carzuri-ai-valuation/carzuri-ai-valuation.php',
				'label'     => 'Carzuri AI Smart Valuation',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-customer-dashboard',
				'main_file' => 'carzuri-customer-dashboard/carzuri-customer-dashboard.php',
				'label'     => 'Carzuri Customer Dashboard',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-buyer-accounts',
				'main_file' => 'carzuri-buyer-accounts/carzuri-buyer-accounts.php',
				'label'     => 'Carzuri Buyer Accounts',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-admin-portal',
				'main_file' => 'carzuri-admin-portal/carzuri-admin-portal.php',
				'label'     => 'Carzuri Admin Portal',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-ai-chatbot',
				'main_file' => 'carzuri-ai-chatbot/carzuri-ai-chatbot.php',
				'label'     => 'Carzuri AI Chatbot',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-reviews-dealer-ratings',
				'main_file' => 'carzuri-reviews-dealer-ratings/carzuri-reviews-dealer-ratings.php',
				'label'     => 'Carzuri Reviews & Dealer Ratings',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-blog-styling',
				'main_file' => 'carzuri-blog-styling/carzuri-blog-styling.php',
				'label'     => 'Carzuri Blog Styling',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-payments',
				'main_file' => 'carzuri-payments/carzuri-payments.php',
				'label'     => 'Carzuri Payments',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-site-header-footer',
				'main_file' => 'carzuri-site-header-footer/carzuri-site-header-footer.php',
				'label'     => 'Carzuri Site Header & Footer',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-login-screen',
				'main_file' => 'carzuri-login-screen/carzuri-login-screen.php',
				'label'     => 'Carzuri Login Screen',
				'required'  => false,
			),
			array(
				'slug'      => 'carzuri-messaging',
				'main_file' => 'carzuri-messaging/carzuri-messaging.php',
				'label'     => 'Carzuri Messaging',
				'required'  => false,
			),
		);
	}
}
