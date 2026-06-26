<?php
/**
 * Follow-up emails.
 *
 * Sends inactive signed-in customers a reminder of the products they viewed,
 * with an optional auto-generated coupon. Scheduling prefers WooCommerce's
 * Action Scheduler and falls back to WP-Cron. Everything is gated behind the
 * "email_enabled" setting (off by default).
 *
 * @package RVPW_Recently_Viewed_Products_For_Woocommerce
 * @since   2.3.0
 */

if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Emails.
 */
class RVPW_Recently_Viewed_Products_For_Woocommerce_Emails {

	/**
	 * Scan cron hook.
	 */
	const SCAN_HOOK = 'rvpw_email_scan';

	/**
	 * Per-run batch size.
	 */
	const BATCH = 40;

	/**
	 * Re-send cooldown in days.
	 */
	const COOLDOWN_DAYS = 30;

	/**
	 * Keep the scheduled scan in sync with the setting. Hooked to 'init'.
	 *
	 * @return void
	 */
	public static function maybe_schedule() {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$enabled  = ( 'yes' === $settings['email_enabled'] );

		if ( function_exists( 'as_next_scheduled_action' ) && function_exists( 'as_schedule_recurring_action' ) ) {
			$scheduled = as_next_scheduled_action( self::SCAN_HOOK );
			if ( $enabled && ! $scheduled ) {
				as_schedule_recurring_action( time() + HOUR_IN_SECONDS, DAY_IN_SECONDS, self::SCAN_HOOK, array(), 'rvpw' );
			} elseif ( ! $enabled && $scheduled && function_exists( 'as_unschedule_all_actions' ) ) {
				as_unschedule_all_actions( self::SCAN_HOOK );
			}
			return;
		}

		$scheduled = wp_next_scheduled( self::SCAN_HOOK );
		if ( $enabled && ! $scheduled ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::SCAN_HOOK );
		} elseif ( ! $enabled && $scheduled ) {
			wp_clear_scheduled_hook( self::SCAN_HOOK );
		}
	}

	/**
	 * Cron callback: find candidates and send.
	 *
	 * @return void
	 */
	public static function scan() {
		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		if ( 'yes' !== $settings['email_enabled'] ) {
			return;
		}

		foreach ( self::get_candidates( $settings, self::BATCH ) as $user_id ) {
			self::send_to_user( $user_id, $settings, false );
		}
	}

	/**
	 * Candidate user IDs: inactive for N days, recently enough to be relevant,
	 * not unsubscribed, and not emailed within the cooldown.
	 *
	 * @param array $settings Settings.
	 * @param int   $batch    Max to return.
	 * @return int[]
	 */
	private static function get_candidates( $settings, $batch ) {
		global $wpdb;

		$history    = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_user_history();
		$inactive   = gmdate( 'Y-m-d H:i:s', time() - ( absint( $settings['email_inactivity_days'] ) * DAY_IN_SECONDS ) );
		$not_before = gmdate( 'Y-m-d H:i:s', time() - ( 90 * DAY_IN_SECONDS ) );

		// phpcs:disable WordPress.DB
		$ids = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT user_id FROM {$history} GROUP BY user_id HAVING MAX(viewed_at) < %s AND MAX(viewed_at) > %s LIMIT %d",
				$inactive,
				$not_before,
				$batch * 3
			)
		);
		// phpcs:enable WordPress.DB

		$candidates = array();
		foreach ( array_map( 'absint', (array) $ids ) as $user_id ) {
			if ( self::is_unsubscribed( $user_id ) || self::recently_sent( $user_id ) ) {
				continue;
			}
			$candidates[] = $user_id;
			if ( count( $candidates ) >= $batch ) {
				break;
			}
		}

		return $candidates;
	}

	/**
	 * Whether a user opted out.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function is_unsubscribed( $user_id ) {
		return (bool) get_user_meta( $user_id, 'rvpw_email_unsubscribed', true );
	}

	/**
	 * Whether a user was emailed within the cooldown window.
	 *
	 * @param int $user_id User ID.
	 * @return bool
	 */
	private static function recently_sent( $user_id ) {
		global $wpdb;

		$log    = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_email_log();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( self::COOLDOWN_DAYS * DAY_IN_SECONDS ) );

		// phpcs:disable WordPress.DB
		$count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$log} WHERE user_id = %d AND status = 'sent' AND sent_at > %s",
				absint( $user_id ),
				$cutoff
			)
		);
		// phpcs:enable WordPress.DB

		return $count > 0;
	}

	/**
	 * Build and send the email to a user.
	 *
	 * @param int   $user_id  User ID.
	 * @param array $settings Settings.
	 * @param bool  $is_test  Whether this is a test send (bypasses skips).
	 * @return bool
	 */
	public static function send_to_user( $user_id, $settings, $is_test = false ) {
		$user = get_userdata( $user_id );
		if ( ! $user || ! is_email( $user->user_email ) ) {
			return false;
		}

		$products_html = self::build_products_html( $user_id );
		if ( '' === $products_html ) {
			if ( ! $is_test ) {
				return false;
			}
			$products_html = '<p><em>' . esc_html__( 'Your recently viewed products will appear here.', 'recently-viewed-products-for-woocommerce' ) . '</em></p>';
		}

		$coupon_code = '';
		$coupon_text = '';
		if ( 'yes' === $settings['email_coupon_enabled'] ) {
			$coupon_code = self::maybe_create_coupon( $settings );
			if ( '' !== $coupon_code ) {
				$coupon_text = sprintf(
					/* translators: %s: coupon code. */
					esc_html__( 'Here is a little something — use code %s at checkout.', 'recently-viewed-products-for-woocommerce' ),
					$coupon_code
				);
			}
		}

		$first = $user->first_name ? $user->first_name : $user->display_name;

		$body = (string) $settings['email_body'];
		$body = str_replace(
			array( '{first_name}', '{products}', '{coupon}' ),
			array( esc_html( $first ), $products_html, esc_html( $coupon_text ) ),
			$body
		);
		$body  = wpautop( $body );
		$body .= self::unsubscribe_footer( $user_id );

		$subject = (string) $settings['email_subject'];
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $user->user_email, $subject, self::wrap_html( $subject, $body ), $headers );

		if ( ! $is_test ) {
			self::log_send( $user_id, $sent ? 'sent' : 'failed', $coupon_code );
		}

		return (bool) $sent;
	}

	/**
	 * Build the products block for the email.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function build_products_html( $user_id ) {
		$products = RVPW_Recently_Viewed_Products_For_Woocommerce_Provider::get_products(
			array(
				'source'  => 'auto',
				'sort'    => 'recent',
				'limit'   => 6,
				'user_id' => absint( $user_id ),
			)
		);

		if ( empty( $products ) ) {
			return '';
		}

		$rows = '';
		foreach ( $products as $product ) {
			$rows .= sprintf(
				'<tr><td style="padding:8px 12px 8px 0;width:64px;">%1$s</td><td style="padding:8px 0;vertical-align:top;"><a href="%2$s" style="font-weight:600;text-decoration:none;">%3$s</a><br><span>%4$s</span></td></tr>',
				wp_kses_post( $product->get_image( 'thumbnail' ) ),
				esc_url( get_permalink( $product->get_id() ) ),
				esc_html( $product->get_name() ),
				wp_kses_post( $product->get_price_html() )
			);
		}

		return '<table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:collapse;">' . $rows . '</table>';
	}

	/**
	 * Create a single-use coupon and return its code.
	 *
	 * @param array $settings Settings.
	 * @return string Coupon code, or '' on failure.
	 */
	private static function maybe_create_coupon( $settings ) {
		if ( ! class_exists( 'WC_Coupon' ) ) {
			return '';
		}

		$amount = (float) $settings['email_coupon_amount'];
		$type   = in_array( $settings['email_coupon_type'], RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_allowed_coupon_types(), true ) ? $settings['email_coupon_type'] : 'percent';
		if ( 'percent' === $type ) {
			$amount = min( 100, $amount );
		}
		if ( $amount <= 0 ) {
			return '';
		}

		$code = 'RVP-' . strtoupper( wp_generate_password( 8, false, false ) );

		try {
			$coupon = new WC_Coupon();
			$coupon->set_code( $code );
			$coupon->set_discount_type( $type );
			$coupon->set_amount( $amount );
			$coupon->set_individual_use( true );
			$coupon->set_usage_limit( 1 );
			$coupon->set_date_expires( time() + ( absint( $settings['email_coupon_expiry_days'] ) * DAY_IN_SECONDS ) );
			$coupon->set_description( __( 'Auto-generated by Recently Viewed Products follow-up email.', 'recently-viewed-products-for-woocommerce' ) );
			$coupon->save();
		} catch ( Exception $e ) {
			return '';
		}

		return $code;
	}

	/**
	 * Record a send.
	 *
	 * @param int    $user_id     User ID.
	 * @param string $status      'sent'|'failed'.
	 * @param string $coupon_code Coupon code, if any.
	 * @return void
	 */
	private static function log_send( $user_id, $status, $coupon_code ) {
		global $wpdb;

		$log = RVPW_Recently_Viewed_Products_For_Woocommerce_Schema::table_email_log();

		// phpcs:disable WordPress.DB
		$wpdb->insert(
			$log,
			array(
				'user_id'     => absint( $user_id ),
				'sent_at'     => gmdate( 'Y-m-d H:i:s' ),
				'status'      => substr( (string) $status, 0, 20 ),
				'coupon_code' => '' !== $coupon_code ? substr( $coupon_code, 0, 64 ) : null,
			),
			array( '%d', '%s', '%s', '%s' )
		);
		// phpcs:enable WordPress.DB
	}

	/**
	 * Token for a user's unsubscribe link.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function unsub_token( $user_id ) {
		return wp_hash( 'rvpw_unsub_' . absint( $user_id ) );
	}

	/**
	 * Unsubscribe URL for a user.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function unsubscribe_url( $user_id ) {
		return add_query_arg(
			array(
				'rvpw_unsub' => absint( $user_id ),
				'rvpw_token' => self::unsub_token( $user_id ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Unsubscribe footer markup.
	 *
	 * @param int $user_id User ID.
	 * @return string
	 */
	private static function unsubscribe_footer( $user_id ) {
		return sprintf(
			'<p style="margin-top:24px;font-size:12px;color:#888;">%1$s <a href="%2$s">%3$s</a>.</p>',
			esc_html__( 'No longer want these emails?', 'recently-viewed-products-for-woocommerce' ),
			esc_url( self::unsubscribe_url( $user_id ) ),
			esc_html__( 'Unsubscribe', 'recently-viewed-products-for-woocommerce' )
		);
	}

	/**
	 * Wrap body content in a minimal HTML email shell.
	 *
	 * @param string $subject Subject (used as title).
	 * @param string $body    Body HTML.
	 * @return string
	 */
	private static function wrap_html( $subject, $body ) {
		$out  = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' . esc_html( $subject ) . '</title></head>';
		$out .= '<body style="margin:0;padding:24px;background:#f6f7f7;font-family:Arial,Helvetica,sans-serif;color:#1d2327;">';
		$out .= '<div style="max-width:560px;margin:0 auto;background:#fff;padding:24px;border-radius:8px;">' . $body . '</div>';
		$out .= '</body></html>';

		return $out;
	}

	/**
	 * Handle an unsubscribe request. Hooked to 'init'.
	 *
	 * @return void
	 */
	public static function handle_unsubscribe() {
		// Email links cannot carry a session nonce; the per-user HMAC token below
		// is the verification mechanism (validated with hash_equals).
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		if ( empty( $_GET['rvpw_unsub'] ) || empty( $_GET['rvpw_token'] ) ) {
			return;
		}

		$user_id = absint( wp_unslash( $_GET['rvpw_unsub'] ) );
		$token   = sanitize_text_field( wp_unslash( $_GET['rvpw_token'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( ! $user_id || ! hash_equals( self::unsub_token( $user_id ), $token ) ) {
			wp_die( esc_html__( 'This unsubscribe link is invalid or has expired.', 'recently-viewed-products-for-woocommerce' ), '', array( 'response' => 400 ) );
		}

		update_user_meta( $user_id, 'rvpw_email_unsubscribed', 1 );

		wp_die(
			esc_html__( 'You have been unsubscribed from recently viewed product emails.', 'recently-viewed-products-for-woocommerce' ),
			esc_html__( 'Unsubscribed', 'recently-viewed-products-for-woocommerce' ),
			array( 'response' => 200 )
		);
	}

	/**
	 * Admin-post handler: send a test email to the current user.
	 *
	 * @return void
	 */
	public static function send_test() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this action.', 'recently-viewed-products-for-woocommerce' ) );
		}

		check_admin_referer( 'rvpw_send_test_email' );

		$settings = RVPW_Recently_Viewed_Products_For_Woocommerce_Settings::get_settings();
		$sent     = self::send_to_user( get_current_user_id(), $settings, true );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'      => 'rvpw-settings',
					'tab'       => 'email',
					'rvpw_test' => $sent ? 'sent' : 'failed',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
