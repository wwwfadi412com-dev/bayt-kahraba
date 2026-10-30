<?php
/**
 * "إدارة التسعير السريع ومزامنة المحل" — exchange-rate price engine.
 *
 * Each product stores a hidden reference price in USD (`_base_usd_price`,
 * optional `_base_usd_sale_price`). One click recalculates SYP prices for all
 * products, one brand, or one category tree using set-based SQL (thousands of
 * products in seconds), rounds to the shop's step (5,000 / 10,000 … or 50 / 100
 * for the new pound), syncs the WooCommerce lookup table and variable parents,
 * then invalidates caches. Includes dry-run preview, history log, CSV
 * price/stock sheet and a WP-CLI command.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

function bk_pricing_settings(): array {
	return wp_parse_args(
		(array) get_option( 'bk_pricing', array() ),
		array(
			'rate'         => 13000,
			'step'         => 5000,
			'mode'         => 'up',
			'auto_on_save' => 1,
			'history'      => array(),
		)
	);
}

function bk_pricing_steps(): array {
	return array(
		0     => __( 'بدون تقريب', 'baytkahraba' ),
		50    => '50',
		100   => '100',
		500   => '500',
		1000  => '1,000',
		5000  => '5,000',
		10000 => '10,000',
	);
}

/**
 * Round a SYP amount to the configured step.
 */
function bk_pricing_round( float $amount, int $step, string $mode = 'up' ): float {
	if ( $step < 2 ) {
		return round( $amount, wc_get_price_decimals() );
	}
	$ratio = $amount / $step;
	$value = 'nearest' === $mode ? round( $ratio ) : ( 'down' === $mode ? floor( $ratio ) : ceil( $ratio ) );
	return (float) ( $value * $step );
}

/**
 * Same rounding as SQL expression (applied to a DECIMAL expression).
 */
function bk_pricing_sql_expr( string $usd_expr, float $rate, int $step, string $mode ): string {
	$rate = number_format( (float) $rate, 6, '.', '' ); // Never scientific notation inside SQL.
	$step = (int) $step;
	$raw  = "( {$usd_expr} * {$rate} )";
	if ( $step < 2 ) {
		return 'ROUND(' . $raw . ', ' . (int) wc_get_price_decimals() . ')';
	}
	$fn = 'nearest' === $mode ? 'ROUND' : ( 'down' === $mode ? 'FLOOR' : 'CEIL' );
	return "( {$fn}( {$raw} / {$step} ) * {$step} )";
}

/**
 * Resolve target product + variation IDs for a scope.
 *
 * @param string $scope   all|brand|cat.
 * @param int    $term_id Brand or category term ID.
 * @return int[]
 */
function bk_pricing_target_ids( string $scope, int $term_id = 0 ): array {
	global $wpdb;
	$join = '';
	if ( in_array( $scope, array( 'brand', 'cat' ), true ) && $term_id ) {
		$taxonomy = 'brand' === $scope ? BK_BRAND_TAX : 'product_cat';
		$terms    = array( $term_id );
		if ( 'cat' === $scope ) {
			$terms = array_merge( $terms, (array) get_term_children( $term_id, 'product_cat' ) );
		}
		$tt_ids = array_map( 'intval', (array) get_terms( array( 'taxonomy' => $taxonomy, 'include' => $terms, 'fields' => 'tt_ids', 'hide_empty' => false ) ) );
		if ( ! $tt_ids ) {
			return array();
		}
		$join = " INNER JOIN {$wpdb->term_relationships} tr ON tr.object_id = p.ID AND tr.term_taxonomy_id IN (" . implode( ',', $tt_ids ) . ')';
	}
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$parents = array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT p.ID FROM {$wpdb->posts} p {$join} WHERE p.post_type = 'product' AND p.post_status IN ('publish','private','draft','pending')" ) );
	if ( ! $parents ) {
		return array();
	}
	$variations = array();
	foreach ( array_chunk( $parents, 1000 ) as $chunk ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$variations = array_merge( $variations, array_map( 'intval', $wpdb->get_col( "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND post_parent IN (" . implode( ',', $chunk ) . ')' ) ) );
	}
	return array_values( array_unique( array_merge( $parents, $variations ) ) );
}

