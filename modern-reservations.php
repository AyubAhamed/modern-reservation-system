<?php
/**
 * Plugin Name: Seaford RSL Booking
 * Plugin URI:  https://synolodigital.com/
 * Description: A modern, mobile-friendly reservation system with date picker, guest & meal selectors, dynamic time slots, email notifications, and an admin settings panel. Use shortcode: [modern_reservation]
 * Version:     1.0.0
 * Author:      ayub-ahamed
 * License:     GPLv2 or later
 * Text Domain: https://synolodigital.com/
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'MR_VERSION', '1.0.0' );
define( 'MR_PLUGIN_FILE', __FILE__ );
define( 'MR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Activation: create CPT and default options.
register_activation_hook( __FILE__, function() {
    // Create default options if not set.
    $defaults = array(
        'max_guests'       => 12,
        'slot_capacity'    => 20,
        'meals'            => array('Breakfast','Lunch','Dinner'),
        'timeslots'        => array(
            'Breakfast' => array('08:00','08:15','08:30','08:45','09:00','09:15','09:30','09:45','10:00'),
            'Lunch'     => array('12:00','12:15','12:30','12:45','13:00','13:15','13:30','13:45','14:00'),
            'Dinner'    => array('17:30','17:45','18:00','18:15','18:30','18:45','19:00','19:15','19:30','19:45','20:00','20:15','20:30')
        ),
        'terms_url'        => '',
        'booking_notice' => 'Please Note: Bookings must be confirmed prior to your arrival. If the staff are unable to contact the phone number or email address provided on the booking to confirm attendance, the bistro may be unable to hold the reservation. It is a requirement that all bookings must adhere to the government Covid-19 regulations within the venue including the QR sign in, sanitising & having a valid Covid Vaccination Certificate. We thank you for your support from the Seaford RSL.',
        'brand_name'       => get_bloginfo('name'),
        'notify_email'     => get_option('admin_email'),
        'calendar_timezone'=> wp_timezone_string(),
    );
    foreach ($defaults as $key => $val) {
        if ( get_option("mr_$key", null) === null ) {
            add_option("mr_$key", $val);
        }
    }
    mr_register_cpt();
    flush_rewrite_rules();
});

// Deactivation
register_deactivation_hook( __FILE__, function() {
    flush_rewrite_rules();
});

// Register Custom Post Type for reservations
function mr_register_cpt() {
	
	// Guest Count
    register_post_type('mr_guest_count', array(
        'labels' => array(
            'name' => __('Guest Counts','modern-reservations'),
            'singular_name' => __('Guest Count','modern-reservations'),
        ),
        'public' => false,
        'show_ui' => false, // hidden from main menu
        'supports' => array('title'),
    ));

    // Time Slot
    register_post_type('mr_time_slot', array(
        'labels' => array(
            'name' => __('Time Slots','modern-reservations'),
            'singular_name' => __('Time Slot','modern-reservations'),
        ),
        'public' => false,
        'show_ui' => false,
        'supports' => array('title'),
    ));
	
    $labels = array(
        'name' => __('Reservations','modern-reservations'),
        'singular_name' => __('Reservation','modern-reservations'),
        'add_new_item' => __('Add New Reservation','modern-reservations'),
        'edit_item' => __('Edit Reservation','modern-reservations'),
        'new_item' => __('New Reservation','modern-reservations'),
        'view_item' => __('View Reservation','modern-reservations'),
        'search_items' => __('Search Reservations','modern-reservations'),
        'not_found' => __('No reservations found.','modern-reservations'),
        'menu_name' => __('Reservations','modern-reservations'),
    );
    $args = array(
        'labels' => $labels,
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array('title'),
        'has_archive' => false,
        'rewrite' => false,
        'capability_type' => 'post',
    );
    register_post_type('mr_reservation',$args);
}
add_action('init','mr_register_cpt');

// Admin settings page
add_action('admin_menu', function(){
    add_menu_page(
        __('Reservations','modern-reservations'),
        __('Reservations','modern-reservations'),
        'manage_options',
        'mr-settings',
        'mr_render_settings_page',
        'dashicons-clipboard',
        26
    );

    // Submenus
    add_submenu_page('mr-settings', __('Guest Count','modern-reservations'), __('Guest Count','modern-reservations'), 'manage_options', 'mr-guest-count', 'mr_render_guest_count_page');
    add_submenu_page('mr-settings', __('Time Slots','modern-reservations'), __('Time Slots','modern-reservations'), 'manage_options', 'mr-time-slots', 'mr_render_time_slots_page');
});

function mr_render_guest_count_page() {
    if ( isset($_POST['mr_guest_nonce']) && wp_verify_nonce($_POST['mr_guest_nonce'],'mr_save_guest') ) {
        $guest_num = intval($_POST['guest_number']);
        if ($guest_num > 0) {
            wp_insert_post(array(
                'post_type'=>'mr_guest_count',
                'post_title'=>$guest_num,
                'post_status'=>'publish'
            ));
            echo '<div class="updated"><p>Guest count added.</p></div>';
        }
    }

    if ( isset($_POST['delete_guest']) && !empty($_POST['delete_ids']) ) {
        foreach ($_POST['delete_ids'] as $id) {
            wp_delete_post(intval($id), true);
        }
        echo '<div class="updated"><p>Selected guest counts deleted.</p></div>';
    }

    $guests = get_posts(array('post_type'=>'mr_guest_count','numberposts'=>-1));
    ?>
		<div class="wrap mr-time-slots">
		<h1>Guest Count</h1>

		<!-- Add Guest Count -->
		<div class="mr-card">
			<h2>Add New Guest Count</h2>
			<form method="post" class="mr-form">
				<?php wp_nonce_field('mr_save_guest','mr_guest_nonce'); ?>
				<div class="mr-row-group">
					<div class="mr-row">
						<label>Number of Guests</label>
						<input type="number" name="guest_number" min="1" required>
					</div>
				</div>
				<button type="submit" class="button button-primary">+ Add Guest Count</button>
			</form>
		</div>

		<!-- Existing Guest Counts -->
		<div class="mr-card">
			<h2>Existing Guest Counts</h2>
			<form method="post">
				<table class="widefat">
					<thead>
						<tr>
							<th>Select</th>
							<th>Guest Number</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($guests as $g): ?>
							<tr>
								<td><input type="checkbox" name="delete_ids[]" value="<?php echo $g->ID;?>"></td>
								<td><?php echo esc_html($g->post_title);?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<button type="submit" name="delete_guest" class="button button-danger">Delete Selected</button>
			</form>
		</div>
	</div>

    <?php
}

function mr_render_time_slots_page() {
    $meals = get_option('mr_meals', array('Breakfast','Lunch','Dinner'));
    $guest_counts = get_posts(array('post_type'=>'mr_guest_count','numberposts'=>-1));

    if ( isset($_POST['mr_slot_nonce']) && wp_verify_nonce($_POST['mr_slot_nonce'],'mr_save_slot') ) {
        $from = sanitize_text_field($_POST['from_time']);
        $to = sanitize_text_field($_POST['to_time']);
        $type = sanitize_text_field($_POST['meal_type']);
        $guest = intval($_POST['guest_number']);
        $title = "$type: $from - $to ($guest guests)";

        wp_insert_post(array(
            'post_type'=>'mr_time_slot',
            'post_title'=>$title,
            'post_status'=>'publish',
            'meta_input'=>array(
                'from'=>$from,
                'to'=>$to,
                'type'=>$type,
                'guest_number'=>$guest
            )
        ));
        echo '<div class="updated"><p>Time slot added.</p></div>';
    }

    if ( isset($_POST['delete_slot']) && !empty($_POST['delete_ids']) ) {
        foreach ($_POST['delete_ids'] as $id) {
            wp_delete_post(intval($id), true);
        }
        echo '<div class="updated"><p>Selected time slots deleted.</p></div>';
    }

    $slots = get_posts(array('post_type'=>'mr_time_slot','numberposts'=>-1));
    ?>
		<div class="wrap mr-time-slots">
			<h1>Manage Time Slots</h1>

			<!-- Add Slot Form -->
			<div class="mr-card">
				<h2>Add New Time Slot</h2>
				<form method="post" class="mr-form">
					<?php wp_nonce_field('mr_save_slot','mr_slot_nonce'); ?>

					<div class="mr-row-group">
						<div class="mr-row">
							<label>From</label>
							<input type="time" name="from_time" required>
						</div>

						<div class="mr-row">
							<label>To</label>
							<input type="time" name="to_time" required>
						</div>
					</div>

					<div class="mr-row-group">
						<div class="mr-row">
							<label>Type</label>
							<select name="meal_type">
								<?php foreach($meals as $m): ?>
									<option value="<?php echo esc_attr($m);?>"><?php echo esc_html($m);?></option>
								<?php endforeach;?>
							</select>
						</div>

						<div class="mr-row">
							<label>Number of Guest</label>
							<input type="number" name="guest_number" min="1" required>
						</div>
					</div>

					<button type="submit" class="button button-primary">+ Save</button>
				</form>
			</div>

			<!-- Existing Slots -->
			<div class="mr-card">
				<h2>Available Time Slots</h2>
				<form method="post">
					<table class="mr-table">
						<thead>
							<tr>
								<th>Select</th>
								<th>From</th>
								<th>To</th>
								<th>Type</th>
								<th>Guests</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach($slots as $s): ?>
								<tr>
									<td><input type="checkbox" name="delete_ids[]" value="<?php echo $s->ID;?>"></td>
									<td><?php echo esc_html(get_post_meta($s->ID,'from',true));?></td>
									<td><?php echo esc_html(get_post_meta($s->ID,'to',true));?></td>
									<td><?php echo esc_html(get_post_meta($s->ID,'type',true));?></td>
									<td><?php echo esc_html(get_post_meta($s->ID,'guest_number',true));?></td>
								</tr>
							<?php endforeach;?>
						</tbody>
					</table>
					<button type="submit" name="delete_slot" class="button button-danger">Delete Selected</button>
				</form>
			</div>
		</div>


    <?php
}

function mr_render_settings_page() {
    if ( ! current_user_can( 'manage_options' ) ) return;
    if ( isset($_POST['mr_settings_nonce']) && wp_verify_nonce($_POST['mr_settings_nonce'],'mr_save_settings') ) {
        $max_guests    = max(1, intval($_POST['max_guests']));
        $slot_capacity = max(1, intval($_POST['slot_capacity']));
        $terms_url     = esc_url_raw($_POST['terms_url']);
        $brand_name    = sanitize_text_field($_POST['brand_name']);
        $calendar_timezone = sanitize_text_field($_POST['calendar_timezone']);
        $booking_notice = sanitize_textarea_field($_POST['booking_notice']);

        // Meals
        $meals = array_filter(array_map('sanitize_text_field', array_map('trim', explode(',', $_POST['meals']))));

        // Timeslots JSON
        $timeslots_json = wp_unslash($_POST['timeslots_json']);
        $timeslots = json_decode($timeslots_json, true);
        if (!is_array($timeslots)) $timeslots = get_option('mr_timeslots');

        // --- FIXED MULTIPLE EMAILS ---
        $notify_emails_raw = explode(',', $_POST['notify_email']);
        $notify_emails = array_map('sanitize_email', array_map('trim', $notify_emails_raw));
        $notify_emails = array_filter($notify_emails); // remove invalid/empty emails
        $notify_emails_str = implode(',', $notify_emails);

        // Save options
        update_option('mr_max_guests', $max_guests);
        update_option('mr_slot_capacity', $slot_capacity);
        update_option('mr_notify_email', $notify_emails_str);
        update_option('mr_terms_url', $terms_url);
        update_option('mr_brand_name', $brand_name);
        update_option('mr_calendar_timezone', $calendar_timezone);
        update_option('mr_meals', $meals);
        update_option('mr_timeslots', $timeslots);
        update_option('mr_booking_notice', $booking_notice);

        echo '<div class="updated"><p>'.__('Settings saved.','modern-reservations').'</p></div>';
    }

    $max_guests = get_option('mr_max_guests',12);
    $slot_capacity = get_option('mr_slot_capacity',20);
    $notify_email = get_option('mr_notify_email',get_option('admin_email'));
    $terms_url = get_option('mr_terms_url','');
    $brand_name = get_option('mr_brand_name',get_bloginfo('name'));
    $calendar_timezone = get_option('mr_calendar_timezone', wp_timezone_string());
    $meals = get_option('mr_meals', array('Breakfast','Lunch','Dinner'));
    $timeslots = get_option('mr_timeslots');

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Modern Reservations — Settings','modern-reservations');?></h1>
        <form method="post">
            <?php wp_nonce_field('mr_save_settings','mr_settings_nonce'); ?>
            <table class="form-table" role="presentation">
                <tr>
                    <th scope="row"><label><?php esc_html_e('Brand Name','modern-reservations');?></label></th>
                    <td><input name="brand_name" type="text" value="<?php echo esc_attr($brand_name);?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Max Guests per Booking','modern-reservations');?></label></th>
                    <td><input name="max_guests" type="number" min="1" value="<?php echo esc_attr($max_guests);?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Capacity per Time Slot','modern-reservations');?></label></th>
                    <td><input name="slot_capacity" type="number" min="1" value="<?php echo esc_attr($slot_capacity);?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Notification Email','modern-reservations');?></label></th>
                    <td><input name="notify_email" type="email" value="<?php echo esc_attr($notify_email);?>" class="regular-text" multiple>
                    <p class="description">You can enter multiple emails, separated by commas.</p></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Terms & Conditions URL','modern-reservations');?></label></th>
                    <td><input name="terms_url" type="url" value="<?php echo esc_attr($terms_url);?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Booking Notice','modern-reservations');?></label></th>
                    <td>
                        <textarea name="booking_notice" rows="5" class="large-text"><?php echo esc_textarea(get_option('mr_booking_notice','')); ?></textarea>
                        <p class="description"><?php esc_html_e('This notice will be displayed above the reservation form to users.','modern-reservations');?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('WordPress Timezone','modern-reservations');?></label></th>
                    <td><input name="calendar_timezone" type="text" value="<?php echo esc_attr($calendar_timezone);?>" class="regular-text">
                        <p class="description"><?php esc_html_e('Used for date/time on reservations and emails.','modern-reservations');?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Meals (comma-separated)','modern-reservations');?></label></th>
                    <td><input name="meals" type="text" value="<?php echo esc_attr(implode(',', $meals));?>" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label><?php esc_html_e('Timeslots JSON','modern-reservations');?></label></th>
                    <td>
<textarea name="timeslots_json" rows="10" cols="70"><?php echo esc_textarea(wp_json_encode($timeslots, JSON_PRETTY_PRINT));?></textarea>
<p class="description"><?php esc_html_e('Provide a JSON object where keys are meal names and values are arrays of HH:MM 24h strings.','modern-reservations');?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <h2><?php esc_html_e('How to Use','modern-reservations');?></h2>
        <p><?php esc_html_e('Add the shortcode [modern_reservation] to any page.','modern-reservations');?></p>
    </div>
    <?php
}

add_action('admin_enqueue_scripts', function($hook){
    // Load for Time Slots AND Guest Count pages
    if (strpos($hook, 'mr-time-slots') !== false || strpos($hook, 'mr-guest-count') !== false) {
        wp_enqueue_style('mr-admin', MR_PLUGIN_URL . 'admin.css', array(), MR_VERSION);
    }
});

// Enqueue assets
add_action('wp_enqueue_scripts', function(){
    // Flatpickr datepicker (CDN)
    wp_enqueue_style('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css', array(), '4.6.13');
    wp_enqueue_script('flatpickr', 'https://cdn.jsdelivr.net/npm/flatpickr', array(), '4.6.13', true);
    // Choices.js for modern selects
    wp_enqueue_style('choices', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/styles/choices.min.css', array(), '10.2.0');
    wp_enqueue_script('choices', 'https://cdn.jsdelivr.net/npm/choices.js/public/assets/scripts/choices.min.js', array(), '10.2.0', true);

	wp_enqueue_style('mr-styles', MR_PLUGIN_URL . 'public/css/modern-reservations.css', array('flatpickr','choices'), MR_VERSION);
    wp_enqueue_script('mr-scripts', MR_PLUGIN_URL . 'public/js/modern-reservations.js', array('jquery','flatpickr','choices'), MR_VERSION, true);
    wp_localize_script('mr-scripts','MR_AJAX', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce('mr_nonce'),
        'max_guests' => (int) get_option('mr_max_guests',12),
        'meals' => get_option('mr_meals'),
        'terms_url' => get_option('mr_terms_url',''),
        'brand' => get_option('mr_brand_name',get_bloginfo('name')),
    ));
});

// Shortcode
add_shortcode('modern_reservation', function($atts){
    ob_start();
    include MR_PLUGIN_DIR . 'templates/form.php';
    return ob_get_clean();
});

// AJAX: get available slots
add_action('wp_ajax_mr_get_slots', 'mr_get_slots');
add_action('wp_ajax_nopriv_mr_get_slots', 'mr_get_slots');
function mr_get_slots() {
    check_ajax_referer('mr_nonce','nonce');
    $date  = sanitize_text_field($_POST['date'] ?? '');
    $meal  = sanitize_text_field($_POST['meal'] ?? '');
    $guests = max(1, intval($_POST['guests'] ?? 1));

    $timeslots = get_option('mr_timeslots', array());
    $capacity  = (int) get_option('mr_slot_capacity', 20);
    $response = array();

    if ( isset($timeslots[$meal]) ) {
        foreach ($timeslots[$meal] as $slot) {
            // Count existing reservations for this date+slot
            $count = mr_count_reservations($date, $slot, $meal);
            $available = max(0, $capacity - $count);
            $response[] = array(
                'time' => $slot,
                'available' => $available >= $guests
            );
        }
    }
    wp_send_json_success($response);
}

function mr_count_reservations($date, $time, $meal) {
    $q = new WP_Query(array(
        'post_type' => 'mr_reservation',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'meta_query' => array(
            'relation' => 'AND',
            array('key' => 'mr_date','value' => $date,'compare' => '='),
            array('key' => 'mr_time','value' => $time,'compare' => '='),
            array('key' => 'mr_meal','value' => $meal,'compare' => '='),
        )
    ));
    $count = $q->found_posts;
    wp_reset_postdata();
    return $count;
}

// AJAX: submit reservation
add_action('wp_ajax_mr_submit', 'mr_submit');
add_action('wp_ajax_nopriv_mr_submit', 'mr_submit');
function mr_submit() {
    // Verify nonce
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'mr_nonce') ) {
        wp_send_json_error(array('message' => __('Security check failed.', 'modern-reservations')));
    }

    // Get and sanitize fields
    $name    = sanitize_text_field($_POST['name']);
    $phone   = sanitize_text_field($_POST['phone']);
    $email   = sanitize_email($_POST['email']);
    $date    = sanitize_text_field($_POST['date']);
    $time    = sanitize_text_field($_POST['time']);
    $meal    = sanitize_text_field($_POST['meal']);
    $guests  = intval($_POST['guests']);
    $note    = sanitize_textarea_field($_POST['note']);
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Validate required fields
    if ( empty($name) || empty($phone) || empty($email) || empty($date) || empty($time) || empty($meal) || empty($guests) ) {
        wp_send_json_error(array('message' => __('Please fill in all required fields.', 'modern-reservations')));
    }

    // Prepare brand name
    $brand = get_option('mr_brand_name', get_bloginfo('name'));

    // Get admin emails (single or multiple)
    $admin_emails_raw = get_option('mr_notify_email', get_option('admin_email'));
    $admin_emails = array_map('trim', explode(',', $admin_emails_raw));
    $admin_emails = array_filter($admin_emails); // remove empty strings

    $subject_admin = sprintf(__('New Reservation — %s', 'modern-reservations'), $brand);
    $body_admin = sprintf(
        __("New reservation received:\n\nName: %s\nPhone: %s\nEmail: %s\nDate: %s\nTime: %s\nMeal: %s\nGuests: %d\nNotes: %s\nPost ID: %d", 'modern-reservations'),
        $name, $phone, $email, $date, $time, $meal, $guests, $note, $post_id
    );

    // Send email to all admins
    if (!empty($admin_emails)) {
        wp_mail($admin_emails, $subject_admin, $body_admin);
    }


    // === GUEST EMAIL CONFIRMATION ===
    $subject_guest = sprintf(__('%s — Reservation Confirmed', 'modern-reservations'), $brand);
    $body_guest = sprintf(
        __("Hi %s,\n\nYour reservation at %s is confirmed:\nDate: %s\nTime: %s\nMeal: %s\nGuests: %d\n\nThank you!", 'modern-reservations'),
        $name, $brand, $date, $time, $meal, $guests
    );

    wp_mail($email, $subject_guest, $body_guest);

    // === SUCCESS RESPONSE ===
    wp_send_json_success(array(
        'message' => __('Reservation confirmed!', 'modern-reservations')
    ));
}
