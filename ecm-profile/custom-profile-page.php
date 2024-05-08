<?php
/*
Plugin Name: Toybeast Custom Profile Page
Description: Adds a custom profile page for users.
Version: 1.0
Author: Your Name
Depends: ToyBeast UpVote
*/
function cpp_process_profile_update() {
	$user_id = get_current_user_id();
    if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update-profile_' . $user_id)) {
        // Handle the form data
        $errors = [];

        // Get the posted data
        $first_name = sanitize_text_field($_POST['first_name']);
        $last_name = sanitize_text_field($_POST['last_name']);
        $nickname = sanitize_text_field($_POST['nickname']);
        $bio = sanitize_textarea_field($_POST['bio']);
        $password = $_POST['pass1'];
        $repeat_password = $_POST['pass2'];
        
        // Validate the data (for example, check if passwords match)
        if ($password && $repeat_password && $password !== $repeat_password) {
            $errors[] = 'Passwords do not match.';
        }

        // If no errors, update the user
        if (empty($errors)) {
            $userdata = array(
                'ID' => $user_id,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'nickname' => $nickname,
                'description' => $bio,
            );
            if ($password) {
                $userdata['user_pass'] = $password;  // Update password
            }
			        // Update share profile preference
			if (isset($_POST['share_profile'])) {
				update_user_meta($user_id, 'share_profile', sanitize_text_field($_POST['share_profile']));
			}
			
			if (isset($_POST['gamertag'])) {
            update_user_meta($user_id, 'gamertag', sanitize_text_field($_POST['gamertag']));
			}

			if (isset($_POST['favorite_game'])) {
				update_user_meta($user_id, 'favorite_game', sanitize_text_field($_POST['favorite_game']));
			}

            $updated = wp_update_user($userdata);
            if (is_wp_error($updated)) {
                $errors[] = $updated->get_error_message();
            } else {
                // Redirect to avoid resubmission on refresh
                wp_redirect(add_query_arg('updated', 'true', $_SERVER['REQUEST_URI']));
                exit;
            }
        }

        // If there were errors, display them
        if (!empty($errors)) {
            foreach ($errors as $error) {
                echo '<div class="error">' . $error . '</div>';
            }
        }
    }
}
add_action('init', 'cpp_process_profile_update');

function enqueue_my_plugin_scripts() {
    // Enqueue your JavaScript
    wp_enqueue_script('profile-plugin-script', plugin_dir_url(__FILE__) . 'js/profile-plugin-script.js', array('jquery'), null, true);
	wp_enqueue_style('cp-style-css', plugin_dir_url(__FILE__) . 'css/cp-style.css?v=0.1');
    // Localize your script right after enqueuing it
    wp_localize_script('profile-plugin-script', 'cpp_vars', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'set_icon_as_flair_nonce' => wp_create_nonce('set_icon_as_flair_nonce'),
		'purchase_ticket_nonce' => wp_create_nonce('purchase_ticket_nonce') // Add this line
    ));
}
add_action('wp_enqueue_scripts', 'enqueue_my_plugin_scripts');

function cpp_install() {
    $page_title = 'User Profile';
    $page_slug = 'user-profile';
    $page_content = 'This is where the profile will be displayed.';
    $page_template = '';  // Use default template, you can specify custom page templates if you have any.

    // Check if the page exists. If not, create it.
    if (null == get_page_by_title($page_title)) {
        $page_id = wp_insert_post(array(
            'post_title' => $page_title,
            'post_content' => $page_content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => $page_slug,
            'page_template' => $page_template
        ));

        // Save the ID to the database for future reference, useful for clean-up tasks.
        update_option('cpp_profile_page_id', $page_id);
    }
}
register_activation_hook(__FILE__, 'cpp_install');

function cpp_template_include($template) {
    if (is_page(get_option('cpp_profile_page_id'))) {
        $template = plugin_dir_path(__FILE__) . 'templates/profile-page.php';
    }
    return $template;
}
add_filter('template_include', 'cpp_template_include', 99);