/**
 * Recalculate SYP prices from USD base prices.
 *
 * @param array $args { rate: float, step: int, mode: up|nearest|down, scope: all|brand|cat, term_id: int, include_sale: bool, dry_run: bool }.
 * @return array{updated: int, total: int, ms: int, preview: array}
 */
function bk_pricing_sync( array $args ): array {
	global $wpdb;
	$started = microtime( true );
	$args    = wp_parse_args( $args, array( 'rate' => 0, 'step' => 0, 'mode' => 'up', 'scope' => 'all', 'term_id' => 0, 'include_sale' => true, 'dry_run' => false ) );
	$rate    = (float) $args['rate'];
	$step    = (int) $args['step'];
	$mode    = in_array( $args['mode'], array( 'up', 'nearest', 'down' ), true ) ? $args['mode'] : 'up';

	if ( $rate <= 0 ) {
		return array( 'updated' => 0, 'total' => 0, 'ms' => 0, 'preview' => array(), 'error' => __( 'سعر الصرف غير صالح', 'baytkahraba' ) );
	}

	$ids = bk_pricing_target_ids( (string) $args['scope'], (int) $args['term_id'] );

	// Keep only IDs that actually have a USD base price.
	$priced = array();
	foreach ( array_chunk( $ids, 1000 ) as $chunk ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$priced = array_merge( $priced, array_map( 'intval', $wpdb->get_col( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_base_usd_price' AND meta_value <> '' AND CAST(meta_value AS DECIMAL(18,4)) > 0 AND post_id IN (" . implode( ',', $chunk ) . ')' ) ) );
	}

	if ( $args['dry_run'] ) {
		$preview = array();
		foreach ( array_slice( $priced, 0, 15 ) as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$base      = (float) get_post_meta( $id, '_base_usd_price', true );
			$preview[] = array(
				'name' => $product->get_formatted_name(),
				'usd'  => $base,
				'old'  => (float) $product->get_regular_price(),
				'new'  => bk_pricing_round( $base * $rate, $step, $mode ),
			);
		}
		return array( 'updated' => 0, 'total' => count( $priced ), 'ms' => (int) ( ( microtime( true ) - $started ) * 1000 ), 'preview' => $preview );
	}

	$base_expr = 'CAST(b.meta_value AS DECIMAL(18,4))';
	$new_price = bk_pricing_sql_expr( $base_expr, $rate, $step, $mode );
	$now       = time();
	$updated   = 0;

	foreach ( array_chunk( $priced, 500 ) as $chunk ) {
		$in = implode( ',', array_map( 'intval', $chunk ) );

		// 1) Guarantee the meta rows exist (products created by importers may lack them).
		foreach ( array( '_regular_price', '_price', '_sale_price' ) as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "INSERT INTO {$wpdb->postmeta} (post_id, meta_key, meta_value) SELECT b.post_id, '{$key}', '' FROM {$wpdb->postmeta} b LEFT JOIN {$wpdb->postmeta} x ON x.post_id = b.post_id AND x.meta_key = '{$key}' WHERE b.meta_key = '_base_usd_price' AND b.post_id IN ({$in}) AND x.meta_id IS NULL" );
		}

		// 2) Regular price.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$updated += (int) $wpdb->query( "UPDATE {$wpdb->postmeta} r INNER JOIN {$wpdb->postmeta} b ON b.post_id = r.post_id AND b.meta_key = '_base_usd_price' SET r.meta_value = {$new_price} WHERE r.meta_key = '_regular_price' AND r.post_id IN ({$in})" );

		// 3) Sale price (only where a USD sale reference exists).
		if ( $args['include_sale'] ) {
			$sale_price = bk_pricing_sql_expr( 'CAST(bs.meta_value AS DECIMAL(18,4))', $rate, $step, $mode );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$wpdb->query( "UPDATE {$wpdb->postmeta} s INNER JOIN {$wpdb->postmeta} bs ON bs.post_id = s.post_id AND bs.meta_key = '_base_usd_sale_price' AND bs.meta_value <> '' AND CAST(bs.meta_value AS DECIMAL(18,4)) > 0 SET s.meta_value = {$sale_price} WHERE s.meta_key = '_sale_price' AND s.post_id IN ({$in})" );
		}

		// 4) Active price respects scheduled sales.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"UPDATE {$wpdb->postmeta} p
			 INNER JOIN {$wpdb->postmeta} r ON r.post_id = p.post_id AND r.meta_key = '_regular_price'
			 LEFT JOIN {$wpdb->postmeta} s ON s.post_id = p.post_id AND s.meta_key = '_sale_price'
			 LEFT JOIN {$wpdb->postmeta} sf ON sf.post_id = p.post_id AND sf.meta_key = '_sale_price_dates_from'
			 LEFT JOIN {$wpdb->postmeta} st ON st.post_id = p.post_id AND st.meta_key = '_sale_price_dates_to'
			 SET p.meta_value = IF(
				s.meta_value <> '' AND CAST(s.meta_value AS DECIMAL(18,4)) > 0
				AND CAST(s.meta_value AS DECIMAL(18,4)) < CAST(r.meta_value AS DECIMAL(18,4))
				AND ( sf.meta_value IS NULL OR sf.meta_value = '' OR CAST(sf.meta_value AS UNSIGNED) <= {$now} )
				AND ( st.meta_value IS NULL OR st.meta_value = '' OR CAST(st.meta_value AS UNSIGNED) > {$now} ),
				s.meta_value, r.meta_value )
			 WHERE p.meta_key = '_price' AND p.post_id IN ({$in})"
		);

		// 5) Lookup table (sorting & price filters read from here).
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query(
			"UPDATE {$wpdb->wc_product_meta_lookup} l
			 INNER JOIN {$wpdb->postmeta} p ON p.post_id = l.product_id AND p.meta_key = '_price'
			 LEFT JOIN {$wpdb->postmeta} r ON r.post_id = l.product_id AND r.meta_key = '_regular_price'
			 SET l.min_price = CAST(p.meta_value AS DECIMAL(19,4)), l.max_price = CAST(p.meta_value AS DECIMAL(19,4)),
			     l.onsale = IF(CAST(p.meta_value AS DECIMAL(19,4)) < CAST(r.meta_value AS DECIMAL(19,4)), 1, 0)
			 WHERE l.product_id IN ({$in})"
		);

		foreach ( $chunk as $id ) {
			wp_cache_delete( $id, 'post_meta' );
			if ( class_exists( 'WC_Cache_Helper' ) ) {
				WC_Cache_Helper::invalidate_cache_group( 'product_' . $id );
			}
		}
	}

	// 6) Variable parents: recompute their price ranges.
	if ( $priced ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$parents = array_map( 'intval', $wpdb->get_col( "SELECT DISTINCT post_parent FROM {$wpdb->posts} WHERE post_type = 'product_variation' AND ID IN (" . implode( ',', $priced ) . ')' ) );
		foreach ( $parents as $parent_id ) {
			WC_Product_Variable::sync( $parent_id );
			wc_delete_product_transients( $parent_id );
		}
	}

	wc_delete_product_transients();
	bk_cache_bump();

	$result = array( 'updated' => $updated, 'total' => count( $priced ), 'ms' => (int) ( ( microtime( true ) - $started ) * 1000 ), 'preview' => array() );
	do_action( 'bk_prices_synced', $result, $args );
	return $result;
}

