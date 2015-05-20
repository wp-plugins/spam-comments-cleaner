<?php
/*
Plugin Name: Spam Comments Cleaner
Plugin URI:
Description: This plugin will delete all your spam comments in a regular time interval.
Version: 1.2.2
Author: Manish Kumar Agarwal
Author URI: http://www.youngtechleads.com
*/

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/

add_action( 'wordpress_spam_cleaner', 'wordpress_spam_cleaner_now' ); 

function wsc_start_cron( $schedule, $spam_delete_time = null ) {
	if ( $spam_delete_time != null ) {
		$time_arr = explode( ':', $spam_delete_time);
		date_default_timezone_set( 'GMT' );
		$time = mktime( $time_arr[0], $time_arr[1], 0);
	} else {
		$time = time();
	}
	wp_schedule_event( $time, $schedule, 'wordpress_spam_cleaner' );
}

function wordpress_spam_cleaner_now() {
	global $wpdb;
	
	$spam_comments_id_arr = $wpdb->get_col( "SELECT comment_id FROM {$wpdb->comments} WHERE comment_approved = 'spam'" ) ;
	if ( !empty( $spam_comments_id_arr ) ) {
		$spam_comments_ids = implode( ', ', array_map('intval', $spam_comments_id_arr) );
		
		$wpdb->query("DELETE FROM {$wpdb->comments} WHERE comment_id IN ( $spam_comments_ids )");
		$wpdb->query("DELETE FROM {$wpdb->commentmeta} WHERE comment_id IN ( $spam_comments_ids )");
		
		$wpdb->query( "OPTIMIZE TABLE $wpdb->comments" );
		$wpdb->query( "OPTIMIZE TABLE $wpdb->commentmeta" );
	}
}

function show_spam_count() {
	global $wpdb;
	echo $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved='spam'" );
}

function reschedule_delete_spam() {
	wp_reschedule_event( (time()+60), 'daily', 'wordpress_spam_cleaner' ); 
}

add_action( 'admin_menu', 'wsc_menu' );

function wsc_menu() {
  add_options_page( 'WordPress Spam Cleaner', 'WordPress Spam Cleaner', 'manage_options', 'wsc-options', 'wsc_options' );
}

