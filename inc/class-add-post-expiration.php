<?php
namespace Post_Expiration\inc;

use \DateTime;
use \DateTimeZone;

defined( 'ABSPATH' ) or die( 'File cannot be accessed directly' );

class Add_Post_Expiration {

	public static function init() {

		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'wp_admin_style' ) );
		add_action( 'post_submitbox_misc_actions', array( __CLASS__, 'add_to_publish_box' ) );
		add_action( 'save_post', array( __CLASS__, 'save_expiration_post_meta' ) );

		// add_filter( 'cron_schedules', array( __CLASS__, 'add_15_minute_cron_interval' ) );
		add_filter( 'manage_edit-peta_action_columns', array( __CLASS__, 'register_columns' ) );
		add_action( 'manage_peta_action_posts_custom_column', array( __CLASS__, 'display_columns' ), 10, 2 );

		add_action( 'check_for_expired_posts', array( __CLASS__, 'unpublish_expired_posts' ) );
		add_action( 'admin_init', array( __CLASS__, 'unpublish_expired_posts' ) );
		add_action( 'template_redirect', array( __CLASS__, 'trash_redirect' ) );
	}

	public static function trash_redirect() {
	    if ( ! current_user_can( 'edit_pages' ) ) {
		    if ( is_404() ) {
				global $wp_query, $wpdb;
				$page_id = $wpdb->get_var( $wp_query->request );
				$post_status = get_post_status( $page_id );
				if ( 'draft' === $post_status ) {
					wp_redirect( home_url( 'action' ), 302 );
					die();
				}
			}
		}
	}

	public static function wp_admin_style() {
		wp_register_style( 'admin-css', plugins_url( 'inc/css/admin.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'admin-css' );
		wp_register_style( 'expiration', plugins_url( 'inc/css/expiration.css', dirname( __FILE__ ) ) );
		wp_enqueue_style( 'expiration' );
	}

	public static function add_to_publish_box() {
		global $post;

		/* check if this is a post, if not then we won't add the custom field */
		/* change this post type to any type you want to add the custom field to */
		// if ( 'peta_action' !== get_post_type( $post ) ) {
		// 	return false;
		// }
		if ( current_user_can( 'edit_pages' ) ) {
			// get the value corrent value of the custom field
			$expiration_enabled = self::get_expiration_enabled( $post->ID );
			$expiration_timestamp = self::get_expiration_timestamp( $post->ID );

			?>
		<div class="misc-pub-section exptime">
<?php		if ( $expiration_enabled ) { ?>
			<span class="need-function" id="exp_timestamp" style="<?php echo $color_style; ?>"> Expiration set for: <b><?php echo $expiration_timestamp;?></b></span>
<?php	} ?>
			<div class="exptime_timestamp_option">
				<div class="hide-if-js" id="post-visibility-select" style="display: block;">
					<label class="selectit"><input type="radio" name="ptb-enable-expiration" value="0" <?php if ( ! $expiration_enabled ) { echo 'checked="checked"'; } ?> class='cs_enable_schedule'> Disable</label><br>
					<label class="selectit"><input type="radio" value="1" name="ptb-enable-expiration" <?php if ( $expiration_enabled ) { echo 'checked="checked"'; } ?> class='cs_enable_schedule' id='exp_enable'> Enable</label><br>
				</div>

				<div class="timestamp-wrap">
					<?php /*	if ( '1' === $expiration_enabled ) {
						echo 'Expiration is Set<br>';
					*/	?>

					<span id='exp_time_f' style="<?php /* echo ($expiration_enabled=='Disable')?'display:none':''; */?>"><?php self::time_dropdown( $expiration_timestamp ) ?>
					</span>
					<p>
						<span class="done-function">Server Time: <?php echo date( 'd M, Y H:i:s', current_time( 'timestamp' ) ); ?> </span>
					</p>
				</div>
			</div>
		</div>
		<?php
		}
	}

	private static function time_dropdown( $datestring ) {
		global $wp_locale;

		if ( ! empty( $datestring ) ) {
			$edit = 'Y';
		} else {
			$edit = false;
		}

		$time_adj = current_time( 'timestamp' );

		$jj = ($edit) ? mysql2date( 'd', $datestring, false ) : gmdate( 'd', $time_adj );
		$mm = ($edit) ? mysql2date( 'm', $datestring, false ) : gmdate( 'm', $time_adj );
		$aa = ($edit) ? mysql2date( 'Y', $datestring, false ) : gmdate( 'Y', $time_adj );
		$hh = ($edit) ? mysql2date( 'H', $datestring, false ) : gmdate( 'H', $time_adj );
		$mn = ($edit) ? mysql2date( 'i', $datestring, false ) : gmdate( 'i', $time_adj );
		$ss = ($edit) ? mysql2date( 's', $datestring, false ) : gmdate( 's', $time_adj );

		$cur_jj = gmdate( 'd', $time_adj );
		$cur_mm = gmdate( 'm', $time_adj );
		$cur_aa = gmdate( 'Y', $time_adj );
		$cur_hh = gmdate( 'H', $time_adj );
		$cur_mn = gmdate( 'i', $time_adj );

		$month = '<select ' . 'id="exp_mm"' . "name=\"exp_m\">\n";
		for ( $i = 1; $i < 13; $i = $i + 1 ) {
			$monthnum = zeroise( $i, 2 );
			$month .= "\t\t\t" . '<option value="' . $monthnum . '"';
			if ( intval( $mm ) === $i ) {
				$month .= ' selected="selected"';
			}
			// translators: 1: month number (01, 02, etc.), 2: month abbreviation
			$month .= '>' . sprintf( __( '%1$s-%2$s' ), $monthnum, $wp_locale->get_month_abbrev( $wp_locale->get_month( $i ) ) ) . "</option>\n";
		}
		$month .= '</select>';

		$day = '<input type="text" ' .'id="exp_jj"'. 'name="exp_d" value="' . $jj . '" size="2" maxlength="2" autocomplete="off" style="width:2em" />';
		$year = '<input type="text" ' .'id="exp_aa"'. 'name="exp_y" value="' . $aa . '" size="4" maxlength="4" autocomplete="off" style="width:3em" />';
		$hour = '<input type="text" ' .'id="exp_hh"' . 'name="exp_hh" value="' . $hh . '" size="2" maxlength="2" autocomplete="off" style="width:2em" />';
		$minute = '<input type="text" ' .'id="exp_mn"'. 'name="exp_mn" value="' . $mn . '" size="2" maxlength="2" autocomplete="off" style="width:2em" />';

		/* translators: 1: month, 2: day, 3: year, 4: hour, 5: minute */
		printf( __( '%1$s %2$s, %3$s @ %4$s : %5$s' ), $month, $day, $year, $hour, $minute );
	}

	/***
	*
	**/
	public static function save_expiration_post_meta( $post_id ) {
		global $wpdb;
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		// Checkbox for "enable scheduling"
		$enabled = ( empty( $_POST['ptb-enable-expiration'] ) ? false : true );
		if ( $enabled ) {
			self::set_expiration_enabled( $post_id );
		} else {
			self::set_expiration_disabled( $post_id );
		}

		// Textboxes for "expiration date"
		if ( isset( $_POST['exp_y'] ) ) {

			$year = $_POST['exp_y'];
			$month = $_POST['exp_m'];
			$day = $_POST['exp_d'];
			$hour = $_POST['exp_hh'];
			$minutes = $_POST['exp_mn'];
			$seconds = '00';

			$date_string = $year . '-' . $month . '-' . $day . ' ' . $hour . ':' . $minutes . ':' . $seconds;

			$datetime = new DateTime( $date_string );
			$date = $datetime->format( 'Y-m-d H:i:s' );

			if ( self::check_date_format( $date ) ) {
				// Only set timestamp if the date is valid
				self::set_expiration_timestamp( $post_id, $date );
			}
		}
	}

	private static function check_date_format( $date ) {
		// match the format of the date
		// in this case, it is ####-##-##
		if ( preg_match( "/^([0-9]{4})-([0-9]{2})-([0-9]{2})\ ([0-9]{2}):([0-9]{2}):([0-9]{2})$/", $date, $parts ) ) {
			// check whether the date is valid or not
			// $parts[1] = year; $parts[2] = month; $parts[3] = day
			// $parts[4] = hour; [5] = minute; [6] = second
			if ( checkdate( $parts[2], $parts[3], $parts[1] ) )	{
				// NOTE: We are only checking the HOUR here, since we won't make use of Min and Sec anyway
				if ( $parts[4] <= 23 ) {
					// time (24-hour hour) is okay
					return true;
				} else {
					// not a valid 24-hour HOUR
					return false;
				}
			} else {
				// not a valid date by php checkdate()
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * register and display columns
	 *
	 */
	public static function register_columns( $columns ) {

		// call the global post type object
		global $post_type_object;

		// get display for column icon
		// Removed from span below = class="dashicons dashicons-welcome-comments">
		$columns['expiration'] = '<span title="' . __( 'Expiration', 'expiration' ) . '">Expiration</span>';

		// return the columns
		return $columns;
	}


	/**
	 * the custom display columns for click counts
	 *
	 * @param  [type] $column_name [description]
	 * @param  [type] $post_id     [description]
	 * @return [type]              [description]
	 */
	public static function display_columns( $column, $post_id ) {

		$expiration_enabled = self::get_expiration_enabled( $post_id );
		$expiration_timestamp = self::get_expiration_timestamp( $post_id );

		// start my column output
		switch ( $column ) {

			case 'expiration':

				$has_post_expired = self::has_post_expired( $post_id );

				if ( ! $expiration_enabled ) {
					echo 'Expiration not set';

				} elseif ( $has_post_expired ) {
					echo 'Post expired';

				} else {

					echo '<span>Post Expires on <br> ' . $expiration_timestamp. '</span>';
				}

				break;
		}
	}

	/***
	*
	**/
	private static function has_post_expired( $post_id ) {
		$enabled = self::get_expiration_enabled( $post_id );
		$expires_on = self::get_expiration_timestamp( $post_id );
		$current_time = current_time( 'mysql' );

		if ( $enabled && ( $expires_on > $current_time ) ) {
			return false;
		} else {
			return true;
		}
	}

	private static function get_expired_posts() {
		$args = array(
			'post_status' => array(
				'publish',
			),
			'post_type'  => 'peta_action',
			'meta_query' => array(
				array(
					'key' => 'post_expiration_enabled',
					'value' => '1',
				),
			),
			'suppress_filters' => false,
		);

		$query = new \WP_Query( $args );
		$posts_with_expiration_enabled = $query->get_posts();

		$expired_posts = array();

		foreach ( $posts_with_expiration_enabled as $post ) {
			if ( self::has_post_expired( $post->ID ) ) {
				$expired_posts[] = $post;
			}
		}
		return $expired_posts;
	}

	public static function unpublish_expired_posts() {

		$expired_posts = self::get_expired_posts();

		foreach ( $expired_posts as $expired_post ) {
			$post_id = $expired_post->ID;
		    self::unpublish_post( $expired_post->ID );
		}
	}

	public static function unpublish_post( $post_id ) {
			$unpublish_post = array(
				'ID'           => $post_id,
				'post_status'   => 'draft',
				);

			wp_update_post( $unpublish_post );
	}

	private static function get_expiration_enabled( $post_id ) {
		$expiration_enabled = get_post_meta( $post_id, 'post_expiration_enabled', true );

		if ( '1' === $expiration_enabled ) {
			return true;
		} else {
			return false;
		}
	}

	private static function set_expiration_enabled( $post_id ) {
		update_post_meta( $post_id, 'post_expiration_enabled', '1' );
	}

	private static function set_expiration_disabled( $post_id ) {
		delete_post_meta( $post_id, 'post_expiration_enabled' );
	}

	private static function get_expiration_timestamp( $post_id ) {
		$expiration_date = get_post_meta( $post_id, 'post_expiration_timestamp', true );
		return $expiration_date;
	}

	private static function set_expiration_timestamp( $post_id, $timestamp ) {
		update_post_meta( $post_id, 'post_expiration_timestamp', $timestamp );
	}

	/***
	* If a user clicks on a post that is expired, we would like the user to redirect
	* to the Take Action landing page.
	**/
	private static function redirect_to_take_action( $post_id ) {
		return 'If a user clicks on an expired post, redirect to Take Action landing page.';
	}

	public static function update_log() {
		$time = date( 'F jS Y, H:i', time() + 25200 );
		$time = date( 'F jS Y, H:i', time() );
		$ban = "#Cron last run $time\r\n";
		$file = plugin_dir_path( __FILE__ ) . '/errors.txt';
		$open = fopen( $file, 'a' );
		$write = fputs( $open, $ban );
		fclose( $open );
	}

	public static function plugin_files() {
		echo '<strong>File Name = </strong>' . basename( __FILE__ ) . '<br>';
		echo '<strong>File Location = </strong>' . __FILE__ . '<br>';
	}

	public static function activation_hook() {
		if ( ! wp_next_scheduled( 'check_for_expired_posts' ) ) {
			wp_schedule_event( time(), '15_minutes', 'check_for_expired_posts' );
		}
	}

	public static function deactivation_hook() {
		wp_schedule_event( time(), '15_minutes', 'check_for_expired_posts' );
	}

	public static function add_15_minute_cron_interval( $schedules ) {
		$schedules['15_minutes'] = array(
			'interval' => 900,
			'display' => __( 'Every 15 Minutes' ),
		);
		return $schedules;
	}
}