function cpp_uninstall() {
    $page_id = get_option('cpp_profile_page_id');
    if ($page_id && get_post($page_id)) {
        wp_delete_post($page_id, true);
        delete_option('cpp_profile_page_id');
    }
}
register_deactivation_hook(__FILE__, 'cpp_uninstall');




// Process the account deletion request on form submission

// Display the form to request account deletion
function cpp_display_delete_account_form() {
    if (is_user_logged_in()) {
		$current_user_id = get_current_user_id();
        ?>
        <form method="post" action="">
            <?php wp_nonce_field('cpp_delete_account_action', 'cpp_delete_account_nonce'); ?>
            <input type="hidden" name="cpp_delete_account" value="1">
            <input class="cpp_acount-delete" type="submit" value="Request Account Deletion" onclick="return confirm('Are you sure you want to delete your account? This will put your account on a 30-day hold before deletion. To cancel deletion request, sign back in and click the Cancel Deletion Request button.');">
        </form>
        <?php
    } else {
        echo "You need to be logged in to request account deletion.";
    }
}
add_shortcode('cpp_delete_account_form', 'cpp_display_delete_account_form');

function cpp_process_account_deletion_request() {
    if (isset($_POST['cpp_delete_account']) && is_user_logged_in()) {
        // Verify nonce for added security
        if (!wp_verify_nonce($_POST['cpp_delete_account_nonce'], 'cpp_delete_account_action')) {
            die('Security check failed.');
        }

        $current_user_id = get_current_user_id();

        update_user_meta($current_user_id, 'cpp_account_deletion_requested', current_time('mysql'));

        // Log the user out and redirect.
        wp_logout();
        wp_redirect(home_url());
        exit;
    }
}
add_action('init', 'cpp_process_account_deletion_request');

// Schedule the daily event for account deletions if not already scheduled
function cpp_schedule_cron() {
    if (!wp_next_scheduled('cpp_delete_held_accounts')) {
        wp_schedule_event(time(), 'daily', 'cpp_delete_held_accounts');
    }
}
add_action('wp', 'cpp_schedule_cron');

// Handle the actual account deletion after 30 days
function cpp_handle_account_deletion() {
    $users = get_users(array(
        'meta_key' => 'cpp_account_deletion_requested',
        'fields' => 'ID'
    ));

    foreach ($users as $user_id) {
        $deletion_date = get_user_meta($user_id, 'cpp_account_deletion_requested', true);
        
        if ($deletion_date && (current_time('timestamp') - strtotime($deletion_date) >= 30*DAY_IN_SECONDS)) {
            wp_delete_user($user_id);
            delete_user_meta($user_id, 'cpp_account_deletion_requested');
        }
    }
}
add_action('cpp_delete_held_accounts', 'cpp_handle_account_deletion');

// Display a message upon login if user has requested account deletion and provide a button to cancel
function cpp_display_cancel_deletion_request() {
    if (is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        
            echo '<div class="notice notice-warning">';
            echo '<p>Your account is set to be deleted on ' . date('F j, Y', strtotime($deletion_date . ' +30 days')) . '. </p>';
            echo '<p>If you wish to cancel this deletion request:</p>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="cpp_cancel_deletion" value="1">';
            echo '<input type="submit" value="Cancel Deletion Request">';
            echo '</form>';
            echo '</div>';
        
    }
}
add_shortcode('cpp_cancel_deletion_request_form', 'cpp_display_cancel_deletion_request');

// Process the cancel deletion request
function cpp_process_cancel_deletion_request() {
    if (isset($_POST['cpp_cancel_deletion']) && is_user_logged_in()) {
        $current_user_id = get_current_user_id();
        delete_user_meta($current_user_id, 'cpp_account_deletion_requested');
        
        wp_mail(
            wp_get_current_user()->user_email, 
            'Account Deletion Request Cancelled', 
            'Your account deletion request has been successfully cancelled.'
        );
       return home_url('/user-profile/');
        exit;
    }
}
add_action('init', 'cpp_process_cancel_deletion_request');