function wsc_options() {
	$valid_nonce = wp_verify_nonce( $_REQUEST['_wpnonce'], 'wordpress_spam_cleaner' );
	if ( $valid_nonce ) {
		if ( isset( $_POST['delete_spam_now_button'] ) )
			wordpress_spam_cleaner_now();
		else if ( isset( $_POST['delete_spam_daily_button'] ) )
			wsc_start_cron( 'daily' );
		else if ( isset( $_POST['stop_deleting_spam_button'] ) )
			wsc_stop_schedule();
		else if ( isset( $_POST['reschedule_delete_spam_button'] ) )
			reschedule_delete_spam();
		else if ( isset( $_POST['delete_spam_hourly_button'] ) )
			wsc_start_cron( 'hourly' );
		else if ( isset( $_POST['delete_spam_twice_button'] ) )
			wsc_start_cron( 'twicedaily' );
		else if ( isset( $_POST['delete_spam_weekly'] ) )
			wsc_start_cron( 'weekly' );
		else if ( isset( $_POST['delete_spam_twiceweekly'] ) )
			wsc_start_cron( 'twiceweekly' );
		else if ( isset( $_POST['delete_spam_monthly'] ) )
			wsc_start_cron( 'monthly' );
		else if ( isset( $_POST['custom_delete_spam_time'] ) )
			wsc_start_cron( 'daily', $_POST['spam_delete_time'] );
	}
	?>
	<div class="wrap">
	<h2>WordPress Spam Cleaner</h2>
	<?php
	if ( !empty( $_POST ) ) { ?>
		<div id="message" class="updated fade">
			<strong>Settings updated</strong>
		</div>
	<?php } ?>
		<div id="form-buttons">
			<p>
				<?php 
				if ( wp_next_scheduled( 'wordpress_spam_cleaner' ) == NULL ) {	
					echo 'The schedule has not been started'; 
				} else  {
					echo 'Next Spam Delete: ', date( "l, F j, Y @ h:i a",( wp_next_scheduled( 'wordpress_spam_cleaner' ) ) );
				} 
				?>
			</p>
			<p>Current Spam Count in your site: <?php show_spam_count(); ?></p><br />
			 
			<form name="delete_spam_now_button" action="" method="post">
				<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
				<input type="hidden" name="delete_spam_now_button" value="update" />
				<div>
					<input class="button button-primary"  id="delete_spam_now_button" type="submit" value="Delete spam now &raquo;" />
				</div>
			</form><br />

			<?php if ( NULL == wp_next_scheduled( 'wordpress_spam_cleaner' ) ) { ?>
				<form name="delete_spam_hourly_button" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_hourly_button" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_hourly_button" type="submit" value="Delete spam hourly &raquo;" />
					</div>
				</form>
				<br />
				<form name="delete_spam_daily_button" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_daily_button" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_daily_button" type="submit" value="Delete spam daily &raquo;" />
					</div>
				</form>
				<br />
				<form name="delete_spam_twice_button" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_twice_button" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_twice_button" type="submit" value="Delete spam twice daily &raquo;" />
					</div>
				</form>
				<br />
				<form name="delete_spam_weekly" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_weekly" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_weekly" type="submit" value="Delete spam weekly &raquo;" />
					</div>
				</form>
				<br />
				<form name="delete_spam_twiceweekly" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_twiceweekly" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_twiceweekly" type="submit" value="Delete spam twice Monthly &raquo;" />
					</div>
				</form>
				<br />
				<form name="delete_spam_monthly" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="delete_spam_monthly" value="update" />
					<div>
						<input class="button button-primary" id="delete_spam_monthly" type="submit" value="Delete spam monthly &raquo;" />
					</div>
				</form>
				<br />
				<form name="custom_delete_spam_time" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="custom_delete_spam_time" value="update" />
					<div>
						<input class="button button-primary" id="custom_delete_spam_time" type="submit" value="Delete spam every day at &raquo;" />
						<input name="spam_delete_time" id="custom_delete_spam_time_text" placeholder="hr:mm" type="text" value="" />
						<?php date_default_timezone_set( 'GMT' ); ?>
						<b>Current time:</b> <?php echo date( 'F j, Y, g:i a' ); ?> GMT
					</div>
				</form>
				<br />
		<?php } else { ?>
				<form name="stop_deleting_spam_button" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="stop_deleting_spam_button" value="update" />
					<div>
						<input class="button button-primary" id="stop_deleting_spam_button" type="submit" value="Stop Deleting Spam &raquo;" />
					</div>
				</form>
				<br />
				<form name="reschedule_delete_spam_button" action="" method="post">
					<?php wp_nonce_field( 'wordpress_spam_cleaner' ); ?>
					<input type="hidden" name="reschedule_delete_spam_button" value="update" />
					<div>
						<input class="button button-primary" id="reschedule_delete_spam_button" type="submit" value="Reschedule to start in 1 minute &raquo;" />
						<i>Helpful for testing purpose</i>
					</div>
				</form>
				<br />
			<?php }	?>
			<br />
			Once you deactivate this plugin spam comments delete cron job will stop automatically.
			<br />
		</div>
		<h3>Quick Links</h3>
		<p>Contact me skype: mfsi_manish mail me: youngtec@youngtechleadds.com</p>
		<p><a target="_blank" href="https://wordpress.org/support/view/plugin-reviews/spam-comments-cleaner?filter=5">Rate Now</a> <strong>If this plugin really helps you, please do consider providing rating which can help others to find this plugin easily.</strong></p>
	</div>
	
	<style>
	#form-buttons .button-primary {
		width: 200px;
	}
</style>
	<?php
}

register_deactivation_hook( __FILE__, 'wsc_stop_schedule' );

function wsc_stop_schedule() {
	wp_clear_scheduled_hook( 'wordpress_spam_cleaner' );
}

add_filter( 'cron_schedules', 'cron_add_weekly' );
 
function cron_add_weekly( $schedules ) {
	// Adds once weekly to the existing schedules.
	$schedules['weekly'] = array(
		'interval' => 604800,
		'display' => __( 'Once Weekly' )
	);
	$schedules['twiceweekly'] = array(
		'interval' => 604800*2,
		'display' => __( 'Twice Monthly' )
	);
	$schedules['monthly'] = array(
		'interval' => 604800*4,
		'display' => __( 'Once Monthly' )
	);
	return $schedules;
}