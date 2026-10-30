<?php
/**
 * Parent → Child → Sub-child category engine + Brand × Category matrix.
 *
 * One cached "catalog matrix" answers every structural question the theme asks:
 * which brands exist under which category (including descendants), product
 * counts per category / brand / intersection. Mega menu, brand hubs, silo
 * routes, sitemaps, internal links and llms.txt all read from it, so adding a
 * category or brand in the dashboard updates the whole site automatically.
 *
 * @package BaytKahraba
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------------------------
 * Category term meta (weight class, keyword profile, custom buying guide)
 * ---------------------------------------------------------------------- */

function bk_category_meta_schema(): array {
	$profiles = array( '' => __( 'تلقائي (حسب اسم التصنيف أو الأب)', 'baytkahraba' ) );
	if ( function_exists( 'bk_keyword_profiles' ) ) {
		foreach ( bk_keyword_profiles() as $key => $profile ) {
			$profiles[ $key ] = $profile['label'];
		}
	}
	return array(
		'bk_weight_class'    => array(
			'label'   => __( 'فئة الوزن للشحن', 'baytkahraba' ),
			'type'    => 'select',
			'options' => array(
				''      => __( 'وراثة من التصنيف الأب', 'baytkahraba' ),
				'small' => __( 'أجهزة صغيرة (شحن خفيف)', 'baytkahraba' ),
				'heavy' => __( 'أجهزة كبيرة (شحن ثقيل)', 'baytkahraba' ),
			),
			'help'    => __( 'تحدد تكلفة الشحن للمحافظات تلقائياً.', 'baytkahraba' ),
		),
		'bk_keyword_profile' => array(
			'label'   => __( 'ملف الكلمات المفتاحية', 'baytkahraba' ),
			'type'    => 'select',
			'options' => $profiles,
			'help'    => __( 'يحدد المرادفات المحلية (براد/ثلاجة، شاشة/تلفزيون…) ونصائح دليل الشراء المولّد.', 'baytkahraba' ),
		),
		'bk_seo_intro'       => array(
			'label' => __( 'مقدمة SEO مخصصة (أعلى الصفحة)', 'baytkahraba' ),
			'type'  => 'textarea',
			'help'  => __( 'اختياري — 2 إلى 3 جمل. تُولّد تلقائياً إن تُركت فارغة.', 'baytkahraba' ),
		),
		'bk_seo_guide'       => array(
			'label' => __( 'دليل الشراء المخصص (HTML أسفل المنتجات)', 'baytkahraba' ),
			'type'  => 'textarea',
			'help'  => __( 'اختياري — يُولّد دليل شراء كامل بعناوين H2/H3 تلقائياً إن تُرك فارغاً.', 'baytkahraba' ),
		),
	);
}

add_action(
	'product_cat_add_form_fields',
	static function (): void {
		wp_nonce_field( 'bk_cat_meta', 'bk_cat_meta_nonce' );
		foreach ( bk_category_meta_schema() as $key => $field ) {
			echo '<div class="form-field"><label>' . esc_html( $field['label'] ) . '</label>';
			bk_admin_field( $key, $field['type'], '', array( 'options' => $field['options'] ?? array(), 'help' => $field['help'], 'rows' => 5 ) );
			echo '</div>';
		}
	},
	20
);

add_action(
	'product_cat_edit_form_fields',
	static function ( WP_Term $term ): void {
		wp_nonce_field( 'bk_cat_meta', 'bk_cat_meta_nonce' );
		foreach ( bk_category_meta_schema() as $key => $field ) {
			echo '<tr class="form-field"><th scope="row"><label>' . esc_html( $field['label'] ) . '</label></th><td>';
			bk_admin_field( $key, $field['type'], get_term_meta( $term->term_id, $key, true ), array( 'options' => $field['options'] ?? array(), 'help' => $field['help'], 'rows' => 8 ) );
			echo '</td></tr>';
		}
	},
	20
);

function bk_save_category_meta( int $term_id ): void {
	if ( ! isset( $_POST['bk_cat_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bk_cat_meta_nonce'] ) ), 'bk_cat_meta' ) ) {
		return;
	}
	foreach ( array_keys( bk_category_meta_schema() ) as $key ) {
		if ( ! isset( $_POST[ $key ] ) ) {
			continue;
		}
		$raw   = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput
		$value = in_array( $key, array( 'bk_seo_guide', 'bk_seo_intro' ), true ) ? wp_kses_post( $raw ) : sanitize_key( $raw );
		'' === $value ? delete_term_meta( $term_id, $key ) : update_term_meta( $term_id, $key, $value );
	}
}
add_action( 'created_product_cat', 'bk_save_category_meta' );
add_action( 'edited_product_cat', 'bk_save_category_meta' );

