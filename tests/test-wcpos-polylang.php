<?php

class Test_WCPOS_Polylang extends WP_UnitTestCase {
	public function setUp(): void {
		parent::setUp();

		$user_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		$user    = get_user_by( 'id', $user_id );
		$user->add_cap( 'access_woocommerce_pos' );
		wp_set_current_user( $user_id );

		if ( ! post_type_exists( 'product' ) ) {
			register_post_type(
				'product',
				array(
					'public' => true,
				)
			);
		}

		if ( ! function_exists( 'pll_default_language' ) || ! function_exists( 'pll_languages_list' ) ) {
			add_filter( 'wcpos_polylang_is_supported', '__return_true' );
		}

		$this->configure_polylang_languages();
	}

	public function tearDown(): void {
		unset( $_SERVER['HTTP_X_WCPOS'] );
		global $wp;
		if ( isset( $wp ) && isset( $wp->query_vars['wcpos'] ) ) {
			unset( $wp->query_vars['wcpos'] );
		}
		remove_all_filters( 'wcpos_polylang_default_language' );
		remove_all_filters( 'wcpos_polylang_is_supported' );
		remove_all_filters( 'wcpos_polylang_minimum_version' );
		remove_all_filters( 'posts_where' );
		remove_all_filters( 'posts_pre_query' );
		wp_set_current_user( 0 );
		parent::tearDown();
	}

	public function test_product_query_adds_lang_for_wcpos_route(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$args    = array();
		$request = new WP_REST_Request( 'GET', '/wcpos/v1/products' );

		$filtered = apply_filters( 'woocommerce_rest_product_object_query', $args, $request );
		$this->assertArrayHasKey( 'lang', $filtered );
		$this->assertSame( 'en', $filtered['lang'] );
	}

	public function test_product_query_does_not_add_lang_for_non_wcpos_route(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$args    = array();
		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );

