<?php

namespace Lasntg\Admin\Test;

use Lasntg\Admin\Products\ProductUtils;

use Lasntg\Admin\Group\GroupUtils;

use Faker\Factory as Faker;
use WP_UnitTestCase;

class ProductUtilsTest extends WP_UnitTestCase {

    private static $faker;

    public function set_up() {
        parent::set_up();
        self::$faker = Faker::create();
    }

	public function testGetProductIdsWithStatus() {
		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
			'post_status' => 'open_for_enrollment'
		]);
		$this->assertIsInt($post_id);
		$post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
			'post_status' => 'enrollment_closed'
		]);
		$this->assertIsInt($post_id);
		$post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
			'post_status' => 'date_passed'
		]);
		$this->assertIsInt($post_id);
		$post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
			'post_status' => 'closed'
		]);
		$this->assertIsInt($post_id);

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
			'post_status' => 'publish'
		]);
		$this->assertIsInt($post_id);

		$post_id = self::factory()->post->create_object([
			'post_title' => self::$faker->sentence(),
			'post_excerpt' => self::$faker->paragraph(),
			'post_content' => self::$faker->paragraph(),
			'post_type' => 'post',
			'post_status' => 'draft'
		]);
		$this->assertIsInt($post_id);

		$product_ids = ProductUtils::get_product_ids_with_status( [ 'open_for_enrollment', 'enrollment_closed', 'date_passed' ] );
		sort( $product_ids );
		$this->assertSame( $post_ids, $product_ids );
	}

	public function testGetLimitedProductIdsForGrantYear() {

		$all_post_ids = [];
		$expected_post_ids = [];
		$grant_year = 2025;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'grant_year', $grant_year, true );
		$expected_post_ids[] = $post_id;
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'grant_year', 2024, true );
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'grant_year', $grant_year, true );
		$expected_post_ids[] = $post_id;
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'grant_year', 2026, true );
		$all_post_ids[] = $post_id;

		$product_ids = ProductUtils::get_limited_product_ids_for_grant_year( $grant_year, $all_post_ids );
		$this->assertSame( $expected_post_ids, $product_ids );

	}

	/**
	 * Selected posts have a group membership ie. product visibility restriction.
	 */
	public function testGetLimitedProductIdsWithAnyGroupRestriction() {

		$all_post_ids = [];
		$expected_post_ids = [];

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;
		$expected_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;
		$expected_post_ids[] = $post_id;

		$product_ids = ProductUtils::get_limited_product_ids_with_any_group_restriction( $all_post_ids );
		$this->assertSame( $expected_post_ids, $product_ids );

	}

	/**
	 * Selected posts now don't have any group membership ie. product is visible to everyone.
	 */
	public function testGetLimitedProductIdsVisibleToAllGroups() {

		$all_post_ids = [];
		$expected_post_ids = [];

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		$all_post_ids[] = $post_id;
		$expected_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		$all_post_ids[] = $post_id;
		$expected_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;

		$product_ids = ProductUtils::get_limited_product_ids_visible_to_all_groups( $all_post_ids );
		$this->assertSame( $expected_post_ids, $product_ids );

	}

	public function testGetLimitedProductIdsVisibleToGroups() {

		$all_post_ids = [];
		$expected_post_ids = [];
		$group_ids = [123, 456];

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		$meta_id = add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', $group_ids[0], false );
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		$expected_post_ids[] = $post_id;
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', $group_ids[1], false );
		$expected_post_ids[] = $post_id;
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		add_post_meta( $post_id, 'groups-read', self::$faker->unique()->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;

		$post_id = self::factory()->post->create_object([
			'post_type' => 'product',
		]);
		$this->assertIsInt($post_id);
		add_post_meta( $post_id, 'groups-read', self::$faker->randomDigitNotNull(), false );
		$all_post_ids[] = $post_id;

		$product_ids = ProductUtils::get_limited_product_ids_visible_to_groups(  $group_ids, $all_post_ids );
		$this->assertSame( $expected_post_ids, $product_ids );
	}

}

