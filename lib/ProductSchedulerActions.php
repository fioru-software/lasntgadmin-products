<?php
namespace Lasntg\Admin\Products;

use Lasntg\Admin\Group\GroupUtils;

class ProductSchedulerActions {
    public static function init(): void {
        self::add_actions();
    }

    public static function add_actions():void {
        add_action( 'init', [ self::class, 'queue_async_action' ] );
        // add_action( 'init', [ self::class, 'notify_to_check_course_status' ] );
        add_action( 'lasntgadmin_close_courses', [self::class, 'close_courses'] );
        add_action( 'lasntgadmin_close_notify', [self::class, 'notify_to_check_course_status'] );
    }

    // close course on midnight of start_date
    public static function close_courses()
    {
        error_log( 'Processing courses to close for ' . date( 'Y-m-d H:i:s' ) );
        $fromNow = strtotime("+36 hours");
        $fromNowDate = date("Ymd", $fromNow);
        $fromNowTime = date("H:i:s", $fromNow);
       
        $posts = get_posts(array(
            'numberposts'   => -1,
            'post_type'     => 'product',
            'post_status'     => ProductUtils::$publish_status,
            'meta_query' => array(
                'relation' => 'AND',
                 array(
                    'key'     => 'start_date',
                    'value'   => $fromNowDate,
                    'compare' => '=',
                ),
                array(
                    'key'     => 'start_time',
                    'value'   => $fromNowTime,
                    'compare' => '<=',
                ),

            )
        ));
        
        foreach($posts as $post){
            $product_id = $post->ID;
            $product   = wc_get_product( $product_id );
            $product->set_status(ProductUtils::$statuses['closed']);
            $product->save();
            error_log("Closed Course #$product_id at ".date("Y/m/d H:i:s"));
        }
    }
    
    public static function notify_to_check_course_status() :void
    {
        $key = 'lasntg_last_notified';
        error_log( 'Processing courses to close for ' . date( 'Y-m-d H:i:s' ) );
        $fromNow = strtotime("-1 week");
        $fromNowDate = date("Ymd", $fromNow);
       
        $posts = get_posts(array(
            'post_type'     => 'product',
            'post_status'     => ProductUtils::$statuses['closed'],
            'meta_query' => array(
                // 'relation' => 'AND',
                 array(
                    'key'     => 'end_date',
                    'value'   => $fromNowDate,
                    'compare' => '<=',
                ),
               

            )
        ));
        
        foreach($posts as $post){
            $product_id = $post->ID;
            $product   = wc_get_product( $product_id );
            $last_sent = get_post_meta($product_id, $key, true);
            if($last_sent && $last_sent > strtotime("-1 week")){
                continue;
            }
            $groups = GroupUtils::get_read_group_ids($product_id);
            $end_date = get_post_meta($product_id, 'end_date', true);
            $weeks = (strtotime("now") - strtotime($end_date)) / (7 * 86400);
            $weeks = floor($weeks);
            
            $users_to_send = [];
            foreach($groups as $group_id){
                $group = new \Groups_Group( $group_id );
                $users = $group->users;
                if(!$users){
                    continue;
                }
                foreach($users as $user){
                    if ( in_array( 'regional_training_centre_manager', $user->roles ) === false ) {
                        continue;
                    }
                    if(!in_array($user->ID, $users_to_send)){
                        $users_to_send[] = $user->ID;
                        self::send_notification_mail($product, $user, $weeks);
                    }
                }
            }
        }
    }

    private static function send_notification_mail($product, $user, $weeks) :void
    {
        $name = $product->get_name();
        $email = $user->user_email;
        $subject = "The {$name} end date passed by more than $weeks week(s) ago, please check course status.";
        
        $link = admin_url( 'post.php?post=' . $product->get_id() ) . '&action=edit';
        $body = "The {$name} end date passed by more than $weeks week(s) ago, please check course status. <a href='$link'>Click here to change status</a> ";
        $headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $email, $subject, $body, $headers );
    }

    public static function queue_async_action() {
		if ( false === as_has_scheduled_action( 'lasntgadmin_close_courses' ) ) {
            as_schedule_recurring_action( strtotime( '+5 minutes' ), MINUTE_IN_SECONDS, 'lasntgadmin_close_courses' );    
		}if ( false === as_has_scheduled_action( 'lasntgadmin_close_notify' ) ) {
            as_schedule_recurring_action( strtotime( '+5 minutes' ), MINUTE_IN_SECONDS, 'lasntgadmin_close_notify' );    
		}
        
	}
    

}