/* -------------------------------------------------------------------------
 * Cache invalidation — any structural change bumps the global cache version.
 * ---------------------------------------------------------------------- */

add_action(
	'init',
	static function (): void {
		$taxonomies = array( 'product_cat', BK_BRAND_TAX );
		foreach ( array( 'created_term', 'edited_term', 'delete_term' ) as $hook ) {
			add_action(
				$hook,
				static function ( $term_id, $tt_id, $taxonomy ) use ( $taxonomies ) {
					if ( in_array( $taxonomy, $taxonomies, true ) ) {
						bk_cache_bump();
					}
				},
				10,
				3
			);
		}
	}
);
add_action(
	'save_post_product',
	static function ( int $post_id ): void {
		if ( ! wp_is_post_revision( $post_id ) && ! wp_is_post_autosave( $post_id ) ) {
			bk_cache_bump();
		}
	}
);
add_action(
	'set_object_terms',
	static function ( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( in_array( $taxonomy, array( 'product_cat', BK_BRAND_TAX ), true ) ) {
			bk_cache_bump();
		}
	},
	10,
	4
);
foreach ( array( 'trashed_post', 'untrashed_post', 'deleted_post' ) as $bk_hook ) {
	add_action(
		$bk_hook,
		static function ( $post_id ) {
			if ( 'product' === get_post_type( $post_id ) ) {
				bk_cache_bump();
			}
		}
	);
}
unset( $bk_hook );

/* -------------------------------------------------------------------------
 * Catalog matrix
 * ---------------------------------------------------------------------- */

/**
 * id => parent map for all product categories.
 *
 * @return array<int,int>
 */
function bk_category_parent_map(): array {
	return (array) bk_remember(
		'cat-parent-map',
		static function () {
			global $wpdb;
			$rows = $wpdb->get_results( "SELECT term_id, parent FROM {$wpdb->term_taxonomy} WHERE taxonomy = 'product_cat'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$map  = array();
			foreach ( $rows as $row ) {
				$map[ (int) $row->term_id ] = (int) $row->parent;
			}
			return $map;
		}
	);
}

/**
 * Ancestor IDs of a category, nearest first.
 *
 * @return int[]
 */
function bk_category_ancestor_ids( int $term_id ): array {
	$map       = bk_category_parent_map();
	$ancestors = array();
	$guard     = 0;
	while ( ! empty( $map[ $term_id ] ) && $guard++ < 10 ) {
		$term_id     = $map[ $term_id ];
		$ancestors[] = $term_id;
	}
	return $ancestors;
}

/**
 * 1 = parent, 2 = child, 3 = sub-child.
 */
function bk_category_depth( int $term_id ): int {
	return count( bk_category_ancestor_ids( $term_id ) ) + 1;
}

/**
 * Brand × Category matrix with descendant roll-up and exact (de-duplicated) counts.
 *
 * @return array{cat_totals: array<int,int>, brand_totals: array<int,int>, by_cat: array<int,array<int,int>>, by_brand: array<int,array<int,int>>}
 */
function bk_get_catalog_matrix(): array {
	return (array) bk_remember(
		'catalog-matrix',
		static function () {
			global $wpdb;
			$hidden = (int) ( get_term_by( 'slug', 'exclude-from-catalog', 'product_visibility' )->term_taxonomy_id ?? 0 );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT tr.object_id AS pid, tt.term_id AS tid, tt.taxonomy AS tax
					 FROM {$wpdb->term_relationships} tr
					 INNER JOIN {$wpdb->term_taxonomy} tt ON tt.term_taxonomy_id = tr.term_taxonomy_id
					 INNER JOIN {$wpdb->posts} p ON p.ID = tr.object_id
					 WHERE p.post_type = 'product' AND p.post_status = 'publish'
					   AND tt.taxonomy IN ('product_cat', %s)
					   AND tr.object_id NOT IN (SELECT object_id FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d)",
					BK_BRAND_TAX,
					$hidden
				)
			);

			$products = array();
			foreach ( $rows as $row ) {
				$products[ (int) $row->pid ][ 'product_cat' === $row->tax ? 'c' : 'b' ][] = (int) $row->tid;
			}

			$matrix = array( 'cat_totals' => array(), 'brand_totals' => array(), 'by_cat' => array(), 'by_brand' => array() );
			foreach ( $products as $sets ) {
				$cats = array();
				foreach ( $sets['c'] ?? array() as $cat_id ) {
					$cats[ $cat_id ] = true;
					foreach ( bk_category_ancestor_ids( $cat_id ) as $ancestor ) {
						$cats[ $ancestor ] = true;
					}
				}
				$brands = array_unique( $sets['b'] ?? array() );
				foreach ( array_keys( $cats ) as $cat_id ) {
					$matrix['cat_totals'][ $cat_id ] = ( $matrix['cat_totals'][ $cat_id ] ?? 0 ) + 1;
					foreach ( $brands as $brand_id ) {
						$matrix['by_cat'][ $cat_id ][ $brand_id ]   = ( $matrix['by_cat'][ $cat_id ][ $brand_id ] ?? 0 ) + 1;
						$matrix['by_brand'][ $brand_id ][ $cat_id ] = ( $matrix['by_brand'][ $brand_id ][ $cat_id ] ?? 0 ) + 1;
					}
				}
				foreach ( $brands as $brand_id ) {
					$matrix['brand_totals'][ $brand_id ] = ( $matrix['brand_totals'][ $brand_id ] ?? 0 ) + 1;
				}
			}
			foreach ( array( 'by_cat', 'by_brand' ) as $axis ) {
				foreach ( $matrix[ $axis ] as &$list ) {
					arsort( $list );
				}
				unset( $list );
			}
			return $matrix;
		}
	);
}

