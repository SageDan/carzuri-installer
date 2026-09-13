=== Carzuri Installer ===
Contributors: carzuri
Tags: installer, deployment, multi-plugin, carzuri
Requires at least: 5.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later

One-click deployment tool for the full Carzuri plugin suite, built for
reuse when the Carzuri platform is deployed for a new client.

== What this is ==

This plugin bundles zip copies of all 19 Carzuri plugins inside its own
`bundled-plugins/` folder, and installs + activates every one of them on
a fresh WordPress site with a single click, in the correct dependency
order (Core first).

== How to package this for a new client ==

1. Take a current, working zip of each of the 19 Carzuri plugins (the
   same zips you'd normally upload one at a time via
   Plugins > Add New > Upload Plugin).
2. Set a fresh activation code for this deployment. In Git Bash, run:
       echo -n "YOUR-CHOSEN-CODE" | sha256sum
   Copy the hash it prints and paste it into
   carzuri-installer-license-config.php, replacing the existing
   CARZURI_INSTALLER_ACTIVATION_HASH value. Keep the plain-text code
   itself somewhere private (a password manager, not this file) — you'll
   need to give it to whoever runs the installer on the client's site.
4. Drop all 19 zip files into this plugin's `bundled-plugins/` folder.
   Each zip's FILENAME must exactly match its "slug" as listed in
   includes/class-carzuri-installer-manifest.php, e.g.:
       bundled-plugins/carzuri-core.zip
       bundled-plugins/carzuri-listings.zip
       bundled-plugins/carzuri-dealer-marketplace.zip
       ... (see the manifest file for the full list of 19)
5. Zip up this ENTIRE carzuri-installer folder (with all 19 bundled
   zips now inside it) into one file: carzuri-installer.zip.
6. On the new client's fresh WordPress install, upload and activate
   ONLY that one carzuri-installer.zip via Plugins > Add New > Upload
   Plugin — do not upload the 19 individual plugin zips separately.
7. A new "Carzuri Installer" menu item appears in wp-admin (near the
   top). Open it — it will ask for the activation code you set in
   step 2 before showing anything else. Enter it, then click
   "Install Plugins & Create Pages." This single click installs and
   activates all 19 plugins AND creates every needed WordPress page
   with its shortcode already inserted (including setting the Home
   page as the site's static front page, if you leave that box checked).
8. Review the Setup Checklist at the bottom of that same page to
   confirm every page shows "✓ Created," then finish client-specific
   configuration in Carzuri → Settings (currency, supported locations,
   Gemini API key, payment gateway key).
9. Once setup is confirmed working, Carzuri Installer itself can be
   deactivated and deleted — it's a one-time deployment tool, not
   something the live site needs running long-term.

== Keeping this installer up to date ==

Whenever a bug fix or feature is added to any of the 19 plugins on a
live client site, re-export that plugin's updated zip and replace the
matching file in bundled-plugins/ here, so the NEXT new client deployed
from this installer starts from the current, fixed code rather than an
old snapshot. This is exactly the kind of thing git (see the project's
own version-control setup) is good at tracking over time — commit this
installer folder to its own repo, and updating it becomes a normal
"replace this one zip, commit, push" operation like any other change.

== Reusing this pattern for a different (non-Carzuri) project ==

Almost nothing in this installer is Carzuri-specific except the plugin
list itself. To adapt it for a new, unrelated project (e.g. a school
management system):
1. Rewrite includes/class-carzuri-installer-manifest.php with that
   project's own plugin slugs, main files, and correct install order.
2. Rewrite the manifest in includes/class-carzuri-installer-pages.php
   with that project's own pages, content/shortcodes, and the
   shortcode string used to detect an existing page.
3. Everything else (class-carzuri-installer-engine.php, the license
   gate, the admin page shell, the packaging process above) works
   unchanged.
