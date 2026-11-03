=== SheetMora ===
Contributors: wpmethods, ajharrashed
Tags: woocommerce, google sheets, orders, integration
Requires at least: 5.9
Tested up to: 7.1
Requires PHP: 7.4
Requires Plugins: woocommerce
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatically send selected WooCommerce order fields to one Google Sheet when matching order statuses change.

== Description ==

Send orders to a Google spreadsheet you control using a Google Apps Script web app deployed in that spreadsheet.

= Free features =
* Single Sheet: all matching orders go to the WooCommerce Orders tab.
* Update an existing row by Order ID instead of inserting duplicate orders.
* Choose trigger statuses and filter orders by product categories.
* Select order, billing, product, shipping and discount fields.
* Generate an authenticated Google Apps Script receiver.
* Background delivery with up to three attempts per destination.
* Formula injection protection, locked writes and header validation.
* WooCommerce High-Performance Order Storage compatibility.

= Optional separate Pro add-on =
The separately distributed [Pro add-on](https://wpmethods.com/product/sheetmora/) adds Google OAuth connection and direct Sheets API delivery without Apps Script, category-wise sheets, daily/weekly/monthly sheets, custom sheet naming, extra fields and independent sheet configurations with optional webhook forwarding. Each configuration has its own destination, fields and filters. Premium implementations are not included in this free plugin.

= External services and privacy =
This plugin uses Google Apps Script and Google Sheets. After you save a destination and an order enters a selected status, it sends the selected order fields (including the required Order ID, amount, billing name, status, creation date and product name) and an authentication secret to your configured HTTPS script.google.com web app. Optional selected columns can contain personal information such as email, phone, address and customer notes. Failed submissions may be retried up to three times. Data is stored in your Google spreadsheet according to your Google account permissions. No order data is sent to WP Methods by the free plugin.

Google service information: https://developers.google.com/apps-script/overview
Google Terms: https://policies.google.com/terms
Google Privacy Policy: https://policies.google.com/privacy

The store owner is responsible for restricting spreadsheet access, keeping the generated script private and updating their privacy policy as appropriate.

= Source =
Readable JavaScript and CSS are included in assets/. No build process is required. The plugin uses WordPress.org updates; it does not install or update code from a third-party server.

== Installation ==
1. Install and activate WooCommerce.
2. Upload the sheetmora folder to /wp-content/plugins/ and activate the plugin.
3. Open SheetMora, choose statuses, categories and fields, then save.
4. Generate the script. In a new Google spreadsheet, open Extensions > Apps Script and paste the entire generated code.
5. Deploy as a web app: execute as yourself, access Anyone. The receiver authenticates requests with the generated secret.
6. Save the deployed https://script.google.com/macros/s/DEPLOYMENT_ID/exec URL in the plugin settings.
7. Move an order into a selected status and allow the background task to run.

== Frequently Asked Questions ==

= Is Single Sheet free? =
Yes. The free plugin is fully functional with one destination and one WooCommerce Orders tab. Pro features are supplied by a separate add-on.

= How does the category filter work? =
An order is sent when it contains at least one selected category. Leave the filter empty to include all categories. This filter does not create separate category tabs in the free plugin.

= Does SheetMora import settings from the previously named plugin? =
No. Configure SheetMora, activate your Pro license if applicable, reconnect Google and generate/deploy a SheetMora receiver for manual delivery. Settings, encryption formats, queued jobs and receiver metadata from the previous identity are not imported or supported.

= What if I change fields or sheet settings? =
Save your settings, generate the script again and deploy a new version. A different column schema requires a matching tab or a new spreadsheet. The receiver refuses mismatched headers to protect existing data.

= Why has an order not arrived? =
Confirm the order status and category filters match, the web app is deployed with the documented access, and the current script version and headers match saved settings. Check WooCommerce > Status > Scheduled Actions and Logs (source sm). WP-Cron must run if Action Scheduler is unavailable. Pending jobs recheck current settings and do not send orders that no longer match.

== Changelog ==

= 1.0.0 =
* Initial public release.
* Single Sheet order sync with authenticated Apps Script delivery, field selection and category/status filters.
* Background retries, formula injection protection and HPOS support.
* Settings shortcut, documentation and optional product/support links.
