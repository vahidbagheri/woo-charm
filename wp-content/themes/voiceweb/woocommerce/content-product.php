<?php
/**
 * WooCommerce product-loop template. Native hooks are preserved inside the shared card.
 * @version 9.4.0
 */
defined( 'ABSPATH' ) || exit;
get_template_part( 'template-parts/woocommerce/product-card', null, array( 'product' => $GLOBALS['product'] ?? null ) );