//points system shop page

function cpp_points_shop_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return 'Please log in to access the points shop.';
    }

    $user_id = get_current_user_id(); // This needs to be before you get the user's tickets.
    $user_tickets = get_user_meta($user_id, 'purchased_tickets', true);
    if (empty($user_tickets)) {
        $user_tickets = 0;
    }

    $output = ''; // Initialize the output.

    $enable_contest = get_option('upvote_enable_contest');
    $ticket_cost = get_option('upvote_contest_ticket_cost', 100); 
    $contest_end_date = get_option('upvote_contest_end_date');
    $current_date = date("Y-m-d");

    if ($enable_contest == '1') {
       

        if ($current_date <= $contest_end_date) {
            $output .= '<div id="contest-ticket-section">';
            $output .= '<p>Purchase a ticket for <br><strong>' . get_option('upvote_contest_name') . '</strong></p>';
            $output .= '<p>You have acquired <strong class="ticket-count">' . $user_tickets . '</strong> tickets.</p>';
            $output .= '<button class="purchase-ticket" style="margin-bottom:20px"  data-cost="' . $ticket_cost . '">Trade in ' . $ticket_cost . ' points</button>';
            $output .= '</div>';
        } else {
            $output .= '<div id="contest-ticket-section">';
            $output .= '<p><strong>' . get_option('upvote_contest_name') . '</strong> contest has ended. Stay tuned for more contests!</p>';
            $output .= '</div>';
        }
    } else {
        $output .= '<p class="_not-running">No Contest or Giveaways are currently running.</p>';
    }

    $user_id = get_current_user_id();
    $user_points = get_user_meta($user_id, 'upvote_post_points', true);
	if (empty($user_points)){
		$user_points = 0;
	}
    $purchased_icons = get_user_meta($user_id, 'purchased_icons', true);

    // Fetch the flair icons and titles from the database.
    $flair_icons = get_option('upvote_flair_icons', []);
    $flair_titles = get_option('upvote_flair_titles', []);
	$flair_costs = get_option('upvote_flair_costs', []);

    // Construct the icons for the shop based on the fetched data
    $icons = [];
    foreach ($flair_icons as $index => $icon_url) {
        $icon_title = isset($flair_titles[$index]) ? $flair_titles[$index] : '';
		$cost = isset($flair_costs[$index]) ? esc_attr($flair_costs[$index]) : '';
        // Assuming a constant cost for now, but you can adjust as needed.
        $icons[] = ['url' => $icon_url, 'title' => $icon_title, 'cost' => $cost];
    }
    $options = get_option('upvote_post_settings');
    $output .= '<div id="points-shop">';
	$output .= '<h3>Current Points</h3>';
    $output .= '<div id="user-points-display">You have ' . $user_points . ' / '.$options['max_point_accumulation'].' points.</div>';
	$output .= '<h3 style="margin-top:20px">Trade Points for Flair Icons</h3>';
	$output .= '<div class="_cpp_icon-group">';
    foreach ($icons as $icon) {
        $isPurchased = in_array($icon['url'], $purchased_icons);
        
        $output .= '<div class="icon-item">';
        $output .= '<img src="' . $icon['url'] . '" alt="' . esc_attr($icon['title']) . '" width="50" height="50" >';
		if ($icon['cost'] == 0){
			 $output .= '<span>Free</span>';
		} else {
			 $output .= '<span>' . $icon['cost'] . ' points</span>';
		}
        
        if ($isPurchased) {
            $output .= '<span>Aquired</span>';
        } else {
            $output .= '<button class="purchase-icon" data-icon-url="' . $icon['url'] . '" data-cost="' . $icon['cost'] . '">Trade</button>';
        }

        $output .= '</div>';
    }
	$output .= '</div>';
	//$output .= '<button id="clear-purchases">Clear All Trades</button>';
    $output .= '</div>';  // Close #points-shop

    $output .= '
    <script>
    document.querySelectorAll(".purchase-icon").forEach(button => {
    button.addEventListener("click", function() {
        const iconURL = this.dataset.iconUrl;
        const cost = parseInt(this.dataset.cost);

        fetch("' . admin_url('admin-ajax.php') . '", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "purchase_icon",
                icon_url: iconURL,
                cost: cost,
                nonce: "' . wp_create_nonce("purchase_icon_nonce") . '",
            }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Update UI to reflect the purchase.
                button.parentElement.innerHTML = `<img src="${iconURL}" alt="Icon" width="50px" height="50px"><span>${cost} points</span><span>Acquired</span>`;
                
                // Add the newly purchased flair to the "choose flair" section.
				const flairSelectionGroup = document.querySelector("._cpp_icon-group2");
				const newFlairElem = document.createElement("div");
				newFlairElem.className = "icon-item";
				newFlairElem.innerHTML = `
				<img src="${iconURL}" alt="Flair" style="display:block; width:50px; height:50px;">
					<input type="radio" name="flair-icon" value="${iconURL}">
				`;

				flairSelectionGroup.insertBefore(newFlairElem, flairSelectionGroup.querySelector(".set-icon-flair"));
                
                // Update user\'s points in real-time.
                const currentPointsDisplay = document.getElementById("user-points-display");
                let currentPoints = parseInt(currentPointsDisplay.textContent.match(/\d+/)[0]);
                currentPoints -= cost;
                currentPointsDisplay.textContent = `You have ${currentPoints} points.`;
            } else {
                alert(data.message);
            }
        });
    });
});
    </script>
	<script>
	document.getElementById("clear-purchases").addEventListener("click", function() {
    fetch("' . admin_url('admin-ajax.php') . '", {
        method: "POST",
        headers: {
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams({
            action: "clear_all_purchases",
            nonce: "' . wp_create_nonce("clear_purchases_nonce") . '",
        }),
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();  // Reload the page to refresh the shop display
        } else {
            alert(data.message);
        }
    });
});
	</script>';

    return $output;
}
add_shortcode('cpp_points_shop', 'cpp_points_shop_shortcode');

