<?php
/**
 * Optional WP Methods products and community links for the settings sidebar.
 *
 * @package SM
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$sm_products        = array(
	array(
		'name'        => 'Refmora',
		'url'         => 'https://wpmethods.com/product/refmora-best-affiliate-plugin-for-woocommerce/',
		'image'       => 'sm-refmora.png',
		'description' => __( 'Track referrals, manage affiliate commissions and handle withdrawals.', 'sheetmora' ),
		'type'        => __( 'Free + Pro', 'sheetmora' ),
		'source'      => 'WPMethods.com',
	),
	array(
		'name'        => 'PartiCOD',
		'url'         => 'https://wordpress.org/plugins/particod/',
		'image'       => 'sm-particod.webp',
		'description' => __( 'Collect shipping costs or fixed and percentage advance payments.', 'sheetmora' ),
		'type'        => __( 'Free plugin', 'sheetmora' ),
		'source'      => 'WordPress.org',
	),
	array(
		'name'        => 'Order List Table',
		'url'         => 'https://wordpress.org/plugins/order-list-table-elementor-widget/',
		'image'       => 'sm-order-list-table.gif',
		'description' => __( 'Display WooCommerce orders and products in customizable tables.', 'sheetmora' ),
		'type'        => __( 'Free plugin', 'sheetmora' ),
		'source'      => 'WordPress.org',
	),
	array(
		'name'        => 'Social Chat Floating Icons',
		'url'         => 'https://wordpress.org/plugins/wpmethods-social-chat-floating-icons/',
		'image'       => 'sm-social-chat.gif',
		'description' => __( 'Help visitors contact you through their favorite chat apps.', 'sheetmora' ),
		'type'        => __( 'Free plugin', 'sheetmora' ),
		'source'      => 'WordPress.org',
	),
	array(
		'name'        => 'Click Shop',
		'url'         => 'https://wpmethods.com/product/click-shop-wordpress-landing-page-type-ecommerce-theme/',
		'image'       => 'sm-click-shop.webp',
		'description' => __( 'Create an ecommerce storefront with WooCommerce.', 'sheetmora' ),
		'type'        => __( 'Paid theme', 'sheetmora' ),
		'source'      => 'WPMethods.com',
	),
);
$sm_community_links = array(
	array(
		'url'   => 'https://buymeacoffee.com/ajharrashed',
		'icon'  => 'coffee',
		'label' => __( 'Buy me a coffee', 'sheetmora' ),
		'class' => 'sm-resource-coffee',
	),
	array(
		'url'   => 'https://wpmethods.com/sheetmora-documentation/',
		'icon'  => 'book-alt',
		'label' => __( 'Documentation', 'sheetmora' ),
		'class' => '',
	),
	array(
		'url'   => 'https://www.youtube.com/@wpmethods',
		'icon'  => 'video-alt3',
		'label' => __( 'YouTube - WP Methods', 'sheetmora' ),
		'class' => '',
	),
	array(
		'url'   => 'https://wpmethods.com',
		'icon'  => 'admin-home',
		'label' => __( 'Visit WP Methods', 'sheetmora' ),
		'class' => '',
	),
);
?>
<details class="sm-card sm-resources" open>
	<summary class="sm-resources-summary"><span class="dashicons dashicons-grid-view" aria-hidden="true"></span><span><?php esc_html_e( 'Other Products', 'sheetmora' ); ?></span><span class="dashicons dashicons-arrow-down-alt2 sm-resources-chevron" aria-hidden="true"></span></summary>
	<div class="sm-resources-body">
		<p class="sm-resources-intro"><?php esc_html_e( 'Optional tools from WP Methods.', 'sheetmora' ); ?></p>
		<ul class="sm-resource-products">
			<?php foreach ( $sm_products as $sm_product ) : ?>
				<li>
					<a class="sm-resource-product" href="<?php echo esc_url( $sm_product['url'] ); ?>" target="_blank" rel="noopener noreferrer">
						<img class="sm-resource-logo" src="<?php echo esc_url( SM_URL . 'assets/img/' . $sm_product['image'] ); ?>" width="40" height="40" alt="" loading="lazy" decoding="async">
						<span class="sm-resource-copy">
							<strong><?php echo esc_html( $sm_product['name'] ); ?></strong>
							<span class="sm-resource-description"><?php echo esc_html( $sm_product['description'] ); ?></span>
							<span class="sm-resource-meta"><span class="sm-resource-type"><?php echo esc_html( $sm_product['type'] ); ?></span><span><?php echo esc_html( $sm_product['source'] ); ?></span></span>
						</span>
						<span class="dashicons dashicons-external sm-resource-external" aria-hidden="true"></span>
						<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sheetmora' ); ?></span>
					</a>
				</li>
			<?php endforeach; ?>
		</ul>
		<section class="sm-resource-community" aria-labelledby="sm-community-title">
			<span class="sm-eyebrow"><?php esc_html_e( 'Made with care', 'sheetmora' ); ?></span>
			<h2 id="sm-community-title"><?php esc_html_e( 'Support & Connect', 'sheetmora' ); ?></h2>
			<p><?php esc_html_e( 'Enjoying SheetMora? Support the creator or explore more tutorials and tools.', 'sheetmora' ); ?></p>
			<?php foreach ( $sm_community_links as $sm_community_link ) : ?>
				<a class="sm-resource-community-link <?php echo esc_attr( $sm_community_link['class'] ); ?>" href="<?php echo esc_url( $sm_community_link['url'] ); ?>" target="_blank" rel="noopener noreferrer">
					<span class="dashicons dashicons-<?php echo esc_attr( $sm_community_link['icon'] ); ?>" aria-hidden="true"></span>
					<span><?php echo esc_html( $sm_community_link['label'] ); ?></span>
					<span class="dashicons dashicons-external sm-resource-external" aria-hidden="true"></span>
					<span class="screen-reader-text"><?php esc_html_e( '(opens in a new tab)', 'sheetmora' ); ?></span>
				</a>
			<?php endforeach; ?>
		</section>
	</div>
</details>