		$filtered = apply_filters( 'woocommerce_rest_product_object_query', $args, $request );
		$this->assertArrayNotHasKey( 'lang', $filtered );
	}

	/**
	 * v2 lane: catalogue reads are proxied internally to /wc/v3/products, so the
	 * route is not a WCPOS route. The X-WCPOS header on the outer request is what
	 * identifies the lane.
	 */
	public function test_product_query_adds_lang_for_v2_proxied_wc_route(): void {
		$this->requires_wcpos_request_helper();
		$this->mark_request_as_pos();
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );

		$filtered = apply_filters( 'woocommerce_rest_product_object_query', array(), $request );
		$this->assertArrayHasKey( 'lang', $filtered );
		$this->assertSame( 'en', $filtered['lang'] );
	}

	/**
	 * v2 serialized lane: free builds a bare `GET /` request in PHP, so
	 * get_route() is literally '/'.
	 */
	public function test_product_query_adds_lang_for_v2_serialized_bare_route(): void {
		$this->requires_wcpos_request_helper();
		$this->mark_request_as_pos();
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$request = new WP_REST_Request( 'GET', '/' );

		$filtered = apply_filters( 'woocommerce_rest_product_object_query', array(), $request );
		$this->assertArrayHasKey( 'lang', $filtered );
		$this->assertSame( 'en', $filtered['lang'] );
	}

	/**
	 * v2 writes arrive on /wcpos/v2/push/{collection}.
	 */
	public function test_product_query_adds_lang_for_wcpos_v2_route(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$request = new WP_REST_Request( 'POST', '/wcpos/v2/push/products' );

		$filtered = apply_filters( 'woocommerce_rest_product_object_query', array(), $request );
		$this->assertArrayHasKey( 'lang', $filtered );
		$this->assertSame( 'en', $filtered['lang'] );
	}

	public function test_product_variation_query_adds_lang_for_v2_proxied_wc_route(): void {
		$this->requires_wcpos_request_helper();
		$this->mark_request_as_pos();
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products/123/variations' );

		$filtered = apply_filters( 'woocommerce_rest_product_variation_object_query', array(), $request );
		$this->assertArrayHasKey( 'lang', $filtered );
		$this->assertSame( 'en', $filtered['lang'] );
	}

	/**
	 * The v2 gate must not leak into non-POS traffic: an ordinary /wc/v3 request
	 * without the WCPOS markers stays unfiltered.
	 */
	public function test_product_query_does_not_add_lang_for_plain_wc_route_without_pos_markers(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$request = new WP_REST_Request( 'GET', '/wc/v3/products' );

		$filtered = apply_filters( 'woocommerce_rest_product_variation_object_query', array(), $request );
		$this->assertArrayNotHasKey( 'lang', $filtered );
	}

	public function test_fast_sync_is_intercepted_and_language_filtered(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$english_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'English Product',
			)
		);
		$french_id  = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'French Product',
			)
		);
		$this->assertGreaterThan( 0, $english_id );
		$this->assertGreaterThan( 0, $french_id );

		add_filter(
			'posts_pre_query',
			static function ( $posts, $query ) use ( $english_id, $french_id ) {
				if ( 'product' !== $query->get( 'post_type' ) ) {
					return $posts;
				}

				$lang = $query->get( 'lang' );
				if ( 'en' === $lang ) {
					return array( $english_id );
				}
				if ( 'fr' === $lang ) {
					return array( $french_id );
				}

				return array( $english_id, $french_id );
			},
			20,
			2
		);

		$request = new WP_REST_Request( 'GET', '/wcpos/v1/products' );
		$request->set_param( 'posts_per_page', -1 );
		$request->set_param( 'fields', array( 'id' ) );

		$response = apply_filters( 'rest_pre_dispatch', null, rest_get_server(), $request );
		$this->assertInstanceOf( 'WP_REST_Response', $response );

		$data = $response->get_data();
		$ids  = wp_list_pluck( $data, 'id' );

		$this->assertContains( $english_id, $ids, 'Fast-sync payload: ' . wp_json_encode( $data ) );
		$this->assertNotContains( $french_id, $ids, 'Fast-sync payload: ' . wp_json_encode( $data ) );
	}

	public function test_fast_sync_not_intercepted_for_non_fast_sync_fields(): void {
		$request = new WP_REST_Request( 'GET', '/wcpos/v1/products' );
		$request->set_param( 'posts_per_page', -1 );
		$request->set_param( 'fields', array( 'id', 'name' ) );

		$response = apply_filters( 'rest_pre_dispatch', null, rest_get_server(), $request );
		$this->assertNull( $response );
	}

	public function test_fast_sync_with_modified_date_field_returns_date(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$english_id = wp_insert_post(
			array(
				'post_type'   => 'product',
				'post_status' => 'publish',
				'post_title'  => 'English Product Date',
			)
		);
		$this->assertGreaterThan( 0, $english_id );

		add_filter(
			'posts_pre_query',
			static function ( $posts, $query ) use ( $english_id ) {
				if ( 'product' !== $query->get( 'post_type' ) ) {
					return $posts;
				}

				if ( 'en' === $query->get( 'lang' ) ) {
					return array( $english_id );
				}

				return array();
			},
			20,
			2
		);

		$request = new WP_REST_Request( 'GET', '/wcpos/v1/products' );
		$request->set_param( 'posts_per_page', -1 );
		$request->set_param( 'fields', array( 'id', 'date_modified_gmt' ) );

		$response = apply_filters( 'rest_pre_dispatch', null, rest_get_server(), $request );
		$this->assertInstanceOf( 'WP_REST_Response', $response );

		$data = $response->get_data();
		$this->assertNotEmpty( $data );
		$this->assertSame( $english_id, (int) $data[0]['id'] );
		$this->assertMatchesRegularExpression( '/\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}/', (string) $data[0]['date_modified_gmt'] );
	}

	public function test_store_meta_fields_include_language(): void {
		$fields = apply_filters( 'woocommerce_pos_store_meta_fields', array() );
		$this->assertArrayHasKey( 'language', $fields );
		$this->assertSame( '_wcpos_polylang_language', $fields['language'] );
	}

	public function test_store_response_includes_language_meta_for_single_item(): void {
		$store_id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Test Store',
			)
		);
		$this->assertGreaterThan( 0, $store_id );
		update_post_meta( $store_id, '_wcpos_polylang_language', 'fr' );

		$request  = new WP_REST_Request( 'GET', '/wcpos/v1/stores/' . $store_id );
		$response = new WP_REST_Response(
			array(
				'id' => $store_id,
			)
		);

		$result = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );
		$data   = $result->get_data();

		$this->assertSame( 'fr', $data['language'] );
	}

	public function test_store_response_falls_back_to_default_language(): void {
		add_filter(
			'wcpos_polylang_default_language',
			static function () {
				return 'en';
			}
		);

		$store_id = wp_insert_post(
			array(
				'post_type'   => 'post',
				'post_status' => 'publish',
				'post_title'  => 'Default Language Store',
			)
		);
		$this->assertGreaterThan( 0, $store_id );

		$request  = new WP_REST_Request( 'GET', '/wcpos/v1/stores/' . $store_id );
		$response = new WP_REST_Response(
			array(
				'id' => $store_id,
			)
		);

		$result = apply_filters( 'rest_post_dispatch', $response, rest_get_server(), $request );
		$data   = $result->get_data();

		$this->assertSame( 'en', $data['language'] );
	}

	public function test_polylang_guard_disables_query_and_store_fields(): void {
		add_filter( 'wcpos_polylang_is_supported', '__return_false' );

		$args     = array();
		$request  = new WP_REST_Request( 'GET', '/wcpos/v1/products' );
		$filtered = apply_filters( 'woocommerce_rest_product_object_query', $args, $request );

		$this->assertArrayNotHasKey( 'lang', $filtered );

		$fields = apply_filters( 'woocommerce_pos_store_meta_fields', array() );
		$this->assertArrayNotHasKey( 'language', $fields );
	}

	/**
	 * Skip when the free WCPOS plugin is too old to expose wcpos_request().
	 */
	private function requires_wcpos_request_helper(): void {
		if ( ! function_exists( 'wcpos_request' ) ) {
			$this->markTestSkipped( 'The free WCPOS plugin does not expose wcpos_request().' );
		}
	}

	/**
	 * Mark the current request as a WCPOS request, the way the POS client does.
	 */
	private function mark_request_as_pos(): void {
		$_SERVER['HTTP_X_WCPOS'] = '1';
	}

	/**
	 * Configure a minimal Polylang setup for tests.
	 */
	private function configure_polylang_languages(): void {
		if ( ! function_exists( 'pll_languages_list' ) ) {
			return;
		}

		$existing = pll_languages_list( array( 'fields' => 'slug' ) );
		$existing = is_array( $existing ) ? $existing : array();

		if ( ! in_array( 'en', $existing, true ) ) {
			$this->create_language( 'English', 'en', 'en_US', 'us' );
		}

		if ( ! in_array( 'fr', $existing, true ) ) {
			$this->create_language( 'French', 'fr', 'fr_FR', 'fr' );
		}

		$settings_request = new WP_REST_Request( 'POST', '/pll/v1/settings' );
		$settings_request->set_param( 'default_lang', 'en' );
		rest_get_server()->dispatch( $settings_request );
	}

	/**
	 * Create a Polylang language through its REST API.
	 *
	 * @param string $name
	 * @param string $slug
	 * @param string $locale
	 * @param string $flag
	 */
	private function create_language( string $name, string $slug, string $locale, string $flag ): void {
		$request = new WP_REST_Request( 'POST', '/pll/v1/languages' );
		$request->set_param( 'name', $name );
		$request->set_param( 'slug', $slug );
		$request->set_param( 'locale', $locale );
		$request->set_param( 'flag_code', $flag );

		$response = rest_get_server()->dispatch( $request );
		$status   = $response->get_status();

		if ( $status >= 300 && 400 !== $status ) {
			$this->fail( sprintf( 'Failed creating Polylang language "%s" (HTTP %d).', $slug, $status ) );
		}
	}
}