// Purchase Handler

function purchase_icon_handler() {
    // Security check
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'purchase_icon_nonce')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    $user_id = get_current_user_id();
    $user_points = get_user_meta($user_id, 'upvote_post_points', true);
    $icon_url = sanitize_text_field($_POST['icon_url']);
    $cost = intval($_POST['cost']);
    // Check if the user has enough points
    if ($user_points < $cost) {
        wp_send_json_error(['message' => 'Not enough points to purchase this icon.']);
        return;
    }

    // Deduct the points
    update_user_meta($user_id, 'upvote_post_points', $user_points - $cost);

    // Add icon to user's purchased icons
    $purchased_icons = get_user_meta($user_id, 'purchased_icons', true);
	if (!$purchased_icons) {
		$purchased_icons = [];
	}
	$purchased_icons[] = $_POST['icon_url']; // Add the newly purchased icon to the array
	update_user_meta($user_id, 'purchased_icons', $purchased_icons);

    wp_send_json_success(['message' => 'Icon purchased successfully.']);
}
add_action('wp_ajax_purchase_icon', 'purchase_icon_handler');
add_action('wp_ajax_nopriv_purchase_icon', 'purchase_icon_handler'); // Ideally, we should not allow non-logged in users to access this, but it's here just in case.

function cpp_clear_all_purchases() {
    // Verify nonce for security
    check_ajax_referer('clear_purchases_nonce', 'nonce');

    $user_id = get_current_user_id();

    // For this example, I'll assume that there's a user_meta field where purchases are stored
    // You'll need to adjust this logic to suit your exact setup
    delete_user_meta($user_id, 'purchased_icons');

    // Respond to the AJAX call
    wp_send_json_success(['message' => 'Purchases cleared successfully.']);
}
add_action('wp_ajax_clear_all_purchases', 'cpp_clear_all_purchases');

