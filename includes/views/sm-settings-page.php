<?php
/**
 * Integration settings and a clearly labeled, informational Pro preview.
 *
 * @package SM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap sm-settings-wrapper">
	<header class="sm-header">
		<div class="sm-brand-icon"><span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span></div>
		<div class="sm-heading">
			<div class="sm-eyebrow"><?php esc_html_e( 'WP Methods / WooCommerce integration', 'sheetmora' ); ?></div>
			<h1><?php esc_html_e( 'SheetMora', 'sheetmora' ); ?></h1>
			<p><?php esc_html_e( 'Less manual work. Every order, neatly organized.', 'sheetmora' ); ?></p>
		</div>
		<div class="sm-header-actions">
			<span class="sm-badge sm-badge-ready"><?php echo $extended ? esc_html__( 'Pro add-on enabled', 'sheetmora' ) : esc_html__( 'Free edition', 'sheetmora' ); ?></span>
			<a class="sm-text-link" href="#sm-setup"><span class="dashicons dashicons-editor-help" aria-hidden="true"></span><?php esc_html_e( 'Setup guide', 'sheetmora' ); ?></a>
		</div>
	</header>

	<?php settings_errors(); ?>
	<?php if ( has_action( 'sm_navigation' ) ) : ?>
		<?php do_action( 'sm_navigation' ); ?>
	<?php else : ?>
	<nav class="sm-workspace-nav" aria-label="<?php esc_attr_e( 'Integration navigation', 'sheetmora' ); ?>"><a href="#sm-configuration"><span class="dashicons dashicons-admin-settings" aria-hidden="true"></span><?php esc_html_e( 'Configuration', 'sheetmora' ); ?></a><a href="#sm-script-setup"><span class="dashicons dashicons-editor-code" aria-hidden="true"></span><?php esc_html_e( 'Script setup', 'sheetmora' ); ?></a><a href="#sm-pro-preview"><span class="dashicons dashicons-star-empty" aria-hidden="true"></span><?php esc_html_e( 'Explore Pro', 'sheetmora' ); ?></a></nav>
	<?php endif; ?>

	<div class="sm-overview" aria-label="<?php esc_attr_e( 'Configuration overview', 'sheetmora' ); ?>">
		<?php foreach ( $overview as $sm_stat ) : ?>
		<div class="sm-stat"><span class="sm-stat-icon dashicons dashicons-<?php echo esc_attr( $sm_stat['icon'] ); ?>" aria-hidden="true"></span><div><strong class="<?php echo ! empty( $sm_stat['metric'] ) ? esc_attr( 'sm-' . $sm_stat['metric'] . '-count' ) : ''; ?>"><?php echo esc_html( $sm_stat['value'] ); ?></strong><span><?php echo esc_html( $sm_stat['label'] ); ?></span></div></div>
		<?php endforeach; ?>
	</div>

	<div class="sm-layout">
		<main class="sm-main">
			<?php do_action( 'sm_connection_panel', $this ); ?>
			<?php if ( has_action( 'sm_render_configuration' ) ) : ?>
				<?php do_action( 'sm_render_configuration', $this ); ?>
			<?php else : ?>
			<form action="options.php" method="post" class="sm-card sm-settings-card" id="sm-configuration">
				<div class="sm-card-heading">
					<div><span class="sm-eyebrow"><?php esc_html_e( 'Make it yours', 'sheetmora' ); ?></span><h2><?php esc_html_e( 'Integration settings', 'sheetmora' ); ?></h2><p><?php esc_html_e( 'Choose which orders to send and the details you need.', 'sheetmora' ); ?></p></div>
					<span class="sm-section-icon dashicons dashicons-admin-settings" aria-hidden="true"></span>
				</div>
				<div class="sm-settings-body">
					<?php
					settings_fields( 'sm_gs_settings' );
					do_settings_sections( 'sm_gs_settings' );
					?>
				</div>
				<div class="sm-save-bar">
					<span><span class="dashicons dashicons-info-outline" aria-hidden="true"></span><?php esc_html_e( 'Save your configuration to apply changes.', 'sheetmora' ); ?></span>
					<?php submit_button( __( 'Save settings', 'sheetmora' ), 'primary', 'submit', false ); ?>
				</div>
			</form>
			<?php endif; ?>

			<?php if ( apply_filters( 'sm_show_script_setup', true ) ) : ?>
			<section class="sm-card" id="sm-script-setup" aria-labelledby="sm-script-title">
				<div class="sm-card-heading"><div><span class="sm-eyebrow"><?php esc_html_e( 'Connect your spreadsheet', 'sheetmora' ); ?></span><h2 id="sm-script-title"><?php esc_html_e( 'Generate Google Apps Script', 'sheetmora' ); ?></h2><p><?php esc_html_e( 'Your saved fields, ready for Google Sheets.', 'sheetmora' ); ?></p></div><span class="sm-section-icon dashicons dashicons-editor-code" aria-hidden="true"></span></div>
				<div class="sm-card-body">
					<p><?php esc_html_e( 'Generate your script, paste it into your spreadsheet’s Apps Script editor, then deploy it as a web app. Follow the setup guide for the deployment options.', 'sheetmora' ); ?></p>
					<div class="sm-inline-note"><span class="dashicons dashicons-shield" aria-hidden="true"></span><p><?php esc_html_e( 'Keep the generated script private. It contains your connection secret. After changing fields or sheet settings, save and deploy a new script version.', 'sheetmora' ); ?></p></div>
					<button type="button" id="sm-generate-script" class="button button-primary"><?php esc_html_e( 'Generate Google Apps Script', 'sheetmora' ); ?></button>
					<div id="sm-script-output" hidden>
						<div class="sm-output-heading"><label for="sm-generated-script"><?php esc_html_e( 'Your generated script', 'sheetmora' ); ?></label><span class="sm-badge"><?php esc_html_e( 'Google Apps Script', 'sheetmora' ); ?></span></div>
						<textarea id="sm-generated-script" class="large-text code" rows="18" readonly spellcheck="false"></textarea>
						<button type="button" id="sm-copy-script" class="button"><?php esc_html_e( 'Copy to clipboard', 'sheetmora' ); ?></button>
						<span id="sm-copy-status" role="status" hidden><?php esc_html_e( 'Copied!', 'sheetmora' ); ?></span>
					</div>
				</div>
			</section>
			<?php endif; ?>
		</main>

		<aside class="sm-sidebar" aria-label="<?php esc_attr_e( 'Setup and add-on information', 'sheetmora' ); ?>">
			<section class="sm-card sm-guide" id="sm-setup" aria-labelledby="sm-guide-title">
				<?php if ( has_action( 'sm_render_setup_guide' ) ) : ?>
					<?php do_action( 'sm_render_setup_guide' ); ?>
				<?php else : ?>
				<div class="sm-sidebar-heading"><span class="sm-eyebrow"><?php esc_html_e( 'A few simple steps', 'sheetmora' ); ?></span><h2 id="sm-guide-title"><?php esc_html_e( 'From store to spreadsheet', 'sheetmora' ); ?></h2></div>
				<ol class="sm-steps">
					<li><span>1</span><div><h3><?php esc_html_e( 'Choose & save', 'sheetmora' ); ?></h3><p><?php esc_html_e( 'Select your statuses, categories and fields. Save your settings.', 'sheetmora' ); ?></p></div></li>
					<li><span>2</span><div><h3><?php esc_html_e( 'Create your web app', 'sheetmora' ); ?></h3><p><?php esc_html_e( 'Generate the script. In your spreadsheet, open Extensions > Apps Script and paste the code.', 'sheetmora' ); ?></p><p><?php esc_html_e( 'Deploy as a web app: execute as yourself, access Anyone. Requests are protected by your generated secret.', 'sheetmora' ); ?></p></div></li>
					<li><span>3</span><div><h3><?php esc_html_e( 'Connect & sync', 'sheetmora' ); ?></h3><p><?php esc_html_e( 'Paste the deployment’s /exec URL into settings and save. Matching status changes will send orders in the background.', 'sheetmora' ); ?></p></div></li>
				</ol>
				<?php endif; ?>
			</section>

			<?php require SM_PATH . 'includes/views/sm-admin-resources.php'; ?>

			<?php if ( ! $extended ) : ?>
			<section class="sm-card sm-pro-preview" id="sm-pro-preview" aria-labelledby="sm-pro-title">
				<div class="sm-pro-heading"><span class="sm-badge sm-badge-pro"><?php esc_html_e( 'PRO ADD-ON', 'sheetmora' ); ?></span><span class="dashicons dashicons-star-empty" aria-hidden="true"></span><h2 id="sm-pro-title"><?php esc_html_e( 'More ways to organize.', 'sheetmora' ); ?></h2><p><?php esc_html_e( 'Pro Features Preview', 'sheetmora' ); ?></p></div>
				<div class="sm-pro-body">
					<p class="sm-preview-disclosure"><?php esc_html_e( 'Available in the separately installed Pro add-on. The features below are a preview, not settings.', 'sheetmora' ); ?></p>
					<h3><?php esc_html_e( 'Organize your sheets', 'sheetmora' ); ?></h3>
					<div class="sm-preview-modes">
						<div><span class="dashicons dashicons-google" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Connect with Google', 'sheetmora' ); ?></strong><small><?php esc_html_e( 'Create and sync sheets without Apps Script', 'sheetmora' ); ?></small></span></div>
						<div><span class="dashicons dashicons-category" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Category-wise', 'sheetmora' ); ?></strong><small><?php esc_html_e( 'A separate tab for every category', 'sheetmora' ); ?></small></span></div>
						<div><span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Daily, Weekly & Monthly', 'sheetmora' ); ?></strong><small><?php esc_html_e( 'Keep orders organized by date', 'sheetmora' ); ?></small></span></div>
						<div><span class="dashicons dashicons-edit" aria-hidden="true"></span><span><strong><?php esc_html_e( 'Custom sheet names', 'sheetmora' ); ?></strong><small><?php esc_html_e( 'Your naming style, automatically', 'sheetmora' ); ?></small></span></div>
					</div>
					<p class="sm-preview-disclosure"><?php esc_html_e( 'Multiple configuration profiles: choose different rules, fields and destinations for each spreadsheet.', 'sheetmora' ); ?></p>
					<h3><?php esc_html_e( 'Extra checkout fields', 'sheetmora' ); ?></h3>
					<ul class="sm-preview-fields">
						<li><?php esc_html_e( 'Product Image Link', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Product Quantity', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Product Price', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Product Total', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Customer ID', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Product SKU', 'sheetmora' ); ?></li>
						<li><?php esc_html_e( 'Coupon Used', 'sheetmora' ); ?></li>
					</ul>
					<div class="sm-pro-extra"><span class="dashicons dashicons-networking" aria-hidden="true"></span><p><?php esc_html_e( 'Plus multiple spreadsheet destinations and optional webhook forwarding.', 'sheetmora' ); ?></p></div>
					<a class="sm-pro-link" href="https://wpmethods.com/product/sheetmora/" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Explore Pro', 'sheetmora' ); ?><span class="dashicons dashicons-arrow-right-alt" aria-hidden="true"></span></a>
					<p class="sm-free-reminder"><?php esc_html_e( 'Single Sheet is included in Free.', 'sheetmora' ); ?></p>
				</div>
			</section>
			<?php endif; ?>
		</aside>
	</div>
	<footer class="sm-footer"><span class="dashicons dashicons-privacy" aria-hidden="true"></span><p><?php esc_html_e( 'Order data is sent to Google only after you configure a destination. Review your store privacy policy and spreadsheet access before enabling.', 'sheetmora' ); ?> <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Google Privacy Policy', 'sheetmora' ); ?></a></p></footer>
</div>
