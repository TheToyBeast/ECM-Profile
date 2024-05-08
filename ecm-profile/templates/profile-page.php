<?php
/**
 * Template Name: Custom Profile Page
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$user_share_profile_setting = get_user_meta($current_user->ID, 'share_profile', true);

// Process form data if the form is submitted
if (isset($_POST['submit']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'update-profile_' . $user_id)) {
    // Save user data
    // This is just an example. Always sanitize and validate the input data
    update_user_meta($user_id, 'first_name', sanitize_text_field($_POST['first_name']));
    update_user_meta($user_id, 'last_name', sanitize_text_field($_POST['last_name']));
    update_user_meta($user_id, 'nickname', sanitize_text_field($_POST['nickname']));
    update_user_meta($user_id, 'description', sanitize_textarea_field($_POST['bio']));
    
    if (!empty($_POST['pass1']) && $_POST['pass1'] == $_POST['pass2']) {
        wp_update_user(array('ID' => $user_id, 'user_pass' => $_POST['pass1']));
    }
    // After processing, redirect to prevent form resubmission
    wp_redirect($_SERVER['REQUEST_URI']);
    exit;
}

// After processing the form and potential redirection, get the user data again for display:
$selected_flair = get_user_meta($current_user->ID, 'selected_flair', true);
$flairs = get_option('user_flairs');
$flair_icon_url = get_user_meta($current_user->ID, 'selected_flair', true); 

get_header(); ?>

<div class="cpp-profile">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="return-home-button">Return to Homepage</a>
	<div class="cpp_header">
	<?php
	echo '<div>'.get_avatar( $current_user->ID, 96 ).'</div>';
?>
		
	 <div class="cpp_name"><h2>Welcome, <?php echo $current_user->display_name;?></h2>
	<?php if ($flair_icon_url) {
    echo '<img src="' . esc_url($flair_icon_url) . '" alt="User Flair" width="30px"; height="30px" class="user-flair-icon">';
	} else {
		
	} ?>
    <p>Email: <?php echo $current_user->user_email; ?><br>
	<?php echo wp_loginout( $_SERVER['REQUEST_URI'] );?></p></div>
	</div>
	<div class="cpp_flex">
	<div class="_user_profile">
		<?php 	echo '<p>'. ecm_gravatar_profile_link() . '</p>'; ?>
<form method="post">
	<h2>Update Profile</h2>
	<div>
    <label for="first_name">First Name</label><br>
    <input type="text" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>">
    </div>
	<div>
    <label for="last_name">Last Name</label><br>
    <input type="text" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>">
    </div>
	<div>
    <label for="nickname">Nickname</label><br>
    <input type="text" name="nickname" value="<?php echo esc_attr( $current_user->nickname ); ?>" required>
    </div>
	<div>
    <label for="bio">Biographical Info</label><br>
    <textarea name="bio" rows="10"><?php echo esc_textarea( $current_user->description ); ?></textarea>
    </div>
	<div style="margin:10px 0">
    <label>Share Profile:</label><br>
    <input type="radio" id="share_yes" name="share_profile" value="yes" <?php checked( $user_share_profile_setting, 'yes' ); ?>>
	<label for="share_yes">Yes</label><br>
	<input type="radio" id="share_no" name="share_profile" value="no" <?php checked( $user_share_profile_setting, 'no' ); ?>>
	<label for="share_no">No</label>
	</div>
	<div>
    <label for="gamertag">GamerTag:</label><br>
    <input type="text" name="gamertag" id="gamertag" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'gamertag', true)); ?>">
	</div>

	<div>
		<label for="favorite_game">Favorite Game:</label><br>
		<input type="text" name="favorite_game" id="favorite_game" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'favorite_game', true)); ?>">
	</div>
    <!-- For changing password -->
	<div>
    <label for="pass1">Change Password</label><br>
    <input type="password" name="pass1" autocomplete="new-password">
    </div>
	<div>
    <label for="pass2">Repeat Password</label><br>
    <input type="password" name="pass2">
	</div>
    <!-- ... other fields ... -->

    <?php wp_nonce_field('update-profile_' . $user_id) ?>
    <input type="submit" name="submit" value="Update Profile">
</form>
		<?php 
$deletion_date = get_user_meta($current_user->ID, 'cpp_account_deletion_requested', true);
if (!$deletion_date){
	echo do_shortcode('[cpp_delete_account_form]');
} else {
	echo do_shortcode('[cpp_cancel_deletion_request_form]');
}
?>
</div>
	<div class="user_extras">
		<h2>Site Extras</h2>
		<h3>Current Contest | Giveaways</h3>
	
    <!-- Add more profile fields as required -->
	<?php
	$user_points = get_user_meta($current_user->ID, 'upvote_post_points', true);
	$options = get_option('upvote_post_settings');
	
	echo do_shortcode( '[cpp_points_shop]' );
	echo '<hr>';
    // echo '<h2>Double your points week now on!</h2>';
	echo '<p style="font-size:12px;">You can get points by posting, upvoting, commenting, and sharing. The more you engage, the more points you acquire!</p>';
	echo '<ul style="font-size:12px;">';
	echo '<li>'.esc_attr($options['point_per_post']).' Points for posting!</li>';
	echo '<li>'.esc_attr($options['point_per_upvote']).' Points for recieving an upvote!</li>';
	echo '<li>'.esc_attr($options['point_per_comment']).' Points for commenting!</li>';
	echo '<li>'.esc_attr($options['point_per_share']).' Points for sharing!</li>';
	echo '</ul>';
	echo do_shortcode( '[cpp_display_purchased_icons]' );
	 ?>
<?php
if ( is_user_logged_in() ) {
    $user_id = $current_user->ID;
    
    if ( isset($_POST['submit'] ) ) {
        // Nonce for security
        check_admin_referer( 'update-profile_' . $user_id );

        // Save user data
        // This is just an example. Always sanitize and validate the input data
        update_user_meta( $user_id, 'first_name', $_POST['first_name'] );
        update_user_meta( $user_id, 'last_name', $_POST['last_name'] );
        update_user_meta( $user_id, 'nickname', $_POST['nickname'] );
        update_user_meta( $user_id, 'description', $_POST['bio'] );

        // ... add other fields as needed

        if ( !empty($_POST['pass1'] ) ) {
            wp_update_user( array( 'ID' => $user_id, 'user_pass' => $_POST['pass1'] ) );
        }
    }
?>
	</div>
		

<?php
} else {
    echo "Please log in to view your profile.";
}
?>
</div>	

<?php if (is_plugin_active('ecm-forum/ecm-forum-index.php')) { ?>
<br>
<h2>Forum Tabs</h2>
	<?php 
	global $wpdb;
	$table_name = $wpdb->prefix . 'ecm_forum_custom_table';
	$new_reports_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE report_status = 0");
	$current_user_id = get_current_user_id();
	$user_role = get_user_meta($current_user_id, 'ecm_forum_role', true);?>
	<div class="user-profile-tabs">
    <ul class="tabs">
		<li class="tab-link" data-tab="tab-posts">Latest Posts</li>
        <li class="tab-link current" data-tab="tab-comments">Latest Comments</li>
		<li class="tab-link" data-tab="tab-replies">Latest Replies</li>
		<li class="tab-link" data-tab="tab-saved-topics">Saved Forum Topics</li>
        <li class="tab-link" data-tab="tab-saved-posts">Saved Forum Posts</li>
		<?php if ($user_role == 'moderator'){ ?><li class="tab-link" data-tab="tab-reports">Reports <?php if ($new_reports_count > 0): ?>
            <span id="newReportsIndicator">New</span>
        <?php endif; ?></li><?php } ?>
    </ul>
<?php

	if ($user_role == 'moderator'){ ?>
<div id="tab-reports" class="tab-content">
	<div class="table-container">
	<?php
    $reports = get_reports();
	echo '<table>';
    echo '<thead><tr><th>Report ID</th><th>Reported by</th><th>Post Name</th><th>Comment ID</th><th>Reason</th><th>Details</th><th>Time</th><th>Status</th></tr></thead>';
    echo '<tbody>';
    foreach ($reports as $report) {
	$user = get_userdata($report->user_id);
    $username = ($user) ? $user->display_name : 'User Not Found';
	$post_title = get_the_title($report->post_id);
	$post_link = get_permalink($report->post_id);
	$profile_link = get_author_profile_link($report->user_id);
	$human_readable_date = human_time_diff(strtotime($report->time), current_time('timestamp')) . ' ago';
    echo '<tr>';
    echo '<td>' . esc_html($report->id) . '</td>';
    echo '<td><a href="' . $profile_link . '" target="_blank">'. esc_html($username) . '</a></td>';
    echo '<td><a href="' . $post_link . '" target="_blank">' . esc_html($post_title) . '</a></td>';
	if ($report->comment_id == 0){
		echo '<td> </td>';
	} else {
		echo '<td><a href="' . $post_link . '/#'.esc_html($report->comment_id).'" target="_blank">' . esc_html($report->comment_id) . '</a></td>';
	}
    echo '<td>' . esc_html($report->report_reason) . '</td>';
    echo '<td>' . esc_html($report->report_details) . '</td>';
    echo '<td>' . esc_html($human_readable_date) . '</td>';
    echo '<td>';
    echo '<input type="checkbox" class="reviewed-checkbox" data-report-id="' . esc_attr($report->id) . '" ' . checked($report->report_status, 1, false) . '>';
    echo '</td>';
    echo '</tr>';
}
    echo '</tbody></table>';?>
	</div></div>
<?php } ?>

<div id="tab-comments" class="tab-content">
    <?php
    $args = [
        'number' => 20, // Retrieve more to account for replies
        'status' => 'approve',
        'user_id' => $user_id
    ];

    $all_comments = get_comments($args);
    $displayed_comments = []; // Store IDs of comments that are displayed

    if (empty($all_comments)) {
        echo "You currently have no comments.";
    } else {
        foreach ($all_comments as $comment) {
            if (count($displayed_comments) >= 10) {
                break; // Stop if we have displayed 10 comments/replies
            }

            if (in_array($comment->comment_ID, $displayed_comments)) {
                continue; // Skip if this comment has already been displayed
            }

            // Define the allowed HTML tags and attributes
            $allowed_tags = [
                'a' => [
                    'href' => true,
                    'title' => true
                ],
                'strong' => [],
                'em' => [],
            ];

            // Apply the allowed HTML tags and attributes
            $filtered_comments = wp_kses($comment->comment_content, $allowed_tags);

            if (strlen($filtered_comments) > 250) {
                // If the text content exceeds 250 characters, truncate and add "..."
                $text_content = substr($filtered_comments, 0, 250) . '...';
            } else {
                $text_content = $filtered_comments;
            }

            // Display the comment content without images
            echo "<div class='comment'>";
            echo "<h3>On Post: <a href='" . get_permalink($comment->comment_post_ID) . '/#' . $comment->comment_ID . "'>" . get_the_title($comment->comment_post_ID) . "</a></h3>";
            echo '<p><b>Posted on: ' . get_comment_date() . '</b></p>';
            echo "<p><strong>" . $current_user->display_name . "'s Comment: </strong>";
            echo $text_content;
            echo "</p></div>";
            $displayed_comments[] = $comment->comment_ID;
        }
    }
    ?>
</div>


	<div id="tab-posts" class="tab-content">
    <!-- Latest posts will be loaded here -->
    <?php
    $latest_posts_query = new WP_Query([
        'post_type' => ['post', 'upvote_post', 'ecm_forum_post'], // Array of post types
        'posts_per_page' => 10, // Number of posts to show
        'orderby' => 'date', // Order by date
        'order' => 'DESC', // Latest posts first
        'author' => $user_id
    ]);

    if ($latest_posts_query->have_posts()) :
        while ($latest_posts_query->have_posts()) : $latest_posts_query->the_post();
            // Get the post content
            $post_content = get_the_content();

            // Define the allowed HTML tags and attributes
            $allowed_tags = [
                'a' => [
                    'href' => true,
                    'title' => true
                ],
                'strong' => [],
                'em' => [],
                'span' => [
                    'class' => true
                ]
            ];

            // Apply the allowed HTML tags and attributes
            $filtered_content = wp_kses($post_content, $allowed_tags);

            // Get the text content and limit it to 250 characters
            $filtered_content = trim($filtered_content); // Trim any leading/trailing whitespace
			if (strlen($filtered_content) > 250) {
            // If the text content exceeds 250 characters, truncate and add "..."
            $text_content= substr($filtered_content, 0, 250) . '...';
        	} else {
				$text_content= $filtered_content;
			}

            // Display the post
            echo "<div class='cp_post'>";
            echo '<h3><a href="' . get_the_permalink() . '">' . get_the_title() .'</a></h3>';
            echo '<p><b>Posted on: ' . get_the_date() . '</b></p>';
            echo '<div class="ecm_post-content">' . $text_content . '</div>';
            echo "</div>";
        endwhile;
    else :
        echo "<p>No posts found.</p>";
    endif;

    wp_reset_postdata(); // Reset the post data to avoid conflicts
    ?>
</div>

    <div id="tab-saved-topics" class="tab-content">
        <!-- Saved forum posts will be loaded here -->
		<?php echo do_shortcode('[ecm_saved_items type="topics"]'); ?>
    </div>
	<div id="tab-saved-posts" class="tab-content">
        <!-- Saved forum posts will be loaded here -->
		<?php echo do_shortcode('[ecm_saved_items type="posts"]'); ?>
    </div>
	<div id="tab-replies" class="tab-content">
    <?php
    $comments = get_comments(array(
        'user_id' => $user_id,
        'status' => array('approve', 'hold'),
        'type' => 'comment'
    ));

    if (empty($comments)) {
        echo "You currently have no replies.";
    } else {
        foreach ($comments as $comment) {
            // Retrieve the post from the comment
            $comment_post_ID = $comment->comment_post_ID;
            $comment_post = get_post($comment_post_ID);
            $comment_post_title = $comment_post->post_title;
            $comment_post_permalink = get_permalink($comment_post_ID);
            $comment_content = $comment->comment_content;
            $comment_content_stripped = strip_tags($comment->comment_content, '<a><strong><em>');
            $parent_id = $comment->comment_ID;
            if (strlen($comment_content_stripped) > 250) {
                // If the text content exceeds 250 characters, truncate and add "..."
                $text_content = substr($comment_content_stripped, 0, 250) . '...';
            }

            $replies = get_comments(array(
                'status' => array('approve', 'hold'),
                'type' => 'comment',
                'parent' => $parent_id
            ));

            foreach ($replies as $reply) {
                $reply_content = $reply->comment_content;
                $reply_content_stripped = strip_tags($reply->comment_content, '<a><strong><em><style>');
                if (strlen($reply_content_stripped) > 250) {
                    // If the text content exceeds 250 characters, truncate and add "..."
                    $comment_content = substr($reply_content_stripped, 0, 250) . '...';
                } else {
                    $comment_content = $reply_content_stripped;
                }
                echo '<div class="comment">';
                echo '<h3>On Post: <a href="'. get_permalink($reply->comment_post_ID) . '#'.$reply->comment_ID.'">' . get_the_title($reply->comment_post_ID) . '</a></h3>';
                echo '<p><b>Posted on: ' . get_comment_date('', $reply->comment_ID) . '</b></p>';
                echo '<p><b>' . $reply->comment_author . ' replied: </b>';
                echo $comment_content;
                // Check if the reply contains an image
                echo '</p></div>';
            }
        }
    }
    ?>
</div>
	</div>
<div>
	            <?php
            // Query for shared user-generated checklists
            $user_args = array(
                'post_type'      => 'checklist',
                'author__in' => $user_id,
                'posts_per_page' => -1
            );
            $user_query = new WP_Query($user_args);

			if ($user_query->have_posts()) :
				echo '<h2>My Checklists</h2>';
				while ($user_query->have_posts()) : $user_query->the_post();
			echo '<div class="_checklist-form">';
					the_title( '<p class="entry-title" style="margin-top:20px;"><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></p>' );
					// Add a delete button with a nonce for security
					echo '<form method="post" action="' . esc_url( admin_url('admin-post.php') ) . '" style="display:inline;">
							<input type="hidden" name="action" value="delete_my_checklist">
							<input type="hidden" name="checklist_id" value="' . get_the_ID() . '">
							' . wp_nonce_field('delete_checklist_nonce', '_wpnonce', true, false) . '
							<input type="submit" value="Delete Checklist" onclick="return confirm(\'Are you sure you want to delete this checklist?\');">
						  </form>';
					echo '</div>';
				endwhile;
			endif;
			wp_reset_postdata();
            ?>
</div>


	<div class="ecm-friends">
	<h2>Friends List</h2>
	<div>
	<?php
				// In your user profile template
$current_user_id = get_current_user_id(); // or the ID of the user being viewed
$friends = toybeast_get_user_friends($current_user_id);
	if ( empty($friends)){
		echo 'Go add some friends!';
	} else {

echo '<table class="friend-list">';
echo '<thead>';
echo '<tr>';
echo '<th>Username</th>';
echo '<th>Posts</th>';
echo '<th>Reputation</th>';
echo '<th>ECM Forum Role</th>';
echo '<th>Status</th>';
echo '<th>Actions</th>';
echo '</tr>';
echo '</thead>';
echo '<tbody>';

foreach ($friends as $friend) {
    $reputation = isset($friend['reputation']) ? intval($friend['reputation']) : 0;
    $ecm_forum_role = get_user_meta($friend['ID'], 'ecm_forum_role', true);
    $ecm_forum_role = !empty($ecm_forum_role) ? $ecm_forum_role : 'No role assigned';
    $is_blocked = ecm_is_friend_blocked($current_user_id, $friend['ID']);
    $block_action = $is_blocked ? 'Unblock' : 'Block';

    echo '<tr>';
    if ($friend['is_blocked'] && $friend['blocked_by_user_id'] == $current_user_id) {
        // Current user has blocked this friend
        echo '<td colspan="6">' . esc_html($friend['user_login']) . ' (Blocked)</td>';
    } elseif ($friend['is_blocked']) {
        // Current user is blocked by this friend
        echo '<td colspan="6">You have been blocked by ' . esc_html($friend['user_login']) . '</td>';
    } else {
        // Normal friend listing
        echo '<td data-label="Username:">' . esc_html($friend['user_login']) . '</td>';
        echo '<td data-label="Posts:">' . esc_html($friend['post_count']) . '</td>';
        echo '<td data-label="Reputation:">' . $reputation . '</td>';
        echo '<td data-label="ECM Forum Role:">' . esc_html($ecm_forum_role) . '</td>';
        echo '<td data-label="Status:">' . esc_html(ucfirst($friend['status'])) . '</td>';
        echo '<td class="ecm-actions" data-label="Actions:">';
        echo '<button class="ecm-remove-friend" data-friend-id="' . esc_attr($friend['ID']) . '">Remove Friend</button> ';
        echo '<button class="ecm-block-unblock-friend" data-friend-id="' . esc_attr($friend['ID']) . '" data-action="' . $block_action . '">' . $block_action . ' Friend</button>';
        echo '</td>';
    }
    echo '</tr>';
}

echo '</tbody>';
echo '</table>';

	}

		

	?>
	</div>
</div>
	</div>

<?php } ?>
<div style="max-width:1000px; margin:0 auto 40px auto; font-size:14px;">
<hr>
<p><b>Points System Disclaimer</b><br><br>
<b>No Monetary Value: </b>The points accumulated on ToyBeast.ca have no monetary value and cannot be exchanged, traded, or redeemed for cash or any monetary instrument. They are virtual and solely for the purpose of representing a user's interaction and participation on the platform.<br>

<b>Resetting Points: </b>We reserve the right to reset or adjust points for any user or all users at any time without prior notice. This may be due to system updates, suspicious activities, or any other reason deemed necessary by the platform's administrators.<br>

<b>Earning and Usage: </b>Points are earned through specific actions and activities on ToyBeast.ca. The usage, benefits, or privileges associated with these points are at the sole discretion of ToyBeast.ca and may change without notice.<br>

<b>Account Termination: </b>If a user's account is terminated, either by the user or by the administrators of ToyBeast.ca, any and all points associated with that account will be forfeited.<br>

<b>No Transfers: </b>Points cannot be transferred between accounts or users under any circumstances.<br>

<b>Modifications: </b>This disclaimer and the points system rules and regulations may be modified or updated at any time. It is the responsibility of the users to periodically review these terms and stay informed.<br><br>

<b>By participating in the points system, users agree to abide by these terms and any future modifications. Any misuse or violation may result in the adjustment or removal of points and/or account suspension.</b></p>
</div>
</div>
<!-- Add this form somewhere appropriate in your profile-page.php -->
<!-- Include WordPress footer -->
<?php get_footer(); ?>