function cpp_display_purchased_icons_shortcode() {
    $user_id = get_current_user_id();
    $purchased_icons = get_user_meta($user_id, 'purchased_icons', true);
    $current_flair = get_user_meta($user_id, 'selected_flair', true);
	
    $output = '<div class="purchased-icons">';
    $output .= '<h3 style="margin-top:20px">Select Your Flair Icon:</h3>';
    $output .= '<form id="flair-selection-form">';
	$output .= '<div class="_cpp_icon-group2">';

    // Option to have no flair
    $output .= '<div class="icon-item">';
	$output .= '<span style="width:50px; height:50px; padding-top:20px; text-align:center;">None</span>';
    $output .= '<input type="radio" name="flair-icon" value=""' . (!$current_flair ? ' checked' : '') . '>';
    $output .= '</label></div>';

    if ($purchased_icons && is_array($purchased_icons)) {
        foreach ($purchased_icons as $icon) {
            $checked = ($current_flair == $icon) ? ' checked' : '';
            $output .= '<div class="icon-item"><label>';
			$output .= '<img src="' . $icon . '" alt="Icon" width="50px" height="50px">';
            $output .= '<input type="radio" name="flair-icon" value="' . $icon . '"' . $checked . '>';
            $output .= '</label></div>';
        }
    }
    $output .= '</form>';
	$output .= '<button type="submit" class="set-icon-flair">Use Selected as Flair</button>';
    $output .= '</div></div>';
    
    return $output;
}
add_shortcode('cpp_display_purchased_icons', 'cpp_display_purchased_icons_shortcode');


function cpp_set_icon_as_flair() {
    // Verify the nonce for security.
    if(!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'set_icon_as_flair_nonce')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }

    $user_id = get_current_user_id();
    $icon_url = sanitize_text_field($_POST['icon_url']);

    // Save the selected flair.
    update_user_meta($user_id, 'selected_flair', $icon_url);

    wp_send_json_success();
}
add_action('wp_ajax_set_icon_as_flair', 'cpp_set_icon_as_flair');

// add to settings page

function upvote_post_register_settings() {
    add_settings_section(
        'upvote_flair_section',
        'Flair Icons',
        'upvote_flair_section_callback',
        'upvote_post'
    );

    add_settings_field(
        'upvote_flair_icons',
        'Flair Icons',
        'upvote_flair_icons_callback',
        'upvote_post',
        'upvote_flair_section'
    );
	
	add_settings_section(
        'upvote_contest_section',
        'Contest Settings',
        'upvote_contest_section_callback',
        'upvote_post'
    );

    add_settings_field(
        'upvote_enable_contest',
        'Enable Contest',
        'upvote_enable_contest_callback',
        'upvote_post',
        'upvote_contest_section'
    );

    add_settings_field(
        'upvote_contest_name',
        'Contest Name',
        'upvote_contest_name_callback',
        'upvote_post',
        'upvote_contest_section'
    );

    add_settings_field(
        'upvote_contest_ticket_cost',
        'Ticket Cost',
        'upvote_contest_ticket_cost_callback',
        'upvote_post',
        'upvote_contest_section'
    );

    add_settings_field(
        'upvote_contest_end_date',
        'End Date',
        'upvote_contest_end_date_callback',
        'upvote_post',
        'upvote_contest_section'
    );
	
	add_settings_field(
        'clear_tickets_button',
        'Clear Ticket Purchases',
        'cpp_clear_tickets_button_callback',
        'upvote_post',
        'upvote_contest_section'
    );

    register_setting('upvote_post', 'upvote_flair_icons');
	register_setting('upvote_post', 'upvote_flair_titles');
	register_setting('upvote_post', 'upvote_flair_costs');// <-- Add this line here
	register_setting('upvote_post', 'upvote_enable_contest');
    register_setting('upvote_post', 'upvote_contest_name');
    register_setting('upvote_post', 'upvote_contest_ticket_cost');
    register_setting('upvote_post', 'upvote_contest_end_date');
	register_setting('upvote_post', 'clear_tickets_button');
}
add_action('admin_init', 'upvote_post_register_settings', 100);