/**
 * Published product count of a category including its descendants.
 */
function bk_category_count( int $term_id ): int {
	return (int) ( bk_get_catalog_matrix()['cat_totals'][ $term_id ] ?? 0 );
}

/**
 * Number of products for a brand inside a category (descendants included).
 */
function bk_intersection_count( int $brand_id, int $cat_id ): int {
	return (int) ( bk_get_catalog_matrix()['by_cat'][ $cat_id ][ $brand_id ] ?? 0 );
}

/* -------------------------------------------------------------------------
 * Tree API
 * ---------------------------------------------------------------------- */

/**
 * Full category tree (menu order), only branches that contain products.
 *
 * @return array<int, array{id:int,name:string,slug:string,url:string,count:int,image_id:int,depth:int,children:array}>
 */
function bk_get_category_tree(): array {
	return (array) bk_remember(
		'cat-tree',
		static function () {
			$terms = get_terms(
				array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => false,
					'orderby'    => 'menu_order',
					'exclude'    => array( (int) get_option( 'default_product_cat' ) ),
				)
			);
			if ( is_wp_error( $terms ) ) {
				return array();
			}
			$by_parent = array();
			foreach ( $terms as $term ) {
				if ( bk_category_count( $term->term_id ) > 0 ) {
					$by_parent[ (int) $term->parent ][] = $term;
				}
			}
			$build = static function ( int $parent, int $depth ) use ( &$build, $by_parent ): array {
				$nodes = array();
				foreach ( $by_parent[ $parent ] ?? array() as $term ) {
					$nodes[] = array(
						'id'       => (int) $term->term_id,
						'name'     => $term->name,
						'slug'     => $term->slug,
						'url'      => (string) get_term_link( $term ),
						'count'    => bk_category_count( $term->term_id ),
						'image_id' => (int) get_term_meta( $term->term_id, 'thumbnail_id', true ),
						'depth'    => $depth,
						'children' => $depth < 3 ? $build( (int) $term->term_id, $depth + 1 ) : array(),
					);
				}
				return $nodes;
			};
			return $build( 0, 1 );
		}
	);
}

/**
 * Find a node anywhere in the tree.
 */
function bk_find_category_node( int $term_id, ?array $nodes = null ): ?array {
	foreach ( $nodes ?? bk_get_category_tree() as $node ) {
		if ( $node['id'] === $term_id ) {
			return $node;
		}
		$found = bk_find_category_node( $term_id, $node['children'] );
		if ( $found ) {
			return $found;
		}
	}
	return null;
}

/**
 * Child category nodes (for the visual drill-down cards).
 */
function bk_get_child_categories( int $term_id ): array {
	return bk_find_category_node( $term_id )['children'] ?? array();
}

/**
 * Root → … → term chain of WP_Term objects.
 *
 * @return WP_Term[]
 */
function bk_category_chain( int $term_id ): array {
	$ids   = array_reverse( bk_category_ancestor_ids( $term_id ) );
	$ids[] = $term_id;
	return array_values( array_filter( array_map( static fn( $id ) => get_term( $id, 'product_cat' ), $ids ), static fn( $t ) => $t instanceof WP_Term ) );
}

