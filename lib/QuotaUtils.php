<?php

namespace Lasntg\Admin\Quotas;

/**
 * QuotaUtil
 */
class QuotaUtils {


	/**
	 * Get the remaining quota
	 *
	 * @param  integer $post_id post/product id.
	 * @param  integer $group_id the group id.
	 * @return string
	 */
	public static function remaining_quota( $post_id, $group_id ): string {
		return get_post_meta( $post_id, '_quotas_field_' . $group_id, true );
	}

	/**
	 * Reduce quota by number
	 *
	 * @param  integer $post_id post/product id.
	 * @param  integer $group_id the group id.
	 * @param  integer $by the number you want to reduce with.
	 * @return bool
	 */
	public static function reduce_quota( int $post_id, int $group_id, int $by = 1 ): bool {
		$quota = self::remaining_quota( $post_id, $group_id );
		if ( '0' === $quota ) {
			return false;
		}
		if ( '' !== $quota ) {
			$quota = (int) $quota - $by;
			if ( $quota < 0 ) {
				return false;
			}
			update_post_meta( $post_id, '_quotas_field_' . $group_id, esc_attr( $quota ) );
		}

		return true;
	}
}
