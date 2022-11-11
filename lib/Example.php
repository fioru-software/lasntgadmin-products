<?php

namespace Lasntg\Admin;

/**
 * Example
 */
class Example {

	protected static $instance = null;

	protected function __construct() {
	}

	public static function get_instance(): Example {
		if ( null == self::$instance ) {
				self::$instance = new Example();
		}
			return self::$instance;
	}

}