function upvote_flair_section_callback() {
    echo 'Add and manage flair icons here.';
}

function upvote_flair_icons_callback() {
    $flair_icons = get_option('upvote_flair_icons', []);
    $flair_titles = get_option('upvote_flair_titles', []);
	$flair_costs = get_option('upvote_flair_costs', []);

    echo '<div id="flair-icons-repeater">';
    
    foreach ($flair_icons as $index => $icon) {
        $title = isset($flair_titles[$index]) ? esc_attr($flair_titles[$index]) : '';
		$cost = isset($flair_costs[$index]) ? esc_attr($flair_costs[$index]) : '';
        echo '<div class="flair-icon-item">';
        echo '<img src="' . esc_url($icon) . '" width="50" height="50">';
        echo '<input type="hidden" name="upvote_flair_icons[]" value="' . esc_url($icon) . '">';
        echo '<label>Title: <input type="text" name="upvote_flair_titles[]" value="' . $title . '"></label>';
		echo '<label>Cost: <input type="text" name="upvote_flair_costs[]" value="' . $cost . '"></label>';
        echo '<button class="remove-flair-icon">Remove</button>';
        echo '</div>';
    }

    echo '</div>';

    echo '<button id="add-flair-icon">Add Icon</button>';
	
	function upvote_contest_section_callback() {
    echo 'Manage your contest settings here.';
}

function upvote_enable_contest_callback() {
    $checked = get_option('upvote_enable_contest') ? 'checked' : '';
    echo '<input type="checkbox" name="upvote_enable_contest" value="1" ' . $checked . '> Enable';
}

function upvote_contest_name_callback() {
    $value = get_option('upvote_contest_name', '');
    echo '<input type="text" name="upvote_contest_name" value="' . esc_attr($value) . '">';
}

function upvote_contest_ticket_cost_callback() {
    $value = get_option('upvote_contest_ticket_cost', '');
    echo '<input type="number" name="upvote_contest_ticket_cost" value="' . esc_attr($value) . '"> Points';
}

function upvote_contest_end_date_callback() {
    $value = get_option('upvote_contest_end_date', '');
    echo '<input type="date" name="upvote_contest_end_date" value="' . esc_attr($value) . '">';
}
}

function upvote_enqueue_admin_scripts($hook) {
    if ('settings_page_upvote-settings' !== $hook) {
        return;
    }
function cpp_clear_tickets_button_callback() {
    echo '<form method="post" action="">';
    echo '<input type="submit" name="clear_tickets" value="Clear All Ticket Purchases" class="button button-secondary" onclick="return confirm(\'Are you sure you want to clear all ticket purchases?\');">';
    echo '</form>';
}
    // Enqueue your JavaScript here.
     wp_enqueue_script('upload-script', plugin_dir_url(__FILE__) . 'js/upload-script.js', array('jquery'), null, true);

    // Enqueue WordPress Media Uploader scripts
    wp_enqueue_media();
}
add_action('admin_enqueue_scripts', 'upvote_enqueue_admin_scripts');