/**
 * Persist a history row (last 20 runs).
 */
function bk_pricing_log( array $args, array $result ): void {
	$settings = bk_pricing_settings();
	array_unshift(
		$settings['history'],
		array(
			'time'    => time(),
			'user'    => wp_get_current_user()->display_name ?: 'CLI',
			'rate'    => (float) $args['rate'],
			'step'    => (int) $args['step'],
			'scope'   => bk_pricing_scope_label( (string) $args['scope'], (int) $args['term_id'] ),
			'updated' => (int) $result['total'],
			'ms'      => (int) $result['ms'],
		)
	);
	$settings['history'] = array_slice( $settings['history'], 0, 20 );
	update_option( 'bk_pricing', $settings, false );
}

function bk_pricing_scope_label( string $scope, int $term_id ): string {
	if ( 'brand' === $scope && ( $term = get_term( $term_id, BK_BRAND_TAX ) ) instanceof WP_Term ) {
		return sprintf( __( 'ماركة: %s', 'baytkahraba' ), $term->name );
	}
	if ( 'cat' === $scope && ( $term = get_term( $term_id, 'product_cat' ) ) instanceof WP_Term ) {
		return sprintf( __( 'تصنيف: %s', 'baytkahraba' ), $term->name );
	}
	return __( 'كل المنتجات', 'baytkahraba' );
}

