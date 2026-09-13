<?php
/**
 * Phase 2: automatic page creation. Single source of truth for which
 * pages the platform needs and which shortcode each one gets — the admin
 * UI's live checklist and the actual page-creation logic both read from
 * this same manifest, so they can never drift out of sync with each other.
 *
 * Detecting an "already created" page reuses the exact same pattern the
 * Carzuri plugins themselves already use everywhere (e.g.
 * Carzuri_Dealer_Shortcodes::render_login_form(), Carzuri_Admin_Blog_REST,
 * etc.): search post_content for the shortcode string via a direct query,
 * rather than trusting a specific slug or title. That means this is safe
 * to run more than once — re-running never creates duplicate pages, and
 * it stays correct even if someone later renames a page or changes its
 * slug by hand.
 *
 * @package CarzuriInstaller
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Carzuri_Installer_Pages {

	/**
	 * Every page the platform needs, with its title, full page content
	 * (the shortcode(s) to insert), and the specific shortcode string used
	 * to detect whether a matching page already exists. Every entry here
	 * has been verified against the real add_shortcode() call in that
	 * plugin's own source.
	 *
	 * @return array[]
	 */
	public static function get_page_manifest() {
		return array(
			array( 'title' => 'Home', 'content' => "[carzuri_hero]\n[carzuri_search_bar]\n[carzuri_vehicle_grid featured_only=\"true\" limit=\"5\" columns=\"5\"]\n[carzuri_latest_blog_posts]", 'check_shortcode' => '[carzuri_hero]' ),
			array( 'title' => 'Buyer Login', 'content' => '[carzuri_buyer_login]', 'check_shortcode' => '[carzuri_buyer_login]' ),
			array( 'title' => 'Buyer Registration', 'content' => '[carzuri_buyer_registration]', 'check_shortcode' => '[carzuri_buyer_registration]' ),
			array( 'title' => 'Dealer Login', 'content' => '[carzuri_dealer_login]', 'check_shortcode' => '[carzuri_dealer_login]' ),
			array( 'title' => 'Dealer Registration', 'content' => '[carzuri_dealer_registration]', 'check_shortcode' => '[carzuri_dealer_registration]' ),
			array( 'title' => 'Dealer Dashboard', 'content' => '[carzuri_dealer_dashboard]', 'check_shortcode' => '[carzuri_dealer_dashboard]' ),
			array( 'title' => 'Dealer Profile', 'content' => '[carzuri_dealer_profile]', 'check_shortcode' => '[carzuri_dealer_profile]' ),
			array( 'title' => 'Dealers Directory', 'content' => '[carzuri_dealers_directory]', 'check_shortcode' => '[carzuri_dealers_directory]' ),
			array( 'title' => 'Admin Login', 'content' => '[carzuri_admin_login]', 'check_shortcode' => '[carzuri_admin_login]' ),
			array( 'title' => 'Admin Portal', 'content' => '[carzuri_admin_dashboard]', 'check_shortcode' => '[carzuri_admin_dashboard]' ),
			array( 'title' => 'Request a Valuation', 'content' => '[carzuri_ai_valuation]', 'check_shortcode' => '[carzuri_ai_valuation]' ),
			array( 'title' => 'My Valuation History', 'content' => '[carzuri_valuation_history]', 'check_shortcode' => '[carzuri_valuation_history]' ),
			array( 'title' => 'Customer Dashboard', 'content' => '[carzuri_customer_dashboard]', 'check_shortcode' => '[carzuri_customer_dashboard]' ),
			array( 'title' => 'My Favorites', 'content' => '[carzuri_my_favorites]', 'check_shortcode' => '[carzuri_my_favorites]' ),
			array( 'title' => 'Saved Searches', 'content' => '[carzuri_saved_searches]', 'check_shortcode' => '[carzuri_saved_searches]' ),
			array( 'title' => 'Finance Calculator', 'content' => '[carzuri_finance_calculator]', 'check_shortcode' => '[carzuri_finance_calculator]' ),
			array( 'title' => 'Apply for Finance', 'content' => '[carzuri_finance_application_form]', 'check_shortcode' => '[carzuri_finance_application_form]' ),
			array( 'title' => 'My Finance Applications', 'content' => '[carzuri_my_finance_applications]', 'check_shortcode' => '[carzuri_my_finance_applications]' ),
			array( 'title' => 'Finance Admin Queue', 'content' => '[carzuri_finance_admin_queue]', 'check_shortcode' => '[carzuri_finance_admin_queue]' ),
			array( 'title' => 'My Inquiries', 'content' => '[carzuri_my_inquiries]', 'check_shortcode' => '[carzuri_my_inquiries]' ),
			array( 'title' => 'My Messages', 'content' => '[carzuri_my_messages]', 'check_shortcode' => '[carzuri_my_messages]' ),
		);
	}

	/**
	 * Shortcodes that never need their own page — auto-embedded elsewhere.
	 * Display-only, so the checklist can explain why they're absent
	 * instead of looking like something got missed.
	 */
	public static function get_no_page_needed_items() {
		return array(
			array( 'title' => 'AI Chatbot', 'note' => 'Injects itself site-wide via wp_footer.' ),
			array( 'title' => 'Site Header & Footer', 'note' => 'Hooks into every page automatically.' ),
			array( 'title' => 'Vehicle & Dealer Reviews', 'note' => 'Auto-embedded inside the single-vehicle page and dealer profile.' ),
			array( 'title' => 'Message Dealer button', 'note' => 'Auto-embedded inside the single-vehicle page and dealer dashboard.' ),
		);
	}

	/**
	 * Does a published or draft page already contain this shortcode
	 * anywhere in its content? Mirrors the exact lookup pattern the
	 * Carzuri plugins themselves use for the same purpose.
	 *
	 * @param string $shortcode e.g. '[carzuri_buyer_login]'
	 * @return int|null Matching page ID, or null if none exists.
	 */
	public static function find_existing_page_id( $shortcode ) {
		global $wpdb;

		$like = '%' . $wpdb->esc_like( $shortcode ) . '%';

		$page_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts WHERE post_type = 'page' AND post_status IN ('publish','draft') AND post_content LIKE %s LIMIT 1",
				$like
			)
		);

		return $page_id ? (int) $page_id : null;
	}

	/**
	 * Create every page in the manifest that doesn't already exist.
	 * Safe to run repeatedly — existing pages are left untouched.
	 *
	 * @param bool $set_home_as_front_page If true and the Home page is
	 *             newly created (or already exists), also sets it as this
	 *             site's static front page.
	 * @return array Per-page result rows for display.
	 */
	public static function create_all_pages( $set_home_as_front_page = true ) {
		$results     = array();
		$home_page_id = null;

		foreach ( self::get_page_manifest() as $page ) {
			$existing_id = self::find_existing_page_id( $page['check_shortcode'] );

			if ( $existing_id ) {
				$results[] = array(
					'title'   => $page['title'],
					'status'  => 'exists',
					'message' => __( 'A page with this shortcode already exists — left untouched.', 'carzuri-installer' ),
					'edit_link' => get_edit_post_link( $existing_id, '' ),
				);
				if ( 'Home' === $page['title'] ) {
					$home_page_id = $existing_id;
				}
				continue;
			}

			$new_id = wp_insert_post(
				array(
					'post_title'   => $page['title'],
					'post_content' => $page['content'],
					'post_status'  => 'publish',
					'post_type'    => 'page',
				),
				true
			);

			if ( is_wp_error( $new_id ) ) {
				$results[] = array(
					'title'   => $page['title'],
					'status'  => 'error',
					'message' => $new_id->get_error_message(),
					'edit_link' => '',
				);
				continue;
			}

			$results[] = array(
				'title'   => $page['title'],
				'status'  => 'created',
				'message' => __( 'Page created and shortcode inserted.', 'carzuri-installer' ),
				'edit_link' => get_edit_post_link( $new_id, '' ),
			);

			if ( 'Home' === $page['title'] ) {
				$home_page_id = $new_id;
			}
		}

		if ( $set_home_as_front_page && $home_page_id ) {
			update_option( 'show_on_front', 'page' );
			update_option( 'page_on_front', $home_page_id );
		}

		return $results;
	}
}
