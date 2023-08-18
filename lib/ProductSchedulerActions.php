<?php
namespace Lasntg\Admin\Products;

require_once( plugin_dir_path( __FILE__ ) . '/action-scheduler/action-scheduler.php' );

class ProductSchedulerActions {
    public static function init(): void {
        self::add_actions();

        // When scheduling the action, provide the arguments as an array.
        as_schedule_single_action( time(), [self::class, 'purchase_notification'], array(
            'bob@foo.bar',
            'Learning Action Scheduler (e-book)',
        ) );
    }

    public static function add_actions():void {
        add_action( 'init', [ self::class, 'queue_async_action' ] );
        add_action( 'lasntgadmin_close_course', [self::class, 'close_courses'] );
    }

    // close course on midnight of start_date
    public static function close_courses()
    {
        error_log( 'Processing courses to close for ' . date( 'Y-m-d H:i:s' ) );
        $posts = get_posts(array(
            'numberposts'   => -1,
            'post_type'     => 'product',
            'meta_key'      => 'color',
            'meta_value'    => 'red'
        ));
    }
    public static function queue_async_action() {
		if ( false === as_has_scheduled_action( 'lasntgadmin_close_course' ) ) {
            as_schedule_recurring_action( strtotime( 'tomorrow' ), DAY_IN_SECONDS, 'lasntgadmin_close_course', array(), '', true );
		}
	}
    

}