/* -------------------------------------------------------------------------
 * Auto-price on save & CSV import
 * ---------------------------------------------------------------------- */

function bk_pricing_apply_to_product( WC_Product $product ): void {
	$settings = bk_pricing_settings();
	$base     = (float) $product->get_meta( '_base_usd_price' );
	if ( ! $settings['auto_on_save'] || $base <= 0 || $product->is_type( 'variable' ) ) {
		return;
	}
	$product->set_regular_price( (string) bk_pricing_round( $base * (float) $settings['rate'], (int) $settings['step'], (string) $settings['mode'] ) );
	$sale = (float) $product->get_meta( '_base_usd_sale_price' );
	if ( $sale > 0 ) {
		$product->set_sale_price( (string) bk_pricing_round( $sale * (float) $settings['rate'], (int) $settings['step'], (string) $settings['mode'] ) );
	}
}
add_action( 'woocommerce_admin_process_product_object', 'bk_pricing_apply_to_product', 20 );
add_filter(
	'woocommerce_product_import_pre_insert_product_object',
	static function ( WC_Product $product ): WC_Product {
		bk_pricing_apply_to_product( $product );
		return $product;
	},
	20
);

/* -------------------------------------------------------------------------
 * Admin screen
 * ---------------------------------------------------------------------- */

add_action(
	'admin_menu',
	static function (): void {
		add_submenu_page( BK_ADMIN_SLUG, __( 'إدارة التسعير السريع ومزامنة المحل', 'baytkahraba' ), __( 'التسعير ومزامنة المحل', 'baytkahraba' ), 'manage_woocommerce', 'bk-pricing', 'bk_render_pricing_page' );
	},
	20
);

add_action( 'admin_post_bk_pricing_sync', 'bk_handle_pricing_sync' );
add_action( 'admin_post_bk_pricing_export', 'bk_handle_pricing_export' );
add_action( 'admin_post_bk_pricing_import', 'bk_handle_pricing_import' );

function bk_pricing_request_args(): array {
	// phpcs:disable WordPress.Security.NonceVerification -- verified by caller.
	$scope = sanitize_key( wp_unslash( $_POST['scope'] ?? 'all' ) );
	return array(
		'rate'         => (float) wc_format_decimal( wp_unslash( $_POST['rate'] ?? 0 ) ),
		'step'         => array_key_exists( (int) ( $_POST['step'] ?? 0 ), bk_pricing_steps() ) ? (int) $_POST['step'] : 0,
		'mode'         => sanitize_key( wp_unslash( $_POST['mode'] ?? 'up' ) ),
		'scope'        => in_array( $scope, array( 'all', 'brand', 'cat' ), true ) ? $scope : 'all',
		'term_id'      => 'brand' === $scope ? absint( $_POST['brand_id'] ?? 0 ) : absint( $_POST['cat_id'] ?? 0 ),
		'include_sale' => ! empty( $_POST['include_sale'] ),
		'dry_run'      => isset( $_POST['preview'] ),
	);
	// phpcs:enable
}

function bk_handle_pricing_sync(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'غير مصرح', 'baytkahraba' ), 403 );
	}
	check_admin_referer( 'bk_pricing_sync' );
	$args = bk_pricing_request_args();

	// Remember the rate & rounding for next time and for auto-on-save.
	$settings                 = bk_pricing_settings();
	$settings['rate']         = $args['rate'];
	$settings['step']         = $args['step'];
	$settings['mode']         = $args['mode'];
	$settings['auto_on_save'] = ! empty( $_POST['auto_on_save'] ) ? 1 : 0;
	update_option( 'bk_pricing', $settings, false );

	$result = bk_pricing_sync( $args );
	if ( ! $args['dry_run'] && empty( $result['error'] ) ) {
		bk_pricing_log( $args, $result );
	}
	set_transient( 'bk_pricing_result_' . get_current_user_id(), array( 'args' => $args, 'result' => $result ), 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=bk-pricing&done=1' ) );
	exit;
}

