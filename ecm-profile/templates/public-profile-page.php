<?php
/**
 * Template Name: Public Profile Page
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
if (!is_user_logged_in()) {
    wp_redirect(home_url('/login/'));
    exit;
}
$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
$current_user = get_user_by( 'ID', $user_id );
$share_profile = get_user_meta($user_id, 'share_profile', true);


// After processing the form and potential redirection, get the user data again for display:
$selected_flair = get_user_meta($current_user->ID, 'selected_flair', true);
$flairs = get_option('user_flairs');
$flair_icon_url = get_user_meta($current_user->ID, 'selected_flair', true); 

get_header(); ?>
<?php if ($share_profile == 'yes') { ?>
<div class="cpp-profile">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="return-home-button">Return to Homepage</a>
	<div class="cpp_header">
	<?php
	echo '<div>'.get_avatar( $current_user->ID, 96 ).'</div>';?>
	 <div class="cpp_name"><h2><?php echo $current_user->display_name; ?>'s Profile</h2>
	<?php if ($flair_icon_url) {
    echo '<img src="' . esc_url($flair_icon_url) . '" alt="User Flair" width="30px"; height="30px" class="user-flair-icon">';
	} else {
		
	} ?>
    <p>Email: <?php echo $current_user->user_email; ?><br>
	</p></div>
	</div>
	<div class="cpp_flex">
	<div class="_user_profile">

	<h2>Profile</h2>
	<div>
    <label for="first_name">First Name</label><br>
    <input type="text" name="first_name" value="<?php echo esc_attr( $current_user->first_name ); ?>" readonly>
    </div>
	<div>
    <label for="last_name">Last Name</label><br>
    <input type="text" name="last_name" value="<?php echo esc_attr( $current_user->last_name ); ?>" readonly>
    </div>
	<div>
    <label for="nickname">Nickname</label><br>
    <input type="text" name="nickname" value="<?php echo esc_attr( $current_user->nickname ); ?>" readonly>
    </div>
	<div>
    <label for="bio">Biographical Info</label><br>
    <textarea name="bio" rows="10" readonly><?php echo esc_textarea( $current_user->description ); ?></textarea>
    </div>
	<div>
    <label for="gamertag">GamerTag:</label><br>
    <input type="text" name="gamertag" id="gamertag" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'gamertag', true)); ?>" readonly>
	</div>

	<div>
		<label for="favorite_game">Favorite Game:</label><br>
		<input type="text" name="favorite_game" id="favorite_game" value="<?php echo esc_attr(get_user_meta($current_user->ID, 'favorite_game', true)); ?>" readonly>
	</div>
	
    <!-- ... other fields ... -->

</div>
	<div class="user_extras">
		<h2>Site Extras</h2>
	
    <!-- Add more profile fields as required -->
	<?php
	$user_points = get_user_meta($current_user->ID, 'upvote_post_points', true);
	$options = get_option('upvote_post_settings');
	
	
	echo '<hr>';
	echo '<p style="font-size:12px;">You can get points by posting, upvoting, commenting, and sharing. The more you engage, the more points you acquire!</p>';
	echo '<ul style="font-size:12px;">';
	echo '<li>'.esc_attr($options['point_per_post']).' Points for posting!</li>';
	echo '<li>'.esc_attr($options['point_per_upvote']).' Points for recieving an upvote!</li>';
	echo '<li>'.esc_attr($options['point_per_comment']).' Points for commenting!</li>';
	echo '<li>'.esc_attr($options['point_per_share']).' Points for sharing!</li>';
	echo '</ul>';
	 ?>	</div>
</div>
	<div class="user-profile-tabs">
    <ul class="tabs">
		<li class="tab-link" data-tab="tab-posts">Latest Posts</li>
        <li class="tab-link current" data-tab="tab-comments">Latest Comments</li>
    </ul>

<div id="tab-comments" class="tab-content">
    <?php
    $args = [
        'number' => 20, // Retrieve more to account for replies
        'status' => 'approve',
        'user_id' => $user_id
    ];

    $all_comments = get_comments($args);
    $displayed_comments = []; // Store IDs of comments that are displayed

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
        echo "<p><strong>On Post:</strong> <a href='" . get_permalink($comment->comment_post_ID) . "'>" . get_the_title($comment->comment_post_ID) . "</a></p>";
        echo "<p><strong>" . $current_user->display_name . "'s Comment: </strong></p>";
        echo $text_content;
        echo "</div>";
        $displayed_comments[] = $comment->comment_ID;
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
            echo '<div class="ecn_post-content">' . wpautop($text_content) . '</div>';
            echo "</div>";
        endwhile;
    else :
        echo "<p>No posts found.</p>";
    endif;

    wp_reset_postdata(); // Reset the post data to avoid conflicts
    ?>
</div>

</div>
<?php } else { ?>
<div class="cpp-profile">
	<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="return-home-button">Return to Homepage</a>
	<div class="cpp_header">
	<?php
	echo '<div>'.get_avatar( $current_user->ID, 96 ).'</div>';?>
	 <div class="cpp_name"><h2><?php echo $current_user->display_name; ?></h2>
	<?php if ($flair_icon_url) {
    echo '<img src="' . esc_url($flair_icon_url) . '" alt="User Flair" width="30px"; height="30px" class="user-flair-icon">';
	} else {
		
	} ?>
    </div>
	</div>
	<div class="cpp_flex">
	<div class="_user_profile">
	<p><b>This user is not sharing their profile.</b></p>
    <!-- ... other fields ... -->

</div>
	<div class="user_extras">
		<h2>Site Extras</h2>
	
    <!-- Add more profile fields as required -->
	<?php
	$user_points = get_user_meta($current_user->ID, 'upvote_post_points', true);
	$options = get_option('upvote_post_settings');
	
	
	echo '<hr>';
	echo '<p style="font-size:12px;">You can get points by posting, upvoting, commenting, and sharing. The more you engage, the more points you acquire!</p>';
	echo '<ul style="font-size:12px;">';
	echo '<li>'.esc_attr($options['point_per_post']).' Points for posting!</li>';
	echo '<li>'.esc_attr($options['point_per_upvote']).' Points for recieving an upvote!</li>';
	echo '<li>'.esc_attr($options['point_per_comment']).' Points for commenting!</li>';
	echo '<li>'.esc_attr($options['point_per_share']).' Points for sharing!</li>';
	echo '</ul>';
	 ?>	</div>
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
