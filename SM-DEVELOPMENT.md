# SheetMora — Free 1.0.0 / Pro 1.0.0

Free uses `sheetmora/sheetmora.php` and the `sheetmora` text domain. Pro uses `sheetmora-pro/sheetmora-pro.php` and `sheetmora-pro`. First-party classes/constants use SM, hooks/options/actions use sm_, CSS selectors use sm-, and implementation filenames use sm or class-sm. Conventional WordPress entrypoints, readme.txt, LICENSE, index.php, phpcs.xml.dist and the third-party update checker retain conventional filenames.

SheetMora supports its current identity and configuration format only. There is no import from a previous plugin identity, payload-job adapter, receiver metadata alias, or fallback for another encryption context. Configure SheetMora, activate the license, reconnect Google and generate/deploy a current receiver for manual delivery. Removing compatibility code does not delete existing database options or remote spreadsheet data.

## Server endpoints

* License API: `https://wpmethods.com/sheetmora-license/api.php`
* Pro updates: `https://wpmethods.com/updates-sheetmora-pro?action=get_metadata&slug=sheetmora-pro`
* Google connection service: `https://connect-sheetmora.wpmethods.com/index.php`
* Service OAuth redirect URI: `https://connect-sheetmora.wpmethods.com/index.php?action=callback`
* Direct site OAuth callback: `admin_url('admin-post.php?action=sm_google_callback')`

The provider must deploy these endpoints. Changing client URLs does not deploy servers, configure DNS or register a Google OAuth application. The license API contract remains form-encoded POST fields `action`, `license_key`, `domain` and optional `token`; JSON responses must include boolean `success`. Supported actions are `validate_license`, `activate_license`, `check_domain_activation` and `deactivate_license`. Activation returns `token` or `matching_token`; an active-domain check returns `active_domain_match` and, when recovering a token, `matching_token`. Only the WordPress add-on uses the license API; the Google connection service does not validate licenses. Update metadata must identify `sheetmora-pro` and link to a ZIP with the `sheetmora-pro/sheetmora-pro.php` entrypoint.

The standalone service requires HTTPS, PHP 8.0+, OpenSSL, cURL, PDO SQLite and private writable storage outside the document root. Its SM-README.md documents SM_ environment variables, encrypted storage, deployment and Google consent configuration. Secrets are never distributed inside the plugin. The service handles authorization; order data travels directly from the store to Google.

## Configuration and delivery

Free has one authenticated Apps Script URL, Single Sheet and base fields. Pro owns OAuth, API clients, premium fields, category/date/custom routing, independent configurations, optional webhooks, licensing and commercial updates. The Free Pro preview is informational, with no disabled premium controls or paid implementations.

Pro reads only `sm_profiles` for destination rules. With no saved profile collection it shows one fresh API configuration; it does not import Free filters, selected fields, URL lists, webhook settings or a global spreadsheet selection. Each profile owns its destination, account subject verification, statuses, categories, fields, mode, tab name, template and optional webhook. Empty saved profiles mean no destinations. A setup-view change never changes profile transports. Required fields and the trailing form-completeness marker are validated server-side. Browser input cannot forge Google verification.

Each destination has its own hashed job identity and retries, up to three attempts. Workers re-read current settings and orders; editing, pausing or removing a profile invalidates pending job hashes. API writes use literal string cells, matching headers, spreadsheet leases and Order ID upserts. Manual receivers authenticate a persistent secret, lock writes, neutralize formula prefixes and reject mismatched headers. Webhooks receive the profile's selected data without Google credentials, with redirects disabled.

Only the current `sm-google-v1` credential context and `sm-connect-v1` service-storage context are accepted. Local credentials use WordPress salts; changing salts requires reconnection. OAuth uses expiring, single-use session-bound state. The scopes are openid, email and drive.file; existing arbitrary Drive files need separate authorization. No historical bulk export or Google Picker is included.

## Validation and packages

Run `php tests/sm-current-format.php` with OpenSSL and mbstring enabled. It includes integration, API and profile regressions, then verifies current-only encryption, modes, account identity and callback names. Run `php tests/sm-google-broker.php` with OpenSSL/PDO SQLite, and `node -e "require('vm').runInThisContext(require('fs').readFileSync('tests/sm-receiver.test.cjs','utf8'))"`. These use fake HTTP or an in-memory database; they never send order data or validate real licenses.

Run PHPCS with phpcs.xml.dist and the Pro ruleset. The optional WordPress smoke suite uses tests/sm-wp-bootstrap.php and tests/sm-wp-smoke.php to render settings/license views using in-memory options and blocked external HTTP. A running local WordPress database is required. Static Plugin Check sniffs can run without a database; the full WordPress Plugin Check requires a working WordPress bootstrap.

Build with `scripts/sm-build.ps1`. Its Free allowlist excludes Pro code, development files and tests. Distribute `dist/sheetmora-1.0.0.zip`, `dist/sheetmora-pro-1.0.0.zip` and the separately deployed `dist/sm-google-connection-service-1.0.0.zip`. Do not ZIP the whole development workspace. Update Free and Pro together.

