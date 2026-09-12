<?php
/**
 * Carzuri Installer — Activation Code Configuration
 *
 * EDIT THIS FILE before zipping up the installer for a new client.
 *
 * Set CARZURI_INSTALLER_ACTIVATION_HASH to the SHA-256 hash of whatever
 * activation code you want to require for THIS deployment. Never put the
 * plain-text code itself in this file — only its hash. That way, anyone
 * who opens this file (even after getting hold of the zip) sees only a
 * scrambled fingerprint, not the actual code.
 *
 * HOW TO GENERATE A HASH FOR A NEW CODE:
 * Open Git Bash (or any terminal with openssl/sha256sum available) and run:
 *
 *     echo -n "YOUR-CHOSEN-CODE-HERE" | sha256sum
 *
 * Copy the long hex string it prints (before the space) and paste it below.
 * Example: if your code is "CARZURI-DEMO-2026", running that command
 * prints a 64-character hex string — that string is what goes here.
 *
 * NOTE ON WHAT THIS ACTUALLY PROTECTS AGAINST: this is a local code check
 * baked into the plugin's own PHP, not a remote license server. It stops
 * casual/accidental reuse by someone who doesn't know the code, but a
 * developer with full access to these files could remove the check
 * entirely if they chose to. It's a deterrent, not unbreakable DRM.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// CHANGE THIS before packaging for each client. This placeholder is the
// real SHA-256 hash of the literal code "CHANGE-ME" — it's a working
// example, not a random string, but you must replace it with your own
// client-specific code's hash before shipping this to anyone.
define( 'CARZURI_INSTALLER_ACTIVATION_HASH', 'a268e47c2aabfd8c9e6eac615564d426d33f08bcd7fd2789315517676987a97f' );
