<?php
namespace Lasntg\Admin\Products;

use DateTime;
use DateTimeZone;
use Lasntg\Admin\Group\GroupUtils;
use Groups_Group;

class ProductSchedulerActions {
	public static function init(): void {
		self::add_actions();
	}
	private static function get_timezone() {
		return new DateTimeZone( wp_timezone_string() );
	}
	public static function add_actions(): void {
		add_action( 'init', [ self::class, 'queue_async_action' ] );
		add_action( 'init', [ self::class, 'close_courses' ] );

		add_action( 'lasntgadmin_close_courses', [ self::class, 'close_courses' ] );
		add_action( 'lasntgadmin_close_notify', [ self::class, 'notify_to_check_course_status' ] );
	}

	public static function close_courses(): void {
		$tz = self::get_timezone();

		$from_now_date = new DateTime( 'now', $tz );
		$posts         = get_posts(
			array(
				'posts_per_page' => 30,
				'post_type'      => 'product',
				'post_status'    => ProductUtils::$publish_status,
				'meta_query'     => array(
					'relation' => 'AND',
					array( //phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey,Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'start_date',
						'value'   => $from_now_date->format( 'Ymd' ),
						'compare' => '<=',
					),
					array( //phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'lastg_enrollment_closed_',
						'compare' => 'NOT LIKE',
						'value'   => '%',
					),
				),
			)
		);
		foreach ( $posts as $post ) {
			$product_id = $post->ID;

			$meta_key = 'lasntg_enrollment_closed_' . $product_id;

			$start_date = get_post_meta( $product_id, 'start_date', true );
			$start_time = get_post_meta( $product_id, 'start_time', true );
			if ( ! $start_date || $start_time ) {
				continue;
			}
			$start_date_time_str = $start_date . ' ' . $start_time;

			$start_date_time = DateTime::createFromFormat( 'Ymd H:i:s', $start_date_time_str, $tz );
			if ( ! $start_date_time ) {
				continue;
			}
			$diff = $from_now_date->diff( $start_date_time );

			$hours = $diff->h + ( $diff->days * 24 );

			if ( $start_date_time > $from_now_date ) {
				if ( $hours > 8 ) {
					continue;
				}
			}
			try {
				$product = wc_get_product( $product_id );
			} catch ( \Exception $e ) {
				error_log( "Couldn't find product with ID: " + $product_id );
				continue;
			}
			$product->set_status( 'enrollment_closed' );
			$product->save();
			update_post_meta( $product_id, $meta_key, 1 );
		}//end foreach
	}

	public static function notify_to_check_course_status(): void {
		$key   = 'lasntg_last_notified';
		$posts = self::get_enrolments_closed();
		$now   = new DateTime();
		$now->setTimezone( self::get_timezone() );
		foreach ( $posts as $post ) {
			$product_id = $post->ID;
			try {
				$product = wc_get_product( $product_id );
			} catch ( \Exception $e ) {
				error_log( "Couldn't find product with ID: " + $product_id );
				continue;
			}
			$last_sent = get_post_meta( $product_id, $key, true );

			if ( $last_sent && $last_sent > strtotime( '-1 week' ) ) {
				continue;
			}

			$groups   = GroupUtils::get_read_group_ids( $product_id );
			$end_date = get_post_meta( $product_id, 'end_date', true );
			$end_time = get_post_meta( $product_id, 'end_time', true );
			if ( ! $end_time ) {
				$end_time = '00:00:00';
			}

			$end_date_str = "$end_date $end_time";
			$end_date     = DateTime::createFromFormat( 'Ymd H:i:s', $end_date_str );
			if ( ! $end_date ) {
				continue;
			}

			$interval = $now->diff( $end_date );
			$days     = is_a( $interval, 'DateInterval' ) ? $interval->days : 0;

			if ( $days < 21 ) {
				continue;
			}

			$weeks = floor( $days / 7 );

			$users_to_send = [];
			foreach ( $groups as $group_id ) {
				$group = new Groups_Group( $group_id );
				$users = $group->users;
				if ( ! $users ) {
					continue;
				}
				foreach ( $users as $user ) {
					if ( in_array( 'regional_training_centre_manager', $user->roles ) === false ) {
						continue;
					}
					if ( ! in_array( $user->ID, $users_to_send ) ) {
						$users_to_send[] = $user->ID;
						self::send_notification_mail( $product, $user, $weeks );
					}
				}
			}
			update_post_meta( $product_id, $key, strtotime( 'now' ) );
		}//end foreach
	}
	/**
	 * Get Enrolments closed that that have passed 3 weeks from now.
	 *
	 * @return array
	 */
	private static function get_enrolments_closed() {
		$from_now = new DateTime( 'now' );
		$from_now->setTimezone( self::get_timezone() );
		$from_now->modify( '-3 week' );

		return get_posts(
			array(
				'posts_per_page' => -1,
				'post_type'      => 'product',
				'post_status'    => 'enrollment_closed',
				'meta_query'     => array(
					'relation' => 'AND',
					array( // phpcs:ignore Universal.Arrays.MixedArrayKeyTypes.ImplicitNumericKey, Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'end_date',
						'value'   => ' ',
						'compare' => '!=',
					),
					array( // phpcs:ignore Universal.Arrays.MixedKeyedUnkeyedArray.Found
						'key'     => 'end_date',
						'value'   => $from_now->format( 'Ymd' ),
						'compare' => '<=',
					),

				),
			)
		);
	}
	private static function send_notification_mail( $product, $user, $weeks ): void {
		$name    = $product->get_name();
		$email   = $user->user_email;
		$subject = 'LASNTG OBS course status reminder.';

		$link    = admin_url( 'post.php?post=' . $product->get_id() ) . '&action=edit';
		$body    = "Hi! <br/> The course {$name} END DATE passed by more than $weeks week(s) ago, please check course status. <a href='$link'>Click here to change status</a> ";
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		wp_mail( $email, $subject, $body, $headers );
	}

	public static function queue_async_action() {
		if ( function_exists( 'as_has_scheduled_action' ) ) {
			if ( false === as_has_scheduled_action( 'lasntgadmin_close_courses' ) ) {
				as_schedule_recurring_action( strtotime( '+1 hour' ), HOUR_IN_SECONDS, 'lasntgadmin_close_courses', [], '', true );
			}if ( false === as_has_scheduled_action( 'lasntgadmin_close_notify' ) ) {
				as_schedule_recurring_action( strtotime( '+1 hour' ), HOUR_IN_SECONDS, 'lasntgadmin_close_notify', [], '', true );
			}
		}
	}
}