## Google connection button

Pro 1.4.2 puts Connect with Google before setup choices and keeps it visible in manual setup. The protected POST uses a WordPress nonce and session-bound OAuth state, with the default SheetMora broker URL. Both the browser session and canonical WordPress URL must use HTTPS. Unavailable prerequisites are explained next to the visible, disabled button. The account management view replaces it after connection. Automatic spreadsheet creation selects the first API profile and never converts manual profiles. Manual-only profiles do not request automatic API creation.

Run `php tests/sm-google-connect.php` for connection-time profile preservation. The WordPress smoke test additionally checks HTTP, mixed-scheme and HTTPS views, configured/unconfigured buttons, nonce validity, manual setup and connected-account controls. These tests do not authorize a real Google account.

Pro 1.4.3 refines profile actions and destination selection. Duplicate/Remove use semantic buttons with visible text, decorative icons and consistent Blue/Red styling. Connection methods remain native radios inside a labeled fieldset, with scoped ring/dot styling, keyboard focus, forced-color support and responsive cards. Behavior and saved profile data are unchanged.


Pro 1.4.7 reports fixed allowlisted connection-service/Google error reasons and HTTP status in administrator notices. Unknown provider descriptions, error objects, response bodies and network messages are never reflected. Invalid grants/connections retain reconnect handling. Run php tests/sm-google-errors.php for offline error classification, malformed responses, secret redaction, rate limiting, redirects and unchanged successful PKCE start requests. The connection service and license validation contract are unchanged.


Pro/service 1.4.8 removes license validation only from the hosted Google OAuth start and refresh flow. The plugin no longer sends a license key to the broker, and new records omit it even if an older client submits one. Pro feature entitlement, activation/deactivation and commercial updates remain unchanged. Existing encryption, SQLite schema, callback validation, browser state and handoff proof remain in place. Deploy the updated service class and Pro plugin together; keep the existing private storage and key.


Pro 1.4.9 uses its built-in connection endpoint by default. The custom service checkbox reveals an optional HTTPS endpoint; a separate advanced choice preserves direct OAuth apps. sm_google_provider_mode records an explicit default/service/direct selection, while existing current configurations are inferred until saved. A saved vendor URL is treated as default. Unchanged settings preserve connected accounts; provider or credential changes require reconnection. Server-side validation rejects invalid custom URLs before mutation. Run php tests/sm-google-provider.php and the WordPress smoke suite for provider selection, validation and conditional field rendering.


Pro 1.4.10 adds contextual setup instructions and official Google links for enabling APIs, consent setup, creating OAuth clients and finding Client ID/Secret values. Help distinguishes the store callback used by a direct app from a hosted service callback. Field help uses aria-describedby, external links use noopener/noreferrer, and the default endpoint remains hidden. Connection behavior and saved settings are unchanged.


Pro 1.4.11 uses the official Google G PNG downloaded unchanged from Google's branding guide. The connection CTA uses the guide's light palette (white, #747775 border, #1f1f1f text) with a full-color logo, local font fallbacks, visible keyboard focus and forced-color support. The connection action, nonce, field names and saved settings are unchanged.


Free 1.4.5 links Support & Connect to https://wpmethods.com/sheetmora-documentation/ in both editions. The standalone documentation lives in documentation/sheetmora-documentation and is separately packaged as dist/sm-sheetmora-documentation-1.0.3.zip. It contains static HTML/CSS/JS, local search, copy controls, section navigation and deployment instructions. The Free plugin allowlist excludes the documentation site and its development checks. Uploading to the provider's website is a separate deployment step.

Documentation 1.0.1 adds a keyboard-accessible file-tab editor with complete escaped service source, line numbers and copy controls. The build refreshes embedded source from the canonical Pro service and includes its matching download ZIP. File layout and server environment instructions accompany the editor. Plugin behavior and settings are unchanged.

Documentation 1.0.2 adds a searchable Download Free & Pro section with WordPress.org and WP Methods links, accessible navigation, and responsive edition cards. The paid destination uses HTTPS. Plugin settings and runtime behavior are unchanged.

## Public release 1.0.0
Free and Pro version headers, constants, stable tags, dependency requirements and public changelogs now start at 1.0.0. Earlier 1.1–1.4 notes above describe unpublished development iterations. The service package uses the Pro release version; encrypted data formats are unchanged. Documentation release 1.0.3 reflects the reset.

The first-party production updater extends the bundled v5p6 library without editing vendor code. It disables updater debug warnings, Debug Bar panels and debug AJAX even if WP_DEBUG or Debug Bar is enabled by the host. Normal licensing, update checks, actionable admin notices and safe WooCommerce delivery logs remain active. The plugins do not change global PHP error settings or WordPress debug constants. Tests and development fixtures remain outside release ZIPs. Run tests/sm-release.php through WP-CLI with tests/sm-wp-bootstrap.php for real updater initialization and mocked metadata success/failure checks without network or cron writes.