function cpp_purchase_ticket_callback() {
    // Verify nonce
    check_ajax_referer('purchase_ticket_nonce', 'nonce');

    // Check if the 'cost' key exists in the POST data
    if (!isset($_POST['cost'])) {
        wp_send_json_error(['message' => 'Invalid request.']);
    }
	

    // Get ticket cost and ensure it's a valid integer
    $ticket_cost = filter_input(INPUT_POST, 'cost', FILTER_VALIDATE_INT);
    
    if (!$ticket_cost) {
        wp_send_json_error(['message' => 'Invalid ticket cost provided.']);
    }

    // Get user info
    $user_id = get_current_user_id();
    
    // Check for valid user points
    $user_points = get_user_meta($user_id, 'upvote_post_points', true);
    $user_points = is_numeric($user_points) ? intval($user_points) : 0;

    if ($user_points < $ticket_cost) {
        wp_send_json_error(['message' => 'You don\'t have enough points to purchase a ticket.']);
    }

    // Deduct points from user's total
    update_user_meta($user_id, 'upvote_post_points', $user_points - $ticket_cost);

    // Handle purchased tickets
    $user_tickets = get_user_meta($user_id, 'purchased_tickets', true);
    $user_tickets = is_numeric($user_tickets) ? intval($user_tickets) : 0;
    
    // Increment the user's tickets
    $user_tickets += 1;
    update_user_meta($user_id, 'purchased_tickets', $user_tickets);

    wp_send_json_success(['message' => 'Ticket purchased successfully.']);
}
add_action('wp_ajax_purchase_ticket', 'cpp_purchase_ticket_callback');

add_filter('manage_users_columns', 'cpp_add_user_tickets_column');

function cpp_add_user_tickets_column($columns) {
    $columns['purchased_tickets'] = 'Purchased Tickets';
    return $columns;
}

add_action('manage_users_custom_column', 'cpp_show_user_tickets_count', 10, 3);

function cpp_show_user_tickets_count($value, $column_name, $user_id) {
    if ('purchased_tickets' == $column_name) {
        return get_user_meta($user_id, 'purchased_tickets', true);
    }
    return $value;
}

add_filter('manage_users_sortable_columns', 'cpp_make_tickets_column_sortable');

function cpp_make_tickets_column_sortable($columns) {
    $columns['purchased_tickets'] = 'purchased_tickets';
    return $columns;
}

add_action('pre_get_users', 'cpp_sort_users_by_tickets');

function cpp_sort_users_by_tickets($query) {
    if (isset($query->query_vars['orderby']) && 'purchased_tickets' == $query->query_vars['orderby']) {
        $query->set('meta_key', 'purchased_tickets');
        $query->set('orderby', 'meta_value_num');  // Sort by number
    }
}

function cpp_clear_all_ticket_purchases() {
    if (isset($_POST['clear_tickets'])) {
        // Fetch all users
        $users = get_users();

        // Loop through each user and delete the 'purchased_tickets' meta
        foreach ($users as $user) {
            delete_user_meta($user->ID, 'purchased_tickets');
        }

        // Optionally, show an admin notice confirming the clear action
        add_action('admin_notices', 'cpp_cleared_tickets_notice');
    }
}
add_action('admin_init', 'cpp_clear_all_ticket_purchases');

function cpp_cleared_tickets_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('All ticket purchases have been cleared.', 'cpp-domain'); ?></p>
    </div>
    <?php
}

function cpp_create_public_profile_page() {
    $public_profile_page = array(
        'post_title' => 'Public Profile',
        'post_content' => '[cpp_public_profile]', // You can use a shortcode or template directly
        'post_status' => 'publish',
        'post_type' => 'page',
        'post_name' => 'public-profile'
    );

    // Check if the page exists
    if (null == get_page_by_title('Public Profile')) {
        // Insert the post into the database
        wp_insert_post($public_profile_page);
    }
}

register_activation_hook(__FILE__, 'cpp_create_public_profile_page');

function cpp_include_template($template) {
	error_log('Requested Page: ' . get_query_var('pagename')); // Logs the queried page name
    if (is_page('public-profile')) {
        $template = plugin_dir_path(__FILE__) . 'templates/public-profile-page.php';
    }
    return $template;
}
add_filter('template_include', 'cpp_include_template', 99);

function get_author_profile_link($user_id) {
    $share_profile = get_user_meta($user_id, 'share_profile', true);

    if ($share_profile == 'yes') {
        // Construct the profile link
        $profile_link = site_url() . '/public-profile?user_id=' . $user_id;
        return $profile_link;
    }

    return false;
}