function bk_render_pricing_page(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		return;
	}
	global $wpdb;
	$settings = bk_pricing_settings();
	$flash    = get_transient( 'bk_pricing_result_' . get_current_user_id() );
	delete_transient( 'bk_pricing_result_' . get_current_user_id() );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery
	$with_base = (int) $wpdb->get_var( "SELECT COUNT(DISTINCT pm.post_id) FROM {$wpdb->postmeta} pm INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id AND p.post_type IN ('product','product_variation') WHERE pm.meta_key = '_base_usd_price' AND pm.meta_value <> '' AND CAST(pm.meta_value AS DECIMAL(18,4)) > 0" );
	$total     = (int) ( wp_count_posts( 'product' )->publish ?? 0 );
	?>
	<div class="wrap bk-admin">
		<h1><?php esc_html_e( 'إدارة التسعير السريع ومزامنة المحل', 'baytkahraba' ); ?></h1>
		<p class="bk-admin-lead"><?php esc_html_e( 'اكتب سعر الصرف المرجعي لليوم واضغط "تحديث جميع الأسعار فوراً" — يُعاد حساب سعر كل منتج بالليرة من سعره المرجعي بالدولار ويُقرّب تلقائياً، فيطابق المتجر سعر المحل 100٪.', 'baytkahraba' ); ?></p>

		<?php if ( $flash ) : ?>
			<?php $r = $flash['result']; ?>
			<?php if ( ! empty( $r['error'] ) ) : ?>
				<div class="notice notice-error"><p><?php echo esc_html( $r['error'] ); ?></p></div>
			<?php elseif ( $flash['args']['dry_run'] ) : ?>
				<div class="notice notice-info">
					<p><strong><?php printf( esc_html__( 'معاينة: سيتم تحديث %1$s منتج/تنويعة (%2$s).', 'baytkahraba' ), esc_html( number_format_i18n( $r['total'] ) ), esc_html( bk_pricing_scope_label( $flash['args']['scope'], $flash['args']['term_id'] ) ) ); ?></strong></p>
					<table class="widefat striped bk-preview-table"><thead><tr><th><?php esc_html_e( 'المنتج', 'baytkahraba' ); ?></th><th>USD</th><th><?php esc_html_e( 'السعر الحالي', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'السعر الجديد', 'baytkahraba' ); ?></th></tr></thead><tbody>
					<?php foreach ( $r['preview'] as $row ) : ?>
						<tr><td><?php echo esc_html( wp_strip_all_tags( $row['name'] ) ); ?></td><td>$<?php echo esc_html( $row['usd'] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['old'] ) ); ?></td><td><strong><?php echo esc_html( number_format_i18n( $row['new'] ) ); ?></strong></td></tr>
					<?php endforeach; ?>
					</tbody></table>
				</div>
			<?php else : ?>
				<div class="notice notice-success"><p><?php printf( esc_html__( 'تم تحديث أسعار %1$s منتج/تنويعة خلال %2$s ميلي ثانية. المتجر الآن مطابق لسعر المحل.', 'baytkahraba' ), '<strong>' . esc_html( number_format_i18n( $r['total'] ) ) . '</strong>', esc_html( number_format_i18n( $r['ms'] ) ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?></p></div>
			<?php endif; ?>
		<?php endif; ?>

		<div class="bk-admin-grid">
			<div class="bk-admin-card">
				<h2><?php esc_html_e( 'سعر الصرف المرجعي اليومي + التقريب التلقائي', 'baytkahraba' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-bk-pricing-form>
					<?php wp_nonce_field( 'bk_pricing_sync' ); ?>
					<input type="hidden" name="action" value="bk_pricing_sync">
					<table class="form-table" role="presentation">
						<tr><th><label for="bk-rate"><?php esc_html_e( 'سعر صرف الدولار (ل.س)', 'baytkahraba' ); ?></label></th>
							<td><input type="number" step="any" min="1" id="bk-rate" name="rate" class="bk-big-input" value="<?php echo esc_attr( (string) $settings['rate'] ); ?>" required></td></tr>
						<tr><th><?php esc_html_e( 'التقريب لأقرب', 'baytkahraba' ); ?></th>
							<td><?php bk_admin_field( 'step', 'select', $settings['step'], array( 'options' => bk_pricing_steps() ) ); ?>
								<?php bk_admin_field( 'mode', 'select', $settings['mode'], array( 'options' => array( 'up' => __( 'للأعلى (موصى به)', 'baytkahraba' ), 'nearest' => __( 'للأقرب', 'baytkahraba' ), 'down' => __( 'للأسفل', 'baytkahraba' ) ) ) ); ?></td></tr>
						<tr><th><?php esc_html_e( 'نطاق التحديث', 'baytkahraba' ); ?></th>
							<td>
								<label><input type="radio" name="scope" value="all" checked> <?php esc_html_e( 'كل المنتجات', 'baytkahraba' ); ?></label><br>
								<label><input type="radio" name="scope" value="brand"> <?php esc_html_e( 'ماركة محددة:', 'baytkahraba' ); ?></label>
								<?php wp_dropdown_categories( array( 'taxonomy' => BK_BRAND_TAX, 'name' => 'brand_id', 'hide_empty' => false, 'hierarchical' => true ) ); ?><br>
								<label><input type="radio" name="scope" value="cat"> <?php esc_html_e( 'تصنيف (أب أو ابن مع كل فروعه):', 'baytkahraba' ); ?></label>
								<?php wp_dropdown_categories( array( 'taxonomy' => 'product_cat', 'name' => 'cat_id', 'hide_empty' => false, 'hierarchical' => true ) ); ?>
							</td></tr>
						<tr><th><?php esc_html_e( 'خيارات', 'baytkahraba' ); ?></th>
							<td>
								<label><input type="checkbox" name="include_sale" value="1" checked> <?php esc_html_e( 'تحديث أسعار العروض المرتبطة بسعر دولار', 'baytkahraba' ); ?></label><br>
								<label><input type="checkbox" name="auto_on_save" value="1" <?php checked( (int) $settings['auto_on_save'], 1 ); ?>> <?php esc_html_e( 'حساب السعر بالليرة تلقائياً عند حفظ/استيراد أي منتج', 'baytkahraba' ); ?></label>
							</td></tr>
					</table>
					<p class="bk-actions">
						<button type="submit" name="preview" value="1" class="button button-secondary button-hero"><?php esc_html_e( 'معاينة قبل التحديث', 'baytkahraba' ); ?></button>
						<button type="submit" name="apply" value="1" class="button button-primary button-hero bk-apply"><?php esc_html_e( 'تحديث جميع الأسعار فوراً', 'baytkahraba' ); ?></button>
					</p>
				</form>
			</div>

			<div class="bk-admin-card">
				<h2><?php esc_html_e( 'حالة التسعير', 'baytkahraba' ); ?></h2>
				<p class="bk-stat"><span><?php echo esc_html( number_format_i18n( $with_base ) ); ?></span> <?php esc_html_e( 'منتج/تنويعة مرتبطة بسعر دولار مرجعي', 'baytkahraba' ); ?></p>
				<p class="bk-stat"><span><?php echo esc_html( number_format_i18n( $total ) ); ?></span> <?php esc_html_e( 'منتج منشور', 'baytkahraba' ); ?></p>

				<h3><?php esc_html_e( 'ورقة الأسعار والمخزون (Excel / CSV)', 'baytkahraba' ); ?></h3>
				<p><a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=bk_pricing_export' ), 'bk_pricing_export' ) ); ?>"><?php esc_html_e( 'تصدير ورقة الأسعار والمخزون', 'baytkahraba' ); ?></a></p>
				<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'bk_pricing_import' ); ?>
					<input type="hidden" name="action" value="bk_pricing_import">
					<input type="file" name="bk_csv" accept=".csv,text/csv" required>
					<label><input type="checkbox" name="recalc" value="1" checked> <?php esc_html_e( 'إعادة حساب الليرة بعد الاستيراد', 'baytkahraba' ); ?></label>
					<?php submit_button( __( 'استيراد وتحديث', 'baytkahraba' ), 'secondary', 'submit', false ); ?>
				</form>
				<p class="description"><?php esc_html_e( 'الأعمدة: id, sku, base_usd_price, base_usd_sale_price, stock_quantity, stock_status. يكفي id أو sku. للاستيراد الكامل للمنتجات استخدم أداة ووكومرس الأصلية (الحقول المخصصة مدعومة).', 'baytkahraba' ); ?></p>
			</div>
		</div>

		<?php if ( $settings['history'] ) : ?>
			<div class="bk-admin-card">
				<h2><?php esc_html_e( 'سجل التحديثات', 'baytkahraba' ); ?></h2>
				<table class="widefat striped"><thead><tr><th><?php esc_html_e( 'التاريخ', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'المستخدم', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'سعر الصرف', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'التقريب', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'النطاق', 'baytkahraba' ); ?></th><th><?php esc_html_e( 'العدد', 'baytkahraba' ); ?></th></tr></thead><tbody>
				<?php foreach ( $settings['history'] as $row ) : ?>
					<tr><td><?php echo esc_html( wp_date( 'Y-m-d H:i', (int) $row['time'] ) ); ?></td><td><?php echo esc_html( $row['user'] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['rate'], 2 ) ); ?></td><td><?php echo esc_html( number_format_i18n( $row['step'] ) ); ?></td><td><?php echo esc_html( $row['scope'] ); ?></td><td><?php echo esc_html( number_format_i18n( $row['updated'] ) ); ?></td></tr>
				<?php endforeach; ?>
				</tbody></table>
			</div>
		<?php endif; ?>
	</div>
	<?php
}

