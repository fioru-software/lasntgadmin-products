<?php

namespace Lasntg\Admin\Products;

use WC_Product;

/**
 * The payment lock prevents two payments for the same product to be processed simultaneously, 
 * which could result in negative stock.
 */
class PaymentLock {

	const META_KEY = '_payment_lock';

	public static function is_locked( WC_Product $product ): bool {
		$result = false;
		if ( $product->meta_exists( self::META_KEY ) ) {
			$result = $product->get_meta( self::META_KEY, true );
		}
		return $result;
	}

	public static function lock( WC_Product $product ): void {
		if ( $product->meta_exists( self::META_KEY ) ) {
			$product->update_meta_data( self::META_KEY, true );
		} else {
			$product->add_meta_data( self::META_KEY, true, true );
		}
		$product->save_meta_data();
	}

	public static function unlock( WC_Product $product ): void {
		if ( $product->meta_exists( self::META_KEY ) ) {
			$product->update_meta_data( self::META_KEY, false );
		} else {
			$product->add_meta_data( self::META_KEY, false, true );
		}
		$product->save_meta_data();
	}
}