/**
 * Brands present in a category (descendants included), most products first.
 *
 * @return array<int, array{term: WP_Term, count: int}>
 */
function bk_get_category_brands( int $cat_id, int $limit = 0 ): array {
	$out = array();
	foreach ( bk_get_catalog_matrix()['by_cat'][ $cat_id ] ?? array() as $brand_id => $count ) {
		$term = get_term( $brand_id, BK_BRAND_TAX );
		if ( $term instanceof WP_Term ) {
			$out[] = array( 'term' => $term, 'count' => (int) $count );
		}
		if ( $limit && count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

/**
 * Categories in which a brand has products.
 *
 * @param int      $brand_id Brand term ID.
 * @param int|null $depth    Restrict to depth (1 parent, 2 child, 3 sub-child) or null for all.
 * @return array<int, array{term: WP_Term, count: int, depth: int}>
 */
function bk_get_brand_categories( int $brand_id, ?int $depth = null ): array {
	$out = array();
	foreach ( bk_get_catalog_matrix()['by_brand'][ $brand_id ] ?? array() as $cat_id => $count ) {
		$d = bk_category_depth( (int) $cat_id );
		if ( null !== $depth && $d !== $depth ) {
			continue;
		}
		$term = get_term( (int) $cat_id, 'product_cat' );
		if ( $term instanceof WP_Term && (int) get_option( 'default_product_cat' ) !== $term->term_id ) {
			$out[] = array( 'term' => $term, 'count' => (int) $count, 'depth' => $d );
		}
	}
	return $out;
}

/**
 * Hub tab bar: child-level categories when available, otherwise parents.
 */
function bk_get_brand_hub_tabs( int $brand_id ): array {
	$children = bk_get_brand_categories( $brand_id, 2 );
	return count( $children ) >= 2 ? $children : bk_get_brand_categories( $brand_id, 1 );
}

/**
 * Deepest category assigned to a product (used for breadcrumbs & silo links).
 */
function bk_product_primary_category( int $product_id ): ?WP_Term {
	$terms = get_the_terms( $product_id, 'product_cat' );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return null;
	}
	usort( $terms, static fn( $a, $b ) => bk_category_depth( $b->term_id ) <=> bk_category_depth( $a->term_id ) );
	return $terms[0];
}

/**
 * Shipping weight class with inheritance: small|heavy (default heavy for safety).
 */
function bk_category_weight_class( int $term_id ): string {
	foreach ( array_merge( array( $term_id ), bk_category_ancestor_ids( $term_id ) ) as $id ) {
		$class = (string) get_term_meta( $id, 'bk_weight_class', true );
		if ( in_array( $class, array( 'small', 'heavy' ), true ) ) {
			return $class;
		}
	}
	return 'heavy';
}

/**
 * Price range of a category / brand / intersection from the WC lookup table.
 *
 * @return array{min: float, max: float}
 */
function bk_price_range( ?int $cat_id = null, ?int $brand_id = null ): array {
	return (array) bk_remember(
		"price-range:{$cat_id}:{$brand_id}",
		static function () use ( $cat_id, $brand_id ) {
			global $wpdb;
			$joins  = '';
			$wheres = '';
			if ( $cat_id ) {
				$ids    = array_merge( array( $cat_id ), get_term_children( $cat_id, 'product_cat' ) ?: array() );
				$tt_ids = array_map( 'intval', (array) get_terms( array( 'taxonomy' => 'product_cat', 'include' => $ids, 'fields' => 'tt_ids', 'hide_empty' => false ) ) );
				$joins .= " INNER JOIN {$wpdb->term_relationships} trc ON trc.object_id = l.product_id AND trc.term_taxonomy_id IN (" . implode( ',', $tt_ids ?: array( 0 ) ) . ')';
			}
			if ( $brand_id ) {
				$brand_tt = (int) ( get_term( $brand_id, BK_BRAND_TAX )->term_taxonomy_id ?? 0 );
				$joins   .= " INNER JOIN {$wpdb->term_relationships} trb ON trb.object_id = l.product_id AND trb.term_taxonomy_id = {$brand_tt}";
			}
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$row = $wpdb->get_row( "SELECT MIN(NULLIF(l.min_price,0)) AS min_p, MAX(l.max_price) AS max_p FROM {$wpdb->wc_product_meta_lookup} l INNER JOIN {$wpdb->posts} p ON p.ID = l.product_id AND p.post_status = 'publish' {$joins} {$wheres}" );
			return array( 'min' => (float) ( $row->min_p ?? 0 ), 'max' => (float) ( $row->max_p ?? 0 ) );
		}
	);
}