/* -------------------------------------------------------------------------
 * CSV price & stock sheet
 * ---------------------------------------------------------------------- */

function bk_handle_pricing_export(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'غير مصرح', 'baytkahraba' ), 403 );
	}
	check_admin_referer( 'bk_pricing_export' );

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=bk-prices-' . gmdate( 'Y-m-d' ) . '.csv' );
	$out = fopen( 'php://output', 'w' );
	fwrite( $out, "\xEF\xBB\xBF" ); // UTF-8 BOM so Excel shows Arabic correctly.
	fputcsv( $out, array( 'id', 'sku', 'name', 'brand', 'category', 'base_usd_price', 'base_usd_sale_price', 'regular_price', 'sale_price', 'stock_status', 'stock_quantity' ) );

	$page = 1;
	do {
		$ids = wc_get_products( array( 'type' => array( 'simple', 'variation', 'external' ), 'limit' => 200, 'page' => $page++, 'return' => 'ids', 'status' => array( 'publish', 'private', 'draft' ) ) );
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( ! $product ) {
				continue;
			}
			$parent_id = $product->get_parent_id() ?: $id;
			$brand     = bk_get_product_brand( $parent_id );
			$cat       = bk_product_primary_category( $parent_id );
			fputcsv(
				$out,
				array(
					$id,
					$product->get_sku(),
					wp_strip_all_tags( $product->get_formatted_name() ),
					$brand ? $brand->name : '',
					$cat ? $cat->name : '',
					$product->get_meta( '_base_usd_price' ),
					$product->get_meta( '_base_usd_sale_price' ),
					$product->get_regular_price(),
					$product->get_sale_price(),
					$product->get_stock_status(),
					$product->get_stock_quantity(),
				)
			);
		}
	} while ( count( $ids ) === 200 );
	fclose( $out ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	exit;
}

