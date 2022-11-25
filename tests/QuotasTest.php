<?php


/**
 * Class SampleTest
 *
 * @package Wordpress_Classic_Theme_template
 */

/**
 * Example test case.
 */
class QuotaUtilTest extends WP_UnitTestCase {

    public function set_up() {
        parent::set_up();
    }

	/**
	 * A single example test.
	 */
	public function testSingleton() {
		// Replace this with some actual testing code.
        $this->assertInstanceOf( Example::class, Example::get_instance());
	}
}