function bk_handle_pricing_import(): void {
	if ( ! current_user_can( 'manage_woocommerce' ) ) {
		wp_die( esc_html__( 'غير مصرح', 'baytkahraba' ), 403 );
	}
	check_admin_referer( 'bk_pricing_import' );

	$file = $_FILES['bk_csv'] ?? null; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
	if ( ! $file || UPLOAD_ERR_OK !== (int) $file['error'] || $file['size'] > 5 * MB_IN_BYTES || ! preg_match( '/\.csv$/i', (string) $file['name'] ) ) {
		wp_die( esc_html__( 'ملف CSV غير صالح (الحد الأقصى 5MB).', 'baytkahraba' ) );
	}

	$handle = fopen( $file['tmp_name'], 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	$first  = (string) fgets( $handle );
	$delim  = substr_count( $first, ';' ) > substr_count( $first, ',' ) ? ';' : ',';
	$header = array_map( static fn( $h ) => sanitize_key( trim( str_replace( "\xEF\xBB\xBF", '', $h ) ) ), str_getcsv( $first, $delim ) );
	$count  = 0;

	while ( ( $row = fgetcsv( $handle, 0, $delim ) ) !== false ) {
		$data = array_combine( $header, array_pad( array_slice( $row, 0, count( $header ) ), count( $header ), '' ) );
		$id   = absint( $data['id'] ?? 0 ) ?: wc_get_product_id_by_sku( (string) ( $data['sku'] ?? '' ) );
		$prod = $id ? wc_get_product( $id ) : null;
		if ( ! $prod ) {
			continue;
		}
		foreach ( array( 'base_usd_price', 'base_usd_sale_price' ) as $key ) {
			if ( isset( $data[ $key ] ) && '' !== trim( $data[ $key ] ) ) {
				$prod->update_meta_data( '_' . $key, wc_format_decimal( $data[ $key ] ) );
			}
		}
		if ( isset( $data['stock_quantity'] ) && '' !== trim( $data['stock_quantity'] ) ) {
			$prod->set_manage_stock( true );
			$prod->set_stock_quantity( (int) $data['stock_quantity'] );
		} elseif ( ! empty( $data['stock_status'] ) && in_array( $data['stock_status'], array( 'instock', 'outofstock', 'onbackorder' ), true ) ) {
			$prod->set_stock_status( $data['stock_status'] );
		}
		if ( ! empty( $_POST['recalc'] ) ) {
			bk_pricing_apply_to_product( $prod );
		}
		$prod->save();
		++$count;
	}
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions
	bk_cache_bump();

	set_transient( 'bk_pricing_result_' . get_current_user_id(), array( 'args' => array( 'dry_run' => false, 'scope' => 'all', 'term_id' => 0 ), 'result' => array( 'total' => $count, 'ms' => 0, 'updated' => $count ) ), 300 );
	wp_safe_redirect( admin_url( 'admin.php?page=bk-pricing&done=1' ) );
	exit;
}

/* -------------------------------------------------------------------------
 * WP-CLI: wp bk prices --rate=13000 --step=5000 [--mode=up] [--brand=slug] [--cat=slug] [--dry-run]
 * ---------------------------------------------------------------------- */

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command(
		'bk prices',
		static function ( array $positional, array $assoc ): void {
			$settings = bk_pricing_settings();
			$scope    = 'all';
			$term_id  = 0;
			if ( ! empty( $assoc['brand'] ) && ( $t = get_term_by( 'slug', $assoc['brand'], BK_BRAND_TAX ) ) ) {
				$scope   = 'brand';
				$term_id = $t->term_id;
			} elseif ( ! empty( $assoc['cat'] ) && ( $t = get_term_by( 'slug', $assoc['cat'], 'product_cat' ) ) ) {
				$scope   = 'cat';
				$term_id = $t->term_id;
			}
			$args   = array(
				'rate'         => (float) ( $assoc['rate'] ?? $settings['rate'] ),
				'step'         => (int) ( $assoc['step'] ?? $settings['step'] ),
				'mode'         => (string) ( $assoc['mode'] ?? $settings['mode'] ),
				'scope'        => $scope,
				'term_id'      => $term_id,
				'include_sale' => true,
				'dry_run'      => isset( $assoc['dry-run'] ),
			);
			$result = bk_pricing_sync( $args );
			if ( ! empty( $result['error'] ) ) {
				WP_CLI::error( $result['error'] );
			}
			if ( ! $args['dry_run'] ) {
				bk_pricing_log( $args, $result );
			}
			WP_CLI::success( sprintf( '%d products %s in %dms', $result['total'], $args['dry_run'] ? 'would be updated' : 'updated', $result['ms'] ) );
		}
	);
}
