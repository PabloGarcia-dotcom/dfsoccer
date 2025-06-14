<?php

/**
 * Plugin Name: dfsoccer
  * Plugin URI: https://superfantasy.net/
 * Description: dfsoccer is a comprehensive solution for managing and customizing your own fantasy soccer leagues, clubs, and players. 
 This plugin provides a robust set of features designed to enhance your fantasy soccer experience and allow for complete customization. 
 Easily create and manage custom clubs, players, and leagues to suit your fantasy soccer needs.
 Input player data via CSV for quick and efficient management.
 Assign capabilities to different user roles, such as subscribers, 
 contributors, and others, enabling them to add and manage players, clubs, and leagues.
 * Version: 1.0
 * Author: Ivan Herceg
 * License: GPL2

 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Register custom post type 'Players'
function dfsoccer_register_player_post_type() {
    $labels = array(
        'name'               => 'Players',
        'singular_name'      => 'Player',
        'menu_name'          => 'Players',
        'name_admin_bar'     => 'Player',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Player',
        'new_item'           => 'New Player',
        'edit_item'          => 'Edit Player',
        'view_item'          => 'View Player',
        'all_items'          => 'All Players',
        'search_items'       => 'Search Players',
        'parent_item_colon'  => 'Parent Players:',
        'not_found'          => 'No players found.',
        'not_found_in_trash' => 'No players found in Trash.'
    );

    $capabilities = array(
        'publish_posts'       => 'publish_dfsoccer_players',
        'edit_posts'          => 'edit_dfsoccer_players',
        'edit_others_posts'   => 'edit_others_dfsoccer_players',
        'delete_posts'        => 'delete_dfsoccer_players',
        'delete_others_posts' => 'delete_others_dfsoccer_players',
        'read_private_posts'  => 'read_private_dfsoccer_players',
        'edit_post'           => 'edit_dfsoccer_player',
        'delete_post'         => 'delete_dfsoccer_player',
        'read_post'           => 'read_dfsoccer_player',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'players'),
        'capability_type'    => array('dfsoccer_player', 'dfsoccer_players'),
        'capabilities'       => $capabilities,
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments'),
        'show_in_rest'       => true,
    );

    register_post_type('dfsoccer_player', $args);
}
add_action('init', 'dfsoccer_register_player_post_type');



// Add meta boxes for custom fields (Position, Price, and Club)
function dfsoccer_add_custom_box() {
    add_meta_box(
        'dfsoccer_player_info',
        'Player Details',
        'dfsoccer_custom_box_html',
        'dfsoccer_player'
    );
}
add_action('add_meta_boxes', 'dfsoccer_add_custom_box');




// Shortcode to display player details

function dfsoccer_player_details_shortcode($atts) {
    $atts = shortcode_atts(array(
        'id' => get_the_ID()
    ), $atts, 'player_details');

    $post_id = $atts['id'];
    if (get_post_type($post_id) !== 'dfsoccer_player') {
        return ''; // Return empty if not a player post type
    }

    $position = get_post_meta($post_id, 'dfsoccer_position', true);
    $price = get_post_meta($post_id, 'dfsoccer_price', true);
    $club_id = get_post_meta($post_id, 'dfsoccer_club_id', true); // Fetch the club ID

    // Fetch the club name using club ID
    $club_name = '';
    if (!empty($club_id)) {
        $club_name = get_the_title($club_id);
    }

    $output = '<div class="dfsoccer-player-details">';
    $output .= '<p><strong>Position:</strong> ' . esc_html($position) . '</p>';
    $output .= '<p><strong>Price:</strong> $' . esc_html($price) . '</p>';
    $output .= '<p><strong>Club:</strong> ' . esc_html($club_name) . '</p>';
    $output .= '</div>';

    return $output;
}
add_shortcode('player_details', 'dfsoccer_player_details_shortcode');



function dfsoccer_append_player_details($content) {
    if (is_single() && get_post_type() == 'dfsoccer_player') {
        $details = do_shortcode('[player_details]');
        $content .= $details;
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_player_details');


function dfsoccer_append_league_budget($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {
        $league_id = get_the_ID(); // Get the current league ID

        // Check if the current user is the author of the league post
        $league_post = get_post($league_id);
        if ($league_post && $league_post->post_author == get_current_user_id()) {
            // Append the set budget form
            $form_shortcode = '[set_league_budget league_id="' . $league_id . '"]';
            $form_info = do_shortcode($form_shortcode);
            $content .= $form_info;
        }
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_league_budget');

// Flush rewrite rules on plugin activation
function dfsoccer_activate() {
    dfsoccer_register_player_post_type();
    dfsoccer_register_club_post_type();
    dfsoccer_register_league_post_type();
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'dfsoccer_activate');

// Flush rewrite rules on plugin deactivation
function dfsoccer_deactivate() {
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'dfsoccer_deactivate');

// Add the submenu page
function dfsoccer_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=dfsoccer_player',  // Parent slug
        'Export or Import Players',             // Page title
        'Export or Import Players',             // Menu title
        'manage_options',                       // Capability
        'dfsoccer_export_players',             // Menu slug
        'dfsoccer_export_players_page'         // Function to display the page
    );
}
add_action('admin_menu', 'dfsoccer_add_admin_menu');

// Function to display the export/import page
function dfsoccer_export_players_page() {
    ?>
    <div class="wrap">
        <h1>Export Players</h1>
        <form method="post" action="">
            <input type="hidden" name="export_players" value="1">
            <?php submit_button('Export Players'); ?>
        </form>
        
        <h1>Import Players</h1>
        <form method="post" enctype="multipart/form-data" action="">
            <input type="file" name="import_players_csv" required>
            <input type="hidden" name="import_players" value="1">
            <?php submit_button('Import Players'); ?>
        </form>
    </div>
    <?php
}

function dfsoccer_handle_export() {
    if (isset($_POST['export_players'])) {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }

        // Prepare query to fetch player data
        $query_args = array(
            'post_type'      => 'dfsoccer_player',
            'posts_per_page' => -1,  // Get all players
            'post_status'    => 'publish'
        );
        $query = new WP_Query($query_args);

        // Generate the filename
        $filename = 'players-' . gmdate('YmdHis') . '.csv';

        // Set the headers to initiate the file download
        header("Content-Type: text/csv");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        // Use WordPress filesystem API
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once (ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }

        // Prepare CSV content
// Start output buffering to capture any unexpected output
ob_start();

// Prepare CSV content
$csv_content = "Title,Position,Price,Club\n";  // Column headers

// Fetch and prepare player data
if ($query->have_posts()) : 
    while ($query->have_posts()) : $query->the_post();
        $title = get_the_title();
        $position = get_post_meta(get_the_ID(), 'dfsoccer_position', true);
        $price = get_post_meta(get_the_ID(), 'dfsoccer_price', true);
        $club_id = get_post_meta(get_the_ID(), 'dfsoccer_club_id', true);
        $club = get_the_title($club_id);

        // Escape special characters and enclose fields in quotes
        $csv_content .= '"' . str_replace('"', '""', $title) . '",' .
                        '"' . str_replace('"', '""', $position) . '",' .
                        '"' . str_replace('"', '""', $price) . '",' .
                        '"' . str_replace('"', '""', $club) . "\"\n";
    endwhile; 
endif;
wp_reset_postdata();

// Clear any previous output
ob_clean();

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="players.csv"');

// Prevent caching
header('Pragma: no-cache');
header('Expires: 0');

// Output the CSV content
echo $csv_content;

// End the script to prevent any additional output
exit;

        // Exit to ensure no further processing
        exit;
    }
}
add_action('admin_init', 'dfsoccer_handle_export');



function dfsoccer_handle_import() {
    if (isset($_POST['import_players'])) {
        if (!current_user_can('manage_options')) {
            wp_die('You do not have sufficient permissions to access this page.');
        }
        
        if (!isset($_FILES['import_players_csv'])) {
            wp_die('Please upload a file.');
        }
        
        $csv_file = $_FILES['import_players_csv']['tmp_name'];
        if (!is_uploaded_file($csv_file)) {
            wp_die('File upload error. Make sure you are uploading a valid CSV file.');
        }
        
        // Use WordPress filesystem API
        global $wp_filesystem;
        if (empty($wp_filesystem)) {
            require_once (ABSPATH . '/wp-admin/includes/file.php');
            WP_Filesystem();
        }
        
        // Read the CSV file content
        $csv_content = $wp_filesystem->get_contents($csv_file);
        if ($csv_content === false) {
            wp_die('Error reading file.');
        }
        
        // Parse CSV content
        $lines = explode("\n", $csv_content);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines);
        
        // Skip the header row
        array_shift($lines);
        
        foreach ($lines as $line) {
            $data = str_getcsv($line);
            if (count($data) < 4) continue; // Skip invalid rows
            
            $post_title = sanitize_text_field($data[0]);
            $position = sanitize_text_field($data[1]);
            $price = sanitize_text_field($data[2]);
            $club = sanitize_text_field($data[3]);
            
            // Set default position to midfielder if not one of the four types
            $valid_positions = array('goalkeeper', 'defender', 'midfielder', 'attacker');
            if (!in_array($position, $valid_positions)) {
                $position = 'midfielder';
            }
            
            // Check if club exists, create if not
            $query_args = array(
                'post_type'      => 'dfsoccer_club',
                'post_status'    => 'publish',
                'title'          => $club,
                'posts_per_page' => 1,
            );
            $query = new WP_Query($query_args);
            if ($query->have_posts()) {
                $query->the_post();
                $club_id = get_the_ID();
                wp_reset_postdata();
            } else {
                // Create new club post
                $club_id = wp_insert_post(array(
                    'post_title'   => $club,
                    'post_type'    => 'dfsoccer_club',
                    'post_status'  => 'publish',
                ));
            }
            
            // Insert player post
            $post_id = wp_insert_post(array(
                'post_title'   => $post_title,
                'post_type'    => 'dfsoccer_player',
                'post_status'  => 'publish',
                'post_author'  => get_current_user_id(),
            ));
            
            if ($post_id) {
                // Set post meta including club
                update_post_meta($post_id, 'dfsoccer_position', $position);
                update_post_meta($post_id, 'dfsoccer_price', $price);
                update_post_meta($post_id, 'dfsoccer_club_id', $club_id);
            }
        }
        
        echo '<div class="updated"><p>Players imported successfully.</p></div>';
    }
}
add_action('admin_init', 'dfsoccer_handle_import');



// Register Club Post Type
function dfsoccer_register_club_post_type() {
    $labels = array(
        'name'               => 'Clubs',
        'singular_name'      => 'Club',
        'menu_name'          => 'Clubs',
        'name_admin_bar'     => 'Club',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New Club',
        'new_item'           => 'New Club',
        'edit_item'          => 'Edit Club',
        'view_item'          => 'View Club',
        'all_items'          => 'All Clubs',
        'search_items'       => 'Search Clubs',
        'parent_item_colon'  => 'Parent Clubs:',
        'not_found'          => 'No clubs found.',
        'not_found_in_trash' => 'No clubs found in Trash.'
    );

    $capabilities = array(
        'publish_posts'       => 'publish_dfsoccer_clubs',
        'edit_posts'          => 'edit_dfsoccer_clubs',
        'edit_others_posts'   => 'edit_others_dfsoccer_clubs',
        'delete_posts'        => 'delete_dfsoccer_clubs',
        'delete_others_posts' => 'delete_others_dfsoccer_clubs',
        'read_private_posts'  => 'read_private_dfsoccer_clubs',
        'edit_post'           => 'edit_dfsoccer_club',
        'delete_post'         => 'delete_dfsoccer_club',
        'read_post'           => 'read_dfsoccer_club',
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'clubs'),
        'capability_type'    => array('dfsoccer_club', 'dfsoccer_clubs'),
        'capabilities'       => $capabilities,
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor', 'author', 'thumbnail'),
        'show_in_rest'       => true,
    );

    register_post_type('dfsoccer_club', $args);
}
add_action('init', 'dfsoccer_register_club_post_type');


// Modify player custom fields to select from existing clubs
function dfsoccer_custom_box_html($post) {

    // Fetch existing values
    $position = get_post_meta($post->ID, 'dfsoccer_position', true);
    $price = get_post_meta($post->ID, 'dfsoccer_price', true);
    $club_id = get_post_meta($post->ID, 'dfsoccer_club_id', true); // Fetch the saved club ID

    // Get all clubs to populate the dropdown
    $args = array(
        'post_type'      => 'dfsoccer_club',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC'
    );
    $clubs = get_posts($args);
    ?>
<p>
    <label for="dfsoccer_position">Position:</label>
    <select id="dfsoccer_position" name="dfsoccer_position" class="postbox">
        <option value="goalkeeper" <?php selected($position, 'goalkeeper'); ?>>Goalkeeper</option>
        <option value="defender" <?php selected($position, 'defender'); ?>>Defender</option>
        <option value="midfielder" <?php selected($position, 'midfielder'); ?>>Midfielder</option>
        <option value="attacker" <?php selected($position, 'attacker'); ?>>Attacker</option>
    </select>
</p>

<p>
    <label for="dfsoccer_price">Price:</label>
    <input type="number" id="dfsoccer_price" name="dfsoccer_price" value="<?php echo esc_attr($price); ?>" class="widefat" step="any">
</p>

    <p>
        <label for="dfsoccer_club_id">Club:</label>
        <select id="dfsoccer_club_id" name="dfsoccer_club_id" class="postbox">
            <option value="">Select a Club</option>
            <?php foreach ($clubs as $club) : ?>
                <option value="<?php echo esc_attr($club->ID); ?>" <?php selected($club_id, $club->ID); ?>>
                    <?php echo esc_html($club->post_title); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </p>
    <?php
}


// Save the custom fields data when a post is saved
function dfsoccer_save_postdata($post_id) {

    // Check if it's an autosave or revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($post_id)) {
        return;
    }

    // Check if post type is set in the $_POST array
    if (isset($_POST['post_type'])) {
        // Check permissions
        if ('dfsoccer_player' === $_POST['post_type'] && current_user_can('edit_post', $post_id)) {
            if (isset($_POST['dfsoccer_position'])) {
                update_post_meta($post_id, 'dfsoccer_position', $_POST['dfsoccer_position']);
            }
            if (isset($_POST['dfsoccer_price'])) {
                update_post_meta($post_id, 'dfsoccer_price', $_POST['dfsoccer_price']);
            }
            if (isset($_POST['dfsoccer_club_id'])) {
                update_post_meta($post_id, 'dfsoccer_club_id', sanitize_text_field($_POST['dfsoccer_club_id']));
            }
            if (isset($_POST['dfsoccer_team_id'])) {
                update_post_meta($post_id, 'dfsoccer_team_id', sanitize_text_field($_POST['dfsoccer_team_id']));
            }
        }
    }
}
add_action('save_post', 'dfsoccer_save_postdata');



function dfsoccer_register_league_post_type() {
    $labels = array(
        'name'               => 'Leagues',
        'singular_name'      => 'League',
        'menu_name'          => 'Leagues',
        'name_admin_bar'     => 'League',
        'add_new'            => 'Add New',
        'add_new_item'       => 'Add New League',
        'new_item'           => 'New League',
        'edit_item'          => 'Edit League',
        'view_item'          => 'View League',
        'all_items'          => 'All Leagues',
        'search_items'       => 'Search Leagues',
        'parent_item_colon'  => 'Parent Leagues:',
        'not_found'          => 'No leagues found.',
        'not_found_in_trash' => 'No leagues found in Trash.'
    );
    $capabilities = array(
        'publish_posts'       => 'publish_dfsoccer_leagues',
        'edit_posts'          => 'edit_dfsoccer_leagues',
        'edit_others_posts'   => 'edit_others_dfsoccer_leagues',
        'delete_posts'        => 'delete_dfsoccer_leagues',
        'delete_others_posts' => 'delete_others_dfsoccer_leagues',
        'read_private_posts'  => 'read_private_dfsoccer_leagues',
        'edit_post'           => 'edit_dfsoccer_league',
        'delete_post'         => 'delete_dfsoccer_league',
        'read_post'           => 'read_dfsoccer_league',
    );
    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array('slug' => 'leagues'),
        'capability_type'    => array('dfsoccer_league', 'dfsoccer_leagues'),
        'capabilities'       => $capabilities,
        'has_archive'        => true,
        'hierarchical'       => false,
        'menu_position'      => null,
        'supports'           => array('title', 'editor'),
        'show_in_rest'       => true,
    );
    register_post_type('dfsoccer_league', $args);
}
add_action('init', 'dfsoccer_register_league_post_type');



// Add Shortcode for League Details
function dfsoccer_league_details_shortcode($atts) {
    // Extract shortcode attributes
    $atts = shortcode_atts(array(
        'id' => 0,
    ), $atts, 'league_details');

    // Get league ID from shortcode attributes
    $league_id = intval($atts['id']);

    // Fetch league details
    $league = get_post($league_id);
    if (!$league || $league->post_type !== 'dfsoccer_league') {
        return 'Invalid league ID';
    }

    // Output league details
    ob_start();
    ?>
    <div class="dfsoccer-league-details">
        <h2><?php echo esc_html($league->post_title); ?></h2>
<div class="league-content">
    <?php 
    $content = wp_kses_post($league->post_content);
    echo apply_filters('the_content', $content); 
    ?>
</div>

    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('league_details', 'dfsoccer_league_details_shortcode');

// Add Shortcode for League Creation Form
function dfsoccer_league_creation_form_shortcode() {
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return 'You need to be logged in to create a league.';
    }

    // Display league creation form
    ob_start();
    ?>
    <div class="dfsoccer-league-creation-form">
        <h2>Create a New League</h2>
        <form method="post" action="">
            <p><label for="league_title">League Title:</label><br />
            <input type="text" id="league_title" name="league_title" /></p>

            <!-- Add more fields as needed -->

            <input type="submit" name="submit_league" value="Create League" />
        </form>
    </div>
    <?php
    return ob_get_clean();
}
add_shortcode('league_creation_form', 'dfsoccer_league_creation_form_shortcode');

// Process League Creation Form Submission


add_action('init', 'dfsoccer_process_league_creation_form', 10);

function dfsoccer_process_league_creation_form() {
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_league'])) {
        if (!is_user_logged_in()) {
            return;  // Ensure user is logged in
        }

        $current_user = wp_get_current_user();
        $league_title = sanitize_text_field($_POST['league_title']);

        // Including the shortcode directly in the initial post content
        $league_id = wp_insert_post(array(
            'post_title'    => $league_title,
            'post_content'  => '[fixture_selection_form]',  // Add shortcode directly here
            'post_type'     => 'dfsoccer_league',
            'post_status'   => 'publish',
            'post_author'   => $current_user->ID,
        ));

        if ($league_id && !is_wp_error($league_id)) {
            wp_redirect(get_permalink($league_id));
            exit;
        } else {
            echo 'Error creating league. Please try again.';
        }
    }
}

function dfsoccer_enqueue_select2() {
    $select2_version = '4.0.13';
    $suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

    wp_enqueue_style(
        'select2-css', 
        plugins_url( "select/select2-{$select2_version}/dist/css/select2{$suffix}.css", __FILE__ ),
        array(),
        $select2_version
    );

    wp_enqueue_script(
        'select2-js', 
        plugins_url( "select/select2-{$select2_version}/dist/js/select2{$suffix}.js", __FILE__ ),
        array('jquery'),
        $select2_version,
        true
    );
}
add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_select2');





function dfsoccer_enqueue_jquery() {
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_jquery');

function dfsoccer_enqueue_assets() {
    if (is_page() || is_single()) { // Adjust condition as needed
        wp_enqueue_style('dfsoccer-fixture-selection-css', plugin_dir_url(__FILE__) . 'css/dfsoccer-fixture-selection.css');
        wp_enqueue_script('dfsoccer-fixture-selection-js', plugin_dir_url(__FILE__) . 'js/dfsoccer-fixture-selection.js', array('jquery'), null, true);
    }
}
add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_assets');


function dfsoccer_fixture_selection_form_shortcode($atts) {
    // For non-logged in users - show login message but continue execution
    $login_message = '';
    if (!is_user_logged_in()) {
        $login_message = '<div style="background-color: rgba(59, 130, 246, 0.2); border-left: 4px solid #3b82f6; padding: 12px; margin: 15px 0; color: #eff6ff;">
            <strong>Note:</strong> You are viewing as a guest. <a href="' . wp_login_url(get_permalink()) . '" style="color: #2563eb; text-decoration: underline;">Log in</a> to save your player selections.
        </div>';
    }
    
    $atts = shortcode_atts(array(
        'league_id' => '0'
    ), $atts, 'fixture_selection_form');
    
    $league_id = intval($atts['league_id']);
    $user_id = get_current_user_id();
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
    $player_meta_key = 'dfsoccer_selected_players_' . $league_id;
    
    $output = '<div class="dfsoccer-fixture-and-player-selection">';
    
    // Add login message if user is not logged in
    if (!empty($login_message)) {
        $output .= $login_message;
    }
    
    // Check both regular and API fixture meta keys
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    $api_saved_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
    
    // Determine which fixtures to use - prefer manually entered ones if both exist
    $active_fixtures = !empty($saved_fixtures) ? $saved_fixtures : $api_saved_fixtures;
    $fixture_source = !empty($saved_fixtures) ? 'manual' : (!empty($api_saved_fixtures) ? 'api' : '');
    
    // Handle fixture form submission - only for logged-in users
    if (is_user_logged_in() && isset($_POST['submit_fixtures'])) {
        $num_fixtures = intval($_POST['num_fixtures']);
        $fixtures = [];
        
        for ($i = 0; $i < $num_fixtures; $i++) {
            $fixtures[] = [
                'home_club_id' => sanitize_text_field($_POST["home_club_$i"]),
                'away_club_id' => sanitize_text_field($_POST["away_club_$i"]),
                'fixture_date' => sanitize_text_field($_POST["fixture_date_$i"]),
            ];
        }
        
        update_post_meta($league_id, $fixture_meta_key, $fixtures);
        $active_fixtures = $fixtures;
        $fixture_source = 'manual';
        
        $output .= '<p>Fixtures saved successfully. Please continue to select players.</p>';
    }
    
    // Handle API fixture import - only for logged-in users
    if (is_user_logged_in() && isset($_POST['import_api_fixtures'])) {
        // Get the selected league ID from the dropdown
        $selected_api_league_id = intval($_POST['api_league_id']);
        
        // First, get the league details to get the start date
        $combined_leagues = dfsoccer_get_combined_available_leagues();
        $league_start_date = null;
        
        // Find the selected league and get its start date
        foreach ($combined_leagues as $league) {
            if (isset($league['id']) && $league['id'] == $selected_api_league_id && isset($league['start_date'])) {
                $league_start_date = $league['start_date'];
                break;
            }
        }
        
        // Now make the API request for fixtures and players
        $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $selected_api_league_id . '/fixture-players?nocache=1&LSCWP_CTRL=NOCACHE';
        $response = wp_remote_get($api_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $api_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($api_data['fixtures']) && !empty($api_data['fixtures'])) {
                $fixtures = array_map(function($fixture) use ($league_start_date) {
                    // Use the league start date if fixture date is not available
                    $fixture_date = isset($fixture['date']) && !empty($fixture['date']) 
                        ? $fixture['date'] 
                        : ($league_start_date ? $league_start_date : date('Y-m-d H:i:s'));
                    
                    // Include club names if available in the API response
                    $home_club_name = isset($fixture['home_club_name']) ? $fixture['home_club_name'] : '';
                    $away_club_name = isset($fixture['away_club_name']) ? $fixture['away_club_name'] : '';
                    
                    return [
                        'home_club_id' => $fixture['home_club_id'],
                        'away_club_id' => $fixture['away_club_id'],
                        'fixture_date' => $fixture_date,
                        'home_club_name' => $home_club_name,
                        'away_club_name' => $away_club_name
                    ];
                }, $api_data['fixtures']);
                
                // Save with the API meta key to indicate source
                update_post_meta($league_id, $api_fixture_meta_key, $fixtures);
                update_post_meta($league_id, 'dfsoccer_api_source_league_id', $selected_api_league_id);

                $active_fixtures = $fixtures;
                $fixture_source = 'api';
                
                $output .= '<p>Fixtures imported from API successfully. Please continue to select players.</p>';
            } else {
                $output .= '<p>Error: No fixtures found in the API response.</p>';
            }
        } else {
            $output .= '<p>Error connecting to the API. Please try again later.</p>';
        }
    }
    
    $league_name = get_the_title($league_id);
    
    // Function to display fixtures with club names
    function display_fixtures($fixtures, $source = '') {
        $source_text = ($source === 'api') ? ' (Imported from API)' : '';
        $output = '<h2>Selected Fixtures' . $source_text . '</h2>';
        
        foreach ($fixtures as $fixture) {
            // First try to use stored club names for API fixtures
            if ($source === 'api' && !empty($fixture['home_club_name']) && !empty($fixture['away_club_name'])) {
                $home_club_name = esc_html($fixture['home_club_name']);
                $away_club_name = esc_html($fixture['away_club_name']);
            } else {
                // Fall back to database lookup
                $home_club_name = esc_html(get_the_title($fixture['home_club_id']));
                $away_club_name = esc_html(get_the_title($fixture['away_club_id']));
            }
            
            $formatted_date = esc_html(date_i18n('Y-m-d H:i:s', strtotime($fixture['fixture_date'])));
$output .= "<p style=\"color: #ffffff; font-size: 1rem; text-align: center; margin: 1rem 0; line-height: 1.5;\">
  Fixture: 
  <span style=\"font-weight: bold; color: #86efac; text-transform: uppercase; letter-spacing: 0.05em;\">{$home_club_name}</span> 
  <span style=\"color: #eab308; font-weight: bold; margin: 0 0.5rem;\">vs</span> 
  <span style=\"font-weight: bold; color: #86efac; text-transform: uppercase; letter-spacing: 0.05em;\">{$away_club_name}</span> 
  <br>
  <span style=\"display: inline-block; margin-top: 0.5rem; font-size: 0.9rem; color: #bbf7d0; background-color: rgba(22, 101, 52, 0.4); padding: 0.25rem 0.75rem; border-radius: 1rem;\">
    <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"12\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"display: inline-block; vertical-align: middle; margin-right: 0.25rem;\">
      <circle cx=\"12\" cy=\"12\" r=\"10\"></circle>
      <polyline points=\"12 6 12 12 16 14\"></polyline>
    </svg>
    " . date('M d, Y', strtotime($formatted_date)) . " • " . date('H:i', strtotime($formatted_date)) . "
  </span>
</p>";                }
        
        return $output;
    }
    
    if (!empty($active_fixtures)) {
        $first_fixture_date = strtotime($active_fixtures[0]['fixture_date']);
        $current_time = current_time('timestamp');

        $output .= do_shortcode('[time_until_fixture]');

        // Always display fixtures
        $output .= display_fixtures($active_fixtures, $fixture_source);
        
        // Always display player selection regardless of fixture timing
        // Check if fixtures are from API
        if ($fixture_source === 'api') {
            // Get the source league ID
            $source_league_id = get_post_meta($league_id, 'dfsoccer_api_source_league_id', true);
            
            if (!empty($source_league_id)) {
                // If we have a source league ID, use the API players shortcode with src parameter
                $output .= do_shortcode('[dfsoccer_fantasy_manager league_id="' . esc_attr($league_id) . '" src="' . esc_attr($source_league_id) . '" mode="11"]');
            } else {
                // Fallback to regular shortcode if somehow source league ID is missing
                $output .= do_shortcode('[players_for_fixtures league_id="' . esc_attr($league_id) . '"]');
            }
        } else {
            // For manually entered fixtures, use the original players shortcode
            $output .= do_shortcode('[players_for_fixtures league_id="' . esc_attr($league_id) . '"]');
        }
        
        // Show warning if first fixture has already started
if ($current_time >= $first_fixture_date) {
    $output .= '<div style="background-color: #ffffff; border-left: 4px solid #36a33d; padding: 12px; margin: 15px 0; color: #36a33d;">
        <strong>Note:</strong> The first fixture has already started. Any changes to your player selection may not be counted for scoring purposes.
    </div>';
}
    }
    
    // Show fixture selection options if no fixtures are saved and user has permission
    if (empty($active_fixtures) && is_user_logged_in() && current_user_can('edit_dfsoccer_leagues')) {
        ob_start();
        ?>
        <h2>Select Fixtures for League <?php echo esc_html($league_name); ?></h2>
        
        <div class="fixture-selection-tabs">
            <ul class="tabs">
                <li class="active"><a href="#manual-fixtures">Enter Fixtures Manually</a></li>
                <li><a href="#api-fixtures">Import Fixtures from API</a></li>
            </ul>
            
            <div class="tab-content">
                <!-- Manual Fixture Entry Form -->
                <div id="manual-fixtures" class="tab-pane active">
                    <form id="fixture_selection_form" method="post" action="">
                        <input type="hidden" name="league_id" value="<?php echo esc_attr($league_id); ?>">
                        <label for="num_fixtures">Number of Fixtures:</label><br />
                        <input type="number" id="num_fixtures" name="num_fixtures" min="1" max="10" value="1" />
                        <div id="fixture_fields_container"></div>
                        <input type="submit" name="submit_fixtures" value="Submit Fixtures" />
                    </form>
                    <div id="club_options" style="display:none;">
                        <?php echo dfsoccer_get_club_options(); ?>
                    </div>
                </div>
                
                <!-- API Fixture Import Form -->
                <div id="api-fixtures" class="tab-pane">
                    <form id="api_fixture_form" method="post" action="">
                        <input type="hidden" name="league_id" value="<?php echo esc_attr($league_id); ?>">
                        
                        <p>Import fixtures from another league using the API.</p>
                        
                        <div id="api_leagues_loading">Loading available leagues...</div>
                        <div id="api_leagues_container" style="display:none;">
                            <label for="api_league_id">Select League to Import From:</label><br />
                            <select id="api_league_id" name="api_league_id" required>
                                <option value="">-- Select a League --</option>
                            </select>
                            <br /><br />
                            <input type="submit" name="import_api_fixtures" value="Import Fixtures" />
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <style>
            .fixture-selection-tabs .tabs {
                display: flex;
                list-style: none;
                padding: 0;
                margin: 0 0 20px 0;
                border-bottom: 1px solid #ccc;
            }
            .fixture-selection-tabs .tabs li {
                margin-right: 5px;
            }
            .fixture-selection-tabs .tabs li a {
                display: block;
                padding: 10px 15px;
                text-decoration: none;
                background: #f5f5f5;
                color: #333;
                border: 1px solid #ccc;
                border-bottom: none;
            }
            .fixture-selection-tabs .tabs li.active a {
                background: #fff;
                border-bottom: 1px solid #fff;
                margin-bottom: -1px;
                font-weight: bold;
            }
            .fixture-selection-tabs .tab-content {
                padding: 20px;
                border: 1px solid #ccc;
                border-top: none;
            }
            .fixture-selection-tabs .tab-pane {
                display: none;
            }
            .fixture-selection-tabs .tab-pane.active {
                display: block;
            }
            .league-section {
                margin-bottom: 30px;
                padding: 15px;
                border: 1px solid #ddd;
                border-radius: 5px;
            }
            .league-section h4 {
                margin-top: 0;
                color: #333;
                font-weight: bold;
                border-bottom: 1px solid #eee;
                padding-bottom: 5px;
            }
            .user-leagues {
                background-color: #f8f9fa;
            }
            .admin-leagues {
                background-color: #fff;
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            // Tab functionality
            $('.fixture-selection-tabs .tabs a').on('click', function(e) {
                e.preventDefault();
                var targetId = $(this).attr('href');
                
                // Update tabs
                $('.fixture-selection-tabs .tabs li').removeClass('active');
                $(this).parent().addClass('active');
                
                // Update tab content
                $('.fixture-selection-tabs .tab-pane').removeClass('active');
                $(targetId).addClass('active');
            });
            
            // Use the new combined function to get leagues
            var combinedLeagues = <?php echo json_encode(dfsoccer_get_combined_available_leagues()); ?>;
            
            $('#api_leagues_loading').hide();
            $('#api_leagues_container').show();
            
            // Populate the dropdown with combined leagues
            var select = $('#api_league_id');
            $.each(combinedLeagues, function(index, league) {
                var startDateInfo = league.start_date ? ' (Starts: ' + league.start_date + ')' : '';
                var creatorInfo = league.creator_name ? ' [Created by: ' + league.creator_name + ']' : '';
                var sourceInfo = league.source ? ' (' + league.source + ')' : '';
                
                select.append($('<option>', {
                    value: league.id,
                    text: league.name + startDateInfo + creatorInfo + sourceInfo
                }));
                
                // Add optgroup separators if needed
                if (index === 0 && league.source === 'Followed Users') {
                    select.append($('<option>', {
                        value: '',
                        text: '--- Admin Leagues ---',
                        disabled: true
                    }));
                }
            });
            
            // Fixture fields code
            $('#num_fixtures').on('change', function() {
                createFixtureFields($(this).val());
            });
            
            function createFixtureFields(numFixtures) {
                var container = $('#fixture_fields_container');
                var clubOptions = $('#club_options').html();
                
                container.empty();
                
                for (var i = 0; i < numFixtures; i++) {
                    var fixtureHtml = '<div class="fixture-row">' +
                        '<h3>Fixture ' + (i + 1) + '</h3>' +
                        '<label for="home_club_' + i + '">Home Club:</label>' +
                        '<select name="home_club_' + i + '" id="home_club_' + i + '" required>' + clubOptions + '</select><br>' +
                        '<label for="away_club_' + i + '">Away Club:</label>' +
                        '<select name="away_club_' + i + '" id="away_club_' + i + '" required>' + clubOptions + '</select><br>' +
                        '<label for="fixture_date_' + i + '">Date and Time:</label>' +
                        '<input type="datetime-local" name="fixture_date_' + i + '" id="fixture_date_' + i + '" required><br>' +
                        '</div>';
                    
                    container.append(fixtureHtml);
                }
                
                // Initialize select2 for the new fixture fields if select2 is available
                if ($.fn.select2) {
                    $('.fixture-row select').select2({
                        placeholder: 'Select a club',
                        allowClear: true
                    });
                }
            }
            
            // Initialize with one fixture field
            createFixtureFields(1);
        });
        </script>
        <?php
        $output .= ob_get_clean();
    }
    
    $output .= '</div>'; // Close main div
    return $output;
}
add_shortcode('fixture_selection_form', 'dfsoccer_fixture_selection_form_shortcode');

// New function to get combined leagues from followed users and admin
function dfsoccer_get_combined_available_leagues() {
    $combined_leagues = array();
    
    // Get followed user IDs from settings
    $followed_user_ids = dfsoccer_get_followed_user_ids();
    
    // If we have followed user IDs, fetch their leagues first
    if (!empty($followed_user_ids)) {
        foreach ($followed_user_ids as $user_id) {
            $user_leagues_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/available-leagues/' . $user_id . '?nocache=1&LSCWP_CTRL=NOCACHE';
            $user_response = wp_remote_get($user_leagues_url);
            
            if (!is_wp_error($user_response) && wp_remote_retrieve_response_code($user_response) === 200) {
                $user_leagues = json_decode(wp_remote_retrieve_body($user_response), true);
                
                if (is_array($user_leagues)) {
                    // Add source information to distinguish these leagues
                    foreach ($user_leagues as &$league) {
                        $league['source'] = 'Followed Users';
                        $league['source_user_id'] = $user_id;
                    }
                    
                    $combined_leagues = array_merge($combined_leagues, $user_leagues);
                }
            }
        }
    }
    
    // Then fetch admin leagues
    $admin_leagues_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/available-leagues?nocache=1&LSCWP_CTRL=NOCACHE';
    $admin_response = wp_remote_get($admin_leagues_url);
    
    if (!is_wp_error($admin_response) && wp_remote_retrieve_response_code($admin_response) === 200) {
        $admin_leagues = json_decode(wp_remote_retrieve_body($admin_response), true);
        
        if (is_array($admin_leagues)) {
            // Add source information
            foreach ($admin_leagues as &$league) {
                $league['source'] = 'Admin';
            }
            
            $combined_leagues = array_merge($combined_leagues, $admin_leagues);
        }
    }
    
    // If no followed users are configured, just return admin leagues
    if (empty($followed_user_ids)) {
        // Remove source info since it's just admin leagues
        foreach ($combined_leagues as &$league) {
            unset($league['source']);
        }
    }
    
    return $combined_leagues;
}


function dfsoccer_get_club_options() {
    $args = array(
        'post_type'      => 'dfsoccer_club',
        'posts_per_page' => -1,
    );
    $clubs = get_posts($args);
    $options = '';
    foreach ($clubs as $club) {
        $options .= sprintf(
            '<option value="%s">%s</option>',
            esc_attr($club->ID),
            esc_html($club->post_title)
        );
    }
    return $options;
}


function dfsoccer_enqueue_scripts() {
    // Enqueue player selection script
    wp_enqueue_script(
        'dfsoccer-player-selection',
        plugin_dir_url(__FILE__) . 'js/dfsoccer-player-selection.js',
        array('jquery'),
        '1.0.0',
        true
    );



    // Enqueue team-player toggle script
    wp_enqueue_script(
        'team-player-toggle',
        plugin_dir_url(__FILE__) . 'js/team-player-toggle.js',
        array(),
        '1.0.0',
        true
    );
}

add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_scripts');



function dfsoccer_display_players_for_fixtures_shortcode($atts) {
    if (!is_user_logged_in()) {
        return 'You need to be logged in to select players.';
    }

    $atts = shortcode_atts(array(
        'league_id' => '0'
    ), $atts, 'players_for_fixtures');

    $league_id = intval($atts['league_id']);
    $user_id = get_current_user_id();
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $player_meta_key = 'dfsoccer_selected_players_' . $league_id;
    $budget = floatval(get_post_meta($league_id, 'dfsoccer_league_budget', true));

    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);

    if (empty($saved_fixtures)) {
        return '';
    }

    $output = '<form id="player_selection_form" method="post">';
    
    // Display league budget and current price
    $output .= '<div id="budget_info">
                    <p>League Budget: <span id="league_budget">' . $budget . '</span></p>
                    <p>Current Price: <span id="current_price" style="color: green;">0</span></p>
                </div>';

    if (isset($_POST['submit_players'])) {
        $selected_players = $_POST['selected_players'] ?? [];
        $total_cost = 0;

        foreach ($selected_players as $player_id) {
            $player_price = floatval(get_post_meta($player_id, 'dfsoccer_price', true));
            $total_cost += $player_price;
        }

        if (count($selected_players) < 6) {
            $output .= '<div class="error">You must select exactly six players.</div>';
        } elseif (count($selected_players) > 6) {
            $output .= '<div class="error">You cannot select more than six players.</div>';
        } elseif ($total_cost > $budget) {
            $output .= '<div class="error">You are over budget.</div>';
        } else {
            update_user_meta($user_id, $player_meta_key, $selected_players);
            $output .= '<div class="success">Players selected successfully!</div>';
        }
    }

    $output .= '<div>
                    <input type="text" id="player_search" placeholder="Search for players...">
                    <select id="club_filter">
                        <option value="">All Clubs</option>';
    $clubs = array_unique(array_merge(
        array_column($saved_fixtures, 'home_club_id'),
        array_column($saved_fixtures, 'away_club_id')
    ));
    foreach ($clubs as $club_id) {
        $output .= '<option value="' . $club_id . '">' . get_the_title($club_id) . '</option>';
    }
    $output .= '    </select>
                    <select id="position_filter">
                        <option value="">All Positions</option>
                        <option value="goalkeeper">Goalkeeper</option>
                        <option value="defender">Defender</option>
                        <option value="midfielder">Midfielder</option>
                        <option value="attacker">Attacker</option>
                    </select>
                    <input type="number" id="priceFilter" placeholder="Filter by price">
                </div>';
    $output .= '<button id="repaginate-button" type="button" style="display:none;">Repaginate Players</button>';

    // Create a container for sorted players
    $output .= '<div id="sorted_players_container"></div>';

    // Create a hidden container for the original club-based players
    $output .= '<div id="players_container" style="display: none;">';
    foreach ($saved_fixtures as $fixture) {
        $home_club_id = $fixture['home_club_id'];
        $away_club_id = $fixture['away_club_id'];

        $output .= '<div class="club-players" data-club-id="' . $home_club_id . '">' . dfsoccer_list_players_by_club($home_club_id, $league_id, true, $player_meta_key, $user_id) . '</div>';
        $output .= '<div class="club-players" data-club-id="' . $away_club_id . '">' . dfsoccer_list_players_by_club($away_club_id, $league_id, true, $player_meta_key, $user_id) . '</div>';
    }
    $output .= '</div>';

    $output .= '<input type="submit" name="submit_players" value="Save Selections" />';
    $output .= '</form>';

    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("player_selection_form");
        const searchInput = document.getElementById("player_search");
        const clubFilter = document.getElementById("club_filter");
        const positionFilter = document.getElementById("position_filter");
        const priceFilter = document.getElementById("priceFilter");
        const budget = ' . $budget . ';
        const currentPriceElement = document.getElementById("current_price");
        const originalPlayersContainer = document.getElementById("players_container");
        const sortedPlayersContainer = document.getElementById("sorted_players_container");
        const repaginateButton = document.getElementById("repaginate-button");
        const itemsPerPage = 10;
        let currentPage = 1;
		



        // Function to sort and display players
        function sortAndDisplayPlayers() {
            const playerCards = Array.from(originalPlayersContainer.querySelectorAll(".player-card"));
            
            // Sort players by price (highest to lowest)
            playerCards.sort((a, b) => {
                const priceA = parseFloat(a.querySelector("input").getAttribute("data-price"));
                const priceB = parseFloat(b.querySelector("input").getAttribute("data-price"));
                return priceB - priceA;
            });

            // Clear and repopulate sorted container
            sortedPlayersContainer.innerHTML = "";
            playerCards.forEach(card => {
                sortedPlayersContainer.appendChild(card.cloneNode(true));
            });

            // Reattach event listeners to the cloned elements
            sortedPlayersContainer.querySelectorAll("input[name=\'selected_players[]\']").forEach(checkbox => {
                checkbox.addEventListener("change", updateCurrentPrice);
            });
        }

        // Create pagination container
        const paginationContainer = document.createElement("div");
        paginationContainer.id = "pagination";
        sortedPlayersContainer.after(paginationContainer);

        function showPage(page) {
            const visibleCards = sortedPlayersContainer.querySelectorAll(".player-card:not(.filtered-out)");
            const startIndex = (page - 1) * itemsPerPage;
            const endIndex = startIndex + itemsPerPage;

            visibleCards.forEach((card, index) => {
                if (index >= startIndex && index < endIndex) {
                    card.style.display = "";
                } else {
                    card.style.display = "none";
                }
            });
        }

        function updatePagination() {
            const visibleCards = sortedPlayersContainer.querySelectorAll(".player-card:not(.filtered-out)");
            const totalPages = Math.ceil(visibleCards.length / itemsPerPage);

            paginationContainer.innerHTML = "";
            for (let i = 1; i <= totalPages; i++) {
                const pageButton = document.createElement("button");
                pageButton.textContent = i;
                pageButton.addEventListener("click", () => {
                    currentPage = i;
                    showPage(currentPage);
                    updatePagination();
					
                });
                if (i === currentPage) {
                    pageButton.disabled = true;
                }
                paginationContainer.appendChild(pageButton);
            }

            paginationContainer.style.display = totalPages > 1 ? "block" : "none";
        }

        function areFiltersActive() {
            return searchInput.value !== "" || 
                   clubFilter.value !== "" || 
                   positionFilter.value !== "" || 
                   priceFilter.value !== "";
        }

        function resetFilters() {
            searchInput.value = "";
            clubFilter.value = "";
            positionFilter.value = "";
            priceFilter.value = "";
            const playerCards = sortedPlayersContainer.querySelectorAll(".player-card");
            playerCards.forEach(card => {
                card.style.display = "";
                card.classList.remove("filtered-out");
            });
        }

        repaginateButton.addEventListener("click", function() {
            resetFilters();
            currentPage = 1;
            showPage(currentPage);
            updatePagination();
            this.style.display = "none";
        });

        form.addEventListener("submit", function(event) {
            const selectedPlayers = document.querySelectorAll("input[name=\'selected_players[]\']:checked");
            let totalCost = 0;
            selectedPlayers.forEach(player => {
                totalCost += parseFloat(player.getAttribute("data-price"));
            });

            if (totalCost > budget) {
                event.preventDefault();
                alert("You are over budget.");
            }
        });

        function filterPlayers() {
            const searchTerm = searchInput.value.toLowerCase();
            const selectedClub = clubFilter.value;
            const selectedPosition = positionFilter.value;

            const playerCards = sortedPlayersContainer.querySelectorAll(".player-card");

            playerCards.forEach(card => {
                const playerName = card.querySelector(".player-name").textContent.toLowerCase();
                const playerClub = card.getAttribute("data-club-id");
                const playerPosition = card.getAttribute("data-position");

                let matchesSearch = playerName.includes(searchTerm);
                let matchesClub = !selectedClub || playerClub === selectedClub;
                let matchesPosition = !selectedPosition || playerPosition === selectedPosition;

                if (matchesSearch && matchesClub && matchesPosition) {
                    card.style.display = "block";
                    card.classList.remove("filtered-out");
                } else {
                    card.style.display = "none";
                    card.classList.add("filtered-out");
                }
            });

            if (searchTerm || priceFilter.value) {
                clubFilter.value = "";
                positionFilter.value = "";
            }

            if (areFiltersActive()) {
                paginationContainer.style.display = "none";
                repaginateButton.style.display = "block";
            } else {
                paginationContainer.style.display = "block";
                repaginateButton.style.display = "none";
                currentPage = 1;
                showPage(currentPage);
                updatePagination();
            }
        }

     window.updateCurrentPrice = function() {

    if (window.selectedPlayers && window.selectedPlayers.length === 0) {
        console.log("Empty player selection detected, setting price to 0");
        const currentPriceElement = document.getElementById("current_price");
        if (currentPriceElement) {
            currentPriceElement.textContent = "0.00";
            currentPriceElement.style.color = "green";
        }
        window.currentTotalCost = 0;
        return; // Exit the function early
    }
    // Try to determine the most recently updated player selection
    let playerIds = [];
    
    // First check window.selectedPlayers (global array)
    if (window.selectedPlayers && window.selectedPlayers.length > 0) {
        playerIds = window.selectedPlayers;
        console.log("Using global selectedPlayers:", playerIds);
    } 
else if (typeof selectedPlayers !== "undefined" && selectedPlayers.length > 0) {
        playerIds = selectedPlayers;
        console.log("Using local selectedPlayers:", playerIds);
    }
    // Last resort: get directly from checkboxes
    else {
const checkedBoxes = document.querySelectorAll("input[type=checkbox]:checked");
        playerIds = Array.from(checkedBoxes).map(cb => cb.value);
        console.log("Using checkbox state for players:", playerIds);
    }
    
    // Find UI elements
    const currentPriceElement = document.getElementById("current_price");
    const budgetElement = document.getElementById("budget_value") || document.querySelector(".budget-amount");

var budgetInput = null;
var inputs = document.getElementsByTagName("input");
for (var i = 0; i < inputs.length; i++) {
    if (inputs[i].name === "league_budget") {
        budgetInput = inputs[i];
        break;
    }
}
    let totalCost = 0;
    
    // Process each selected player
    playerIds.forEach(playerId => {
        try {
            const playerInput = document.querySelector(`input[name="selected_players[]"][value="${playerId}"]`);
            if (playerInput) {
                const playerPrice = parseFloat(playerInput.getAttribute("data-price"));
                const playerName = playerInput.getAttribute("data-name") || "Unknown player";
                
                if (!isNaN(playerPrice)) {
                    console.log(`Adding player: ${playerName} (ID: ${playerId}) - Price: ${playerPrice}`);
                    totalCost += playerPrice;
                    console.log("Running total:", totalCost.toFixed(2));
                }
            }
        } catch (error) {
            console.error(`Error processing player ${playerId}:`, error);
        }
    });
    
    console.log("Final total cost:", totalCost.toFixed(2));
    
    // Update UI if elements exist
    if (currentPriceElement) {
        currentPriceElement.textContent = totalCost.toFixed(2);
        
        if (totalCost > budget) {
            console.log("Over budget! Budget:", budget);
            currentPriceElement.style.color = "red";
        } else {
            console.log("Within budget! Budget:", budget);
            currentPriceElement.style.color = "green";
        }
    } else {
        console.error("Price element not found");
    }
};  
	   
	   
	   
        searchInput.addEventListener("input", filterPlayers);
        clubFilter.addEventListener("change", filterPlayers);
        positionFilter.addEventListener("change", filterPlayers);

        priceFilter.addEventListener("input", function() {
            var filterValue = parseFloat(this.value);
            var players = sortedPlayersContainer.querySelectorAll(".player-card");

            players.forEach(function(player) {
                var price = parseFloat(player.querySelector("input").getAttribute("data-price"));
                if (price <= filterValue || isNaN(filterValue)) {
                    player.style.display = "flex";
                    player.classList.remove("filtered-out");
                } else {
                    player.style.display = "none";
                    player.classList.add("filtered-out");
                }
            });

            clubFilter.value = "";
            positionFilter.value = "";

            if (filterValue) {
                paginationContainer.style.display = "none";
                repaginateButton.style.display = "block";
            } else {
                paginationContainer.style.display = "block";
                repaginateButton.style.display = "none";
                currentPage = 1;
                showPage(currentPage);
                updatePagination();
            }
        });

        // Initial setup
        sortAndDisplayPlayers();
        updateCurrentPrice();
        showPage(currentPage);
        updatePagination();
		
    });
	
	
    </script>';
	
	

    return $output;
}

add_shortcode('players_for_fixtures', 'dfsoccer_display_players_for_fixtures_shortcode');


function dfsoccer_append_league_details($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {
        $league_id = get_the_ID(); // Get the current league ID
        $shortcode = '[fixture_selection_form league_id="' . $league_id . '"]'; // Construct the shortcode with the league ID
        $details = do_shortcode($shortcode); // Execute the shortcode
        $content .= $details; // Append the result to the content
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_league_details');


function dfsoccer_enqueue_styles() {
    wp_enqueue_style(
        'dfsoccer-teams-display',
        plugin_dir_url(__FILE__) . 'css/dfsoccer-teams-display.css',
        array(), // Dependencies (leave empty if none)
        '1.0.0'  // Version
    );
}

add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_styles');


function dfsoccer_display_teams_shortcode($atts) {
    // Extract shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'league_id' => 0,
        'per_page' => 20 // Default number of teams per page
    ), $atts, 'display_teams');
    
    $league_id = intval($atts['league_id']);
    $per_page = intval($atts['per_page']);
    
    if (!$league_id) {
        return 'Invalid League ID';
    }
    
    // Initialize output
    ob_start();
    
    // Get the earliest fixture date from post meta
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;

    $fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    $api_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);

    // Use API fixtures if regular fixtures don't exist
    if (empty($fixtures)) {
        $fixtures = $api_fixtures;
    }
    
    if (empty($fixtures)) {
        echo '<div>No fixtures set for this league.</div>';
        return ob_get_clean();
    }
    
    // Sort fixtures by date to find the earliest
    usort($fixtures, function($a, $b) {
        return strtotime($a['fixture_date']) <=> strtotime($b['fixture_date']);
    });
    
    $current_time = current_time('timestamp');
    $first_fixture_time = strtotime($fixtures[0]['fixture_date']);
    $show_players = $current_time >= $first_fixture_time;
    
    // Fetch users who have participated in this league
    $args = array(
        'meta_key'   => 'dfsoccer_selected_players_' . $league_id,
        'meta_value' => '', // Any non-empty value
        'meta_compare' => '!=',
        'fields' => 'all_with_meta'
    );
    
    $users = get_users($args);
    
    // Start building the HTML
    echo '<div class="dfsoccer-teams-display" id="fantasysoccerteamsdisplay-' . esc_attr($league_id) . '">';
    
    // Add pagination controls if we have users
    if (!empty($users)) {
        echo '<div class="controls">';
        echo '<div class="per-page-control">';
        echo '<label for="fantasysoccerteamsdisplay-per-page-' . esc_attr($league_id) . '">Teams per page:</label>';
        echo '<select id="fantasysoccerteamsdisplay-per-page-' . esc_attr($league_id) . '" class="per-page-select" onchange="fantasysoccerteamsdisplayChangeItemsPerPage(' . esc_attr($league_id) . ')">';
        echo '<option value="10"' . ($per_page == 10 ? ' selected' : '') . '>10</option>';
        echo '<option value="20"' . ($per_page == 20 ? ' selected' : '') . '>20</option>';
        echo '<option value="30"' . ($per_page == 30 ? ' selected' : '') . '>30</option>';
        echo '<option value="0">All</option>';
        echo '</select>';
        echo '</div>';
        echo '</div>';
        
        // Add pagination info
        echo '<div class="pagination-info" id="fantasysoccerteamsdisplay-pagination-info-' . esc_attr($league_id) . '"></div>';
    }
    
    // Check if we have any users
    if (empty($users)) {
        echo '<table>';
        echo '<tr><th>Position</th><th>Team</th><th>Total Points</th></tr>';
        echo '<tr><td colspan="3">No teams have been selected for this league.</td></tr>';
        echo '</table>';
    } else {
        // Process team data
        $teams = [];
        $position = 1;
        
        // List each user and their selected players
        foreach ($users as $user) {
            $selected_players = get_user_meta($user->ID, 'dfsoccer_selected_players_' . $league_id, true);
            
            if (!empty($selected_players)) {
                $team_total_points = 0;
                $player_details = '';
                $team_budget = 0; // Initialize team budget
                
                // Retrieve match results for the league
                $match_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
                $results_array = json_decode($match_results, true);
                
                // Process each player in the team
                foreach ($selected_players as $player_id) {
                    $player_post = get_post($player_id);
                    
                    if ($player_post) {
                        // Get player price for budget calculation
                        $price = get_post_meta($player_id, 'dfsoccer_price', true);
                        $team_budget += floatval($price);
                        
                        // Initialize points to zero by default
                        $total_points = 0;
                        
                        // Check if we have valid match results to use
                        if (isset($results_array) && is_array($results_array) && 
                            isset($results_array[$player_id]) && 
                            isset($results_array[$player_id]['total_points'])) {
                            // Use actual results if available
                            $total_points = $results_array[$player_id]['total_points'];
                        }
                        
                        // Add to team total and player details
                        $team_total_points += floatval($total_points);
                        
                        $has_points_class = (floatval($total_points) > 0) ? ' class="has-points"' : '';
                        $player_details .= '<tr class="player-row">' . 
                                          '<td colspan="2">' . esc_html(get_the_title($player_post->ID)) . '</td>' . 
                                          '<td' . $has_points_class . '><strong>' . esc_html(number_format($total_points, 2)) . '</strong></td>' . 
                                          '</tr>';
                    }
                }
                
                // Get user prediction points from the database
                global $wpdb;
                $table_name = $wpdb->prefix . 'dfsoccer_points';
                $prediction_points = 0;
                
                // Check if table exists
                $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
                if ($table_exists) {
                    $query = "SELECT SUM(points) as total_points FROM {$table_name} WHERE user_id = %d";
                    $result = $wpdb->get_var($wpdb->prepare($query, $user->ID));
                    if ($result !== null) {
                        $prediction_points = floatval($result);
                    }
                }
                
                // Add this team to our teams array with all tiebreaker data
                $teams[] = [
                    'user_name' => esc_html($user->display_name),
                    'total_points' => $team_total_points,
                    'player_details' => $player_details,
                    'position' => $position++,
                    'budget' => $team_budget,
                    'prediction_points' => $prediction_points,
                    'random_factor' => mt_rand(1, 1000) // Add random factor for final tiebreaker
                ];
            }
        }
        
        // Sort teams by total points in descending order with multiple tiebreakers
        usort($teams, function($a, $b) {
            // First tiebreaker: Team total points
            $points_comparison = $b['total_points'] <=> $a['total_points'];
            if ($points_comparison !== 0) {
                return $points_comparison;
            }
            
            // Second tiebreaker: Team budget (lower wins)
            $budget_comparison = $a['budget'] <=> $b['budget'];
            if ($budget_comparison !== 0) {
                return $budget_comparison;
            }
            
            // Third tiebreaker: Prediction points
            $prediction_comparison = $b['prediction_points'] <=> $a['prediction_points'];
            if ($prediction_comparison !== 0) {
                return $prediction_comparison;
            }
            
            // Final tiebreaker: Random factor
            return $b['random_factor'] <=> $a['random_factor'];
        });
        
        // Update positions after sorting
        foreach ($teams as $index => $team) {
            $teams[$index]['position'] = $index + 1;
        }
        
        // Convert to JSON for JavaScript pagination
        $teams_json = json_encode($teams);
        
        // Create table structure
        echo '<table id="fantasysoccerteamsdisplay-table-' . esc_attr($league_id) . '">';
        echo '<tr><th>Position</th><th>Team</th><th>Total Points</th></tr>';
        echo '<tbody id="fantasysoccerteamsdisplay-tbody-' . esc_attr($league_id) . '">';
        echo '<!-- Teams will be inserted here by JavaScript -->';
        echo '</tbody>';
        echo '</table>';
        
        // Add pagination controls
        echo '<div class="pagination" id="fantasysoccerteamsdisplay-pagination-' . esc_attr($league_id) . '"></div>';
    }
    
    echo '</div>'; // End dfsoccer-teams-display
    
    // Add CSS for styling
    echo '<style>
        .team-meta {
            font-size: 0.85em;
            color: #666;
        }
		

    </style>';
        
    // Add JavaScript to handle pagination
    if (!empty($users)) {
        ?>
        <script type="text/javascript">
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize teams data
            const fantasysoccerteamsdisplayData = <?php echo $teams_json; ?>;
            const fantasysoccerteamsdisplayShowPlayers = <?php echo $show_players ? 'true' : 'false'; ?>;
            const fantasysoccerteamsdisplayLeagueId = <?php echo $league_id; ?>;
            const fantasysoccerteamsdisplayDefaultPerPage = <?php echo $per_page; ?>;
            
            fantasysoccerteamsdisplayInitialize(
                fantasysoccerteamsdisplayData, 
                fantasysoccerteamsdisplayLeagueId, 
                fantasysoccerteamsdisplayShowPlayers, 
                fantasysoccerteamsdisplayDefaultPerPage
            );
        });
        
        // Initialize the teams display with pagination
        function fantasysoccerteamsdisplayInitialize(teamsData, leagueId, showPlayers, defaultPerPage) {
            // Configuration
            let fantasysoccerteamsdisplayCurrentPage = 1;
            let fantasysoccerteamsdisplayItemsPerPage = defaultPerPage;
            const fantasysoccerteamsdisplayTotalItems = teamsData.length;
            
            // Get DOM elements
            const fantasysoccerteamsdisplayTbody = document.getElementById('fantasysoccerteamsdisplay-tbody-' + leagueId);
            const fantasysoccerteamsdisplayPaginationInfo = document.getElementById('fantasysoccerteamsdisplay-pagination-info-' + leagueId);
            const fantasysoccerteamsdisplayPagination = document.getElementById('fantasysoccerteamsdisplay-pagination-' + leagueId);
            const fantasysoccerteamsdisplayPerPageSelect = document.getElementById('fantasysoccerteamsdisplay-per-page-' + leagueId);
            
            // Render teams for current page
            function fantasysoccerteamsdisplayRenderTeams() {
                // Clear container
                fantasysoccerteamsdisplayTbody.innerHTML = '';
                
                // Calculate start and end indices
                const startIndex = (fantasysoccerteamsdisplayCurrentPage - 1) * fantasysoccerteamsdisplayItemsPerPage;
                const endIndex = fantasysoccerteamsdisplayItemsPerPage === 0 ? 
                    fantasysoccerteamsdisplayTotalItems : 
                    Math.min(startIndex + fantasysoccerteamsdisplayItemsPerPage, fantasysoccerteamsdisplayTotalItems);
                
                // Update showing info
                if (fantasysoccerteamsdisplayPaginationInfo) {
                    if (fantasysoccerteamsdisplayItemsPerPage === 0 || fantasysoccerteamsdisplayTotalItems <= fantasysoccerteamsdisplayItemsPerPage) {
                        fantasysoccerteamsdisplayPaginationInfo.innerHTML = 'Showing all ' + fantasysoccerteamsdisplayTotalItems + ' teams';
                    } else {
                        fantasysoccerteamsdisplayPaginationInfo.innerHTML = 'Showing ' + (startIndex + 1) + '-' + endIndex + ' of ' + fantasysoccerteamsdisplayTotalItems + ' teams';
                    }
                }
                
                // Get teams for current page
                const teamsToShow = fantasysoccerteamsdisplayItemsPerPage === 0 ? 
                    teamsData : 
                    teamsData.slice(startIndex, endIndex);
                
                // Create team rows
                let html = '';
                teamsToShow.forEach(team => {
                    // Team row with position and name
                    html += '<tr class="team-row" data-position="' + team.position + '">' +
                            '<td><strong>' + team.position + '</strong></td>' +
                            '<td>' +
                            '<div class="team-name">' + team.user_name + '\'s Team</div>' +
                            '<div class="team-meta">' + team.budget.toFixed(0) + ' budget•' + team.prediction_points.toFixed(0) + ' points</div>' +
                            '</td>' +
                            '<td><strong>Total ' + team.total_points.toFixed(2) + '</strong></td></tr>';
                    
                    // Player details row if showing players
                    if (showPlayers) {
                        html += '<tr class="player-details"><td colspan="3"><table class="player-details-table">' + 
                                team.player_details + 
                                '</table></td></tr>';
                    }
                });
                
                // Insert the HTML
                fantasysoccerteamsdisplayTbody.innerHTML = html;
                
                // Add event listeners to team rows
                if (showPlayers) {
                    const teamRows = fantasysoccerteamsdisplayTbody.querySelectorAll('.team-row');
                    teamRows.forEach(row => {
                        row.addEventListener('click', function() {
                            this.classList.toggle('active');
                            let nextRow = this.nextElementSibling;
                            if (nextRow && nextRow.classList.contains('player-details')) {
                                if (this.classList.contains('active')) {
                                    nextRow.style.display = 'table-row';
                                } else {
                                    nextRow.style.display = 'none';
                                }
                            }
                        });
                    });
                }
                
                // Render pagination
                fantasysoccerteamsdisplayRenderPagination();
            }
            
            // Render pagination controls
            function fantasysoccerteamsdisplayRenderPagination() {
                if (!fantasysoccerteamsdisplayPagination) return;
                
                // Calculate total pages
                const totalPages = Math.ceil(fantasysoccerteamsdisplayTotalItems / fantasysoccerteamsdisplayItemsPerPage);
                
                // Don't show pagination if only one page or showing all
                if (totalPages <= 1 || fantasysoccerteamsdisplayItemsPerPage === 0) {
                    fantasysoccerteamsdisplayPagination.style.display = 'none';
                    return;
                } else {
                    fantasysoccerteamsdisplayPagination.style.display = 'flex';
                }
                
                // Create pagination HTML
                let paginationHTML = '';
                
                // Previous button
                paginationHTML += '<button class="pagination-button ' + (fantasysoccerteamsdisplayCurrentPage === 1 ? 'disabled' : '') + '" ' +
                    'onclick="if(' + fantasysoccerteamsdisplayCurrentPage + ' > 1) fantasysoccerteamsdisplayChangePage(' + leagueId + ', ' + (fantasysoccerteamsdisplayCurrentPage - 1) + ')">←</button>';
                
                // Page buttons
                const maxVisibleButtons = 5;
                let startPage = Math.max(1, fantasysoccerteamsdisplayCurrentPage - Math.floor(maxVisibleButtons / 2));
                const endPage = Math.min(totalPages, startPage + maxVisibleButtons - 1);
                
                // Adjust start page if we're near the end
                startPage = Math.max(1, endPage - maxVisibleButtons + 1);
                
                // First page button if not starting at 1
                if (startPage > 1) {
                    paginationHTML += '<button class="pagination-button" onclick="fantasysoccerteamsdisplayChangePage(' + leagueId + ', 1)">1</button>';
                    
                    // Ellipsis if needed
                    if (startPage > 2) {
                        paginationHTML += '<span style="margin: 0 4px">...</span>';
                    }
                }
                
                // Page number buttons
                for (let i = startPage; i <= endPage; i++) {
                    paginationHTML += '<button class="pagination-button ' + (fantasysoccerteamsdisplayCurrentPage === i ? 'active' : '') + '" ' +
                        'onclick="fantasysoccerteamsdisplayChangePage(' + leagueId + ', ' + i + ')">' + i + '</button>';
                }
                
                // Ellipsis and last page if not ending at the last page
                if (endPage < totalPages) {
                    // Ellipsis if needed
                    if (endPage < totalPages - 1) {
                        paginationHTML += '<span style="margin: 0 4px">...</span>';
                    }
                    
                    // Last page button
                    paginationHTML += '<button class="pagination-button" onclick="fantasysoccerteamsdisplayChangePage(' + leagueId + ', ' + totalPages + ')">' + totalPages + '</button>';
                }
                
                // Next button
                paginationHTML += '<button class="pagination-button ' + (fantasysoccerteamsdisplayCurrentPage === totalPages ? 'disabled' : '') + '" ' +
                    'onclick="if(' + fantasysoccerteamsdisplayCurrentPage + ' < ' + totalPages + ') fantasysoccerteamsdisplayChangePage(' + leagueId + ', ' + (fantasysoccerteamsdisplayCurrentPage + 1) + ')">→</button>';
                
                // Set the HTML
                fantasysoccerteamsdisplayPagination.innerHTML = paginationHTML;
            }
            
            // Expose the change page function globally
            window.fantasysoccerteamsdisplayChangePage = function(leagueId, page) {
                fantasysoccerteamsdisplayCurrentPage = page;
                fantasysoccerteamsdisplayRenderTeams();
                window.scrollTo({
                    top: document.getElementById('fantasysoccerteamsdisplay-' + leagueId).offsetTop - 20,
                    behavior: 'smooth'
                });
            };
            
            // Expose the change items per page function globally
            window.fantasysoccerteamsdisplayChangeItemsPerPage = function(leagueId) {
                const perPageSelect = document.getElementById('fantasysoccerteamsdisplay-per-page-' + leagueId);
                if (perPageSelect) {
                    fantasysoccerteamsdisplayItemsPerPage = parseInt(perPageSelect.value);
                    fantasysoccerteamsdisplayCurrentPage = 1; // Reset to first page
                    fantasysoccerteamsdisplayRenderTeams();
                }
            };
            
            // Initialize by rendering the teams
            fantasysoccerteamsdisplayRenderTeams();
        }
        </script>
        <?php
    }
    
    return ob_get_clean();
}
add_shortcode('display_teams', 'dfsoccer_display_teams_shortcode');







function dfsoccer_enqueue_countdown_timer_assets() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'countdown_timer')) {
        wp_enqueue_style(
            'dfsoccer-countdown-timer-css',
            plugin_dir_url(__FILE__) . 'css/dfsoccer-countdown-timer.css',
            array(),
            '1.0.0'
        );
        
        wp_enqueue_script(
            'dfsoccer-countdown-timer-js',
            plugin_dir_url(__FILE__) . 'js/dfsoccer-countdown-timer.js',
            array('jquery'),
            '1.0.0',
            true
        );

        // Get WordPress timezone
        $wp_timezone = wp_timezone();
        
        // Convert fixture time to WordPress timezone
        $date = new DateTime('2024-08-23 15:00:00', $wp_timezone);
        
        wp_localize_script('dfsoccer-countdown-timer-js', 'dfsoccerCountdownData', array(
            'firstFixtureDate' => $date->getTimestamp(),
            'wpTimezone' => array(
                'offset' => $wp_timezone->getOffset(new DateTime('now')),
                'name' => wp_timezone_string()
            )
        ));
    }
}
add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_countdown_timer_assets');

function dfsoccer_countdown_timer_shortcode($atts) {
    $atts = shortcode_atts(array(
        'league_id' => '0'
    ), $atts, 'countdown_timer');
    
    $league_id = intval($atts['league_id']);
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    
    if (empty($saved_fixtures)) {
        return ' ';
    }

    // Get WordPress timezone
    $wp_timezone = wp_timezone();
    
    // Convert fixture date to WordPress timezone
    $fixture_date = new DateTime($saved_fixtures[0]['fixture_date'], $wp_timezone);
    $first_fixture_date = $fixture_date->getTimestamp();
    
    // Get current time in WordPress timezone
    $current_time = current_time('timestamp');
    
    // Calculate the time remaining
    $time_remaining = $first_fixture_date - $current_time;
    
    $days = floor($time_remaining / (60 * 60 * 24));
    $hours = floor(($time_remaining % (60 * 60 * 24)) / (60 * 60));
    $minutes = floor(($time_remaining % (60 * 60)) / 60);
    $seconds = $time_remaining % 60;

    $countdown_timer_html = '<div class="dfsoccer-container">';
    $countdown_timer_html .= '<h1 id="dfsoccer-headline">Time Until First Fixture:</h1>';
    $countdown_timer_html .= '<div id="dfsoccer-countdown">';
    $countdown_timer_html .= '<ul>';
    $countdown_timer_html .= '<li><span id="dfsoccer-days">' . esc_html(sprintf('%02d', $days)) . '</span>days</li>';
    $countdown_timer_html .= '<li><span id="dfsoccer-hours">' . esc_html(sprintf('%02d', $hours)) . '</span>hours</li>';
    $countdown_timer_html .= '<li><span id="dfsoccer-minutes">' . esc_html(sprintf('%02d', $minutes)) . '</span>minutes</li>';
    $countdown_timer_html .= '<li><span id="dfsoccer-seconds">' . esc_html(sprintf('%02d', $seconds)) . '</span>seconds</li>';
    $countdown_timer_html .= '</ul>';
    $countdown_timer_html .= '</div>';
    $countdown_timer_html .= '</div>';

    wp_enqueue_style('dfsoccer-countdown-timer', plugin_dir_url(__FILE__) . 'css/dfsoccer-countdown-timer.css', array(), '1.0.0');
    wp_enqueue_script('dfsoccer-countdown-timer', plugin_dir_url(__FILE__) . 'js/dfsoccer-countdown-timer.js', array(), '1.0.0', true);

    // Pass timezone information to JavaScript
    wp_localize_script('dfsoccer-countdown-timer', 'dfsoccerCountdownData', array(
        'leagueId' => $league_id,
        'firstFixtureDate' => $first_fixture_date,
        'wpTimezone' => array(
            'offset' => $wp_timezone->getOffset(new DateTime('now')),
            'name' => wp_timezone_string()
        )
    ));

    return $countdown_timer_html;
}
add_shortcode('countdown_timer', 'dfsoccer_countdown_timer_shortcode');




function dfsoccer_append_match_results_to_league_content($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {  // Check if it's a single league post
        $league_id = get_the_ID();  // Get the current league post ID

        // Generate the shortcode with the current league ID
        $results_form_shortcode = do_shortcode('[enter_match_results league_id="' . $league_id . '"]');

        // Append the generated form to the post content
        $content .= $results_form_shortcode;
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_match_results_to_league_content');




function dfsoccer_enter_match_results_shortcode($atts) {
    // Enqueue the JavaScript and CSS files
    wp_enqueue_script('dfsoccer-match-results-js', esc_url(plugin_dir_url(__FILE__) . 'js/dfsoccer-match-results.js'), array('jquery'), '1.0.3', true);
    wp_enqueue_style('dfsoccer-match-results-css', esc_url(plugin_dir_url(__FILE__) . 'css/dfsoccer-match-results.css'), array(), '1.0.2');

    $atts = shortcode_atts([
        'league_id' => ''
    ], $atts, 'enter_match_results');

    $league_id = absint($atts['league_id']);
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;

    // Check if the current user is the author of the league post
    $league_post = get_post($league_id);
    $is_author = $league_post && $league_post->post_author == get_current_user_id();

    // Handle form submission
    if ($is_author && isset($_POST['submit_results']) && check_admin_referer('dfsoccer_submit_results', 'dfsoccer_nonce')) {
        $league_results = [];

        if (isset($_POST['player_stats']) && is_array($_POST['player_stats'])) {
            foreach ($_POST['player_stats'] as $player_id => $stats_json) {
                $player_id = absint($player_id);
                $stats = json_decode(stripslashes($stats_json), true);
                if (is_array($stats)) {
                    $league_results[$player_id] = array_map('absint', $stats);
                    
                    // Calculate total points
                    $total_points = dfsoccercalculate_total_points($player_id, $league_results[$player_id], $league_id);
                    $league_results[$player_id]['total_points'] = $total_points;
                    
                    // Update total points meta for the player
                    update_post_meta($player_id, 'total_points', $total_points);
                }
            }
        }

        // Update the league results in the database
        update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($league_results));

        // Redirect to the same page with a query parameter
        $redirect_url = add_query_arg('form_submitted', 'true', wp_get_referer());
        wp_safe_redirect(esc_url_raw($redirect_url));
        exit;
    }

    // Retrieve saved fixtures
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    if (empty($saved_fixtures)) {
        return esc_html__('No fixtures set for this league.', 'dfsoccer');
    }

    // Retrieve existing match results
    $existing_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $existing_results = $existing_results ? json_decode($existing_results, true) : [];

    $output = '';

    if ($is_author) {
        $output .= '<div id="form_container">';
        $output .= '<form method="post" action="">';
        $output .= wp_nonce_field('dfsoccer_submit_results', 'dfsoccer_nonce', true, false);

        foreach ($saved_fixtures as $fixture) {
            $home_club_id = absint($fixture['home_club_id']);
            $away_club_id = absint($fixture['away_club_id']);

            $output .= '<h3>' . esc_html__('Fixture:', 'dfsoccer') . ' ' . esc_html(get_the_title($home_club_id)) . ' vs ' . esc_html(get_the_title($away_club_id)) . '</h3>';
            $teams = ['Home Team' => $home_club_id, 'Away Team' => $away_club_id];

            foreach ($teams as $team_name => $club_id) {
                $players = get_posts([
                    'post_type' => 'dfsoccer_player',
                    'posts_per_page' => -1,
                    'meta_query' => [
                        ['key' => 'dfsoccer_club_id', 'value' => $club_id]
                    ]
                ]);

                $output .= "<div>" . esc_html($team_name) . " " . esc_html__('Players:', 'dfsoccer') . "</div>";

                foreach ($players as $player) {
                    $player_id = $player->ID;
                    $player_name = get_the_title($player_id);
                    $player_results = isset($existing_results[$player_id]) ? $existing_results[$player_id] : [];
                    $output .= '<div class="player-card" data-player-id="' . esc_attr($player_id) . '">';
                    $output .= "<p class='player-name'>" . esc_html($player_name) . " (ID: " . esc_html($player_id) . "):</p>";
                    $output .= "<input type='hidden' name='player_stats[" . esc_attr($player_id) . "]' id='player_stats_" . esc_attr($player_id) . "' value='" . esc_attr(wp_json_encode($player_results)) . "'>";
                    $output .= "<div id='player_stats_form_" . esc_attr($player_id) . "'></div>";
                    $output .= '</div>';
                }
            }
        }

        $output .= '<input type="submit" name="submit_results" value="' . esc_attr__('Submit Results', 'dfsoccer') . '">';
        $output .= '</form>';
        $output .= '</div>';
    }

    // Display submitted match results
    if ($existing_results) {
        $output .= '<div id="results_container">';
        $output .= '<h2>' . esc_html__('Player points', 'dfsoccer') . '</h2>';
        $output .= '<input type="text" id="results_search" placeholder="' . esc_attr__('Search in results...', 'dfsoccer') . '">';

        foreach ($existing_results as $player_id => $player_stats) {
            $player_name = get_the_title($player_id);
            $output .= '<div class="result-entry">';
            $output .= '<p class="result-player-name">' . esc_html__('Player:', 'dfsoccer') . ' ' . esc_html($player_name) . ' (ID: ' . esc_html($player_id) . ')</p><ul>';
            foreach ($player_stats as $stat_key => $stat_value) {
                $stat_label = esc_html(dfsoccer_get_stat_label($stat_key));
                $output .= '<li>' . $stat_label . ': ' . esc_html($stat_value) . '</li>';
            }
            $output .= '</ul></div>';
        }
        $output .= '</div>';
    }

    // Add inline JavaScript
    wp_add_inline_script('dfsoccer-match-results-js', dfsoccer_get_inline_script(), 'after');

    return $output;
}

add_shortcode('enter_match_results', 'dfsoccer_enter_match_results_shortcode');




function dfsoccer_get_stat_label($key) {
    $labels = [
        'goals' => __('Goals', 'dfsoccer'),
        'assists' => __('Assists', 'dfsoccer'),
        'own' => __('Own Goals', 'dfsoccer'),
        'penalties' => __('Penalties Saved', 'dfsoccer'),
        'missed' => __('Penalties Missed', 'dfsoccer'),
        'conceded' => __('Goals Conceded', 'dfsoccer'),
        'minutes' => __('Minutes Played', 'dfsoccer'),
        'red' => __('Red Cards Received', 'dfsoccer'),
        'yellow' => __('Yellow Cards Received', 'dfsoccer'),
        'total_points' => __('Total Points', 'dfsoccer')
    ];
    return isset($labels[$key]) ? $labels[$key] : ucfirst($key);
}

function dfsoccer_get_inline_script() {
    ob_start();
    ?>
    (function($) {
        $(document).ready(function() {
            const statFields = [
                {key: 'goals', label: '<?php echo esc_js(__('Goals', 'dfsoccer')); ?>'},
                {key: 'assists', label: '<?php echo esc_js(__('Assists', 'dfsoccer')); ?>'},
                {key: 'own', label: '<?php echo esc_js(__('Own Goals', 'dfsoccer')); ?>'},
                {key: 'penalties', label: '<?php echo esc_js(__('Penalties Saved', 'dfsoccer')); ?>'},
                {key: 'missed', label: '<?php echo esc_js(__('Penalties Missed', 'dfsoccer')); ?>'},
                {key: 'conceded', label: '<?php echo esc_js(__('Goals Conceded', 'dfsoccer')); ?>'},
                {key: 'minutes', label: '<?php echo esc_js(__('Minutes Played', 'dfsoccer')); ?>'},
                {key: 'red', label: '<?php echo esc_js(__('Red Cards Received', 'dfsoccer')); ?>'},
                {key: 'yellow', label: '<?php echo esc_js(__('Yellow Cards Received', 'dfsoccer')); ?>'}
            ];

            $('.player-card').each(function() {
                const playerId = $(this).data('player-id');
                const formContainer = $(`#player_stats_form_${playerId}`);
                const hiddenInput = $(`#player_stats_${playerId}`);
                let existingStats = {};
                
                try {
                    existingStats = JSON.parse(hiddenInput.val()) || {};
                } catch (e) {
                    console.error('Error parsing existing stats:', e);
                }

                statFields.forEach(field => {
                    const existingValue = existingStats[field.key] || 0;
                    formContainer.append(`
                        <label>${field.label}:</label>
                        <input type="number" class="stat-input" data-stat="${field.key}" min="0" value="${existingValue}"><br>
                    `);
                });

                formContainer.on('change', '.stat-input', function() {
                    const stats = {};
                    formContainer.find('.stat-input').each(function() {
                        stats[$(this).data('stat')] = parseInt($(this).val()) || 0;
                    });
                    hiddenInput.val(JSON.stringify(stats));
                });
            });

            // Player search functionality
            $('#player_search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.player-card').each(function() {
                    const playerName = $(this).find('.player-name').text().toLowerCase();
                    $(this).toggle(playerName.includes(searchTerm));
                });
            });

            // Results search functionality
            $('#results_search').on('input', function() {
                const searchTerm = $(this).val().toLowerCase();
                $('.result-entry').each(function() {
                    const resultText = $(this).text().toLowerCase();
                    $(this).toggle(resultText.includes(searchTerm));
                });
            });
        });
    })(jQuery);
    <?php
    return ob_get_clean();
}


function get_player_stat($player_id, $stat_key, $league_id, $default = 0) {
    // Retrieve the entire match result JSON for the league
    $match_results_json = get_post_meta($league_id, 'dfsoccer_match_results', true);
    if (!empty($match_results_json)) {
        $match_results = json_decode($match_results_json, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            // Check if this player's stats are available in the match results
            if (isset($match_results[$player_id]) && isset($match_results[$player_id][$stat_key])) {
                return $match_results[$player_id][$stat_key];
            }
        } else {
            error_log("JSON decode error: " . json_last_error_msg());
        }
    }
    return $default;
}

function dfsoccer_list_players_data_by_club($club_id, $meta_key, $user_id) {
    // Capture player data directly from the original function
    $selected_players = get_user_meta($user_id, $meta_key, true);
    if (!is_array($selected_players)) {
        $selected_players = [];
    }

    $args = array(
        'post_type' => 'dfsoccer_player',
        'posts_per_page' => -1,
        'meta_query' => array(
            array(
                'key' => 'dfsoccer_club_id',
                'value' => $club_id,
                'compare' => '='
            )
        )
    );

    $query = new WP_Query($args);
    $players = [];
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $player_id = get_the_ID();
            $player_name = get_the_title();
            $position = get_post_meta($player_id, 'dfsoccer_position', true);
            $price = get_post_meta($player_id, 'dfsoccer_price', true);
            $is_checked = in_array($player_id, $selected_players) ? 'checked' : '';
            
            $players[] = [
                'id' => $player_id,
                'name' => $player_name,
                'position' => $position,
                'price' => $price,
                'is_checked' => $is_checked
            ];
        }
    }
    wp_reset_postdata();

    // Return only the player data
    return $players;
}


function dfsoccer_enqueue_player_selection_styles() {
    // Check if we are on a page where the shortcode might be used
    // (You might need more specific logic here if possible)
    // For now, let's assume it could be anywhere.
    // A more robust check might involve checking post content for the shortcode before enqueueing.
    wp_enqueue_style(
        'dfsoccer-player-selection', // Handle
        plugin_dir_url(__FILE__) . 'css/player-selection.css', // Path to your CSS file
        [], // Dependencies
        '1.0.0' // Version
    );
}
add_action('wp_enqueue_scripts', 'dfsoccer_enqueue_player_selection_styles');

function dfsoccer_bare_player_selection_shortcode($atts) {
    if (!is_user_logged_in()) {
        return '<p>You need to be logged in to select players.</p>';
    }

    $atts = shortcode_atts([
        'league_id' => '0',
        'src'       => '0',
    ], $atts, 'api_players_for_fixtures');

    $league_id = filter_var($atts['league_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $source_league_id = filter_var($atts['src'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $user_id = get_current_user_id();


    $instance_id = ''; // or some appropriate default value

    // Unique ID for this shortcode instance (helps with multiple instances on a page)
    $instance_id = 'dfsoccer_sel_' . $league_id . '_' . uniqid();

    if ($league_id === false || $source_league_id === false) {
        return '<p style="color:red;">Error: Invalid league_id or src.</p>';
    }

    $selection_meta_key = 'dfsoccer_selected_players_' . $league_id;
    $players = [];
    $api_error_message = '';

    // --- API Fetching (same as before) ---
    $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $source_league_id . '/fixture-players?nocache=1&LSCWP_CTRL=NOCACHE';
    $response = wp_remote_get($api_url, ['timeout' => 20]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $api_body = wp_remote_retrieve_body($response);
        $api_data = json_decode($api_body, true);
        if (isset($api_data['players']) && is_array($api_data['players'])) {
            $players = $api_data['players'];
            // --- Sort players by price initially (descending) ---
             usort($players, function($a, $b) {
                $priceA = isset($a['price']) ? floatval($a['price']) : 0;
                $priceB = isset($b['price']) ? floatval($b['price']) : 0;
                return $priceB <=> $priceA; // Descending order
             });
             // ----------------------------------------------------
        } else {
            $api_error_message = 'Invalid API response format.';
        }
    } else {
        $api_error_message = is_wp_error($response) ? $response->get_error_message() : 'Failed to fetch players from API. Status code: ' . wp_remote_retrieve_response_code($response);
    }
    // ----------------------------------------

    // --- Get User Selections & Prepare Positions (mostly same as before) ---
    $user_selected_players = get_user_meta($user_id, $selection_meta_key, true);
    if (!is_array($user_selected_players)) {
        $user_selected_players = [];
    }

    $positions = [];
    foreach ($players as $player) {
        if (!empty($player['position'])) {
            // Use a consistent abbreviation if needed, e.g., map "Forward" to "FWD"
            $pos = strtoupper(substr(esc_attr($player['position']), 0, 3)); // Basic example
             if (strlen($pos) > 3) { // Refine based on your actual position names
                 if (stripos($player['position'], 'Forward') !== false) $pos = 'FWD';
                 elseif (stripos($player['position'], 'Midfielder') !== false) $pos = 'MID';
                 elseif (stripos($player['position'], 'Defender') !== false) $pos = 'DEF';
                 elseif (stripos($player['position'], 'Goalkeeper') !== false) $pos = 'GK';
                 // Add more specific mappings if needed
                 else $pos = 'UNK'; // Unknown or other
             } elseif (strlen($pos) == 2 && stripos($player['position'], 'Goalkeeper') !== false) {
                 $pos = 'GK'; // Handle common case like 'GK' directly
             }
             $positions[$pos] = true;
        }
    }
    $positions = array_keys($positions);
    sort($positions);
    // --------------------------------------------------------------------



    // --- Start Building Output with New Structure ---
    $output = '<div class="dfsoccer-player-selector-wrapper" id="' . esc_attr($instance_id) . '">'; // Wrapper with unique ID
    $output .= '<form method="post" class="dfsoccer-selection-form">';
    $output .= wp_nonce_field('dfsoccer_player_selection_barebones_' . $league_id, 'dfsoccer_player_nonce_barebones', true, false);
    $output .= '<input type="hidden" name="league_id" value="' . esc_attr($league_id) . '">';

    // Display API error if any
    if (!empty($api_error_message)) {
        $output .= '<p style="color:red;">API Error: ' . esc_html($api_error_message) . '</p>';
    }
	
	$output .= do_shortcode('[bezzzedfsoccervisual]'); // Process the shortcode
	


    // Player Selection Panel Structure
    $output .= '<div class="player-selection">'; // Removed width style, let CSS handle it

    // Player Search Area
    $output .= '<div class="player-search">';
    $output .= '<div class="search-header">';
$output .= '<h3 class="search-title" style="color: white;">Available Players</h3>';
	$output .= '<div class="search-input-container">';
    $output .= '<input type="text" placeholder="Search players..." class="search-input">';
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>';
	$output .= '</div>'; // search-input-container
    $output .= '</div>'; // search-header

    // Filter Buttons
    $output .= '<div class="filter-buttons">';
    $output .= '<button type="button" class="filter-btn active" data-filter="all">All</button>'; // Use type="button"
    foreach ($positions as $position) {
        $output .= '<button type="button" class="filter-btn" data-filter="' . esc_attr(strtolower($position)) . '">' . esc_html($position) . '</button>';
    }
    // Filter Icon Button (functionality needs to be added via JS if desired)
    $output .= '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>';
    $output .= '</button>';
    $output .= '</div>'; // filter-buttons
    $output .= '</div>'; // player-search

    // Player List Area
    $output .= '<div class="player-list">';
    $output .= '<div class="player-list-header">';
    $output .= '<div>PLAYER</div>';
    $output .= '<div>POS</div>';
    $output .= '<div style="text-align: right;">PRICE</div>';
    $output .= '<div style="text-align: right;">CLUB</div>';
    $output .= '</div>'; // player-list-header

    $output .= '<div class="player-list-scrollable">'; // Start scrollable area
    if (empty($players) && empty($api_error_message)) {
        $output .= '<p style="text-align: center; padding: 1rem; color: var(--color-green-300);">No players available for this fixture.</p>';
    } else {
        foreach ($players as $player) {
            $player_id = filter_var($player['id'] ?? null, FILTER_VALIDATE_INT);
            if (!$player_id) continue;

            $name = !empty($player['name']) ? esc_html($player['name']) : 'Unknown';
            $price_val = isset($player['price']) ? floatval($player['price']) : 0;
             // Adjust formatting as needed - e.g., adding 'M' if appropriate
             $price_display = '$' . number_format($price_val, 2);
             // $price_display = '$' . number_format($price_val / 1000000, 1) . 'M'; // Example for millions

             $position_raw = !empty($player['position']) ? esc_attr($player['position']) : 'UNK';
             // Map raw position to abbreviation used in filters
             $position_abbr = strtoupper(substr($position_raw, 0, 3));
             if (strlen($position_abbr) > 3) {
                 if (stripos($position_raw, 'Forward') !== false) $position_abbr = 'FWD';
                 elseif (stripos($position_raw, 'Midfielder') !== false) $position_abbr = 'MID';
                 elseif (stripos($position_raw, 'Defender') !== false) $position_abbr = 'DEF';
                 elseif (stripos($position_raw, 'Goalkeeper') !== false) $position_abbr = 'GK';
                 else $position_abbr = 'UNK';
             } elseif (strlen($position_abbr) == 2 && stripos($position_raw, 'Goalkeeper') !== false) {
                 $position_abbr = 'GK';
             }


            $club = !empty($player['club_name']) ? esc_html($player['club_name']) : 'N/A';
$checked = in_array($player_id, $user_selected_players) ? ' checked' : '';
$selected_class = $checked ? ' selected' : ''; // Class for visual selection

            $output .= '<div class="player-item' . $selected_class . '" data-player-id="' . esc_attr($player_id) . '" data-position="' . esc_attr(strtolower($position_abbr)) . '" data-club="' . esc_attr(strtolower($club)) . '" data-price="' . esc_attr($price_val) . '" data-name="' . esc_attr(strtolower($name)) . '">';
            // Hidden Checkbox for form submission
            $output .= '<input type="checkbox" name="selected_players_barebones[]" value="' . esc_attr($player_id) . '" id="player_bare_' . esc_attr($player_id) . '_' . $instance_id . '" style="display:none;"' . $checked . '>';

            // Visible Player Info
$output .= '<div class="player-name" style="color: white;">' . esc_html($name) . '</div>';
            $output .= '<div class="player-position-cell">' . esc_html($position_abbr) . '</div>';
            $output .= '<div class="player-price">' . esc_html($price_display) . '</div>';
            $output .= '<div class="player-club">' . $club . '</div>'; // Use player-club class
            $output .= '</div>'; // player-item
        }
        // Load More button (placeholder, JS will handle it)
        $output .= '<div class="load-more" style="display: none;">'; // Initially hidden if pagination applies
        $output .= '<button type="button" class="load-more-btn">Load More Players</button>';
        $output .= '</div>';
    }
    $output .= '</div>'; // player-list-scrollable
    $output .= '</div>'; // player-list

    // Submit Button (consider styling or moving this)
    $output .= '<p style="text-align: center; margin-top: 1rem;"><input type="submit" name="submit_players_barebones" value="Save Selection" class="button button-primary"></p>'; // Added standard WP button classes

    $output .= '</div>'; // player-selection
    $output .= '</form>';
    $output .= '</div>'; // dfsoccer-player-selector-wrapper


    // --- JavaScript for Interactivity ---
    // Ideally, move this to a separate .js file and enqueue it.
    ob_start(); // Start output buffering to capture JS
    ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('<?php echo esc_js($instance_id); ?>');
    if (!wrapper) return; // Exit if the specific instance isn't found

    const searchInput = wrapper.querySelector('.search-input');
    const filterButtons = wrapper.querySelectorAll('.filter-buttons .filter-btn:not(.filter-more-btn)'); // Exclude the 'more' button for now
    const playerListScrollable = wrapper.querySelector('.player-list-scrollable');
    const allPlayerItems = Array.from(playerListScrollable.querySelectorAll('.player-item')); // Convert NodeList to Array
    const loadMoreContainer = wrapper.querySelector('.load-more');
    const loadMoreBtn = wrapper.querySelector('.load-more-btn');
    const form = wrapper.querySelector('.dfsoccer-selection-form');

    // Position-based selection limits
    const POSITION_LIMITS = {
        'gk': { min: 1, max: 1, name: "Goalkeeper" },
        'def': { min: 4, max: 4, name: "Defender" },
        'mid': { min: 4, max: 4, name: "Midfielder" },
        'fwd': { min: 2, max: 2, name: "Forward" }
    };

    // Track the current selection count per position
    let positionCounts = {
        'gk': 0,
        'def': 0,
        'mid': 0,
        'fwd': 0
    };

    // Calculate total max selections from position limits
    const MAX_SELECTIONS = Object.values(POSITION_LIMITS).reduce(
        (sum, limit) => sum + limit.max, 0
    ); // Should be 11

    const PLAYERS_PER_PAGE = 15; // How many players to show initially / load more

    let currentFilters = {
        search: '',
        position: 'all'
        // Add price, club here if you implement more filters
    };
    let visiblePlayerCount = 0;

    // Helper function to normalize positions consistently
    function getNormalizedPosition(rawPosition) {
        if (!rawPosition) return 'unknown';
        
        const position = rawPosition.toLowerCase();
        
        // Special handling for goalkeepers - check for any variation
        if (position === 'gk' || 
            position === 'goalkeeper' || 
            position === 'goa' || 
            position === 'keeper' ||
            position.includes('goal') || 
            position.includes('keep')) {
            return 'gk';
        } 
        // Handle other positions
        else if (position.includes('def') || position === 'defender') {
            return 'def';
        } else if (position.includes('mid') || position === 'midfielder') {
            return 'mid';
        } else if (position.includes('forw') || position.includes('fwd') || 
                  position.includes('att') || position === 'striker') {
            return 'fwd';
        }
        
        return position; // Return original if no match
    }

    // --- Filtering Logic ---
    function applyFilters() {
        let filteredPlayers = allPlayerItems; // Start with all players

        // 1. Apply Search Filter
        const searchTerm = currentFilters.search.toLowerCase();
        if (searchTerm) {
            filteredPlayers = filteredPlayers.filter(item => {
                const name = item.dataset.name || '';
                return name.includes(searchTerm);
            });
        }

        // 2. Apply Position Filter
        const positionFilter = currentFilters.position.toLowerCase();
        if (positionFilter !== 'all') {
            filteredPlayers = filteredPlayers.filter(item => {
                const pos = item.dataset.position || '';
                return pos === positionFilter;
            });
        }

        // --- Reset display and pagination ---
        allPlayerItems.forEach(item => item.style.display = 'none'); // Hide all initially
        visiblePlayerCount = 0;

        // --- Apply Pagination/Load More ---
        showNextPlayers(filteredPlayers); // Show the first page/batch

        // Show/hide load more button
        if ([loadMoreContainer, loadMoreBtn].every(Boolean)) {
            if (visiblePlayerCount < filteredPlayers.length) {
                loadMoreContainer.style.display = 'block';
            } else {
                loadMoreContainer.style.display = 'none';
            }
            // Update button text (optional)
            loadMoreBtn.textContent = `Load More (${filteredPlayers.length - visiblePlayerCount} remaining)`;
        }
    }

    function showNextPlayers(playerArray) {
        const currentlyVisible = visiblePlayerCount;
        const showUntil = Math.min(currentlyVisible + PLAYERS_PER_PAGE, playerArray.length);

        for (let i = currentlyVisible; i < showUntil; i++) {
            if (playerArray[i]) {
                playerArray[i].style.display = 'grid'; // Use grid display as per CSS
                visiblePlayerCount++;
            }
        }
    }

    // Function to count selected players by position
    function countPositionSelections() {
        // Reset counts
        for (const position in positionCounts) {
            positionCounts[position] = 0;
        }
        
        // Count selections for each position
        const selectedCheckboxes = wrapper.querySelectorAll('.player-item input[type="checkbox"]:checked');
        console.log("Counting positions for", selectedCheckboxes.length, "selected players");
        
        selectedCheckboxes.forEach(checkbox => {
            const playerItem = checkbox.closest('.player-item');
            if (playerItem) {
                // Use normalized position if available
                let position = playerItem.dataset.normalizedPosition;
                
                if (!position) {
                    const originalPosition = playerItem.dataset.position;
                    console.log("  Raw position value:", originalPosition);
                    
                    position = getNormalizedPosition(originalPosition);
                    
                    // Store it for future reference
                    playerItem.dataset.normalizedPosition = position;
                }
                
                if (positionCounts.hasOwnProperty(position)) {
                    positionCounts[position]++;
                    console.log(`  Counted position: ${position}`);
                } else {
                    console.warn(`  Unrecognized position: ${position}`);
                }
            }
        });
        
        console.log("Final position counts:", positionCounts);
        return positionCounts;
    }

    // --- Event Listener for Search ---
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentFilters.search = this.value;
            applyFilters();
        });
    }

    // --- Event Listener for Position Filter Buttons ---
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active class
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            // Update filter state and apply
            currentFilters.position = this.dataset.filter || 'all';
            applyFilters();
        });
    });

    console.log("Setting up player selection with position limits:", POSITION_LIMITS);

    // Player click event listener with improved position handling
    playerListScrollable.addEventListener('click', function(e) {
        const playerItem = e.target.closest('.player-item');
        if ([playerItem, !playerItem.classList.contains('disabled-selection')].every(Boolean)) {
            const checkbox = playerItem.querySelector('input[type="checkbox"]');
            if ([checkbox, !checkbox.disabled].every(Boolean)) {
                // Get original position and normalize it
                const originalPosition = playerItem.dataset.position;
                console.log("Raw position value:", originalPosition);
                
                const playerPosition = getNormalizedPosition(originalPosition);
                console.log("Final mapped position:", playerPosition);
                
                // Store normalized position on the element
                playerItem.dataset.normalizedPosition = playerPosition;
                
                // Pre-selection Check (only if trying to select)
                if (!checkbox.checked) {
                    // Get current counts
                    countPositionSelections();
                    
                    // Check position limit
                    if (positionCounts.hasOwnProperty(playerPosition)) {
                        if (positionCounts[playerPosition] >= POSITION_LIMITS[playerPosition].max) {
                            alert(`You can only select ${POSITION_LIMITS[playerPosition].max} ${POSITION_LIMITS[playerPosition].name}(s).`);
                            return;
                        }
                    } else {
                        console.warn("Position not recognized:", playerPosition);
                    }
                    
                    // Check total selection limit
                    const currentSelectionCount = wrapper.querySelectorAll('.player-item input[type="checkbox"]:checked').length;
                    if (currentSelectionCount >= MAX_SELECTIONS) {
                        alert(`You can select a maximum of ${MAX_SELECTIONS} players.`);
                        return;
                    }
                }

                // Toggle checkbox state
                checkbox.checked = !checkbox.checked;
                
                // Toggle visual class
                playerItem.classList.toggle('selected', checkbox.checked);
                
                // Trigger change event with bubbling
                const event = new Event('change', { bubbles: true });
                checkbox.dispatchEvent(event);
            }
        }
    });

    // Function to update selection availability based on position counts
    function updateSelectionAvailability() {
        // Handle all player items
        const playerItems = wrapper.querySelectorAll('.player-item');
        const totalChecked = wrapper.querySelectorAll('.player-item input[type="checkbox"]:checked').length;
        
        playerItems.forEach(item => {
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (!checkbox) return;
            
            // Get normalized position
            let position = item.dataset.normalizedPosition;
            if (!position) {
                position = getNormalizedPosition(item.dataset.position);
                item.dataset.normalizedPosition = position;
            }
            
            // Disable if:
            // 1. It's not checked AND
            // 2. Either we've hit the total limit OR the position limit for this position
            const disableItem = [
    !checkbox.checked,
    [
        totalChecked >= MAX_SELECTIONS,
        position ? positionCounts[position] >= POSITION_LIMITS[position].max : false
    ].some(Boolean)
].every(Boolean);
            
            // Apply disabled state and styling
            checkbox.disabled = disableItem;
            item.classList.toggle('disabled-selection', disableItem);
            
            // Add visual indicators for position limits
            if ([position, POSITION_LIMITS[position]].every(Boolean)) {
    const current = positionCounts[position];
    const max = POSITION_LIMITS[position].max;
                
                // Add a data attribute for easier styling
                item.setAttribute('data-position-status', 
                    current >= max ? 'full' : 
                    current < max ? 'available' : 'unknown');
            }
        });
    }

    // Function to update position summary based on position counts
    function updatePositionSummary() {
        // First ensure the summary element exists, or create it
        let summaryEl = wrapper.querySelector('.position-selection-summary');
        if (!summaryEl) {
            summaryEl = document.createElement('div');
            summaryEl.className = 'position-selection-summary';
            // Insert it after the search area
            const searchArea = wrapper.querySelector('.player-list');
            if (searchArea) {
                searchArea.parentNode.insertBefore(summaryEl, searchArea.nextSibling);
            }
        }
        
        // Build the summary content using the global position counts
        let summaryHTML = '<div class="selection-counts">';
        let allPositionRequirementsMet = true;
        
        for (const position in POSITION_LIMITS) {
            const limit = POSITION_LIMITS[position];
            const count = positionCounts[position] || 0;
            
            // Determine status class
            let statusClass = 'in-progress';
            if (count < limit.min) {
                statusClass = 'insufficient';
                allPositionRequirementsMet = false;
            } else if (count === limit.max) {
                statusClass = 'complete';
            } else if (count > limit.max) {
                statusClass = 'insufficient';
                allPositionRequirementsMet = false;
            }
            
            summaryHTML += `
                <div class="position-count ${statusClass}">
                    <span class="position-label">${limit.name}s:</span>
                    <span class="count-value">${count}/${limit.max}</span>
                </div>
            `;
        }
        summaryHTML += '</div>';
        
        // Add an overall status message
        const totalSelected = Object.values(positionCounts).reduce((sum, count) => sum + count, 0);
        const isComplete = [totalSelected === MAX_SELECTIONS, allPositionRequirementsMet].every(Boolean);
        
        summaryHTML += `
            <div class="team-status ${isComplete ? 'team-complete' : 'team-incomplete'}">
                ${isComplete ? 'Team selection complete!' : 'Team selection incomplete'}
            </div>
        `;
        
        summaryEl.innerHTML = summaryHTML;
    }

    // --- Event Listener for Load More Button ---
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', function() {
            // Re-evaluate filtered players based on current filters *at the moment of click*
             let filteredPlayers = allPlayerItems;
             const searchTerm = currentFilters.search.toLowerCase();
             if (searchTerm) {
                 filteredPlayers = filteredPlayers.filter(item => (item.dataset.name || '').includes(searchTerm));
             }
             const positionFilter = currentFilters.position.toLowerCase();
             if (positionFilter !== 'all') {
                filteredPlayers = filteredPlayers.filter(item => (item.dataset.position || '') === positionFilter);
             }

             // Show the next batch from the *currently filtered* list
             showNextPlayers(filteredPlayers);

             // Update button visibility/text
             if (visiblePlayerCount >= filteredPlayers.length) {
                 loadMoreContainer.style.display = 'none';
             } else {
                 loadMoreBtn.textContent = `Load More (${filteredPlayers.length - visiblePlayerCount} remaining)`;
             }
        });
    }

 const style = document.createElement('style');
    style.textContent = `
        /* Define root variables (include necessary ones) */
        :root {
            --color-green-950: #052e16; /* Deepest Green */
            --color-green-900: #14532d; /* Primary Background */
            --color-green-800: #166534; /* Slightly Lighter / Interactive Elements */
            --color-green-700: #15803d; /* Brighter Interactive / Selected */
            --color-green-600: #16a34a; /* Success Accent */
            --color-green-500: #22c55e; /* Brighter Success */
            --color-green-400: #4ade80; /* Lighter Accent */
            --color-green-300: #86efac; /* Subtle Text / Accent */
            --color-green-200: #bbf7d0; /* Very Light Text */
            --color-white: #ffffff;     /* Primary Text */

            /* Status Colors */
            --color-red-600: #dc2626;   /* Error/Insufficient */
            --color-amber-500: #f59e0b; /* Warning/Incomplete */
            --color-amber-600: #d97706; /* Darker Warning */
        }

        .position-selection-summary {
            /* Use primary dark green background */
            background-color: var(--color-green-900);
            color: var(--color-white); /* Ensure default text is white */
            margin: 0.625rem 0; /* Converted px to rem */
            padding: 0.625rem;  /* Converted px to rem */
            border-radius: 0.5rem;
            border: 1px solid var(--color-green-800); /* Subtle border */
        }

        .selection-counts {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem; /* Converted px to rem */
            margin-bottom: 0.625rem; /* Converted px to rem */
        }

        .position-count {
            /* Use a slightly lighter green for default tags */
            background-color: var(--color-green-800);
            color: var(--color-green-200); /* Light green text for contrast */
            padding: 0.25rem 0.5rem; /* Converted px to rem */
            border-radius: 0.25rem; /* Converted px to rem */
            font-size: 0.875em; /* Adjusted font size slightly */
            font-weight: 500;
        }

        .position-count.complete {
            /* Use a distinct success green */
            background-color: var(--color-green-600);
            color: var(--color-white); /* White text for high contrast */
        }

        .position-count.insufficient {
            /* Use a distinct error red */
            background-color: var(--color-red-600);
            color: var(--color-white); /* White text for high contrast */
        }

        .team-status {
            text-align: center;
            padding: 0.5rem; /* Increased padding slightly */
            border-radius: 0.25rem; /* Converted px to rem */
            font-weight: bold;
            color: var(--color-white); /* Default white text */
            margin-top: 0.5rem; /* Add some margin */
            border: 1px solid rgba(255, 255, 255, 0.1); /* Subtle border */
        }

        .team-complete {
             /* Use a brighter success green for main status */
            background-color: var(--color-green-500);
             /* Text color already white via .team-status */
             border-color: var(--color-green-400); /* Brighter border */
        }

        .team-incomplete {
            /* Use a warning amber/yellow */
            background-color: var(--color-amber-600); /* Darker amber for contrast */
             /* Text color already white via .team-status - check readability */
             /* Alternatively use dark text: color: var(--color-green-950); */
             border-color: var(--color-amber-500); /* Lighter amber border */
        }

        .disabled-selection {
            /* Standard disabled style, no color theme change needed */
            opacity: 0.5;
            cursor: not-allowed;
        }
    `;
    document.head.appendChild(style);

    // Central change event listener for UI updates
    wrapper.addEventListener('change', function(e) {
        if (e.target.matches('.player-item input[type="checkbox"]')) {
            console.log('Change event triggered for checkbox');
            
            // Sequential updates
            countPositionSelections();      // 1. Update counts
            updateSelectionAvailability();  // 2. Update disabled states
            updatePositionSummary();        // 3. Update summary display
        }
    });

    // Add MutationObserver to catch any checkbox changes that might not trigger events
    const checkboxObserver = new MutationObserver(function(mutations) {
        let needsUpdate = false;
        
        mutations.forEach(mutation => {
    if ([
        mutation.type === 'attributes',
        mutation.attributeName === 'checked',
        mutation.target.matches('input[type="checkbox"]')
    ].every(Boolean)) {
        needsUpdate = true;
    }
});
        
        if (needsUpdate) {
            countPositionSelections();
            updateSelectionAvailability();
            updatePositionSummary();
            console.log("Checkbox mutation detected, updating position summary");
        }
    });

    // Start observing all checkboxes
    wrapper.querySelectorAll('.player-item input[type="checkbox"]').forEach(checkbox => {
        checkboxObserver.observe(checkbox, { attributes: true });
    });

    // --- Initial Setup ---
    applyFilters(); // Apply filters and show initial players on load
    countPositionSelections(); // Initialize position counts
    updateSelectionAvailability(); // Set initial disabled states
    updatePositionSummary(); // Show initial summary

    // --- Optional: Prevent form submission via Enter key in search input ---
    if (searchInput) {
        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault(); // Prevent form submission
                return false;
            }
        });
    }

    // Modify the form submission to validate position requirements
if (form) {
    form.addEventListener('submit', function(e) {
        // Check budget value
        const statValueElement = document.querySelector('.stat-value.budget-error');
        if (statValueElement) {
            // Get the text content and remove the $ and any commas
            const valueText = statValueElement.textContent.replace('$', '').replace(/,/g, '');
            
            // Convert to a number
            const budgetValue = parseFloat(valueText);
            
            // If budget is negative, prevent form submission
            if (budgetValue < 0) {
                e.preventDefault();
                alert('Cannot submit: You are over budget!');
                return false;
            }
        }
        
        // Always count directly from the DOM for form submission
        countPositionSelections();
        
        // Check if all position requirements are met
        let missingSections = [];
        for (const position in POSITION_LIMITS) {
            const limit = POSITION_LIMITS[position];
            const count = positionCounts[position];
            
            if (count < limit.min) {
                missingSections.push(`${limit.name}s (need ${limit.min}, have ${count})`);
            }
        }
        
        if (missingSections.length > 0) {
            e.preventDefault();
            alert(`Team selection incomplete. Missing: ${missingSections.join(', ')}`);
            return false;
        }

        });
    }
});


function dfsoccer_fant_log_selections() {
    // Function to log selected players
    function logSelectedPlayersFromWrapper(wrapper) {
        console.log('Player selections for', wrapper.id);
        const selectedCheckboxes = wrapper.querySelectorAll('.player-item input[type="checkbox"]:checked');
        const selectedPlayers = [];
        
        // Track positions for logging
        const positionCounts = {};
        
        selectedCheckboxes.forEach(checkbox => {
            const playerItem = checkbox.closest('.player-item');
            if (playerItem) {
                const playerId = playerItem.dataset.playerId;
                const playerName = playerItem.dataset.name;
                const playerPrice = playerItem.dataset.price;
                const position = playerItem.dataset.position;
                
                // Count positions
                positionCounts[position] = (positionCounts[position] || 0) + 1;
                
                selectedPlayers.push({
                    id: playerId,
                    name: playerName,
                    price: playerPrice,
                    position: position
                });
                
                console.log(`Selected Player: ID=${playerId}, Name=${playerName}, Position=${position}, Price=$${playerPrice}`);
            }
        });
        
        if (selectedPlayers.length > 0) {
            console.log('All selected players:', selectedPlayers);
            console.log('Position counts:', positionCounts);
            console.log('Total price:', selectedPlayers.reduce((sum, player) => sum + parseFloat(player.price), 0));
        } else {
            console.log('No players selected');
        }
    }

    // Initialize and log already selected players when page loads
    document.addEventListener('DOMContentLoaded', function() {
        const wrappers = document.querySelectorAll('[id^="dfsoccer_sel_"]');
        console.log('Initial player selections:');
        wrappers.forEach(wrapper => {
            logSelectedPlayersFromWrapper(wrapper);
        });
    });
    
    // Add click listener to log selections when players are clicked
    document.addEventListener('click', function(e) {
        if (e.target.closest('.player-item') || e.target.matches('input[type="checkbox"]')) {
            // Wait a small amount of time to ensure checkbox state has been updated
            setTimeout(function() {
                // Get all wrappers on the page (in case you have multiple instances)
                const wrappers = document.querySelectorAll('[id^="dfsoccer_sel_"]');
                
                wrappers.forEach(wrapper => {
                    logSelectedPlayersFromWrapper(wrapper);
                });
            }, 100);
        }
    });
}

// Call this function once to set everything up
dfsoccer_fant_log_selections();
</script>
    <?php
    $output .= ob_get_clean(); // Append the captured JS to the output

    return $output;
}
add_shortcode('api_players_for_fixtures', 'dfsoccer_bare_player_selection_shortcode');

function bezzzedfsoccervisual_shortcode($atts) {
    // Parse attributes with default values
    $atts = shortcode_atts(array(
        'league_id' => null,
        'default_budget' => 700.00,
        'max_players' => 15
    ), $atts, 'bezzzedfsoccervisual');

    // Get the current post ID if no league_id is provided
    $league_id = $atts['league_id'] ? intval($atts['league_id']) : get_the_ID();
    
    // Check for budget updates via transient
    $update_time = get_transient('dfsoccer_budget_updated_' . $league_id);
    if ($update_time) {
        // Force fresh data if recently updated
        wp_cache_delete($league_id, 'post_meta');
    }
    
    // Try to get the league budget from post meta
    $stored_budget = get_post_meta($league_id, 'dfsoccer_league_budget', true);
    
    // Check if budget exists and is valid
    if (empty($stored_budget) || !is_numeric($stored_budget) || floatval($stored_budget) <= 0) {
        // If no valid budget is set, use the default but don't save it
        $budget = floatval($atts['default_budget']);
        error_log("Using default budget of $budget for league ID $league_id");
    } else {
        // Use the stored budget value
        $budget = floatval($stored_budget);
        error_log("Using stored budget of $budget for league ID $league_id");
    }

    // Verify post type or custom league post type
    $post_type = get_post_type($league_id);
    
    // Optional: Add extra validation for specific post types
    $allowed_post_types = apply_filters('bezzzedfsoccervisual_allowed_types', array('league', 'post', 'page'));
    if (!in_array($post_type, $allowed_post_types)) {
        error_log("Invalid post type for league visualization: $post_type");
        $budget = $atts['default_budget'];
    }

    // Start output buffering
    ob_start();
    ?>
    <div class="bezzzedfsoccervisual-container" 
         data-league="<?php echo esc_attr($league_id); ?>"
         data-league-budget="<?php echo number_format($budget, 2, '.', ''); ?>"
         data-max-players="<?php echo intval($atts['max_players']); ?>">
        
        <h3>Selected Team Formation</h3>
        
        <div id="bezzzedfsoccervisual-output">
            <p>Loading formation...</p>
        </div>
        
        <div id="bezzzedfsoccervisual-total" class="visual-total-price">
            <!-- Budget and price details will be loaded here -->
        </div>
    </div>
   <style>
   /* Overall Container */
.bezzzedfsoccervisual-container {
    margin: 15px auto;
    padding: 15px;
    border: 1px solid var(--color-green-800, #166534);
    border-radius: 8px;
    background-color: var(--color-green-950, #052e16);
    width: 100%;
    max-width: 700px;
    box-sizing: border-box;
    box-shadow: 0 2px 10px rgba(0,0,0,0.2);
    color: var(--color-white, #ffffff);
}

.bezzzedfsoccervisual-container h3 {
    text-align: center;
    margin-top: 0;
    margin-bottom: 15px;
    color: var(--color-green-300, #86efac);
    font-size: 1.2em;
    font-weight: bold;
}

/* Soccer Field Styling - Responsive Height */
.soccer-field {
    position: relative;
    width: 100%;
    height: 400px; /* Reduced from 500px */
    background: linear-gradient(to bottom, var(--color-green-600, #16a34a), var(--color-green-500, #22c55e));
    border: 2px solid var(--color-green-700, #15803d);
    box-sizing: border-box;
    overflow: hidden;
    background-image:
        /* Center Circle */
        radial-gradient(circle at center, transparent 49px, rgba(255,255,255,0.3) 49px, rgba(255,255,255,0.3) 50px, transparent 50px),
        /* Center Line */
        linear-gradient(to right, transparent calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% + 0.5px), transparent calc(50% + 0.5px));
    background-size:
        100px 100px,
        100% 1px;
    background-position: center center, center center;
    background-repeat: no-repeat;
    border-radius: 8px;
}

/* Simplified Penalty Area Lines */
.soccer-field::before,
.soccer-field::after {
    content: '';
    position: absolute;
    border: 1px solid rgba(255,255,255,0.3);
    width: 40%;
    height: 25%;
    left: 30%;
    box-sizing: border-box;
}
.soccer-field::before { top: 0; border-top: none; border-radius: 0 0 5px 5px; }
.soccer-field::after { bottom: 0; border-bottom: none; border-radius: 5px 5px 0 0; }

/* Grid for Player Positions */
.player-positions {
    position: absolute;
    top: 0; left: 0;
    width: 100%; height: 100%;
    display: grid;
    grid-template-rows: repeat(4, 1fr);
    grid-template-columns: repeat(5, 1fr);
    padding: 10px; /* Reduced padding */
    box-sizing: border-box;
    gap: 5px; /* Reduced gap */
}

/* Container for each player spot in the grid */
.player-spot {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    height: 100%;
    min-height: 60px; /* Reduced from 80px */
    position: relative;
    transition: transform 0.2s;
}

.player-spot:hover {
    transform: scale(1.03); /* Reduced scale effect */
}

/* The visual jersey/polygon shape - Smaller for mobile */
.player-jersey-shape {
    width: 40px; /* Reduced from 55px */
    height: 45px; /* Reduced from 65px */
    clip-path: polygon(16% 20%, 35% 13%, 40% 20%, 60% 20%, 65% 13%, 86% 20%, 100% 31%, 85% 44%, 80% 40%, 80% 100%, 20% 100%, 20% 40%, 15% 44%, 0 31%);
    background-color: var(--color-yellow-500, #eab308);
    margin-bottom: 3px; /* Reduced margin */
    border: 1px solid var(--color-yellow-400, #facc15);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    flex-shrink: 0;
    transition: background-color 0.3s;
}

.player-spot:hover .player-jersey-shape {
    background-color: var(--color-yellow-400, #facc15);
}

/* Container for the text below the shape */
.player-details {
    max-width: 100%;
    padding: 2px 3px;
    background-color: rgba(22, 101, 52, 0.7);
    border-radius: 4px;
}

/* Player Name Styling */
.player-spot-name {
    font-size: 9px; /* Reduced from 11px */
    font-weight: bold;
    color: var(--color-white, #ffffff);
    line-height: 1.2;
    display: block;
    overflow-wrap: break-word;
    word-wrap: break-word;
    max-width: 100%; /* Ensure text doesn't overflow */
}

/* Player Price Styling */
.player-spot-price {
    font-size: 8px; /* Reduced from 10px */
    color: var(--color-green-300, #86efac);
    display: block;
    font-weight: 500;
}

/* Grid Positioning Classes - Keep the same */
.player-pos-gk { grid-area: 4 / 3 / 5 / 4; }
.player-pos-d1 { grid-area: 3 / 1 / 4 / 2; }
.player-pos-d2 { grid-area: 3 / 2 / 4 / 3; }
.player-pos-d3 { grid-area: 3 / 3 / 4 / 5; }
.player-pos-d4 { grid-area: 3 / 4 / 4 / 6; }
.player-pos-m1 { grid-area: 2 / 1 / 3 / 2; }
.player-pos-m2 { grid-area: 2 / 2 / 3 / 3; }
.player-pos-m3 { grid-area: 2 / 3 / 3 / 4; }
.player-pos-m4 { grid-area: 2 / 4 / 3 / 5; }
.player-pos-m5 { grid-area: 2 / 5 / 3 / 6; }
.player-pos-f1 { grid-area: 1 / 2 / 2 / 3; }
.player-pos-f2 { grid-area: 1 / 3 / 2 / 4; }
.player-pos-f3 { grid-area: 1 / 4 / 2 / 5; }
.player-pos-other { /* Fallback */ }

/* Total Price Styling */
.visual-total-price {
    margin-top: 15px; /* Reduced from 20px */
    font-weight: bold;
    font-size: 1em; /* Reduced from 1.1em */
    text-align: right;
    padding: 8px 10px; /* Reduced padding */
    border-top: 1px solid var(--color-green-800, #166534);
    color: var(--color-green-300, #86efac);
}

/* Loading/Empty State */
#bezzzedfsoccervisual-output p {
    text-align: center;
    color: var(--color-green-300, #86efac);
    padding: 15px; /* Reduced from 20px */
}

/* Optional: Highlight negative budget */
.budget-error {
    color: #ff4136;
    font-weight: bold;
}

/* Team Stats Section */
.team-stats {
    margin-top: 1rem; /* Reduced from 1.5rem */
    background-color: rgba(20, 83, 45, 0.6);
    padding: 0.75rem; /* Reduced from 1rem */
    border-radius: 0.5rem;
}

.team-stats-title {
    font-weight: bold;
    font-size: 1rem; /* Reduced from 1.125rem */
    margin-bottom: 0.5rem; /* Reduced from 0.75rem */
}

/* Stats Grid - Mobile-Responsive */
.stats-grid {
    display: grid;
    grid-template-columns: 1fr; /* Change to single column on mobile */
    gap: 0.5rem; /* Reduced from 1rem */
}

.stat-card {
    background-color: rgba(22, 101, 52, 0.8);
    padding: 0.5rem; /* Reduced from 0.75rem */
    border-radius: 0.5rem;
    text-align: center;
}

/* Budget update highlight effect */
.bezzzedfsoccervisual-container.budget-updated {
    transition: background-color 0.5s ease;
    background-color: rgba(30, 130, 76, 0.7);
}

/* Animation for budget update */
@keyframes budgetUpdated {
    0% { background-color: rgba(22, 101, 52, 0.8); }
    50% { background-color: rgba(56, 189, 107, 0.9); }
    100% { background-color: rgba(22, 101, 52, 0.8); }
}

.budget-updated .stat-card:first-child {
    animation: budgetUpdated 2s ease;
}

/* Media Queries for Different Screen Sizes */
@media (min-width: 768px) {
    /* Tablet and larger */
    .soccer-field {
        height: 450px; /* Slightly larger field */
    }
    
    .player-jersey-shape {
        width: 48px;
        height: 55px;
    }
    
    .player-spot-name {
        font-size: 10px;
    }
    
    .player-spot-price {
        font-size: 9px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr); /* Two columns for tablets */
    }
    
    .player-positions {
        padding: 12px;
        gap: 8px;
    }
}

@media (min-width: 1024px) {
    /* Desktop */
    .soccer-field {
        height: 500px; /* Original height */
    }
    
    .player-jersey-shape {
        width: 55px; /* Original size */
        height: 65px;
    }
    
    .player-spot-name {
        font-size: 11px; /* Original size */
    }
    
    .player-spot-price {
        font-size: 10px; /* Original size */
    }
    
    .stats-grid {
        grid-template-columns: repeat(3, 1fr); /* Three columns for desktop */
    }
    
    .player-positions {
        padding: 15px; /* Original padding */
        gap: 10px; /* Original gap */
    }
}

@media (max-width: 360px) {
    /* Extra small phones */
    .soccer-field {
        height: 350px; /* Even smaller field */
    }
    
    .player-jersey-shape {
        width: 32px;
        height: 38px;
    }
    
    .player-spot-name {
        font-size: 8px;
    }
    
    .player-spot-price {
        font-size: 7px;
    }
    
    .bezzzedfsoccervisual-container {
        padding: 10px;
    }
    
    .player-positions {
        padding: 8px;
        gap: 3px;
    }
}
</style>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.bezzzedfsoccervisual-container');
        const outputElement = document.getElementById('bezzzedfsoccervisual-output');
        const totalElement = document.getElementById('bezzzedfsoccervisual-total');
        const leagueId = parseInt(container.getAttribute('data-league') || '0');

        // Get league budget and max players from container data attributes
        let leagueBudget = parseFloat(container.getAttribute('data-league-budget') || '0');
        const maxPlayers = parseInt(container.getAttribute('data-max-players') || '15');

        // Updated position mapping based on the actual data attributes
        const positionClassMap = {
            'goa': ['player-pos-gk'],
            'def': ['player-pos-d1', 'player-pos-d2', 'player-pos-d3', 'player-pos-d4'],
            'mid': ['player-pos-m1', 'player-pos-m2', 'player-pos-m4', 'player-pos-m5', 'player-pos-m3'],
            'att': ['player-pos-f1', 'player-pos-f3', 'player-pos-m3']
        };

        // Listen for budget updates
        document.addEventListener('dfsoccer_budget_updated', function(event) {
            // Check if the event has data and if it's for our league
            if (event.detail && event.detail.leagueId == leagueId) {
                // Update the budget
                leagueBudget = parseFloat(event.detail.budget);
                
                // Update data attribute
                container.setAttribute('data-league-budget', leagueBudget.toFixed(2));
                
                // Add highlight class
                container.classList.add('budget-updated');
                
                // Remove the class after animation
                setTimeout(function() {
                    container.classList.remove('budget-updated');
                }, 2000);
                
                // Update the display
                updateVisualDisplay();
                
                console.log('Budget updated to: $' + leagueBudget.toFixed(2));
            }
        });

        function updateVisualDisplay() {
            const wrappers = document.querySelectorAll('[id^="dfsoccer_sel_"]');
            const selectedPlayers = [];
            const usedPositions = {
                'goa': [],
                'def': [],
                'mid': [],
                'att': []
            };

            // Collect selected players
            wrappers.forEach(wrapper => {
                const selectedCheckboxes = wrapper.querySelectorAll('.player-item input[type="checkbox"]:checked');
                selectedCheckboxes.forEach(checkbox => {
                    const playerItem = checkbox.closest('.player-item');
                    if (playerItem && playerItem.dataset.playerId && playerItem.dataset.name && playerItem.dataset.price && playerItem.dataset.position) {
                        selectedPlayers.push({
                            id: playerItem.dataset.playerId,
                            name: playerItem.dataset.name,
                            price: playerItem.dataset.price,
                            position: playerItem.dataset.position.toLowerCase()
                        });
                    }
                });
            });

            let fieldHtml = '<div class="soccer-field"><div class="player-positions">';
            let playersHtml = '';
            let totalPrice = 0;

            if (selectedPlayers.length > 0) {
                // Sort players by priority (goalkeeper first, then others)
                const priorityOrder = {
                    'goa': 1,
                    'def': 2,
                    'mid': 3,
                    'att': 4
                };
                selectedPlayers.sort((a, b) => priorityOrder[a.position] - priorityOrder[b.position]);

                // Assign positions based on roles
                const assignedPlayers = selectedPlayers.map(player => {
                    const role = player.position in positionClassMap ? player.position : 'mid';
                    
                    // Find an unused position for this role
                    const availablePositions = positionClassMap[role].filter(
                        pos => !usedPositions[role].includes(pos)
                    );

                    if (availablePositions.length > 0) {
                        const positionClass = availablePositions[0];
                        usedPositions[role].push(positionClass);

                        const playerPrice = parseFloat(player.price) || 0;
                        totalPrice += playerPrice;

                        return {
                            ...player,
                            positionClass,
                            price: playerPrice
                        };
                    }

                    return null;
                }).filter(Boolean); // Remove null entries

                // Generate HTML for assigned players
                assignedPlayers.forEach(player => {
                    playersHtml += `
                        <div class="player-spot ${player.positionClass}">
                            <div class="player-jersey-shape"></div>
                            <div class="player-details">
                                <span class="player-spot-name">${player.name.split(' ').map(word => word.charAt(0).toUpperCase() + word.slice(1)).join(' ')}</span>
                                <span class="player-spot-price">$${player.price.toFixed(2)}</span>
                            </div>
                        </div>
                    `;
                });

                fieldHtml += playersHtml + '</div></div>'; // Close player-positions and soccer-field
                outputElement.innerHTML = fieldHtml;

                // Budget calculation
                const remainingBudget = leagueBudget - totalPrice;
                const budgetStatus = remainingBudget >= 0 ? 'success' : 'error';
                
                totalElement.innerHTML = `
                    <div class="team-stats">
                        <h3 class="team-stats-title">Budget Overview</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-label">BUDGET</span>
                                <span class="stat-value">$${leagueBudget.toFixed(2)}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">TEAM PRICE</span>
                                <span class="stat-value">$${totalPrice.toFixed(2)}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label ${budgetStatus === 'error' ? 'budget-error' : ''}">REMAINING</span>
                                <span class="stat-value ${budgetStatus === 'error' ? 'budget-error' : ''}">$${remainingBudget.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                fieldHtml += '</div></div>'; // Close player-positions and soccer-field
                outputElement.innerHTML = fieldHtml + '<p>No players selected</p>';
                
                totalElement.innerHTML = `
                    <div class="team-stats">
                        <h3 class="team-stats-title">Budget Overview</h3>
                        <div class="stats-grid">
                            <div class="stat-card">
                                <span class="stat-label">BUDGET</span>
                                <span class="stat-value">$${leagueBudget.toFixed(2)}</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">TEAM PRICE</span>
                                <span class="stat-value">$0.00</span>
                            </div>
                            <div class="stat-card">
                                <span class="stat-label">REMAINING</span>
                                <span class="stat-value">$${leagueBudget.toFixed(2)}</span>
                            </div>
                        </div>
                    </div>
                `;
            }
        }

        // Debounce function to prevent rapid firing on multiple clicks
        let debounceTimer;
        function debouncedUpdate() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(updateVisualDisplay, 150);
        }

        // Add click listener to detect changes
        document.addEventListener('click', function(e) {
            if (e.target.closest('[id^="dfsoccer_sel_"] .player-item')) {
                debouncedUpdate();
            }
        });

        // Periodically check for budget updates
        function checkForBudgetUpdates() {
            if (leagueId > 0) {
                fetch(`?dfsoccer_check_budget=${leagueId}&_=${new Date().getTime()}`)
                    .then(response => response.json())
                    .catch(error => console.error('Error checking budget:', error));
            }
        }
        
        // Check for updates every 30 seconds
        setInterval(checkForBudgetUpdates, 30000);

        // Initial update on load
        setTimeout(updateVisualDisplay, 400);
    });
    </script>
    <?php
    // Return the buffered content
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('bezzzedfsoccervisual', 'bezzzedfsoccervisual_shortcode');


// Form submission handler
function dfsoccer_handle_player_selection_submission() {
    // Check if our form was submitted
    if (isset($_POST['submit_players_barebones'])) {
        // Get the league_id from the nonce
        $nonce_name = 'dfsoccer_player_nonce_barebones';
        
        if (!isset($_POST[$nonce_name]) || !wp_verify_nonce($_POST[$nonce_name], 'dfsoccer_player_selection_barebones_' . $_POST['league_id'])) {
            // Nonce verification failed
            return;
        }
        
        $league_id = filter_var($_POST['league_id'], FILTER_VALIDATE_INT);
        $user_id = get_current_user_id();
        
        if (!$league_id || !$user_id) {
            return;
        }
        
        // Get selected players
        $selected_players = isset($_POST['selected_players_barebones']) ? 
                            array_map('intval', $_POST['selected_players_barebones']) : 
                            array();
        
        // Update user meta
        $meta_key = 'dfsoccer_selected_players_' . $league_id;
        update_user_meta($user_id, $meta_key, $selected_players);
        
        // Optional: Add success message
        add_action('the_content', function($content) {
            return '<div class="success-message">Your player selection has been saved.</div>' . $content;
        });
    }
}
add_action('init', 'dfsoccer_handle_player_selection_submission');



function dfsoccerred_div_shortcode($atts) {
    if (!is_user_logged_in()) {
        return ''; // Return nothing if the user is not logged in
    }
    $atts = shortcode_atts(array(
        'league_id' => '0',  // Default league ID if none provided
        'test_disabled' => 'false' // Parameter to test disabled selection
    ), $atts, 'red_div');
    $league_id = intval($atts['league_id']);
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    if (empty($saved_fixtures)) {
        return 'No fixtures set for this league.';
    }
    
    // Check if league has started using your existing function
    $is_selection_disabled = (function_exists('dfsoccer_has_league_started') ? dfsoccer_has_league_started($league_id) : false)
                             || $atts['test_disabled'] === 'true';
    
    $user_id = get_current_user_id();
    $selected_players_meta_key = 'dfsoccer_selected_players_' . $league_id;
    
    // Only load selected players from user meta if the game has started
    if ($is_selection_disabled) {
        $selected_players = get_user_meta($user_id, $selected_players_meta_key, true);
        if (!is_array($selected_players)) {
            $selected_players = [];
        }
    } else {
        // If game hasn't started, don't preload any player selections
        // Let the player selection form handle this
        $selected_players = [];
    }
    
    // Get club IDs from fixtures
    $club_ids = array_unique(array_merge(
        array_column($saved_fixtures, 'home_club_id'),
        array_column($saved_fixtures, 'away_club_id')
    ));
    
    // Load player data for all possible players
    $players = [];
    foreach ($club_ids as $club_id) {
        $club_players = dfsoccer_list_players_data_by_club($club_id, $selected_players_meta_key, $user_id);
        $players = array_merge($players, $club_players);
    }
    
    $image_url = plugins_url('soccer.png', __FILE__);
    
    // Start output with the toggle button
    $output = '<button id="toggleSoccerField" style="
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        padding: 15px;
        background-color: #909090;
        color: white;
        border: none;
        cursor: pointer;
        font-size: 16px;
        font-weight: bold;
        text-transform: uppercase;
        box-shadow: 0 -2px 5px rgba(0,0,0,0.2);
        z-index: 10000;
        transition: background-color 0.3s ease;
    ">Show/Hide Soccer Field</button>';
    
    
    // Add the soccer field container with initial hidden state
    $output .= '<div id="soccerFieldWrapper" style="display: none;">';
    

    
    $output .= '<div class="soccercontainerlarge' . ($is_selection_disabled ? ' selection-disabled' : '') . '">';
    $output .= '<div class="guifield_for_soccercontainer">
        <div class="guifield_for_soccer" style="background-image: url(\'' . esc_url($image_url) . '\'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center;">
            <div class="field_for_soccercontainer">';
    
    for ($i = 1; $i <= 6; $i++) {
        $output .= '<div class="card_wrapper">';
        $output .= '<div class="card_container">';
        $output .= '<div class="card_for_soccer" id="card_for_player_' . $i . '"></div>';
        $output .= '<div class="player_name" id="player_name_' . $i . '"></div>';
        $output .= '</div>';
        $output .= '</div>';
    }
    $output .= '</div></div></div></div></div>';

    // Add the script to pass data to the JavaScript file
    $output .= '<script type="text/javascript">
    window.redDivData = ' . wp_json_encode(array(
        'selectedPlayers' => $selected_players,
        'players' => $players,
        'isSelectionDisabled' => $is_selection_disabled
    )) . ';

    document.addEventListener("DOMContentLoaded", function() {
        var button = document.getElementById("toggleSoccerField");
        var container = document.getElementById("soccerFieldWrapper");
        
        var isVisible = localStorage.getItem("soccerFieldVisible") === "true";
        container.style.display = isVisible ? "block" : "none";
        button.textContent = isVisible ? "Hide Soccer Field" : "Show Soccer Field";
        
        button.addEventListener("click", function() {
            var isCurrentlyVisible = container.style.display === "block";
            container.style.display = isCurrentlyVisible ? "none" : "block";
            button.textContent = isCurrentlyVisible ? "Show Soccer Field" : "Hide Soccer Field";
            localStorage.setItem("soccerFieldVisible", !isCurrentlyVisible);
        });
    });
    </script>';

    // Add CSS for the button
    $output .= '<style>
    .toggle-soccer-field {
        background-color: #4CAF50;
        border: none;
        color: white;
        padding: 10px 20px;
        text-align: center;
        text-decoration: none;
        display: inline-block;
        font-size: 16px;
        margin: 4px 2px;
        cursor: pointer;
        border-radius: 4px;
        transition: all 0.3s ease;
    }
    .toggle-soccer-field:hover {
        background-color: #45a049;
        transform: scale(1.02);
    }
    #soccerFieldWrapper {
        transition: display 0.3s ease;
    }
    .selection-disabled .card_for_soccer {
        cursor: default !important;
    }
    .selection-disabled .card_for_soccer.selected {
        opacity: 0.9;
    }
    .selection-disabled .card_for_soccer.selected:hover {
        transform: none !important;
        box-shadow: none !important;
    }
    </style>';

    wp_enqueue_script('red-div-script', plugins_url('js/red-div.js', __FILE__), array(), null, true);
	
$output .= '<script>
document.addEventListener("DOMContentLoaded", function() {
    if (window.redDivData && window.redDivData.isSelectionDisabled) {
        console.log("League has started, displaying selected team...");
        
        function displaySelectedPlayers() {
            console.log("Displaying players from database...");
            
            const selectedPlayerIds = window.redDivData.selectedPlayers || [];
            const allPlayers = window.redDivData.players || [];
            
            console.log("Selected player IDs:", selectedPlayerIds);
            console.log("Available players:", allPlayers);
            
            const positionColors = {
                "goalkeeper": "#FFC107", // Gold for goalkeeper
                "defender": "#2196F3",   // Blue for defenders
                "midfielder": "#4CAF50", // Green for midfielders
                "attacker": "#F44336"    // Red for attackers
            };
            
            const defaultColor = "#9C27B0"; // Purple
            
            for (let i = 1; i <= 6; i++) {
                const playerCard = document.getElementById("card_for_player_" + i);
                const playerName = document.getElementById("player_name_" + i);
                
                const playerId = selectedPlayerIds[i-1];
                
                if (playerCard && playerName && playerId) {
                    // Find the player data from all players
                    const playerData = allPlayers.find(p => p.id == playerId);
                    
                    if (playerData) {
                        const positionColor = playerData.position ? positionColors[playerData.position] : defaultColor;
                        playerCard.style.backgroundColor = positionColor;
                        playerCard.classList.add("selected");
                        playerCard.style.borderColor = "#000";
                        playerCard.style.borderWidth = "3px";
                        
                        playerName.textContent = playerData.name;
                        playerName.style.color = "#fff";
                        playerName.style.textShadow = "1px 1px 2px rgba(0,0,0,0.7)";
                        playerName.style.fontWeight = "bold";
                        
                        playerCard.dataset.playerId = playerData.id;
                        
                        console.log(`Position ${i}: ${playerData.name} (${playerData.position})`);
                    } else {
                        // Fallback if player data not found but ID exists
                        playerCard.style.backgroundColor = defaultColor;
                        playerCard.classList.add("selected");
                        playerName.textContent = "Player " + playerId;
                        playerCard.dataset.playerId = playerId;
                        
                        console.log(`Position ${i}: Player ID ${playerId} (data not found)`);
                    }
                } else {
                    if (playerCard) {
                        playerCard.style.backgroundColor = "";
                        playerCard.classList.remove("selected");
                        playerCard.dataset.playerId = "";
                    }
                    if (playerName) {
                        playerName.textContent = "";
                    }
                    
                    console.log(`Position ${i}: Empty`);
                }
            }
            
            if (selectedPlayerIds.length > 0) {
                const field = document.querySelector(".guifield_for_soccer");
                if (field) {
                    const messageDiv = document.createElement("div");
                    messageDiv.style.position = "absolute";
                    messageDiv.style.bottom = "10px";
                    messageDiv.style.left = "50%";
                    messageDiv.style.transform = "translateX(-50%)";
                    messageDiv.style.backgroundColor = "rgba(255, 255, 255, 0.7)";
                    messageDiv.style.padding = "8px 15px";
                    messageDiv.style.borderRadius = "20px";
                    messageDiv.style.maxWidth = "80%";
                    messageDiv.style.textAlign = "center";
                    messageDiv.style.zIndex = "5";
                    messageDiv.style.boxShadow = "0 2px 4px rgba(0,0,0,0.2)";
                    messageDiv.style.fontSize = "12px";
                    messageDiv.innerHTML = "<strong>TEAM LOCKED</strong> - League has started";
                    
                    field.appendChild(messageDiv);
                }
            }
            
            console.log("Player display complete");
        }
        
        setTimeout(displaySelectedPlayers, 500);
    } else {
        console.log("League has not started, team display skipped");
    }
});
</script>';
    
    return $output;
}


add_shortcode('red_div', 'dfsoccerred_div_shortcode');







function red_div_enqueue_styles() {
    wp_enqueue_style('red-div-styles', plugin_dir_url(__FILE__) . 'css/soccer-styles.css', array(), '1.0.0');
}
add_action('wp_enqueue_scripts', 'red_div_enqueue_styles');


if (!function_exists('dfsoccer_list_players_by_club')) {
    function dfsoccer_list_players_by_club($club_id, $league_id, $is_author = false, $player_meta_key = '', $user_id = '') {
        $players = get_posts([
            'post_type' => 'dfsoccer_player',
            'posts_per_page' => -1,
            'meta_query' => [
                ['key' => 'dfsoccer_club_id', 'value' => $club_id]
            ]
        ]);

        $output = '';
        foreach ($players as $player) {
            $player_id = $player->ID;
            $player_name = get_the_title($player_id);
            $player_position = strtolower(get_post_meta($player_id, 'dfsoccer_position', true));
            $player_price = get_post_meta($player_id, 'dfsoccer_price', true); // Fetch the player price
            $checked = $is_author && is_array(get_user_meta($user_id, $player_meta_key, true)) && in_array($player_id, get_user_meta($user_id, $player_meta_key, true)) ? 'checked' : '';

            $output .= '<div class="player-card" data-club-id="' . $club_id . '" data-position="' . $player_position . '">';
            if ($is_author) {
                $output .= '<input type="checkbox" name="selected_players[]" value="' . $player_id . '" ' . $checked . ' data-price="' . esc_attr($player_price) . '">';
            }
            $output .= '<span class="player-name">' . $player_name . '</span> (' . ucfirst($player_position) . ') - Price: ' . $player_price;
            $output .= '</div>';
        }

        return $output;
    }
}

function dfsoccer_set_league_budget_shortcode($atts) {
    $atts = shortcode_atts(array(
        'league_id' => '0'
    ), $atts, 'set_league_budget');
    
    $league_id = intval($atts['league_id']);
    if (!$league_id) {
        return 'Invalid League ID';
    }
    
    // Check if the current user is the author of the league post
    $league_post = get_post($league_id);
    if (!$league_post || $league_post->post_author != get_current_user_id()) {
        return 'You do not have permission to set the budget for this league.';
    }
    
    $output = '';
    
    // Create a nonce for security
    $nonce = wp_create_nonce('dfsoccer_budget_nonce');
    
    // If form was submitted the traditional way (non-AJAX fallback)
    if (isset($_POST['submit_budget']) && isset($_POST['_wpnonce']) && wp_verify_nonce($_POST['_wpnonce'], 'set_budget_' . $league_id)) {
        $budget = floatval($_POST['league_budget']);
        
        if ($budget > 0) {
            update_post_meta($league_id, 'dfsoccer_league_budget', $budget);
            
            // Set transient to notify other components
            set_transient('dfsoccer_budget_updated_' . $league_id, time(), 300);
            
            $output .= '<div class="success">Budget set successfully to $' . number_format($budget, 2) . '!</div>';
        } else {
            $output .= '<div class="error">Please enter a valid budget amount greater than zero.</div>';
        }
    }
    
    // Get current budget
    $current_budget = get_post_meta($league_id, 'dfsoccer_league_budget', true);
    
    // Add AJAX functionality
    $output .= '
    <script>
    jQuery(document).ready(function($) {
        $("#league_budget_form_' . $league_id . '").on("submit", function(e) {
            e.preventDefault();
            
            var budget = $("#league_budget_' . $league_id . '").val();
            if (!budget || budget <= 0) {
                $("#budget_status_' . $league_id . '").html("<div class=\"error\">Please enter a valid budget amount greater than zero.</div>");
                return false;
            }
            
            $("#budget_submit_' . $league_id . '").prop("disabled", true).val("Updating...");
            
            $.ajax({
                url: "' . admin_url('admin-ajax.php') . '",
                type: "POST",
                data: {
                    action: "update_league_budget",
                    league_id: ' . $league_id . ',
                    budget: budget,
                    security: "' . $nonce . '"
                },
                success: function(response) {
                    $("#budget_submit_' . $league_id . '").prop("disabled", false).val("Set Budget");
                    
                    if (response.success) {
                        $("#budget_status_' . $league_id . '").html("<div class=\"success\">" + response.data.message + "</div>");
                        
                        // Dispatch custom event for other components to listen to
                        const budgetUpdatedEvent = new CustomEvent("dfsoccer_budget_updated", {
                            detail: {
                                leagueId: ' . $league_id . ',
                                budget: parseFloat(budget)
                            }
                        });
                        document.dispatchEvent(budgetUpdatedEvent);
                    } else {
                        $("#budget_status_' . $league_id . '").html("<div class=\"error\">" + response.data.message + "</div>");
                    }
                },
                error: function() {
                    $("#budget_submit_' . $league_id . '").prop("disabled", false).val("Set Budget");
                    $("#budget_status_' . $league_id . '").html("<div class=\"error\">Connection error. Please try again.</div>");
                }
            });
        });
    });
    </script>
    <style>
        #budget_status_' . $league_id . ' .success {
            color: green;
            padding: 5px;
            background: #f0fff0;
            border: 1px solid #d0e9c6;
            margin: 10px 0;
        }
        #budget_status_' . $league_id . ' .error {
            color: #a94442;
            padding: 5px;
            background: #f2dede;
            border: 1px solid #ebccd1;
            margin: 10px 0;
        }
    </style>';
    
    // Form with unique ID and nonce for security
$output .= '<form id="league_budget_form_' . $league_id . '" method="post" action="" style="background-color: var(--color-green-800, #166534); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">
    ' . wp_nonce_field('set_budget_' . $league_id, '_wpnonce', true, false) . '
    <div style="display: flex; flex-direction: column; gap: 0.5rem;">
        <label for="league_budget_' . $league_id . '" style="color: var(--color-green-300, #86efac); font-weight: 500; margin-bottom: 0.25rem;">Set League Budget:</label>
        <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center;">
            <input type="number" step="0.01" id="league_budget_' . $league_id . '" name="league_budget" value="' . esc_attr($current_budget) . '" style="background-color: var(--color-green-950, #052e16); color: white; border: 1px solid var(--color-green-700, #15803d); padding: 0.5rem; border-radius: 0.25rem; width: 120px;">
            <input type="submit" id="budget_submit_' . $league_id . '" name="submit_budget" value="Set Budget" style="background-color: var(--color-yellow-500, #eab308); color: var(--color-green-950, #052e16); font-weight: bold; padding: 0.5rem 1rem; border-radius: 0.25rem; border: none; cursor: pointer;">
        </div>
        <div id="budget_status_' . $league_id . '" style="color: var(--color-green-300, #86efac); margin-top: 0.5rem; font-size: 0.875rem;"></div>
    </div>
</form>';
	
	
    
    return $output;
}
add_shortcode('set_league_budget', 'dfsoccer_set_league_budget_shortcode');

/**
 * AJAX handler for updating league budget
 */
function dfsoccer_update_league_budget_callback() {
    check_ajax_referer('dfsoccer_budget_nonce', 'security');
    
    $league_id = isset($_POST['league_id']) ? intval($_POST['league_id']) : 0;
    $budget = isset($_POST['budget']) ? floatval($_POST['budget']) : 0;
    
    if (!$league_id || $budget <= 0) {
        wp_send_json_error(array('message' => 'Invalid data provided.'));
        return;
    }
    
    // Check permissions
    $league_post = get_post($league_id);
    if (!$league_post || $league_post->post_author != get_current_user_id()) {
        wp_send_json_error(array('message' => 'You do not have permission to update this league.'));
        return;
    }
    
    // Delete the old value to ensure a clean update
    delete_post_meta($league_id, 'dfsoccer_league_budget');
    
    // Add the new budget value
    $result = add_post_meta($league_id, 'dfsoccer_league_budget', $budget, true);
    
    // Clear cache
    wp_cache_delete($league_id, 'post_meta');
    
    // Set a transient to notify other components
    set_transient('dfsoccer_budget_updated_' . $league_id, time(), 300);
    
    if ($result) {
        wp_send_json_success(array(
            'message' => 'Budget updated successfully to $' . number_format($budget, 2),
            'budget' => $budget,
            'league_id' => $league_id
        ));
    } else {
        wp_send_json_error(array('message' => 'Failed to update budget or no change was made.'));
    }
}
add_action('wp_ajax_update_league_budget', 'dfsoccer_update_league_budget_callback');

/**
 * AJAX endpoint for checking if budget has been updated
 */
function dfsoccer_check_for_budget_updates() {
    if (isset($_GET['dfsoccer_check_budget'])) {
        $league_id = intval($_GET['dfsoccer_check_budget']);
        
        $update_time = get_transient('dfsoccer_budget_updated_' . $league_id);
        $budget = get_post_meta($league_id, 'dfsoccer_league_budget', true);
        
        header('Content-Type: application/json');
        echo json_encode(array(
            'updated' => !empty($update_time),
            'budget' => floatval($budget),
            'league_id' => $league_id
        ));
        exit;
    }
}
add_action('init', 'dfsoccer_check_for_budget_updates');


// Register plugin options page
function dfsoccer_register_options_page() {
    add_options_page('dfsoccer Settings', 'dfsoccer Settings', 'manage_options', 'dfsoccer', 'dfsoccer_options_page');
}
add_action('admin_menu', 'dfsoccer_register_options_page');

function dfsoccer_options_page() {
?>
    <div>
        <h2>dfsoccer Settings</h2>
        <form method="post" action="options.php">
            <?php settings_fields('dfsoccer_options_group'); ?>
            <table>
                <tr valign="top">
                    <th scope="row"><label for="dfsoccer_create_leagues_role">Role that can create leagues</label></th>
                    <td>
                        <select id="dfsoccer_create_leagues_role" name="dfsoccer_create_leagues_role">
                            <?php wp_dropdown_roles(get_option('dfsoccer_create_leagues_role')); ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="dfsoccer_add_players_role">Role that can add players</label></th>
                    <td>
                        <select id="dfsoccer_add_players_role" name="dfsoccer_add_players_role">
                            <?php wp_dropdown_roles(get_option('dfsoccer_add_players_role')); ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="dfsoccer_add_clubs_role">Role that can add clubs</label></th>
                    <td>
                        <select id="dfsoccer_add_clubs_role" name="dfsoccer_add_clubs_role">
                            <?php wp_dropdown_roles(get_option('dfsoccer_add_clubs_role')); ?>
                        </select>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><label for="dfsoccer_followed_user_ids">User IDs to follow leagues from</label></th>
                    <td>
                        <textarea id="dfsoccer_followed_user_ids" name="dfsoccer_followed_user_ids" rows="4" cols="50" placeholder="Enter user IDs separated by commas (e.g., 123, 456, 789)"><?php echo esc_textarea(get_option('dfsoccer_followed_user_ids')); ?></textarea>
                        <p class="description">Enter the WordPress user IDs whose leagues you want to follow, separated by commas. Leave empty to not follow any specific users.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        
        <!-- Helper section to find user IDs -->
        <div style="margin-top: 30px; padding: 20px; background: #f1f1f1; border-radius: 5px;">
            <h3>Find User IDs</h3>
            <p>Need help finding user IDs? Here are some users on your site:</p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Display Name</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $users = get_users(array(
                        'number' => 20, // Limit to 20 users for performance
                        'orderby' => 'registered',
                        'order' => 'DESC'
                    ));
                    
                    foreach ($users as $user) {
                        $user_roles = implode(', ', $user->roles);
                        echo '<tr>';
                        echo '<td><strong>' . $user->ID . '</strong></td>';
                        echo '<td>' . esc_html($user->user_login) . '</td>';
                        echo '<td>' . esc_html($user->display_name) . '</td>';
                        echo '<td>' . esc_html($user->user_email) . '</td>';
                        echo '<td>' . esc_html($user_roles) . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
            <p><em>Showing the 20 most recent users. You can also find user IDs in Users > All Users in your WordPress admin.</em></p>
        </div>
    </div>
<?php
}

// Register plugin settings
function dfsoccer_register_settings() {
    add_option('dfsoccer_create_leagues_role', 'administrator');
    add_option('dfsoccer_add_players_role', 'administrator');
    add_option('dfsoccer_add_clubs_role', 'administrator');
    add_option('dfsoccer_followed_user_ids', ''); // New option for followed user IDs

    register_setting('dfsoccer_options_group', 'dfsoccer_create_leagues_role');
    register_setting('dfsoccer_options_group', 'dfsoccer_add_players_role');
    register_setting('dfsoccer_options_group', 'dfsoccer_add_clubs_role');
    register_setting('dfsoccer_options_group', 'dfsoccer_followed_user_ids', 'dfsoccer_sanitize_user_ids'); // Register with sanitization callback
}
add_action('admin_init', 'dfsoccer_register_settings');

// Sanitization function for user IDs
function dfsoccer_sanitize_user_ids($input) {
    if (empty($input)) {
        return '';
    }
    
    // Split by commas and clean up
    $user_ids = array_map('trim', explode(',', $input));
    $valid_user_ids = array();
    
    foreach ($user_ids as $user_id) {
        // Check if it's a valid number
        if (is_numeric($user_id) && intval($user_id) > 0) {
            $user_id = intval($user_id);
            
            // Check if user exists
            if (get_user_by('id', $user_id)) {
                $valid_user_ids[] = $user_id;
            }
        }
    }
    
    // Remove duplicates and return as comma-separated string
    $valid_user_ids = array_unique($valid_user_ids);
    return implode(', ', $valid_user_ids);
}

// Helper function to get followed user IDs as an array
function dfsoccer_get_followed_user_ids() {
    $user_ids_string = get_option('dfsoccer_followed_user_ids', '');
    
    if (empty($user_ids_string)) {
        return array();
    }
    
    $user_ids = array_map('trim', explode(',', $user_ids_string));
    $user_ids = array_map('intval', $user_ids);
    $user_ids = array_filter($user_ids, function($id) {
        return $id > 0;
    });
    
    return array_values($user_ids);
}

// Add custom capabilities to roles
function dfsoccer_check_capabilities() {
    // Get roles from settings
    $create_leagues_role = get_option('dfsoccer_create_leagues_role', 'administrator');
    $add_players_role = get_option('dfsoccer_add_players_role', 'administrator');
    $add_clubs_role = get_option('dfsoccer_add_clubs_role', 'administrator');

    // Remove all custom capabilities first to ensure a clean slate
    dfsoccer_remove_custom_capabilities();

    // Add capabilities to roles
    dfsoccer_assign_capabilities($create_leagues_role, 'league');
    dfsoccer_assign_capabilities($add_players_role, 'player');
    dfsoccer_assign_capabilities($add_clubs_role, 'club');

    // Ensure larger roles inherit capabilities
    dfsoccer_ensure_larger_roles_inherit_capabilities();
}
add_action('admin_init', 'dfsoccer_check_capabilities');

function dfsoccer_assign_capabilities($role_name, $type) {
    $role = get_role($role_name);
    if ($role) {
        switch ($type) {
            case 'player':
                $role->add_cap('publish_dfsoccer_players');
                $role->add_cap('edit_dfsoccer_players');
                $role->add_cap('delete_dfsoccer_players');
                $role->add_cap('edit_dfsoccer_player');
                $role->add_cap('delete_dfsoccer_player');
                $role->add_cap('read_dfsoccer_player');
                if ($role_name !== 'subscriber') {
                    $role->add_cap('edit_others_dfsoccer_players');
                    $role->add_cap('delete_others_dfsoccer_players');
                    $role->add_cap('read_private_dfsoccer_players');
                }
                break;
            case 'league':
                $role->add_cap('publish_dfsoccer_leagues');
                $role->add_cap('edit_dfsoccer_leagues');
                $role->add_cap('delete_dfsoccer_leagues');
                $role->add_cap('edit_dfsoccer_league');
                $role->add_cap('delete_dfsoccer_league');
                $role->add_cap('read_dfsoccer_league');
                if ($role_name !== 'subscriber') {
                    $role->add_cap('edit_others_dfsoccer_leagues');
                    $role->add_cap('delete_others_dfsoccer_leagues');
                    $role->add_cap('read_private_dfsoccer_leagues');
                }
                break;
            case 'club':
                $role->add_cap('publish_dfsoccer_clubs');
                $role->add_cap('edit_dfsoccer_clubs');
                $role->add_cap('delete_dfsoccer_clubs');
                $role->add_cap('edit_dfsoccer_club');
                $role->add_cap('delete_dfsoccer_club');
                $role->add_cap('read_dfsoccer_club');
                if ($role_name !== 'subscriber') {
                    $role->add_cap('edit_others_dfsoccer_clubs');
                    $role->add_cap('delete_others_dfsoccer_clubs');
                    $role->add_cap('read_private_dfsoccer_clubs');
                }
                break;
        }
    }
}

function dfsoccer_ensure_larger_roles_inherit_capabilities() {
    $roles_hierarchy = array('subscriber', 'contributor', 'author', 'editor', 'administrator');

    $capabilities = array(
        'publish_dfsoccer_players', 'edit_dfsoccer_players', 'delete_dfsoccer_players', 'edit_dfsoccer_player', 'delete_dfsoccer_player', 'read_dfsoccer_player',
        'publish_dfsoccer_leagues', 'edit_dfsoccer_leagues', 'delete_dfsoccer_leagues', 'edit_dfsoccer_league', 'delete_dfsoccer_league', 'read_dfsoccer_league',
        'publish_dfsoccer_clubs', 'edit_dfsoccer_clubs', 'delete_dfsoccer_clubs', 'edit_dfsoccer_club', 'delete_dfsoccer_club', 'read_dfsoccer_club'
    );

    $elevated_capabilities = array(
        'edit_others_dfsoccer_players', 'delete_others_dfsoccer_players', 'read_private_dfsoccer_players',
        'edit_others_dfsoccer_leagues', 'delete_others_dfsoccer_leagues', 'read_private_dfsoccer_leagues',
        'edit_others_dfsoccer_clubs', 'delete_others_dfsoccer_clubs', 'read_private_dfsoccer_clubs'
    );

    foreach ($roles_hierarchy as $key => $role_name) {
        $role = get_role($role_name);
        if ($role) {
            for ($i = $key + 1; $i < count($roles_hierarchy); $i++) {
                $higher_role_name = $roles_hierarchy[$i];
                $higher_role = get_role($higher_role_name);
                if ($higher_role) {
                    foreach ($capabilities as $capability) {
                        if ($role->has_cap($capability)) {
                            $higher_role->add_cap($capability);
                        }
                    }
                    if ($role_name !== 'subscriber') {
                        foreach ($elevated_capabilities as $capability) {
                            if ($role->has_cap($capability)) {
                                $higher_role->add_cap($capability);
                            }
                        }
                    }
                }
            }
        }
    }
}

function dfsoccer_remove_custom_capabilities() {
    global $wp_roles;
    $capabilities = array(
        'publish_dfsoccer_players', 'edit_dfsoccer_players', 'edit_others_dfsoccer_players', 'delete_dfsoccer_players', 'delete_others_dfsoccer_players', 'read_private_dfsoccer_players', 'edit_dfsoccer_player', 'delete_dfsoccer_player', 'read_dfsoccer_player',
        'publish_dfsoccer_leagues', 'edit_dfsoccer_leagues', 'edit_others_dfsoccer_leagues', 'delete_dfsoccer_leagues', 'delete_others_dfsoccer_leagues', 'read_private_dfsoccer_leagues', 'edit_dfsoccer_league', 'delete_dfsoccer_league', 'read_dfsoccer_league',
        'publish_dfsoccer_clubs', 'edit_dfsoccer_clubs', 'edit_others_dfsoccer_clubs', 'delete_dfsoccer_clubs', 'delete_others_dfsoccer_clubs', 'read_private_dfsoccer_clubs', 'edit_dfsoccer_club', 'delete_dfsoccer_club', 'read_dfsoccer_club'
    );

    foreach ($capabilities as $capability) {
        foreach ($wp_roles->roles as $role_name => $role_info) {
            $role = get_role($role_name);
            if ($role) {
                $role->remove_cap($capability);
            }
        }
    }
}

// Hierarchy level checker remains the same
function dfsoccer_get_user_hierarchy_level($user_id) {
    $user = get_userdata($user_id);
    if (!$user) return -1;

    $hierarchy = array(
        'subscriber' => 0,
        'contributor' => 1,
        'author' => 2,
        'editor' => 3,
        'administrator' => 4
    );

    $highest_level = 0;
    foreach ($user->roles as $role) {
        if (isset($hierarchy[$role]) && $hierarchy[$role] > $highest_level) {
            $highest_level = $hierarchy[$role];
        }
    }
    return $highest_level;
}

// Modified filter to check both edit and delete capabilities
function dfsoccer_check_post_capabilities($allcaps, $caps, $args) {
    // Check for both edit and delete capabilities
    if (!isset($args[0]) || !in_array($args[0], array(
        'edit_post', 
        'delete_post',
        'edit_dfsoccer_league',
        'delete_dfsoccer_league',
        'edit_dfsoccer_player',
        'delete_dfsoccer_player',
        'edit_dfsoccer_club',
        'delete_dfsoccer_club'
    ))) {
        return $allcaps;
    }

    // Get the post and check if it's our custom type
    $post_id = isset($args[2]) ? $args[2] : 0;
    $post = get_post($post_id);
    
    if (!$post || !in_array($post->post_type, array('dfsoccer_league', 'dfsoccer_player', 'dfsoccer_club'))) {
        return $allcaps;
    }

    // Get the current user and post author hierarchy levels
    $current_user_id = get_current_user_id();
    
    // Allow users to edit/delete their own posts
    if ($current_user_id == $post->post_author) {
        return $allcaps;
    }

    $current_user_level = dfsoccer_get_user_hierarchy_level($current_user_id);
    $post_author_level = dfsoccer_get_user_hierarchy_level($post->post_author);

    // If current user's level is NOT strictly higher than post author's, deny capability
    if ($current_user_level <= $post_author_level) {
        foreach ($caps as $cap) {
            $allcaps[$cap] = false;
        }
    }

    return $allcaps;
}
add_filter('user_has_cap', 'dfsoccer_check_post_capabilities', 10, 3);

// Additional filter to hide the trash link if user can't delete
function dfsoccer_modify_post_row_actions($actions, $post) {
    if (!in_array($post->post_type, array('dfsoccer_league', 'dfsoccer_player', 'dfsoccer_club'))) {
        return $actions;
    }

    $current_user_id = get_current_user_id();
    
    // Always allow users to trash their own posts
    if ($current_user_id == $post->post_author) {
        return $actions;
    }

    $current_user_level = dfsoccer_get_user_hierarchy_level($current_user_id);
    $post_author_level = dfsoccer_get_user_hierarchy_level($post->post_author);

    // Remove trash/delete links if user level is not higher
    if ($current_user_level <= $post_author_level) {
        unset($actions['trash']);
        unset($actions['delete']);
    }

    return $actions;
}
add_filter('post_row_actions', 'dfsoccer_modify_post_row_actions', 10, 2);

if( !function_exists( 'wp_get_post_type_link' )  ){
    function wp_get_post_type_link( $post_type ){

        global $wp_rewrite; 

        if ( ! $post_type_obj = get_post_type_object( $post_type ) )
            return false;

        if ( get_option( 'permalink_structure' ) && is_array( $post_type_obj->rewrite ) ) {

            $struct = $post_type_obj->rewrite['slug'] ;
            if ( $post_type_obj->rewrite['with_front'] )
                $struct = $wp_rewrite->front . $struct;
            else
                $struct = $wp_rewrite->root . $struct;

            $link = home_url( user_trailingslashit( $struct, 'post_type_archive' ) );       

        } else {
            $link = home_url( '?post_type=' . $post_type );
        }

        return apply_filters( 'the_permalink', $link );
    }
}

// Example usage for your custom post types
add_action('init', function() {
    $player_link = wp_get_post_type_link('dfsoccer_player');
    $league_link = wp_get_post_type_link('dfsoccer_league');
    $club_link = wp_get_post_type_link('dfsoccer_club');


});





function dfsoccer_add_points_rules_meta_box() {
    add_meta_box(
        'dfsoccer_points_rules_meta_box',
        'Points Rules',
        'dfsoccer_points_rules_meta_box_callback',
        'dfsoccer_league',
        'side',
        'default'
    );
}
add_action('add_meta_boxes', 'dfsoccer_add_points_rules_meta_box');

function dfsoccer_points_rules_meta_box_callback($post) {
    $points_rules = get_post_meta($post->ID, 'dfsoccer_points_rules', true);
    if (!$points_rules) {
        $points_rules = [
            'goalkeeper' => ['goals' => 10, 'own' => -7, 'assists' => 7, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
            'defender' => ['goals' => 7, 'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
            'midfielder' => ['goals' => 6, 'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -1, 'penalties' => 8, 'missed' => -4],
            'attacker' => ['goals' => 5, 'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => 0, 'penalties' => 8, 'missed' => -4]
        ];
    }

    $positions = ['goalkeeper', 'defender', 'midfielder', 'attacker'];
    $stats_labels = [
        'goals' => 'Goals',
        'own' => 'Own Goals',
        'assists' => 'Assists',
        'minutes' => 'Minutes Played',
        'red' => 'Red Cards Received',
        'yellow' => 'Yellow Cards Received',
        'conceded' => 'Goals Conceded',
        'penalties' => 'Penalties Saved',
        'missed' => 'Penalties Missed'
    ];

foreach ($positions as $position) {
    echo '<h4>' . esc_html(ucfirst($position)) . '</h4>';
    foreach ($stats_labels as $stat => $label) {
        $value = isset($points_rules[$position][$stat]) ? $points_rules[$position][$stat] : '';
        echo '<label>' . esc_html($label) . ': </label>';
        echo '<input type="text" name="dfsoccer_points_rules[' . esc_attr($position) . '][' . esc_attr($stat) . ']" value="' . esc_attr($value) . '" /><br>';
    }
}
}

function dfsoccer_save_points_rules_meta_box($post_id) {
    if (array_key_exists('dfsoccer_points_rules', $_POST)) {
        update_post_meta($post_id, 'dfsoccer_points_rules', $_POST['dfsoccer_points_rules']);
    }
}
add_action('save_post', 'dfsoccer_save_points_rules_meta_box');

function dfsoccercalculate_total_points($player_id, $player_stats, $league_id) {

    // --- Determine Position ---
    $position = null; // Start with null

    // 1. Try primary source: Player post meta
    $position_raw_meta = get_post_meta($player_id, 'dfsoccer_position', true);
    $position_from_meta = !empty($position_raw_meta) ? strtolower(trim($position_raw_meta)) : '';

    if (!empty($position_from_meta)) {
        $position = $position_from_meta;
        error_log("DFSoccer Calc Points: Using position '$position' from player meta for player $player_id.");
    } else {
        // 2. Fallback: Player meta was empty, try league results meta
        error_log("DFSoccer Calc Points: Position empty in player meta for player $player_id. Attempting fallback from league results (league $league_id).");

        $league_results_json = get_post_meta($league_id, 'dfsoccer_match_results', true);
        $league_results = !empty($league_results_json) ? json_decode($league_results_json, true) : null;

        if (is_array($league_results) && isset($league_results[$player_id]) && isset($league_results[$player_id]['position']) && !empty($league_results[$player_id]['position'])) {
            // Found position in the fallback source
            $position_from_results = $league_results[$player_id]['position'];
            $position = strtolower(trim($position_from_results)); // Assign the fallback position
            error_log("DFSoccer Calc Points: Using fallback position '$position' from league results for player $player_id.");
        } else {
            // Fallback also failed
            error_log("DFSoccer Calc Points Warning: Could not find position for player $player_id in league results fallback either.");
            // $position remains null or empty
        }
    }

    // --- Validate Final Position ---
    // If position is still empty after primary and fallback attempts, we can't proceed.
    if (empty($position)) {
        error_log("DFSoccer Calc Points Error: Final position for player $player_id is unknown. Cannot calculate points.");
        return 0.0;
    }

    // --- Get Rules ---
    $points_rules = get_post_meta($league_id, 'dfsoccer_points_rules', true);
    $points_rules = is_array($points_rules) ? $points_rules : []; // Ensure array

    // Default point rules
    $default_rules = [
        'goalkeeper' => ['goals' => 10, 'own' => -7, 'assists' => 7, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
        'defender'   => ['goals' => 7,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
        'midfielder' => ['goals' => 6,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -1, 'penalties' => 8, 'missed' => -4],
        'attacker'   => ['goals' => 5,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => 0,  'penalties' => 8, 'missed' => -4]
    ];

    // --- Select Rules based on *Final* Position ---
    $rules = null;
    // First, check if the determined position is even valid according to default rules
    if (!array_key_exists($position, $default_rules)) {
        error_log("DFSoccer Calc Points Error: Position '$position' determined for player $player_id is invalid (not in default rules).");
        return 0.0; // Invalid position string
    }

    // Now select rules: league-specific override or default for the valid position
    $rules = (isset($points_rules[$position])) ? $points_rules[$position] : $default_rules[$position];


    // --- Calculate Points ---
    $total_points = 0.0;

    // Check if we successfully determined a ruleset
    if (is_array($rules) && !empty($rules)) {
         // Ensure $player_stats is usable
        if (is_array($player_stats)) {
            foreach ($rules as $stat_key => $points_per_stat) {
                if (isset($player_stats[$stat_key])) {
                     $stat_value = is_numeric($player_stats[$stat_key]) ? floatval($player_stats[$stat_key]) : 0;
                     $points_value = is_numeric($points_per_stat) ? floatval($points_per_stat) : 0;
                     $total_points += $stat_value * $points_value;
                }
            }
         } else {
             error_log("DFSoccer Calc Points Warning: Input player_stats for player $player_id was not a valid array.");
         }
    } else {
        // This should ideally not be reached if position validation worked, but good as a safeguard.
        error_log("DFSoccer Calc Points Error: Could not determine a valid rule set for position '$position', player $player_id.");
        // $total_points remains 0.0
    }


    // --- Debugging & Return ---
    error_log("DFSoccer Calc Points Result - Player ID: $player_id, Final Position: '$position', Total Points: $total_points");
    // Optional detailed logs:
    // error_log("DFSoccer Calc Points Debug - Player Stats Input: " . print_r($player_stats, true));
    // error_log("DFSoccer Calc Points Debug - Applied Rules: " . print_r($rules, true));

    return round($total_points, 2);
}



/**
 * Appends a list of players to a club, sorted by position, with links and prices.
 */
function dfsoccer_append_players_to_club($content) {
    if (is_single() && get_post_type() == 'dfsoccer_club') {
        $club_id = get_the_ID(); // Get the current club ID

        // Fetch all players in the current club
        $args = array(
            'post_type'      => 'dfsoccer_player',
            'posts_per_page' => -1,
            'meta_query'     => array(
                array(
                    'key'     => 'dfsoccer_club_id',
                    'value'   => $club_id,
                    'compare' => '='
                )
            )
        );
        $query = new WP_Query($args);

        // Initialize arrays to store players by position
        $players = array(
            'goalkeeper' => array(),
            'defender'   => array(),
            'midfielder' => array(),
            'attacker'   => array()
        );

        // Populate arrays with players
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $position = get_post_meta(get_the_ID(), 'dfsoccer_position', true);
                $price = get_post_meta(get_the_ID(), 'dfsoccer_price', true);
                if (array_key_exists($position, $players)) {
                    $players[$position][] = array(
                        'name'  => get_the_title(),
                        'link'  => get_permalink(),
                        'price' => $price
                    );
                }
            }
            wp_reset_postdata();
        }

        // Generate the sorted player list HTML
        $output = '<div class="dfsoccer-club-players">';
        foreach ($players as $position => $player_list) {
            if (!empty($player_list)) {
                $output .= '<h3>' . ucfirst($position) . 's</h3>';
                $output .= '<ul>';
                foreach ($player_list as $player) {
                    $output .= '<li><a href="' . esc_url($player['link']) . '">' . esc_html($player['name']) . '</a> - $' . esc_html($player['price']) . '</li>';
                }
                $output .= '</ul>';
            }
        }
        $output .= '</div>';

        // Append the player list to the club's content
        $content .= $output;
    }

    return $content;
}
add_filter('the_content', 'dfsoccer_append_players_to_club');


function df_fantasy_enqueue_scripts() {
    // Enqueue CSS
    wp_enqueue_style('df-fantasy-style', plugin_dir_url(__FILE__) . 'css/df-fantasy.css');

    // Enqueue JS
    wp_enqueue_script('df-fantasy-script', plugin_dir_url(__FILE__) . 'js/df-fantasy.js', array('jquery'), null, true);
}

add_action('wp_enqueue_scripts', 'df_fantasy_enqueue_scripts');






/**
 * Check if a league's fixtures have started based on league ID
 *
 * @param int $league_id The ID of the league to check
 * @return bool True if league has started, false otherwise
 */
function dfsoccer_has_league_started($league_id) {
    $league_id = intval($league_id);
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    
    if (empty($saved_fixtures)) {
        return false; // No fixtures, so league hasn't started
    }
    
    $first_fixture_date = strtotime($saved_fixtures[0]['fixture_date']);
    $current_time = current_time('timestamp');
    
    return $current_time >= $first_fixture_date;
}



function dfsoccer_combined_shortcode($atts) {
    // Extract attributes if needed
    $atts = shortcode_atts(array(), $atts);

    // We won't require login for all tabs - this check will be done per tab

    $current_user_id = get_current_user_id();
    ob_start();
    
    // Add CSS for tabs, league status colors, toggle button, and participant count
    echo '<style>
        /* League styling */
        .league-upcoming { background-color: #d4edda; padding: 5px; } /* Green background for upcoming */
        .league-ended { background-color: #f8d7da; padding: 5px; } /* Red background for ended */
        .league-status-legend { margin-top: 20px; }
        .league-status-legend span { padding: 2px 10px; margin-right: 5px; }

        .participant-count {
            display: block;
            font-size: 0.85em;
            color: #666;
            margin-top: 3px;
        }
        .award-info {
            display: block;
            font-size: 0.85em;
            color: #28a745;
            margin-top: 2px;
        }
        .league-item {
            margin-bottom: 5px;
        }
        
        /* League type styling */
        .league-type {
            display: inline-block;
            font-size: 0.8em;
            padding: 2px 8px;
            margin-left: 5px;
            border-radius: 3px;
        }
        .api-league {
            background-color: #e3f2fd;
            color: #0d47a1;
        }
        .flash-league {
            background-color: #fff3e0;
            color: #e65100;
        }
        
        /* Tab styling */
        .df-leagues-wrapper {
            display: flex;
            flex-direction: column;
            width: 100%;
        }
        .df-leagues-tabs {
            display: flex;
            flex-wrap: wrap;
        }
        .df-leagues-tab {
            cursor: pointer;
            padding: 10px 20px;
            margin: 0px 2px;
            background: #000;
            display: inline-block;
            color: #fff;
            border-radius: 3px 3px 0px 0px;
            box-shadow: 0 0.5rem 0.8rem #00000080;
        }
        .df-leagues-panels {
            background: #fffffff6;
            box-shadow: 0 2rem 2rem #00000080;
            min-height: 200px;
            width: 100%;
            border-radius: 3px;
            overflow: hidden;
            padding: 20px;
        }
        .df-leagues-panel {
            display: none;
            animation: fadein .8s;
        }
        @keyframes fadein {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .df-leagues-radio {
            display: none;
        }
        #df-tab-one:checked ~ .df-leagues-panels #df-panel-recent,
        #df-tab-two:checked ~ .df-leagues-panels #df-panel-my,
        #df-tab-three:checked ~ .df-leagues-panels #df-panel-selected {
            display: block;
        }
        #df-tab-one:checked ~ .df-leagues-tabs #df-one-tab,
        #df-tab-two:checked ~ .df-leagues-tabs #df-two-tab,
        #df-tab-three:checked ~ .df-leagues-tabs #df-three-tab {
            background: #fffffff6;
            color: #000;
            border-top: 3px solid #000;
        }
    </style>';
    
    // Function to get the number of participants in a league
    function get_league_participants_count($league_id) {
        // Fetch users who have participated in this league
        $args = array(
            'meta_key'   => 'dfsoccer_selected_players_' . $league_id,
            'meta_value' => '', // Any non-empty value
            'meta_compare' => '!=',
            'count_total' => true,
            'fields' => 'ids'
        );
        $users_query = new WP_User_Query($args);
        return $users_query->get_total();
    }
    
    // Function to get the reward information for a league
    function get_league_reward_info($league_id, $participant_count) {
        // Get distribution method from league meta
        $distribution_method = get_post_meta($league_id, 'dfsoccer_points_distribution_method', true);
        if (empty($distribution_method)) {
            $distribution_method = 'winner_takes_all'; // Default method
        }
        
        // Check if points have already been awarded
        $points_awarded = get_post_meta($league_id, 'dfsoccer_league_points_awarded', true);
        
        $award_text = '';
        
        // If points have already been awarded, show that information
        if ($points_awarded) {
            $winner_info = get_post_meta($league_id, 'dfsoccer_league_points_winners', true);
            if (!empty($winner_info)) {
                $award_text = sprintf(__('Points awarded on %s', 'dfsoccer'), 
                    date_i18n(get_option('date_format'), strtotime($winner_info['awarded_on'])));
            } else {
                $award_text = __('Points already awarded', 'dfsoccer');
            }
        } else {
            // Otherwise show potential awards based on distribution method
            switch ($distribution_method) {
                case 'winner_takes_all':
                    $award_text = sprintf(__('Award: Winner Takes All (%d points)', 'dfsoccer'), $participant_count);
                    break;
                    
                case 'fixed':
                    // Get fixed points values
                    $first_place = intval(get_post_meta($league_id, 'dfsoccer_fixed_points_first', true));
                    $second_place = intval(get_post_meta($league_id, 'dfsoccer_fixed_points_second', true));
                    $third_place = intval(get_post_meta($league_id, 'dfsoccer_fixed_points_third', true));
                    
                    $places_text = array();
                    if ($first_place > 0) {
                        $places_text[] = sprintf('1st: %d', $first_place);
                    }
                    if ($second_place > 0) {
                        $places_text[] = sprintf('2nd: %d', $second_place);
                    }
                    if ($third_place > 0) {
                        $places_text[] = sprintf('3rd: %d', $third_place);
                    }
                    
                    if (!empty($places_text)) {
                        $award_text = sprintf(__('Award: Fixed Points (%s)', 'dfsoccer'), 
                            implode(', ', $places_text));
                    } else {
                        $award_text = __('Award: Fixed Points (none set)', 'dfsoccer');
                    }
                    break;
                    
                case 'tiered':
                    if ($participant_count < 10) {
                        // If less than 10 participants, reverts to winner takes all
                        $award_text = sprintf(__('Award: Winner Takes All (%d points) - Not enough participants for tiered', 'dfsoccer'), 
                            $participant_count);
                    } else {
                        // Calculate tiered points (max 5 for 1st, 3 for 2nd, 1 for 3rd)
                        $first_place_points = min($participant_count, 5);
                        $second_place_points = min($participant_count - 1, 3);
                        $third_place_points = min($participant_count - 2, 1);
                        
                        $award_text = sprintf(__('Award: Tiered (1st: %d, 2nd: %d, 3rd: %d)', 'dfsoccer'), 
                            $first_place_points, $second_place_points, $third_place_points);
                    }
                    break;
            }
        }
        
        return $award_text;
    }
    
    // Function to display a league with participant count and league type
    function display_league_with_participants($league_id, $league_title) {
        // Use the existing is_league_from_api function to determine if this is an API league
        $league_source = is_league_from_api($league_id);
        
        // Determine league status based on league type
        if ($league_source['from_api'] && !empty($league_source['source_league_id'])) {
            // For API leagues, use dfsoccer_has_league_started on the source league ID
            $source_league_id = $league_source['source_league_id'];
            $league_status_class = dfsoccer_has_league_started($source_league_id) ? 'league-ended' : 'league-upcoming';
        } else {
            // For regular leagues, use dfsoccer_has_league_started directly
            $league_status_class = dfsoccer_has_league_started($league_id) ? 'league-ended' : 'league-upcoming';
        }
        
        $participant_count = get_league_participants_count($league_id);
        $award_info = get_league_reward_info($league_id, $participant_count);
        
        $league_type_label = $league_source['from_api'] ? 
            '<span class="league-type api-league">11 players league</span>' : 
            '<span class="league-type flash-league">Flash league</span>';
        
        $output = '<li class="league-item ' . $league_status_class . '">';
        $output .= '<a href="' . esc_url(get_permalink($league_id)) . '">' . esc_html($league_title) . '</a>';
        $output .= $league_type_label; // Add the league type label
        $output .= '<span class="participant-count">' . 
                  sprintf(_n('%s participant', '%s participants', $participant_count, 'dfsoccer'), 
                  number_format_i18n($participant_count)) . 
                  '</span>';
        $output .= '<span class="award-info">' . esc_html($award_info) . '</span>';
        $output .= '</li>';
        
        return $output;
    }
    
    // Begin tabbed interface
    echo '<div class="df-leagues-wrapper">';
    
    // Tab radio buttons - Last Created Leagues is first and checked by default
    echo '<input class="df-leagues-radio" id="df-tab-one" name="df-leagues-tabs" type="radio" checked>';
    echo '<input class="df-leagues-radio" id="df-tab-two" name="df-leagues-tabs" type="radio">';
    echo '<input class="df-leagues-radio" id="df-tab-three" name="df-leagues-tabs" type="radio">';
    
    // Tab labels - Last Created Leagues is first
    echo '<div class="df-leagues-tabs">';
    echo '<label class="df-leagues-tab" id="df-one-tab" for="df-tab-one">Last Created Leagues</label>';
    echo '<label class="df-leagues-tab" id="df-two-tab" for="df-tab-two">My Leagues</label>';
    echo '<label class="df-leagues-tab" id="df-three-tab" for="df-tab-three">My Entries</label>';
    echo '</div>';
    
    // Tab panels
    echo '<div class="df-leagues-panels">';
    
    // Panel 1: Last Created Leagues (visible to all users)
    echo '<div class="df-leagues-panel" id="df-panel-recent">';
    
    $recent_leagues = new WP_Query(array(
        'post_type' => 'dfsoccer_league',
        'posts_per_page' => -1,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    echo '<ul id="recent_leagues_list">';
    while ($recent_leagues->have_posts()) {
        $recent_leagues->the_post();
        echo display_league_with_participants(get_the_ID(), get_the_title());
    }
    echo '</ul>';
    wp_reset_postdata();
    
    echo '<div class="pagination" id="recent_leagues_pagination"></div>';
    echo '</div>'; // End panel 1
    
    // Panel 2: My Leagues (only for logged in users)
    echo '<div class="df-leagues-panel" id="df-panel-my">';
    
    if (!is_user_logged_in()) {
        echo '<p>You need to be logged in to view your leagues.</p>';
    } else {
        $my_leagues = new WP_Query(array(
            'post_type' => 'dfsoccer_league',
            'author' => $current_user_id,
            'posts_per_page' => -1
        ));
        
        if ($my_leagues->have_posts()) {
            echo '<ul id="my_leagues_list">';
            while ($my_leagues->have_posts()) {
                $my_leagues->the_post();
                echo display_league_with_participants(get_the_ID(), get_the_title());
            }
            echo '</ul>';
        } else {
            echo '<p>No leagues found.</p>';
        }
        wp_reset_postdata();
        
        echo '<div class="pagination" id="my_leagues_pagination"></div>';
    }
    echo '</div>'; // End panel 2
    
    // Panel 3: Leagues with Selected Teams (only for logged in users)
    echo '<div class="df-leagues-panel" id="df-panel-selected">';
    
    if (!is_user_logged_in()) {
        echo '<p>You need to be logged in to view leagues with your selected teams.</p>';
    } else {
        $leagues_with_selected_teams = [];
        $all_leagues = new WP_Query(array(
            'post_type' => 'dfsoccer_league',
            'posts_per_page' => -1
        ));
        
        while ($all_leagues->have_posts()) {
            $all_leagues->the_post();
            $league_id = get_the_ID();
            $selected_players = get_user_meta($current_user_id, 'dfsoccer_selected_players_' . $league_id, true);
            if (!empty($selected_players)) {
                $leagues_with_selected_teams[] = array(
                    'id' => $league_id,
                    'title' => get_the_title()
                );
            }
        }
        
        if (!empty($leagues_with_selected_teams)) {
            echo '<ul id="selected_teams_list">';
            foreach ($leagues_with_selected_teams as $league) {
                echo display_league_with_participants($league['id'], $league['title']);
            }
            echo '</ul>';
        } else {
            echo '<p>No leagues where you have selected teams.</p>';
        }
        
        wp_reset_postdata();
    }
    
    echo '<div class="pagination" id="selected_teams_pagination"></div>';
    echo '</div>'; // End panel 3
    
    echo '</div>'; // End panels
    
    // Add a legend to explain the colors - outside the tabs but inside the wrapper
    echo '<div class="league-status-legend">
        <p><span style="display: inline-block; width: 20px; height: 20px; background-color: #d4edda; margin-right: 5px;"></span> Upcoming Leagues</p>
        <p><span style="display: inline-block; width: 20px; height: 20px; background-color: #f8d7da; margin-right: 5px;"></span> Ended Leagues</p>
        <p><span style="display: inline-block; width: 20px; height: 20px; background-color: #e3f2fd; margin-right: 5px;"></span> 11 Players League</p>
        <p><span style="display: inline-block; width: 20px; height: 20px; background-color: #fff3e0; margin-right: 5px;"></span> Flash League</p>
    </div>';
    
    echo '</div>'; // End wrapper
    
    return ob_get_clean();
}
add_shortcode('df-fantasy-combined', 'dfsoccer_combined_shortcode');

/**
 * DFSoccer Points Management
 * 
 * Enhanced version with:
 * - Custom database table for points
 * - Admin ability to add/reduce points
 * - User-to-user point transfers
 * - Enhanced shortcode with transfer functionality
 */

// Don't allow direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create the custom points table on plugin activation
 */
function dfsoccer_points_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        user_id bigint(20) NOT NULL,
        points int(11) NOT NULL DEFAULT 0,
        description varchar(255) DEFAULT NULL,
        transaction_type varchar(20) NOT NULL,
        related_user_id bigint(20) DEFAULT NULL,
        date_added datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY user_id (user_id),
        KEY related_user_id (related_user_id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Check if table was created successfully
    if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
        error_log('DFSoccer Points Error: Failed to create table');
    }
}

// Add this function to manually create the table if it doesn't exist
function dfsoccer_check_table_exists() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    
    // Check if table exists
    if (!$wpdb->get_var("SHOW TABLES LIKE '$table_name'")) {
        dfsoccer_points_create_table();
        error_log('DFSoccer Points: Table created manually');
    }
}
register_activation_hook(__FILE__, 'dfsoccer_points_create_table');

/**
 * Add points to a user
 *
 * @param int $user_id The user ID
 * @param int $points Number of points to add (use negative for deduction)
 * @param string $description Optional description of the points transaction
 * @param string $transaction_type Type of transaction (add, deduct, transfer_in, transfer_out, admin_add, admin_deduct)
 * @param int|null $related_user_id Related user ID for transfers
 * @return bool|int False on failure, new total points on success
 */
function dfsoccer_add_points($user_id, $points, $description = '', $transaction_type = 'add', $related_user_id = null) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    
    // Ensure table exists
    dfsoccer_check_table_exists();
    
    // Ensure user_id is valid
    if (!get_user_by('id', $user_id)) {
        error_log('DFSoccer Points Error: Invalid user_id: ' . $user_id);
        return false;
    }
    
    // Convert related_user_id to null if empty
    $related_user_id = empty($related_user_id) ? null : intval($related_user_id);
    
    // Prepare data for insert
    $data = array(
        'user_id' => intval($user_id),
        'points' => intval($points),
        'description' => sanitize_text_field($description),
        'transaction_type' => sanitize_text_field($transaction_type),
        'related_user_id' => $related_user_id,
        'date_added' => current_time('mysql')
    );
    
    // Prepare format
    $format = array('%d', '%d', '%s', '%s', '%d', '%s');
    
    // Debug data
    error_log('DFSoccer Insert Data: ' . print_r($data, true));
    
    // Insert the transaction record
    $result = $wpdb->insert($table_name, $data, $format);
    
    if ($result === false) {
        error_log('DFSoccer Points Insert Error: ' . $wpdb->last_error);
        return false;
    }
    
    // Return the updated total
    return dfsoccer_get_points_total($user_id);
}

/**
 * Transfer points between users
 *
 * @param int $from_user_id User ID sending points
 * @param int $to_user_id User ID receiving points
 * @param int $points Number of points to transfer
 * @param string $description Optional description
 * @return bool|array False on failure, array with both users' new totals on success
 */
function dfsoccer_transfer_points($from_user_id, $to_user_id, $points, $description = '') {
    global $wpdb;
    
    // Validate points amount is positive
    $points = abs(intval($points));
    if ($points <= 0) {
        return false;
    }
    
    // Check if sender has enough points
    $sender_total = dfsoccer_get_points_total($from_user_id);
    if ($sender_total < $points) {
        return false;
    }
    
    // Start transaction
    $wpdb->query('START TRANSACTION');
    
    // Deduct points from sender
    $sender_description = empty($description) ? 
        sprintf(__('Transfer to user #%d', 'dfsoccer'), $to_user_id) : 
        $description;
    
    $sender_result = dfsoccer_add_points(
        $from_user_id, 
        -$points, 
        $sender_description, 
        'transfer_out', 
        $to_user_id
    );
    
    if ($sender_result === false) {
        $wpdb->query('ROLLBACK');
        return false;
    }
    
    // Add points to receiver
    $receiver_description = empty($description) ? 
        sprintf(__('Transfer from user #%d', 'dfsoccer'), $from_user_id) : 
        $description;
    
    $receiver_result = dfsoccer_add_points(
        $to_user_id, 
        $points, 
        $receiver_description, 
        'transfer_in', 
        $from_user_id
    );
    
    if ($receiver_result === false) {
        $wpdb->query('ROLLBACK');
        return false;
    }
    
    // Commit transaction
    $wpdb->query('COMMIT');
    
    return array(
        'from_user' => $sender_result,
        'to_user' => $receiver_result
    );
}

/**
 * Get the total points for a user
 *
 * @param int $user_id The user ID
 * @return int Total points for the user
 */
function dfsoccer_get_points_total($user_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    
    $total = $wpdb->get_var($wpdb->prepare(
        "SELECT SUM(points) FROM $table_name WHERE user_id = %d",
        $user_id
    ));
    
    return $total ? intval($total) : 0;
}

/**
 * Get points history for a user
 *
 * @param int $user_id The user ID
 * @param int $limit Optional limit on number of records to return
 * @param int $offset Optional offset for pagination
 * @return array Points history records
 */
function dfsoccer_get_points_history($user_id, $limit = 10, $offset = 0) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    
    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT p.*, 
        u.display_name as related_user_name
        FROM $table_name p
        LEFT JOIN {$wpdb->users} u ON p.related_user_id = u.ID
        WHERE p.user_id = %d 
        ORDER BY p.date_added DESC 
        LIMIT %d OFFSET %d",
        $user_id, $limit, $offset
    ));
    
    return $history;
}

/**
 * Find a user by username or email
 *
 * @param string $user_login Username or email
 * @return WP_User|false User object if found, false otherwise
 */
function dfsoccer_find_user($user_login) {
    // Try to find by username first
    $user = get_user_by('login', $user_login);
    
    // If not found, try by email
    if (!$user) {
        $user = get_user_by('email', $user_login);
    }
    
    return $user;
}

/**
 * Process point transfer form submission
 */
function dfsoccer_process_transfer_form() {
    // Check if table exists and create if needed
    dfsoccer_check_table_exists();

    // Check if our form was submitted
    if (!isset($_POST['dfsoccer_transfer_points_submit'])) {
        return;
    }
    
    // Verify nonce
    if (!isset($_POST['dfsoccer_transfer_nonce']) || 
        !wp_verify_nonce($_POST['dfsoccer_transfer_nonce'], 'dfsoccer_transfer_points')) {
        wp_die(__('Security check failed. Please try again.', 'dfsoccer'));
    }
    
    $current_user_id = get_current_user_id();
    if (!$current_user_id) {
        return;
    }
    
    // Process user-to-user transfer
    if (isset($_POST['dfsoccer_recipient']) && isset($_POST['dfsoccer_points_amount'])) {
        $recipient = sanitize_text_field($_POST['dfsoccer_recipient']);
        $points = intval($_POST['dfsoccer_points_amount']);
        $description = isset($_POST['dfsoccer_transfer_description']) ? 
            sanitize_text_field($_POST['dfsoccer_transfer_description']) : '';
        
        // Find the recipient user
        $recipient_user = dfsoccer_find_user($recipient);
        
        if (!$recipient_user) {
            // Set error message
            set_transient('dfsoccer_points_message', array(
                'type' => 'error',
                'message' => __('Recipient not found. Please check the username or email.', 'dfsoccer')
            ), 60);
            return;
        }
        
        // Don't allow transfer to self
        if ($recipient_user->ID === $current_user_id) {
            set_transient('dfsoccer_points_message', array(
                'type' => 'error',
                'message' => __('You cannot transfer points to yourself.', 'dfsoccer')
            ), 60);
            return;
        }
        
        // Attempt the transfer
        $result = dfsoccer_transfer_points(
            $current_user_id, 
            $recipient_user->ID, 
            $points, 
            $description
        );
        
        if ($result === false) {
            set_transient('dfsoccer_points_message', array(
                'type' => 'error',
                'message' => __('Transfer failed. Please check that you have enough points.', 'dfsoccer')
            ), 60);
        } else {
            set_transient('dfsoccer_points_message', array(
                'type' => 'success',
                'message' => sprintf(
                    __('Successfully transferred %d points to %s.', 'dfsoccer'),
                    $points,
                    $recipient_user->display_name
                )
            ), 60);
        }
    }
    
    // Process admin add/reduce points
    if (current_user_can('manage_options') && 
        isset($_POST['dfsoccer_admin_recipient']) && 
        isset($_POST['dfsoccer_admin_points_amount'])) {
        
        $recipient = sanitize_text_field($_POST['dfsoccer_admin_recipient']);
        $points = intval($_POST['dfsoccer_admin_points_amount']);
        $action = isset($_POST['dfsoccer_admin_action']) ? 
            sanitize_text_field($_POST['dfsoccer_admin_action']) : 'add';
        $description = isset($_POST['dfsoccer_admin_description']) ? 
            sanitize_text_field($_POST['dfsoccer_admin_description']) : '';
        
        // Find the recipient user
        $recipient_user = dfsoccer_find_user($recipient);
        
        if (!$recipient_user) {
            set_transient('dfsoccer_points_message', array(
                'type' => 'error',
                'message' => __('User not found. Please check the username or email.', 'dfsoccer')
            ), 60);
            return;
        }
        
        // Determine the points value based on action
        if ($action === 'deduct') {
            $points = -$points;
            $transaction_type = 'admin_deduct';
        } else {
            $transaction_type = 'admin_add';
        }
        
        // Add the points
        // Enhanced error logging
        global $wpdb;
        
        $result = dfsoccer_add_points(
            $recipient_user->ID, 
            $points, 
            $description, 
            $transaction_type
        );
        
        if ($result === false) {
            // Get database error message
            $db_error = $wpdb->last_error;
            
            // Log error to error log
            error_log('DFSoccer Points Error: ' . $db_error);
            
            set_transient('dfsoccer_points_message', array(
                'type' => 'error',
                'message' => __('Points operation failed. Error: ', 'dfsoccer') . $db_error
            ), 60);
        } else {
            $action_text = $points >= 0 ? __('added to', 'dfsoccer') : __('deducted from', 'dfsoccer');
            set_transient('dfsoccer_points_message', array(
                'type' => 'success',
                'message' => sprintf(
                    __('%d points %s %s. New total: %d', 'dfsoccer'),
                    abs($points),
                    $action_text,
                    $recipient_user->display_name,
                    $result
                )
            ), 60);
        }
    }
    
    // Redirect to prevent form resubmission
    wp_redirect(remove_query_arg('message', wp_get_referer()));
    exit;
}
add_action('init', 'dfsoccer_process_transfer_form');

/**
 * Display notification messages
 */
function dfsoccer_display_points_message() {
    $message = get_transient('dfsoccer_points_message');
    
    if ($message) {
        // Display message and delete transient
        echo '<div class="dfsoccer-message dfsoccer-message-' . esc_attr($message['type']) . '">';
        echo '<p>' . esc_html($message['message']) . '</p>';
        echo '</div>';
        
        delete_transient('dfsoccer_points_message');
    }
}

/**
 * Register shortcode to display user points and transfer form
 */
function dfsoccer_points_shortcode($atts) {
    $atts = shortcode_atts(
        array(
            'show_history' => 'no',
            'history_limit' => 5,
            'show_transfer' => 'yes',
        ),
        $atts,
        'dfsoccer_points'
    );
    
    // Check if user is logged in
    if (!is_user_logged_in()) {
        return '<p>' . __('Please log in to view your points.', 'dfsoccer') . '</p>';
    }
    
    $user_id = get_current_user_id();
    $total_points = dfsoccer_get_points_total($user_id);
    $output = '<div class="dfsoccer-points-display">';
    
    // Display any messages
    ob_start();
    dfsoccer_display_points_message();
    $output .= ob_get_clean();
    
    $output .= '<p class="dfsoccer-points-total">' . sprintf(__('Your current points: <strong>%d</strong>', 'dfsoccer'), $total_points) . '</p>';
    
    // Show transfer form if requested
    if ($atts['show_transfer'] === 'yes') {
        $output .= dfsoccer_get_transfer_form();
        
        // Add admin form if user is admin
        if (current_user_can('manage_options')) {
            $output .= dfsoccer_get_admin_points_form();
        }
    }
    
    // Show history if requested
    if ($atts['show_history'] === 'yes') {
        $history = dfsoccer_get_points_history($user_id, intval($atts['history_limit']));
        
        if (!empty($history)) {
            $output .= '<h4>' . __('Points History', 'dfsoccer') . '</h4>';
            $output .= '<table class="dfsoccer-points-history">';
            $output .= '<thead><tr>';
            $output .= '<th>' . __('Date', 'dfsoccer') . '</th>';
            $output .= '<th>' . __('Points', 'dfsoccer') . '</th>';
            $output .= '<th>' . __('Type', 'dfsoccer') . '</th>';
            $output .= '<th class="description-column">' . __('Description', 'dfsoccer') . '</th>';
            $output .= '<th>' . __('Related User', 'dfsoccer') . '</th>';
            $output .= '</tr></thead>';
            $output .= '<tbody>';
            
            foreach ($history as $record) {
                $date = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($record->date_added));
                
                // Format point value based on transaction type
                $point_value = $record->points;
                if (in_array($record->transaction_type, array('transfer_out', 'admin_deduct'))) {
                    $point_value = '-' . abs($point_value);
                } elseif (in_array($record->transaction_type, array('transfer_in', 'admin_add', 'add'))) {
                    $point_value = '+' . abs($point_value);
                }
                
                // Get readable transaction type
                $transaction_types = array(
                    'add' => __('Add', 'dfsoccer'),
                    'deduct' => __('Deduct', 'dfsoccer'),
                    'transfer_in' => __('Transfer In', 'dfsoccer'),
                    'transfer_out' => __('Transfer Out', 'dfsoccer'),
                    'admin_add' => __('Admin Add', 'dfsoccer'),
                    'admin_deduct' => __('Admin Deduct', 'dfsoccer')
                );
                
                $transaction_type = isset($transaction_types[$record->transaction_type]) ? 
                    $transaction_types[$record->transaction_type] : 
                    $record->transaction_type;
                
                // Add data attributes for responsive design
                $output .= '<tr>';
                $output .= '<td data-label="' . __('Date', 'dfsoccer') . '">' . esc_html($date) . '</td>';
                $output .= '<td data-label="' . __('Points', 'dfsoccer') . '" data-value="' . esc_attr($point_value) . '">' . esc_html($point_value) . '</td>';
                $output .= '<td data-label="' . __('Type', 'dfsoccer') . '">' . esc_html($transaction_type) . '</td>';
                $output .= '<td data-label="' . __('Description', 'dfsoccer') . '" class="description-cell">' . esc_html($record->description) . '</td>';
                $output .= '<td data-label="' . __('Related User', 'dfsoccer') . '">' . ($record->related_user_name ? esc_html($record->related_user_name) : '-') . '</td>';
                $output .= '</tr>';
            }
            
            $output .= '</tbody></table>';
        } else {
            $output .= '<p>' . __('No points history available.', 'dfsoccer') . '</p>';
        }
    }
    
    $output .= '</div>';
    
    // Add CSS inline or enqueue it
    $output .= '<style>
        .dfsoccer-points-display {
            max-width: 100%;
            margin: 20px 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        }
        
        .dfsoccer-points-total {
            font-size: 18px;
            padding: 15px;
            background-color: #f0f8ff;
            border-left: 4px solid #4a90e2;
            margin-bottom: 20px;
            border-radius: 3px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .dfsoccer-points-display h4 {
            margin-top: 30px;
            margin-bottom: 15px;
            font-size: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid #eaeaea;
            color: #333;
        }
        
        /* Table styles */
        .dfsoccer-points-history {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            border-radius: 5px;
            overflow: hidden;
            table-layout: fixed; /* This helps with column width control */
        }
        
        .dfsoccer-points-history thead {
            background-color: #4a90e2;
            color: white;
        }
        
        .dfsoccer-points-history th {
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        /* Make the description column wider */
        .dfsoccer-points-history th.description-column {
            width: 35%;
        }
        
        .dfsoccer-points-history tbody tr {
            border-bottom: 1px solid #eaeaea;
            transition: background-color 0.2s ease;
        }
        
        .dfsoccer-points-history tbody tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        
        .dfsoccer-points-history tbody tr:hover {
            background-color: #f0f8ff;
        }
        
        .dfsoccer-points-history td {
            padding: 10px 15px;
            vertical-align: middle;
        }
        
        /* Description cell with word wrap */
        .dfsoccer-points-history td.description-cell {
            word-wrap: break-word; /* Legacy */
            overflow-wrap: break-word;
            word-break: break-word; /* For wider browser support */
            hyphens: auto;
        }
        
        /* Points styling */
        .dfsoccer-points-history td:nth-child(2) {
            font-weight: bold;
        }
        
        /* Add color for positive and negative points */
        .dfsoccer-points-history td[data-value^="+"] {
            color: #2ea44f; /* Green for positive values */
        }
        
        .dfsoccer-points-history td[data-value^="-"] {
            color: #e53935; /* Red for negative values */
        }
        
        /* Transaction type styling */
        .dfsoccer-points-history td:nth-child(3) {
            text-transform: capitalize;
        }
        
        /* Responsive styles */
        @media screen and (max-width: 768px) {
            .dfsoccer-points-history {
                display: block;
                table-layout: auto;
            }
            
            .dfsoccer-points-history thead,
            .dfsoccer-points-history tbody,
            .dfsoccer-points-history tr {
                display: block;
                width: 100%;
            }
            
            .dfsoccer-points-history thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }
            
            .dfsoccer-points-history tr {
                border: 1px solid #ddd;
                margin-bottom: 15px;
                border-radius: 5px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            
            .dfsoccer-points-history td {
                display: flex;
                border: none;
                border-bottom: 1px solid #eee;
                position: relative;
                padding-left: 50%;
                text-align: left;
                min-height: 30px;
                align-items: center;
                width: 100%;
                box-sizing: border-box;
            }
            
            .dfsoccer-points-history td:last-child {
                border-bottom: none;
            }
            
            .dfsoccer-points-history td:before {
                position: absolute;
                left: 10px;
                width: 45%;
                padding-right: 10px;
                white-space: nowrap;
                font-weight: bold;
                content: attr(data-label);
            }
            
            /* Enhanced description cell for mobile */
            .dfsoccer-points-history td.description-cell {
                white-space: normal;
                min-height: 60px; /* Give more space for description on mobile */
                align-items: flex-start;
                padding-top: 12px;
            }
            
            .dfsoccer-points-history td.description-cell:before {
                padding-top: 2px; /* Align label with text start */
            }
        }
        
        /* Message styles */
        .dfsoccer-message {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid;
        }
        
        .dfsoccer-message.success {
            background-color: #e8f5e9;
            border-color: #4caf50;
            color: #2e7d32;
        }
        
        .dfsoccer-message.error {
            background-color: #ffebee;
            border-color: #ef5350;
            color: #c62828;
        }
        
        .dfsoccer-message.info {
            background-color: #e3f2fd;
            border-color: #2196f3;
            color: #1565c0;
        }
    </style>';
    
    return $output;
}
add_shortcode('dfsoccer_points', 'dfsoccer_points_shortcode');

/**
 * Generate the transfer points form
 *
 * @return string HTML form
 */
function dfsoccer_get_transfer_form() {
    $output = '<div class="dfsoccer-transfer-form-container">';
    $output .= '<h4>' . __('Transfer Points', 'dfsoccer') . '</h4>';
    $output .= '<form method="post" class="dfsoccer-transfer-form">';
    
    // Add nonce for security
    $output .= wp_nonce_field('dfsoccer_transfer_points', 'dfsoccer_transfer_nonce', true, false);
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_recipient">' . __('Recipient (Username or Email):', 'dfsoccer') . '</label>';
    $output .= '<input type="text" id="dfsoccer_recipient" name="dfsoccer_recipient" required />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_points_amount">' . __('Points Amount:', 'dfsoccer') . '</label>';
    $output .= '<input type="number" id="dfsoccer_points_amount" name="dfsoccer_points_amount" min="1" required />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_transfer_description">' . __('Description (Optional):', 'dfsoccer') . '</label>';
    $output .= '<input type="text" id="dfsoccer_transfer_description" name="dfsoccer_transfer_description" />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<button type="submit" name="dfsoccer_transfer_points_submit" class="dfsoccer-submit-button">' . __('Transfer Points', 'dfsoccer') . '</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Generate the admin points management form
 *
 * @return string HTML form
 */
function dfsoccer_get_admin_points_form() {
    $output = '<div class="dfsoccer-admin-form-container">';
    $output .= '<h4>' . __('Admin Points Management', 'dfsoccer') . '</h4>';
    $output .= '<form method="post" class="dfsoccer-admin-form">';
    
    // Add nonce for security
    $output .= wp_nonce_field('dfsoccer_transfer_points', 'dfsoccer_transfer_nonce', true, false);
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_admin_recipient">' . __('User (Username or Email):', 'dfsoccer') . '</label>';
    $output .= '<input type="text" id="dfsoccer_admin_recipient" name="dfsoccer_admin_recipient" required />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_admin_points_amount">' . __('Points Amount:', 'dfsoccer') . '</label>';
    $output .= '<input type="number" id="dfsoccer_admin_points_amount" name="dfsoccer_admin_points_amount" min="1" required />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_admin_action">' . __('Action:', 'dfsoccer') . '</label>';
    $output .= '<select id="dfsoccer_admin_action" name="dfsoccer_admin_action">';
    $output .= '<option value="add">' . __('Add Points', 'dfsoccer') . '</option>';
    $output .= '<option value="deduct">' . __('Deduct Points', 'dfsoccer') . '</option>';
    $output .= '</select>';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<label for="dfsoccer_admin_description">' . __('Description (Optional):', 'dfsoccer') . '</label>';
    $output .= '<input type="text" id="dfsoccer_admin_description" name="dfsoccer_admin_description" />';
    $output .= '</div>';
    
    $output .= '<div class="dfsoccer-form-row">';
    $output .= '<button type="submit" name="dfsoccer_transfer_points_submit" class="dfsoccer-submit-button">' . __('Update Points', 'dfsoccer') . '</button>';
    $output .= '</div>';
    
    $output .= '</form>';
    $output .= '</div>';
    
    return $output;
}

/**
 * Add CSS styles for the points system
 */
function dfsoccer_points_styles() {
    echo '<style>
    .dfsoccer-points-display {
        margin: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }
    .dfsoccer-points-total {
        font-size: 18px;
        margin-bottom: 15px;
        padding: 10px;
        background-color: #f8f8f8;
        border-radius: 4px;
    }
    .dfsoccer-points-history {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 20px;
    }
    .dfsoccer-points-history th,
    .dfsoccer-points-history td {
        padding: 10px;
        border: 1px solid #ddd;
        text-align: left;
    }
    .dfsoccer-points-history th {
        background-color: #f2f2f2;
        font-weight: bold;
    }
    .dfsoccer-points-history tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .dfsoccer-message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    .dfsoccer-message-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .dfsoccer-message-error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .dfsoccer-transfer-form-container,
    .dfsoccer-admin-form-container {
        background: #f9f9f9;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        border: 1px solid #e5e5e5;
    }
    .dfsoccer-admin-form-container {
        background: #f5f5f5;
        border-color: #d5d5d5;
    }
    .dfsoccer-form-row {
        margin-bottom: 15px;
    }
    .dfsoccer-form-row label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .dfsoccer-form-row input,
    .dfsoccer-form-row select {
        width: 100%;
        padding: 8px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .dfsoccer-submit-button {
        background-color: #0073aa;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
    }
    .dfsoccer-submit-button:hover {
        background-color: #005177;
    }
    </style>';
}
add_action('wp_head', 'dfsoccer_points_styles');

/**
 * Example function to add points when a user performs an action
 * You can call this function from your DFSoccer plugin when needed
 *
 * @param int $user_id The user ID
 * @param string $action The action performed
 */
function dfsoccer_award_points_for_action($user_id, $action) {
    // Define point values for different actions
    $point_values = array(
        'login' => 5,
        'match_prediction' => 10,
        'correct_prediction' => 25,
        'share_content' => 15,
        'complete_profile' => 20
    );
    
    // Check if the action exists in our point values array
    if (isset($point_values[$action])) {
        $points = $point_values[$action];
        $description = sprintf(__('Points for %s', 'dfsoccer'), $action);
        dfsoccer_add_points($user_id, $points, $description, 'add');
    }
}

function dfsoccer_append_user_voting($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {
        $league_id = get_the_ID(); // Get the current league ID
        
        // Get the post author (league creator)
        $league_post = get_post($league_id);
        $post_author_id = $league_post ? $league_post->post_author : 0;
        
        if ($post_author_id) {
            // Append the user voting shortcode for the post author
            $voting_shortcode = '[user_voting user_id="' . $post_author_id . '" display_mode="full"]';
            $voting_form = do_shortcode($voting_shortcode);
            $content .= '<div class="league-creator-voting-section">';
            $content .= '<h3>Rate the League Creator</h3>';
            $content .= $voting_form;
            $content .= '</div>';
        }
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_user_voting');

/**
 * Append award points shortcode to league posts
 */
function dfsoccer_append_award_points($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {
        $league_id = get_the_ID(); // Get the current league ID
        
        // Check if the current user is the author of the league post or an admin
        $league_post = get_post($league_id);
        $is_admin = current_user_can('manage_options');
        
        if ($league_post && ($league_post->post_author == get_current_user_id() || $is_admin)) {
            // Append the award points form
            $award_shortcode = '[award_league_points league_id="' . $league_id . '" points="1" description="First place in league"]';
            $award_form = do_shortcode($award_shortcode);
            $content .= '<div class="league-award-points-section">';
            $content .= '<h3>Award Points to First Place</h3>';
            $content .= $award_form;
            $content .= '</div>';
        }
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_award_points');



/**
 * Award points to league teams based on different distribution methods
 * 
 * Usage: [award_league_points league_id="123" points="1" description="League points"]
 */
function dfsoccer_award_league_points_shortcode($atts) {
    // Extract shortcode attributes with defaults
    $atts = shortcode_atts(array(
        'league_id' => 0,
        'points' => 1,
        'description' => 'League points'
    ), $atts, 'award_league_points');
    
    $league_id = intval($atts['league_id']);
    $points_to_award = intval($atts['points']);
    $description = sanitize_text_field($atts['description']);
    
    if (!$league_id) {
        return 'Invalid League ID';
    }
    
    // Get the points distribution method from league meta
    $distribution_method = get_post_meta($league_id, 'dfsoccer_points_distribution_method', true);
    if (empty($distribution_method)) {
        $distribution_method = 'winner_takes_all'; // Default method
    }
    
    // Get fixed points values if method is 'fixed'
    $fixed_points = array();
    if ($distribution_method == 'fixed') {
        $fixed_points = array(
            1 => intval(get_post_meta($league_id, 'dfsoccer_fixed_points_first', true)),
            2 => intval(get_post_meta($league_id, 'dfsoccer_fixed_points_second', true)),
            3 => intval(get_post_meta($league_id, 'dfsoccer_fixed_points_third', true))
        );
    }
    
    // Initialize output
    ob_start();
    
    // Check if points have already been awarded for this league
    $points_awarded = get_post_meta($league_id, 'dfsoccer_league_points_awarded', true);
    
    // Check if form was submitted
    if (isset($_POST['dfsoccer_award_points_submit']) && 
        isset($_POST['dfsoccer_award_nonce']) && 
        wp_verify_nonce($_POST['dfsoccer_award_nonce'], 'dfsoccer_award_points_' . $league_id)) {
        
        if ($points_awarded) {
            echo '<div class="dfsoccer-message dfsoccer-message-error">
                <p>Points have already been awarded for this league.</p>
            </div>';
        } else {
            // Check if league is from API
            $is_api_league = false;
            if (function_exists('is_league_from_api')) {
                $league_source = is_league_from_api($league_id);
                $is_api_league = $league_source['from_api'] && !empty($league_source['source_league_id']);
            }
            
            // Get teams data based on league type
            $teams = array();
            
            if ($is_api_league) {
                // Get teams data from API-based league
                $teams = dfsoccer_get_api_teams_data($league_id);
            } else {
                // Use the original method to get teams data
                $teams = dfsoccer_get_teams_data($league_id);
            }
            
            if (empty($teams)) {
                echo '<div class="dfsoccer-message dfsoccer-message-error">
                    <p>No teams found for this league. Cannot award points.</p>
                </div>';
            } else {
                // Process the award using the original function
                $award_result = dfsoccer_process_award_points($league_id, $points_to_award, $description, $distribution_method, $fixed_points, $teams);
                
                // Display the result message
                echo $award_result;
            }
        }
    }
    
    // Show the button form and distribution method info if points haven't been awarded yet
    if (!$points_awarded) {
        // Display distribution method information
        echo '<div class="dfsoccer-distribution-info">';
        echo '<p><strong>Distribution Method:</strong> ' . esc_html(dfsoccer_get_distribution_method_name($distribution_method)) . '</p>';
        
        if ($distribution_method == 'fixed') {
            echo '<p>Points distribution: 1st Place: ' . $fixed_points[1] . ', 2nd Place: ' . $fixed_points[2] . ', 3rd Place: ' . $fixed_points[3] . '</p>';
        } elseif ($distribution_method == 'tiered') {
            echo '<p>Points will be distributed based on number of participants (minimum 10 for tiered distribution)</p>';
        }
        
        echo '</div>';
        ?>
        <div class="dfsoccer-award-points-form">
            <form method="post">
                <?php wp_nonce_field('dfsoccer_award_points_' . $league_id, 'dfsoccer_award_nonce'); ?>
                <input type="hidden" name="league_id" value="<?php echo esc_attr($league_id); ?>">
                <input type="hidden" name="points" value="<?php echo esc_attr($points_to_award); ?>">
                <input type="hidden" name="description" value="<?php echo esc_attr($description); ?>">
                <button type="submit" name="dfsoccer_award_points_submit" class="dfsoccer-submit-button">
                    Award Points Now
                </button>
            </form>
        </div>
        <?php
    } else {
        // Show information about awarded points
        $winner_info = get_post_meta($league_id, 'dfsoccer_league_points_winners', true);
        if (!empty($winner_info)) {
            echo '<div class="dfsoccer-message dfsoccer-message-info">';
            echo '<p><strong>Points have been awarded for this league</strong></p>';
            echo '<p>Distribution Method: ' . esc_html(dfsoccer_get_distribution_method_name($winner_info['method'])) . '</p>';
            
            foreach ($winner_info['winners'] as $position => $winner) {
                echo '<p>' . esc_html($position) . ': ' . esc_html($winner['user_name']) . 
                     ' (' . esc_html($winner['points_awarded']) . ' points)</p>';
            }
            
            echo '<p>Awarded on: ' . esc_html($winner_info['awarded_on']) . '</p>';
            echo '</div>';
        } else {
            echo '<div class="dfsoccer-message dfsoccer-message-info">
                <p>Points have already been awarded for this league.</p>
            </div>';
        }
    }
    
    return ob_get_clean();
}
add_shortcode('award_league_points', 'dfsoccer_award_league_points_shortcode');

/**
 * Get teams data for API-based leagues
 */
function dfsoccer_get_api_teams_data($league_id) {
    // Get match results
    $match_results_json = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $match_results = !empty($match_results_json) ? json_decode($match_results_json, true) : array();
    
    // Fetch users who have participated in this league
    $args = array(
        'meta_key'   => 'dfsoccer_selected_players_' . $league_id,
        'meta_value' => '', // Any non-empty value
        'meta_compare' => '!=',
        'fields' => 'all_with_meta'
    );
    $users = get_users($args);
    
    if (empty($users)) {
        return array();
    }
    
    $teams = array();
    
    // Process each user's team
    foreach ($users as $user) {
        $selected_players = get_user_meta($user->ID, 'dfsoccer_selected_players_' . $league_id, true);
        
        if (!empty($selected_players) && is_array($selected_players)) {
            $team_total_points = 0;
            
            // Process each player
            foreach ($selected_players as $player_id) {
                // Initialize points to zero by default
                $total_points = 0;
                
                // Check if we have valid match results to use
                if (isset($match_results[$player_id]) && isset($match_results[$player_id]['total_points'])) {
                    // Use existing calculated points
                    $total_points = floatval($match_results[$player_id]['total_points']);
                } else if (function_exists('calculate_points_from_api') && isset($match_results[$player_id])) {
                    // Calculate points if not stored yet
                    $total_points = floatval(calculate_points_from_api($player_id, $match_results[$player_id], $league_id));
                }
                
                // Add to team total
                $team_total_points += $total_points;
            }
            
            // Add team to the array in the format expected by the award function
            $teams[] = array(
                'user_id' => $user->ID,
                'user_name' => $user->display_name,
                'total_points' => $team_total_points
            );
        }
    }
    
    // Sort teams by total points in descending order
    usort($teams, function($a, $b) {
        return $b['total_points'] <=> $a['total_points'];
    });
    
    return $teams;
}

/**
 * Get teams data for non-API leagues
 *
 * @param int $league_id The ID of the league
 * @return array Array of teams with user_id, user_name, and total_points
 */
function dfsoccer_get_teams_data($league_id) {
    // Fetch users who have participated in this league
    $args = array(
        'meta_key'   => 'dfsoccer_selected_players_' . $league_id,
        'meta_value' => '', // Any non-empty value
        'meta_compare' => '!=',
        'fields' => 'all_with_meta'
    );
    $users = get_users($args);
    
    if (empty($users)) {
        return array();
    }
    
    // Get match results for the league (same as display_teams does)
    $match_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $results_array = json_decode($match_results, true);
    
    $teams = array();
    
    // Process each user's team
    foreach ($users as $user) {
        $selected_players = get_user_meta($user->ID, 'dfsoccer_selected_players_' . $league_id, true);
        
        if (!empty($selected_players) && is_array($selected_players)) {
            $team_total_points = 0;
            $player_details = array();
            
            // Process each player (same logic as display_teams)
            foreach ($selected_players as $player_id) {
                // Get player name
                $player_name = get_the_title($player_id);
                
                // Initialize points to zero by default
                $total_points = 0;
                
                // Check if we have valid match results to use (same as display_teams)
                if (isset($results_array) && is_array($results_array) && 
                    isset($results_array[$player_id]) && 
                    isset($results_array[$player_id]['total_points'])) {
                    // Use actual results if available
                    $total_points = $results_array[$player_id]['total_points'];
                }
                
                // Add to team total
                $team_total_points += floatval($total_points);
                
                // Store player details for reference
                $player_details[] = array(
                    'id' => $player_id,
                    'name' => $player_name,
                    'points' => $total_points
                );
            }
            
            // Add team to the array
            $teams[] = array(
                'user_id' => $user->ID,
                'user_name' => $user->display_name . "'s Team",
                'total_points' => $team_total_points,
                'players' => $player_details
            );
        }
    }
    
    // Sort teams by total points in descending order (same as display_teams)
    usort($teams, function($a, $b) {
        return $b['total_points'] <=> $a['total_points'];
    });
    
    return $teams;
}
/**
 * Get user-friendly name for distribution method
 */
function dfsoccer_get_distribution_method_name($method) {
    $methods = array(
        'winner_takes_all' => 'Winner Takes All',
        'fixed' => 'Fixed Points Distribution',
        'tiered' => 'Tiered Distribution'
    );
    
    return isset($methods[$method]) ? $methods[$method] : 'Unknown';
}

/**
 * Process the award points request
 */
function dfsoccer_process_award_points($league_id, $points_to_award, $description, $distribution_method, $fixed_points = array(), $provided_teams = null) {
    // Check if points have already been awarded to prevent double processing
    $points_awarded = get_post_meta($league_id, 'dfsoccer_league_points_awarded', true);
    if ($points_awarded) {
        return '<div class="dfsoccer-message dfsoccer-message-error">Points have already been awarded for this league.</div>';
    }
    
    // If teams are provided (for API leagues), use them directly
    if (is_array($provided_teams) && !empty($provided_teams)) {
        $teams = $provided_teams;
    } else {
        // Use the dedicated function for ordinary leagues
        $teams = dfsoccer_get_teams_data($league_id);
    }
    
    // If there are no teams, return error
    if (empty($teams)) {
        return '<div class="dfsoccer-message dfsoccer-message-error">No eligible teams found.</div>';
    }
    
    $total_participants = count($teams);
    
    // Check if we need to switch from tiered to winner_takes_all due to participant count
    if ($distribution_method == 'tiered' && $total_participants < 10) {
        $distribution_method = 'winner_takes_all';
    }
    
    // Calculate points to award based on distribution method
    $points_distribution = array();
    $winners_info = array(
        'method' => $distribution_method,
        'winners' => array(),
        'awarded_on' => current_time('mysql')
    );
    
    $award_message = '<div class="dfsoccer-message dfsoccer-message-success"><p>';
    
    // Winner Takes All method
    if ($distribution_method == 'winner_takes_all') {
        $winner_points = $points_to_award * $total_participants;
        $top_team = $teams[0];
        
        // Award description for winner takes all
        $award_description = sprintf(
            '%s - Winner Takes All (League: %s)',
            $description,
            get_the_title($league_id)
        );
        
        $result = dfsoccer_add_points(
            $top_team['user_id'],
            $winner_points,
            $award_description,
            'award'
        );
        
        if ($result !== false) {
            $winners_info['winners']["1st Place"] = array(
                'user_id' => $top_team['user_id'],
                'user_name' => $top_team['user_name'],
                'points_awarded' => $winner_points,
                'total_points' => $result
            );
            
            $award_message .= sprintf(
                '%d points awarded to %s for being in first place in %s. Their new total is %d points.',
                $winner_points,
                esc_html($top_team['user_name']),
                esc_html(get_the_title($league_id)),
                $result
            );
        } else {
            return '<div class="dfsoccer-message dfsoccer-message-error">
                <p>Failed to award points. Please check the error logs.</p>
            </div>';
        }
    } 
    // Fixed Points method
    elseif ($distribution_method == 'fixed') {
        $award_message = '<div class="dfsoccer-message dfsoccer-message-success">';
        
        // Award fixed points for top 3 positions
        for ($position = 1; $position <= 3; $position++) {
            if (isset($teams[$position-1]) && $fixed_points[$position] > 0) {
                $team = $teams[$position-1];
                
                $position_text = '';
                switch ($position) {
                    case 1: $position_text = 'first'; break;
                    case 2: $position_text = 'second'; break;
                    case 3: $position_text = 'third'; break;
                }
                
                // Award description for fixed distribution
                $award_description = sprintf(
                    '%s - %s place (League: %s)',
                    $description,
                    ucfirst($position_text),
                    get_the_title($league_id)
                );
                
                $result = dfsoccer_add_points(
                    $team['user_id'],
                    $fixed_points[$position],
                    $award_description,
                    'award'
                );
                
                if ($result !== false) {
                    $winners_info['winners'][$position . "st Place"] = array(
                        'user_id' => $team['user_id'],
                        'user_name' => $team['user_name'],
                        'points_awarded' => $fixed_points[$position],
                        'total_points' => $result
                    );
                    
                    $award_message .= '<p>' . sprintf(
                        '%d points awarded to %s for %s place in %s. Their new total is %d points.',
                        $fixed_points[$position],
                        esc_html($team['user_name']),
                        $position_text,
                        esc_html(get_the_title($league_id)),
                        $result
                    ) . '</p>';
                } else {
                    return '<div class="dfsoccer-message dfsoccer-message-error">
                        <p>Failed to award points. Please check the error logs.</p>
                    </div>';
                }
            }
        }
    } 
    // Tiered method (with at least 10 participants)
    elseif ($distribution_method == 'tiered') {
        // Calculate tiered points (cannot exceed participant count)
        $first_place_points = min($total_participants, 5);
        $second_place_points = min($total_participants - 1, 3);
        $third_place_points = min($total_participants - 2, 1);
        
        $tiered_points = array(
            1 => $first_place_points,
            2 => $second_place_points,
            3 => $third_place_points
        );
        
        $award_message = '<div class="dfsoccer-message dfsoccer-message-success">';
        
        // Award tiered points for top 3 positions
        for ($position = 1; $position <= 3; $position++) {
            if (isset($teams[$position-1]) && $tiered_points[$position] > 0) {
                $team = $teams[$position-1];
                
                $position_text = '';
                switch ($position) {
                    case 1: $position_text = 'first'; break;
                    case 2: $position_text = 'second'; break;
                    case 3: $position_text = 'third'; break;
                }
                
                // Award description for tiered distribution
                $award_description = sprintf(
                    '%s - %s place tiered (League: %s)',
                    $description,
                    ucfirst($position_text),
                    get_the_title($league_id)
                );
                
                $result = dfsoccer_add_points(
                    $team['user_id'],
                    $tiered_points[$position],
                    $award_description,
                    'award'
                );
                
                if ($result !== false) {
                    $winners_info['winners'][$position . "st Place"] = array(
                        'user_id' => $team['user_id'],
                        'user_name' => $team['user_name'],
                        'points_awarded' => $tiered_points[$position],
                        'total_points' => $result
                    );
                    
                    $award_message .= '<p>' . sprintf(
                        '%d points awarded to %s for %s place in %s. Their new total is %d points.',
                        $tiered_points[$position],
                        esc_html($team['user_name']),
                        $position_text,
                        esc_html(get_the_title($league_id)),
                        $result
                    ) . '</p>';
                } else {
                    return '<div class="dfsoccer-message dfsoccer-message-error">
                        <p>Failed to award points. Please check the error logs.</p>
                    </div>';
                }
            }
        }
    }
    
    // Mark this league as having had points awarded
    update_post_meta($league_id, 'dfsoccer_league_points_awarded', time());
    
    // Store the winner information for future reference
    update_post_meta($league_id, 'dfsoccer_league_points_winners', $winners_info);
    
    $award_message .= '</p></div>';
    return $award_message;
}


/**
 * Add the league points distribution metabox to league edit screen
 */
function dfsoccer_add_league_points_metabox() {
    add_meta_box(
        'dfsoccer_league_points_metabox',
        'League Points Distribution',
        'dfsoccer_league_points_metabox_callback',
        'dfsoccer_league', // Assuming this is your league post type
        'side'
    );
}
add_action('add_meta_boxes', 'dfsoccer_add_league_points_metabox');

/**
 * Render the league points distribution metabox
 */
function dfsoccer_league_points_metabox_callback($post) {
    // Add nonce for security
    wp_nonce_field('dfsoccer_league_points_metabox', 'dfsoccer_league_points_nonce');
    
    // Get current values
    $distribution_method = get_post_meta($post->ID, 'dfsoccer_points_distribution_method', true);
    if (empty($distribution_method)) {
        $distribution_method = 'winner_takes_all'; // Default
    }
    
    $fixed_points = array(
        1 => intval(get_post_meta($post->ID, 'dfsoccer_fixed_points_first', true)),
        2 => intval(get_post_meta($post->ID, 'dfsoccer_fixed_points_second', true)),
        3 => intval(get_post_meta($post->ID, 'dfsoccer_fixed_points_third', true))
    );
    
    // Check if points already awarded
    $points_awarded = get_post_meta($post->ID, 'dfsoccer_league_points_awarded', true);
    
    if ($points_awarded) {
        $winner_info = get_post_meta($post->ID, 'dfsoccer_league_points_winners', true);
        echo '<p><strong>Points have already been awarded</strong></p>';
        
        if (!empty($winner_info)) {
            echo '<p>Distribution Method: ' . esc_html(dfsoccer_get_distribution_method_name($winner_info['method'])) . '</p>';
            
            foreach ($winner_info['winners'] as $position => $winner) {
                echo '<p>' . esc_html($position) . ': ' . esc_html($winner['user_name']) . 
                     ' (' . esc_html($winner['points_awarded']) . ' points)</p>';
            }
            
            // Add reset button
            ?>
            <form method="post" action="">
                <?php wp_nonce_field('dfsoccer_reset_points_' . $post->ID, 'dfsoccer_reset_points_nonce'); ?>
                <input type="submit" name="dfsoccer_reset_points" class="button button-secondary" 
                       value="Reset Points Status" onclick="return confirm('Are you sure? This will allow points to be awarded again.');">
            </form>
            <?php
        }
        
        return;
    }
    
    // Distribution method selector
    ?>
    <p>
        <label for="dfsoccer_points_distribution_method"><strong>Points Distribution Method:</strong></label><br>
        <select name="dfsoccer_points_distribution_method" id="dfsoccer_points_distribution_method">
            <option value="winner_takes_all" <?php selected($distribution_method, 'winner_takes_all'); ?>>Winner Takes All</option>
            <option value="fixed" <?php selected($distribution_method, 'fixed'); ?>>Fixed Points</option>
            <option value="tiered" <?php selected($distribution_method, 'tiered'); ?>>Tiered Distribution</option>
        </select>
    </p>
    
    <div id="dfsoccer_fixed_points_container" style="<?php echo $distribution_method == 'fixed' ? 'display:block;' : 'display:none;'; ?>">
        <p><strong>Fixed Points Distribution:</strong></p>
        <p>
            <label for="dfsoccer_fixed_points_first">1st Place:</label>
            <input type="number" name="dfsoccer_fixed_points_first" id="dfsoccer_fixed_points_first" 
                   value="<?php echo esc_attr($fixed_points[1]); ?>" min="0" style="width: 60px;">
        </p>
        <p>
            <label for="dfsoccer_fixed_points_second">2nd Place:</label>
            <input type="number" name="dfsoccer_fixed_points_second" id="dfsoccer_fixed_points_second" 
                   value="<?php echo esc_attr($fixed_points[2]); ?>" min="0" style="width: 60px;">
        </p>
        <p>
            <label for="dfsoccer_fixed_points_third">3rd Place:</label>
            <input type="number" name="dfsoccer_fixed_points_third" id="dfsoccer_fixed_points_third" 
                   value="<?php echo esc_attr($fixed_points[3]); ?>" min="0" style="width: 60px;">
        </p>
    </div>
    
    <div id="dfsoccer_tiered_info" style="<?php echo $distribution_method == 'tiered' ? 'display:block;' : 'display:none;'; ?>">
        <p><strong>Tiered Distribution:</strong></p>
        <p>Points awarded based on total participants (min 10 participants):</p>
        <ul>
            <li>1st Place: 5 points (max)</li>
            <li>2nd Place: 3 points (max)</li>
            <li>3rd Place: 1 point (max)</li>
        </ul>
        <p><em>Note: If fewer than 10 participants, 'Winner Takes All' will be used.</em></p>
    </div>
    
    <div id="dfsoccer_winner_takes_all_info" style="<?php echo $distribution_method == 'winner_takes_all' ? 'display:block;' : 'display:none;'; ?>">
        <p><strong>Winner Takes All:</strong></p>
        <p>First place team gets points equal to the number of participants in the league.</p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        $('#dfsoccer_points_distribution_method').on('change', function() {
            var method = $(this).val();
            
            $('#dfsoccer_fixed_points_container').hide();
            $('#dfsoccer_tiered_info').hide();
            $('#dfsoccer_winner_takes_all_info').hide();
            
            if (method == 'fixed') {
                $('#dfsoccer_fixed_points_container').show();
            } else if (method == 'tiered') {
                $('#dfsoccer_tiered_info').show();
            } else if (method == 'winner_takes_all') {
                $('#dfsoccer_winner_takes_all_info').show();
            }
        });
    });
    </script>
    <?php
}

/**
 * Save the league points distribution settings
 */
function dfsoccer_save_league_points_metabox($post_id) {
    // Check if nonce is set
    if (!isset($_POST['dfsoccer_league_points_nonce'])) {
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['dfsoccer_league_points_nonce'], 'dfsoccer_league_points_metabox')) {
        return;
    }
    
    // If this is autosave, our form has not been submitted
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check user permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save distribution method
    if (isset($_POST['dfsoccer_points_distribution_method'])) {
        $method = sanitize_text_field($_POST['dfsoccer_points_distribution_method']);
        update_post_meta($post_id, 'dfsoccer_points_distribution_method', $method);
    }
    
    // Save fixed points values
    if (isset($_POST['dfsoccer_fixed_points_first'])) {
        update_post_meta($post_id, 'dfsoccer_fixed_points_first', intval($_POST['dfsoccer_fixed_points_first']));
    }
    
    if (isset($_POST['dfsoccer_fixed_points_second'])) {
        update_post_meta($post_id, 'dfsoccer_fixed_points_second', intval($_POST['dfsoccer_fixed_points_second']));
    }
    
    if (isset($_POST['dfsoccer_fixed_points_third'])) {
        update_post_meta($post_id, 'dfsoccer_fixed_points_third', intval($_POST['dfsoccer_fixed_points_third']));
    }
}
add_action('save_post', 'dfsoccer_save_league_points_metabox');

/**
 * Process reset points request
 */
function dfsoccer_process_reset_points() {
    if (isset($_POST['dfsoccer_reset_points']) && isset($_POST['dfsoccer_reset_points_nonce'])) {
        $post_id = get_the_ID();
        
        if (wp_verify_nonce($_POST['dfsoccer_reset_points_nonce'], 'dfsoccer_reset_points_' . $post_id)) {
            // Delete the points awarded meta
            delete_post_meta($post_id, 'dfsoccer_league_points_awarded');
            delete_post_meta($post_id, 'dfsoccer_league_points_winners');
            
            // Add admin notice
            add_action('admin_notices', function() {
                ?>
                <div class="notice notice-success is-dismissible">
                    <p>League points status has been reset. Points can now be awarded again.</p>
                </div>
                <?php
            });
        }
    }
}
add_action('admin_init', 'dfsoccer_process_reset_points');


/**
 * Custom Soccer Points Leaderboard Shortcode
 * Displays a ranking of users based on their total points
 *
 * @param array $atts Shortcode attributes
 * @return string HTML output of the leaderboard
 */
function custom_soccer_points_leaderboard_shortcode($atts) {
    // Parse attributes with defaults
    $atts = shortcode_atts(array(
        'limit' => 10, // Number of users to display
        'title' => 'Points Leaderboard',
    ), $atts, 'custom_soccer_leaderboard');
    
    $limit = intval($atts['limit']);
    $title = sanitize_text_field($atts['title']);
    
    // Start output buffering
    ob_start();
    
    // Add CSS styles with unique prefixes
    echo '<style>
        .cspl-leaderboard-container {
            width: 100%;
            max-width: 600px;
            margin: 20px auto;
        }
        .cspl-leaderboard-table {
            width: 100%;
            border-collapse: collapse;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .cspl-leaderboard-table th, 
        .cspl-leaderboard-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        .cspl-leaderboard-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        .cspl-leaderboard-table tr:hover {
            background-color: #f9f9f9;
        }
        .cspl-rank-cell {
            text-align: center;
            font-weight: bold;
            width: 50px;
        }
        .cspl-username-cell {
            width: 60%;
        }
        .cspl-points-cell {
            text-align: right;
            font-weight: bold;
        }
        .cspl-top-rank-row {
            background-color: #f0f8ff;
        }
        .cspl-leaderboard-title {
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.5em;
        }
    </style>';
    
    // Get total points for each user
    global $wpdb;
    
    // Ensuring the table exists before attempting to query it
    $table_name = $wpdb->prefix . 'dfsoccer_points';
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") == $table_name;
    
    if (!$table_exists) {
        return '<p>Points system is not properly set up. Please contact the administrator.</p>';
    }
    
    // Get top users with total points
    $query = "SELECT 
                user_id, 
                SUM(points) as total_points
              FROM 
                {$table_name}
              GROUP BY 
                user_id
              ORDER BY 
                total_points DESC
              LIMIT %d";
    
    $results = $wpdb->get_results($wpdb->prepare($query, $limit));
    
    // Display the leaderboard
    echo '<div class="cspl-leaderboard-container">';
    echo '<h3 class="cspl-leaderboard-title">' . esc_html($title) . '</h3>';
    
    if (empty($results)) {
        echo '<p>No points data available yet.</p>';
    } else {
        echo '<table class="cspl-leaderboard-table">';
        echo '<thead>';
        echo '<tr>';
        echo '<th class="cspl-rank-cell">#</th>';
        echo '<th class="cspl-username-cell">User</th>';
        echo '<th class="cspl-points-cell">Points</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';
        
        $rank = 1;
        foreach ($results as $result) {
            $user_info = get_userdata($result->user_id);
            if (!$user_info) {
                continue; // Skip if user doesn't exist anymore
            }
            
            $row_class = ($rank <= 3) ? 'cspl-top-rank-row' : '';
            
            echo '<tr class="' . $row_class . '">';
            echo '<td class="cspl-rank-cell">' . $rank . '</td>';
            echo '<td class="cspl-username-cell">' . esc_html($user_info->display_name) . '</td>';
            echo '<td class="cspl-points-cell">' . number_format($result->total_points) . '</td>';
            echo '</tr>';
            
            $rank++;
        }
        
        echo '</tbody>';
        echo '</table>';
    }
    
    echo '</div>';
    
    // Return the buffered output
    return ob_get_clean();
}
add_shortcode('custom_soccer_leaderboard', 'custom_soccer_points_leaderboard_shortcode');



/**
 * Register Custom REST API Endpoint for DFSoccer Fixture/Player Data
 *
 * WARNING: Permission check is currently disabled for testing ('__return_true').
 * Revert to 'is_user_logged_in' or appropriate checks for production.
 */
add_action('rest_api_init', function () {
    register_rest_route('dfsoccer/v1', '/league/(?P<league_id>\d+)/fixture-players', array(
        'methods'             => WP_REST_Server::READABLE, // GET request
        'callback'            => 'dfsoccer_get_fixture_players_data', // Function to handle the request
        'args'                => array(
            'league_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param) && $param > 0; // Ensure league_id is a positive number
                },
                'required' => true,
                'description' => 'The ID of the DFSoccer League post.',
                'type'        => 'integer'
            ),
        ),
        // Allows public access - FOR TESTING ONLY! Revert for production.
        'permission_callback' => '__return_true'
    ));
});

/**
 * Callback function to get PUBLIC fixture and available player data for the API endpoint.
 * Corrected meta keys based on provided shortcode.
 *
 * @param WP_REST_Request $request Full details about the request.
 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
 */
function dfsoccer_get_fixture_players_data(WP_REST_Request $request) {
    $league_id = intval($request['league_id']);

    // --- Validate League Post Type (Adjust 'dfsoccer_league' if needed) ---
    $league_post_type = get_post_type($league_id);
    if ( !$league_post_type || 'dfsoccer_league' !== $league_post_type ) { // Assuming 'dfsoccer_league' is correct
         return new WP_Error(
            'rest_invalid_league',
            esc_html__( 'Invalid League ID provided.', 'dfsoccer' ),
            array( 'status' => 404 )
        );
    }

    // --- Fetch League Specific Data ---
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $budget = floatval(get_post_meta($league_id, 'dfsoccer_league_budget', true));
    $saved_fixtures_raw = get_post_meta($league_id, $fixture_meta_key, true);

    // Check if fixtures exist
    if (empty($saved_fixtures_raw) || !is_array($saved_fixtures_raw)) {
         return new WP_Error(
            'rest_no_fixtures',
            esc_html__( 'No fixtures found for this league.', 'dfsoccer' ),
            array( 'status' => 404 )
        );
    }

    // --- Process Fixtures and Collect Club Information ---
    $fixtures_data = array();
    $club_ids = array();
    $clubs_data = array(); // Stores unique club info: [club_id => ['id' => id, 'name' => name]]

    foreach ($saved_fixtures_raw as $fixture) {
        if (isset($fixture['home_club_id'], $fixture['away_club_id'])) {
            $home_club_id = intval($fixture['home_club_id']);
            $away_club_id = intval($fixture['away_club_id']);

            if ( get_post_status($home_club_id) && get_post_status($away_club_id) ) {
                $club_ids[] = $home_club_id;
                $club_ids[] = $away_club_id;

                $home_club_name = get_the_title($home_club_id);
                $away_club_name = get_the_title($away_club_id);

                // Add fixture details
                $fixtures_data[] = array(
                    'home_club_id'   => $home_club_id,
                    'home_club_name' => $home_club_name ?: 'N/A',
                    'away_club_id'   => $away_club_id,
                    'away_club_name' => $away_club_name ?: 'N/A',
                    // Get fixture date - *** TRY 'date' or find the ACTUAL key in your meta ***
                    'date'           => isset($fixture['date']) ? esc_html($fixture['date']) : null,
                );

                // Store unique club info
                if ($home_club_name && !isset($clubs_data[$home_club_id])) {
                     $clubs_data[$home_club_id] = ['id' => $home_club_id, 'name' => $home_club_name];
                }
                 if ($away_club_name && !isset($clubs_data[$away_club_id])) {
                     $clubs_data[$away_club_id] = ['id' => $away_club_id, 'name' => $away_club_name];
                }
            } else {
                 error_log("DFSoccer API: Invalid club ID in fixture meta for league {$league_id}. Home:{$home_club_id}, Away:{$away_club_id}");
            }
        }
    }
    $club_ids = array_unique($club_ids);

    // --- Fetch ALL Available Players for the Fixtures ---
    $players_data = array();

    if (!empty($club_ids)) {
        $args = array(
            'post_type'      => 'dfsoccer_player', // Correct based on shortcode
            'posts_per_page' => -1,
            'post_status'    => 'publish',
            'meta_query'     => array(
                array(
                    // --- CORRECTED META KEY (Removed leading underscore) ---
                    'key'     => 'dfsoccer_club_id',
                    // --- END CORRECTION ---
                    'value'   => $club_ids,
                    'compare' => 'IN', // Correct for checking against an array of club IDs
                    'type'    => 'NUMERIC'
                ),
            ),
            'orderby'        => 'title',
            'order'          => 'ASC',
        );

        $player_query = new WP_Query($args);

        if ($player_query->have_posts()) {
            while ($player_query->have_posts()) {
                $player_query->the_post();
                $player_id = get_the_ID();
                // --- Use CORRECTED META KEY here too ---
                $player_club_id = intval(get_post_meta($player_id, 'dfsoccer_club_id', true));
                // --- END CORRECTION ---

                // Assume other meta keys are correct unless proven otherwise
                $player_price = floatval(get_post_meta($player_id, 'dfsoccer_price', true));
                $player_position = get_post_meta($player_id, 'dfsoccer_position', true);

                $players_data[] = array(
                    'id'        => $player_id,
                    'name'      => get_the_title(),
                    'price'     => $player_price,
                    'position'  => $player_position ?: 'Unknown',
                    'club_id'   => $player_club_id,
                    'club_name' => isset($clubs_data[$player_club_id]) ? $clubs_data[$player_club_id]['name'] : 'N/A',
                );
            }
            wp_reset_postdata();
        } else {
             // Add logging if no players are found by the query
             error_log("DFSoccer API: WP_Query found no players for clubs: " . implode(',', $club_ids) . " with meta key 'dfsoccer_club_id'");
        }
    } else {
        // Add logging if no valid club IDs were extracted from fixtures
        error_log("DFSoccer API: No valid club IDs found for league {$league_id} to query players.");
    }


    // --- Prepare the Final JSON Response Structure ---
    $response_data = array(
        'league_id'     => $league_id,
        'league_name'   => get_the_title($league_id),
        'budget'        => $budget,
        'fixtures'      => $fixtures_data,
        'players'       => $players_data,
         // Included clubs list again, can be removed if not needed by frontend
        'clubs'         => array_values($clubs_data)
    );

    // Return a successful WP_REST_Response
    return new WP_REST_Response($response_data, 200);
}

add_action('rest_api_init', 'my_custom_available_leagues_init');

// Register the REST API endpoints
function my_custom_available_leagues_init() {
    // Existing endpoint for admin-created leagues
    register_rest_route('dfsoccer/v1', '/available-leagues', array(
        'methods' => 'GET',
        'callback' => 'my_custom_available_leagues_callback',
        'permission_callback' => '__return_true',
    ));
    
    // New endpoint for user-specific leagues
    register_rest_route('dfsoccer/v1', '/available-leagues/(?P<user_id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'my_custom_user_leagues_callback',
        'permission_callback' => '__return_true',
        'args' => array(
            'user_id' => array(
                'validate_callback' => function($param, $request, $key) {
                    return is_numeric($param);
                }
            ),
        ),
    ));
}

// Modified callback for admin-created leagues only
function my_custom_available_leagues_callback($request) {
    $leagues = array();
    
    try {
        $leagues_query = new WP_Query(array(
            'post_type' => 'dfsoccer_league',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'author__in' => get_admin_user_ids() // Only get leagues created by admins
        ));
        
        if ($leagues_query->have_posts()) {
            while ($leagues_query->have_posts()) {
                $leagues_query->the_post();
                $league_id = get_the_ID();
                
                // Check if the league has started
                $has_started = false;
                
                if (function_exists('dfsoccer_has_league_started')) {
                    $has_started = dfsoccer_has_league_started($league_id);
                }
                
                // Only include leagues that haven't started
                if (!$has_started) {
                    // Get fixtures using the league-specific meta key
                    $fixtures = get_post_meta($league_id, 'dfsoccer_saved_fixtures_' . $league_id, true);
                    
                    // Skip leagues with no fixtures
                    if (empty($fixtures) || !is_array($fixtures)) {
                        continue;
                    }
                    
                    $league_data = array(
                        'id' => $league_id,
                        'name' => get_the_title(),
                        'fixtures' => array()
                    );
                    
                    $earliest_timestamp = PHP_INT_MAX;
                    $earliest_date_string = '';
                    
                    foreach ($fixtures as $fixture) {
                        if (isset($fixture['fixture_date'])) {
                            $fixture_timestamp = strtotime($fixture['fixture_date']);
                            
                            if ($fixture_timestamp && $fixture_timestamp < $earliest_timestamp) {
                                $earliest_timestamp = $fixture_timestamp;
                                $earliest_date_string = $fixture['fixture_date'];
                            }
                            
                            // Add club names to the fixture data
                            $fixture_with_names = $fixture;
                            $fixture_with_names['home_club_name'] = get_the_title($fixture['home_club_id']);
                            $fixture_with_names['away_club_name'] = get_the_title($fixture['away_club_id']);
                            
                            // Add to league fixtures array
                            $league_data['fixtures'][] = $fixture_with_names;
                        }
                    }
                    
                    // Add the start date if we found one
                    if ($earliest_date_string) {
                        $league_data['start_date'] = $earliest_date_string;
                    }
                    
                    $leagues[] = $league_data;
                }
            }
            wp_reset_postdata();
        }
    } catch (Exception $e) {
        // If anything goes wrong, return an empty array
        $leagues = array();
    }
    
    return $leagues;
}

// New callback for user-specific leagues
function my_custom_user_leagues_callback($request) {
    $user_id = $request['user_id'];
    $leagues = array();
    
    // Verify the user exists and is a subscriber (or has appropriate role)
    $user = get_user_by('id', $user_id);
    if (!$user) {
        return new WP_Error('invalid_user', 'User not found', array('status' => 404));
    }
    
    try {
        $leagues_query = new WP_Query(array(
            'post_type' => 'dfsoccer_league',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
            'author' => $user_id // Only get leagues created by this specific user
        ));
        
        if ($leagues_query->have_posts()) {
            while ($leagues_query->have_posts()) {
                $leagues_query->the_post();
                $league_id = get_the_ID();
                
                // Check if the league has started
                $has_started = false;
                
                if (function_exists('dfsoccer_has_league_started')) {
                    $has_started = dfsoccer_has_league_started($league_id);
                }
                
                // Only include leagues that haven't started
                if (!$has_started) {
                    // Get fixtures using the league-specific meta key
                    $fixtures = get_post_meta($league_id, 'dfsoccer_saved_fixtures_' . $league_id, true);
                    
                    // Skip leagues with no fixtures
                    if (empty($fixtures) || !is_array($fixtures)) {
                        continue;
                    }
                    
                    $league_data = array(
                        'id' => $league_id,
                        'name' => get_the_title(),
                        'created_by' => $user_id,
                        'creator_name' => $user->display_name,
                        'fixtures' => array()
                    );
                    
                    $earliest_timestamp = PHP_INT_MAX;
                    $earliest_date_string = '';
                    
                    foreach ($fixtures as $fixture) {
                        if (isset($fixture['fixture_date'])) {
                            $fixture_timestamp = strtotime($fixture['fixture_date']);
                            
                            if ($fixture_timestamp && $fixture_timestamp < $earliest_timestamp) {
                                $earliest_timestamp = $fixture_timestamp;
                                $earliest_date_string = $fixture['fixture_date'];
                            }
                            
                            // Add club names to the fixture data
                            $fixture_with_names = $fixture;
                            $fixture_with_names['home_club_name'] = get_the_title($fixture['home_club_id']);
                            $fixture_with_names['away_club_name'] = get_the_title($fixture['away_club_id']);
                            
                            // Add to league fixtures array
                            $league_data['fixtures'][] = $fixture_with_names;
                        }
                    }
                    
                    // Add the start date if we found one
                    if ($earliest_date_string) {
                        $league_data['start_date'] = $earliest_date_string;
                    }
                    
                    $leagues[] = $league_data;
                }
            }
            wp_reset_postdata();
        }
    } catch (Exception $e) {
        // If anything goes wrong, return an empty array
        $leagues = array();
    }
    
    return $leagues;
}

// Helper function to get admin user IDs
function get_admin_user_ids() {
    $admin_users = get_users(array(
        'role__in' => array('administrator', 'editor'), // You can adjust roles as needed
        'fields' => 'ID'
    ));
    
    return $admin_users;
}

function is_league_from_api($league_id) {
    // Check if API fixtures exist for this league
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
    $manual_fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    
    $api_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
    $manual_fixtures = get_post_meta($league_id, $manual_fixture_meta_key, true);
    
    // If manual fixtures exist, return false (manually created)
    if (!empty($manual_fixtures)) {
        return array(
            'from_api' => false,
            'source_league_id' => null
        );
    }
    
    // If API fixtures exist, check for source league ID
    if (!empty($api_fixtures)) {
        // Get the source league ID if it exists
        $source_league_id = get_post_meta($league_id, 'dfsoccer_api_source_league_id', true);
        
        return array(
            'from_api' => true,
            'source_league_id' => $source_league_id
        );
    }
    
    // If no fixtures exist at all
    return array(
        'from_api' => false,
        'source_league_id' => null
    );
}
/**
 * Checks if a league has already started based on displayed message
 *
 * @param int $league_id The ID of the league to check
 * @return bool True if league has started, false otherwise
 */
function api_dfsoccer_has_league_started($league_id) {
    // First check if it's an API league
    $league_info = is_league_from_api($league_id);
    if (!$league_info['from_api']) {
        // Optional: Handle manual leagues differently if needed
        // For now, we'll check fixtures for all leagues
    }
    
    // Check if there's a flag already set
    $league_started = get_post_meta($league_id, 'dfsoccer_api_league_started', true);
    if (!empty($league_started)) {
        return true;
    }
    
    // Get the fixtures for this league
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
    $fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
    
    if (empty($fixtures)) {
        return false; // No fixtures, league hasn't started
    }
    
    // Check if any fixture has started by comparing with current time
    $current_time = current_time('mysql');
    $has_started = false;
    
    foreach ($fixtures as $fixture) {
        // Check if fixture has a start time
        if (isset($fixture['datetime'])) {
            $fixture_time = $fixture['datetime'];
            
            // If fixture time is in the past, league has started
            if (strtotime($fixture_time) <= strtotime($current_time)) {
                $has_started = true;
                
                // Store this information for future checks
                update_post_meta($league_id, 'dfsoccer_api_league_started', 'yes');
                
                break;
            }
        }
    }
    
    return $has_started;
}

/**
 * Filter to automatically mark leagues as started based on displayed message
 * This attaches to the output of your plugin
 */
function dfsoccer_check_league_started_from_output($output, $league_id) {
    // Check if the output contains the "already started" message
    if (strpos($output, 'The first fixture has already started. Player selection is no longer available.') !== false) {
        // Mark this league as started
        update_post_meta($league_id, 'dfsoccer_api_league_started', 'yes');
    }
    
    return $output;
}
// Add this filter to your plugin's output
add_filter('dfsoccer_fixture_player_selection_output', 'dfsoccer_check_league_started_from_output', 10, 2);


/**
 * Display players for fixtures with API support
 * This shortcode checks if the league has fixtures from API and loads players accordingly
 */


// Register the REST API endpoint for match results
function dfsoccer_register_match_results_endpoint() {
    register_rest_route('dfsoccer/v1', '/league/(?P<league_id>\d+)/match-results', array(
        'methods'             => WP_REST_Server::READABLE, // GET request
        'callback'            => 'dfsoccer_get_match_results_api',
        'args'                => array(
            'league_id' => array(
                'validate_callback' => function($param) {
                    return is_numeric($param) && $param > 0;
                },
                'required' => true,
                'description' => 'The ID of the DFSoccer League post.',
                'type'        => 'integer'
            ),
        ),
        'permission_callback' => '__return_true' // For public access
    ));
}
add_action('rest_api_init', 'dfsoccer_register_match_results_endpoint');

// Callback function to get match results
function dfsoccer_get_match_results_api(WP_REST_Request $request) {
    $league_id = intval($request['league_id']);

    // Verify league exists and is the correct post type
    $league_post_type = get_post_type($league_id);
    if (!$league_post_type || 'dfsoccer_league' !== $league_post_type) {
        return new WP_Error(
            'rest_invalid_league',
            esc_html__('Invalid League ID provided.', 'dfsoccer'),
            array('status' => 404)
        );
    }

    // Get fixture meta keys
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;

    // Check for fixtures
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    $api_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);

    // Use API fixtures if regular fixtures don't exist
    if (empty($saved_fixtures)) {
        $saved_fixtures = $api_fixtures;
    }

    if (empty($saved_fixtures)) {
        return new WP_Error(
            'rest_no_fixtures',
            esc_html__('No fixtures found for this league.', 'dfsoccer'),
            array('status' => 404)
        );
    }

    // Get match results
    $match_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $results_array = !empty($match_results) ? json_decode($match_results, true) : null; // Decode only if not empty

    // Check if decoding failed or results are empty
    if (empty($results_array) || !is_array($results_array)) {
        // Return an empty results array instead of an error, might be more user-friendly
        // Or keep the error if that's preferred:
        /*
        return new WP_Error(
            'rest_no_results',
            esc_html__('No match results found or results are invalid for this league.', 'dfsoccer'),
            array('status' => 404)
        );
        */
         $results_array = []; // Send back empty results if none found
    }

    // Format results for API response
    $formatted_results = array();
    foreach ($results_array as $player_id => $stats) {
        // Ensure player_id is valid before proceeding
        $player_id = intval($player_id);
        if ($player_id <= 0) {
            continue; // Skip invalid player IDs
        }

        $player_name = get_the_title($player_id);
        // If player post doesn't exist or name is empty, handle gracefully
        if (!$player_name) {
           $player_name = __('Unknown Player', 'dfsoccer') . ' (ID: ' . $player_id . ')';
        }

        // --- Added: Get Player Position ---
        $player_position_raw = get_post_meta($player_id, 'dfsoccer_position', true);
        // Assign the position, or null if it's empty/false/not found
        $player_position = $player_position_raw ? (string) $player_position_raw : null;
        // --- End Added Section ---

        // Ensure stats is an array
        $stats = is_array($stats) ? $stats : [];

        $player_data = array(
            'id' => $player_id,
            'name' => $player_name,
            'position' => $player_position, // <-- Position added here
            'stats' => $stats
        );

        $formatted_results[] = $player_data;
    }

    // Prepare response data
    $response_data = array(
        'league_id' => $league_id,
        'league_name' => get_the_title($league_id),
        'fixtures' => is_array($saved_fixtures) ? $saved_fixtures : [], // Ensure fixtures is an array
        'player_results' => $formatted_results
    );

        return new WP_REST_Response($response_data, 200);
}

function dfsoccer_display_api_match_results_shortcode($atts) {
    // Enqueue the JavaScript and CSS files
    wp_enqueue_script('dfsoccer-match-results-js', esc_url(plugin_dir_url(__FILE__) . 'js/dfsoccer-match-results.js'), array('jquery'), '1.0.3', true);
    wp_enqueue_style('dfsoccer-match-results-css', esc_url(plugin_dir_url(__FILE__) . 'css/dfsoccer-match-results.css'), array(), '1.0.2');

    $atts = shortcode_atts([
        'league_id' => '',
        'src' => '0'
    ], $atts, 'api_display_match_results');

    $league_id = absint($atts['league_id']);
    $source_league_id = absint($atts['src']);
    
    // If no source provided, try to get source from meta
    if ($source_league_id <= 0) {
        $meta_source_id = get_post_meta($league_id, 'dfsoccer_api_source_league_id', true);
        if (!empty($meta_source_id)) {
            $source_league_id = intval($meta_source_id);
        }
    }
    
    if (!$source_league_id) {
        return ' ';
    }

    // Check if the current user is the author of the league post
    $league_post = get_post($league_id);
    $is_author = $league_post && $league_post->post_author == get_current_user_id();
    
    // Get existing match results from database
    $existing_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $existing_results = $existing_results ? json_decode($existing_results, true) : array();
    
    // If we don't have results in the database yet, fetch from API on first load
    if (empty($existing_results) && $source_league_id > 0) {
        $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $source_league_id . '/match-results?nocache=1&LSCWP_CTRL=NOCACHE';
        $response = wp_remote_get($api_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $api_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($api_data['player_results']) && !empty($api_data['player_results'])) {
                $results_to_save = array();
                foreach ($api_data['player_results'] as $player_data) {
                    if (isset($player_data['id']) && isset($player_data['stats'])) {
                        $player_id = $player_data['id'];
                        $results_to_save[$player_id] = $player_data['stats'];
                    }
                }
                
                // Save to database
                if (!empty($results_to_save)) {
                    update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($results_to_save));
                    $existing_results = $results_to_save; // Update local variable
                }
            }
        }
    }
    
    // Handle form submission for manual edits - similar to original function
    if ($is_author && isset($_POST['submit_results']) && check_admin_referer('dfsoccer_submit_results', 'dfsoccer_nonce')) {
        $league_results = [];

        if (isset($_POST['player_stats']) && is_array($_POST['player_stats'])) {
            foreach ($_POST['player_stats'] as $player_id => $stats_json) {
                $player_id = absint($player_id);
                $stats = json_decode(stripslashes($stats_json), true);
                if (is_array($stats)) {
                    $league_results[$player_id] = array_map('absint', $stats);
                    
                    // Calculate total points
                    if (function_exists('dfsoccercalculate_total_points')) {
                        $total_points = dfsoccercalculate_total_points($player_id, $league_results[$player_id], $league_id);
                        $league_results[$player_id]['total_points'] = $total_points;
                    }
                }
            }
        }

        // Update the league results in the database
        if (!empty($league_results)) {
            update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($league_results));
            $existing_results = $league_results; // Update local variable
            
            // Redirect to prevent resubmission
            $redirect_url = add_query_arg('form_submitted', 'true', wp_get_referer());
            wp_safe_redirect(esc_url_raw($redirect_url));
            exit;
        }
    }
    
    // Get player data from API to display names
    $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $source_league_id . '/fixture-players?nocache=1&LSCWP_CTRL=NOCACHE';
    $response = wp_remote_get($api_url);
    $api_players = array();
    
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $api_data = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($api_data['players']) && !empty($api_data['players'])) {
            foreach ($api_data['players'] as $player) {
                $api_players[$player['id']] = $player;
            }
        }
    }
    
    $output = '';
    
    // Display success message on redirect
    if (isset($_GET['form_submitted']) && $_GET['form_submitted'] === 'true') {
        $output .= '<div class="dfsoccer-match-notice success"><p>' . esc_html__('Results saved successfully!', 'dfsoccer') . '</p></div>';
    }
    
    // Add refresh button if user is the author
    if ($is_author) {
        $output .= '<div class="match-refresh-container">';
        $output .= '<button type="button" id="refresh_api_results" class="button match-refresh-button" data-league-id="' . esc_attr($league_id) . '" data-source-id="' . esc_attr($source_league_id) . '">';
        $output .= '<span class="dashicons dashicons-update"></span> ' . esc_html__('Refresh Results from API', 'dfsoccer');
        $output .= '</button>';
        $output .= '<span id="api_refresh_status" style="display:none;"></span>';
        $output .= '</div>';
    }
    
    // If user is author, display editing form in collapsible panel
    if ($is_author) {
        $output .= '<div class="match-panel-container">';
        $output .= '<div class="match-panel-header" id="edit-stats-header">';
        $output .= '<h2><span class="dashicons dashicons-edit"></span> Edit Player Stats <span class="match-panel-toggle dashicons dashicons-arrow-down-alt2"></span></h2>';
        $output .= '</div>';
        
        $output .= '<div class="match-panel-content" id="edit-stats-content" style="display:none;">';
        $output .= '<form method="post" action="">';
        $output .= wp_nonce_field('dfsoccer_submit_results', 'dfsoccer_nonce', true, false);
        
        if (!empty($api_players)) {
            // Group players by club
            $players_by_club = array();
            foreach ($api_players as $player) {
                $club_id = $player['club_id'];
                if (!isset($players_by_club[$club_id])) {
                    $players_by_club[$club_id] = array();
                }
                $players_by_club[$club_id][] = $player;
            }
            
            // Get fixtures to know which clubs to display
            $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
            $fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
            
            if (!empty($fixtures) && is_array($fixtures)) {
                foreach ($fixtures as $fixture) {
                    $home_club_id = $fixture['home_club_id'];
                    $away_club_id = $fixture['away_club_id'];
                    
                    // Find club names
                    $home_club_name = '';
                    $away_club_name = '';
                    
                    // Look for club names in API data
                    if (isset($api_data['clubs'])) {
                        foreach ($api_data['clubs'] as $club) {
                            if ($club['id'] == $home_club_id) {
                                $home_club_name = $club['name'];
                            }
                            if ($club['id'] == $away_club_id) {
                                $away_club_name = $club['name'];
                            }
                        }
                    }
                    
                    if (empty($home_club_name)) $home_club_name = 'Home Club #' . $home_club_id;
                    if (empty($away_club_name)) $away_club_name = 'Away Club #' . $away_club_id;

                    $output .= '<div class="match-fixture-heading">';
                    $output .= '<h3>' . esc_html($home_club_name) . ' vs ' . esc_html($away_club_name) . '</h3>';
                    $output .= '</div>';
                    
                    // Process both home and away teams
                    $teams = array(
                        'Home Team' => $home_club_id,
                        'Away Team' => $away_club_id
                    );
                    
                    foreach ($teams as $team_name => $club_id) {
                        if (isset($players_by_club[$club_id])) {
                            $output .= '<div class="match-team-section">';
                            $output .= '<h4>' . esc_html($team_name) . '</h4>';
                            $output .= '<div class="match-players-grid">';
                            
                            foreach ($players_by_club[$club_id] as $player) {
                                $player_id = $player['id'];
                                $player_name = $player['name'];
                                $player_results = isset($existing_results[$player_id]) ? $existing_results[$player_id] : array();
                                
                                $output .= '<div class="match-player-card" data-player-id="' . esc_attr($player_id) . '">';
                                $output .= "<div class='match-player-header'>" . esc_html($player_name) . "</div>";
                                $output .= "<input type='hidden' name='player_stats[" . esc_attr($player_id) . "]' id='match_player_stats_" . esc_attr($player_id) . "' value='" . esc_attr(wp_json_encode($player_results)) . "'>";
                                $output .= "<div id='match_player_stats_form_" . esc_attr($player_id) . "' class='match-player-stats-form'></div>";
                                $output .= '</div>';
                            }
                            
                            $output .= '</div>'; // End players-grid
                            $output .= '</div>'; // End team-section
                        }
                    }
                }
            }
        }

        $output .= '<div class="match-form-submit-container">';
        $output .= '<input type="submit" name="submit_results" value="' . esc_attr__('Save Results', 'dfsoccer') . '" class="button button-primary">';
        $output .= '</div>';
        
        $output .= '</form>';
        $output .= '</div>'; // End panel-content
        $output .= '</div>'; // End panel-container
    }
    
    // Add styling
    $output .= '<style>
        /* General Styles */
        .match-api-notice {
            background-color: #e7f3ff;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #2196F3;
            font-weight: bold;
            font-size: 16px;
            border-radius: 4px;
        }
        
        .dfsoccer-match-notice.success {
            background-color: #d4edda;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            border-radius: 4px;
        }
        
        /* Refresh Button Styles */
        .match-refresh-container {
            margin-bottom: 20px;
        }
        
        .match-refresh-button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            align-items: center;
            transition: background-color 0.3s;
        }
        
        .match-refresh-button:hover {
            background-color: #45a049;
        }
        
        .match-refresh-button .dashicons {
            margin-right: 5px;
        }
        
        #api_refresh_status {
            margin-left: 10px;
            font-weight: bold;
            padding: 5px 10px;
            border-radius: 4px;
            background-color: #f8f9fa;
        }
        
        /* Panel Styles */
        .match-panel-container {
            margin-bottom: 30px;
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .match-panel-header {
            background-color: #f8f9fa;
            padding: 15px;
            cursor: pointer;
            border-bottom: 1px solid #ddd;
            transition: background-color 0.3s;
        }
        
        .match-panel-header:hover {
            background-color: #e9ecef;
        }
        
        .match-panel-header h2 {
            margin: 0;
            font-size: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .match-panel-header .dashicons {
            margin-right: 10px;
        }
        
        .match-panel-toggle {
            transition: transform 0.3s;
        }
        
        .match-panel-toggle.open {
            transform: rotate(180deg);
        }
        
        .match-panel-content {
            padding: 20px;
            background-color: white;
        }
        
        /* Fixture and Team Styles */
        .match-fixture-heading {
            background-color: #f1f1f1;
            padding: 10px 15px;
            margin: 20px 0 10px 0;
            border-radius: 4px;
        }
        
        .match-fixture-heading h3 {
            margin: 0;
            font-size: 16px;
            color: #333;
        }
        
        .match-team-section {
            margin-bottom: 25px;
        }
        
        .match-team-section h4 {
            margin: 15px 0 10px 0;
            font-size: 14px;
            color: #555;
            border-bottom: 1px solid #eee;
            padding-bottom: 5px;
        }
        
        /* Players Grid */
        .match-players-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 15px;
        }
        
        /* Player Card Styles */
        .match-player-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        
        .match-player-header {
            background-color: #f8f9fa;
            padding: 10px;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }
        
        .match-player-stats-form {
            padding: 10px;
        }
        
        .match-stat-field {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            align-items: center;
        }
        
        .match-stat-field label {
            font-size: 13px;
            color: #555;
        }
        
        .match-stat-field input {
            width: 60px;
            padding: 4px;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 3px;
        }
        
        /* Form Submit */
        .match-form-submit-container {
            margin-top: 30px;
            text-align: center;
        }
        
        .match-form-submit-container .button {
            padding: 10px 20px;
            font-size: 16px;
        }
    </style>';
    
    // Add custom JavaScript for panel, form, and refresh button
    $output .= '<script>
    jQuery(document).ready(function($) {
        // Panel functionality
        $(".match-panel-header").on("click", function() {
            const content = $("#edit-stats-content");
            const toggle = $(".match-panel-toggle");
            
            if (content.is(":visible")) {
                content.slideUp(300);
                toggle.removeClass("open");
            } else {
                content.slideDown(300);
                toggle.addClass("open");
            }
        });
    
        // Stats fields for form
        const statsFields = [
            { name: "goals", label: "Goals", value: 0 },
            { name: "assists", label: "Assists", value: 0 },
            { name: "own", label: "Own Goals", value: 0 },
            { name: "penalties", label: "Penalties", value: 0 },
            { name: "missed", label: "Penalties Missed", value: 0 },
            { name: "conceded", label: "Goals Conceded", value: 0 },
            { name: "minutes", label: "Minutes Played", value: 0 },
            { name: "red", label: "Red Cards", value: 0 },
            { name: "yellow", label: "Yellow Cards", value: 0 }
        ];
        
        // Initialize player stats forms
        $(".match-player-card").each(function() {
            const playerId = $(this).data("player-id");
            const formContainer = $(`#match_player_stats_form_${playerId}`);
            const playerStatsInput = $(`#match_player_stats_${playerId}`);
            const playerStats = JSON.parse(playerStatsInput.val() || "{}");
            
            // Create form fields for each stat
            statsFields.forEach(stat => {
                const statValue = playerStats[stat.name] !== undefined ? playerStats[stat.name] : 0;
                const fieldHtml = `
                    <div class="match-stat-field">
                        <label>${stat.label}:</label>
                        <input type="number" min="0" class="match-stat-input" 
                               data-stat-name="${stat.name}" 
                               value="${statValue}">
                    </div>
                `;
                formContainer.append(fieldHtml);
            });
            
            // Update hidden input when stats change
            formContainer.on("change", ".match-stat-input", function() {
                const updatedStats = {};
                formContainer.find(".match-stat-input").each(function() {
                    const statName = $(this).data("stat-name");
                    const statValue = parseInt($(this).val(), 10) || 0;
                    updatedStats[statName] = statValue;
                });
                playerStatsInput.val(JSON.stringify(updatedStats));
            });
        });
        
        // Refresh button functionality
        $("#refresh_api_results").on("click", function() {
            const button = $(this);
            const statusSpan = $("#api_refresh_status");
            const leagueId = button.data("league-id");
            const sourceId = button.data("source-id");
            
            // Disable button during request
            button.prop("disabled", true);
            statusSpan.text("Fetching updated results...").show();
            
            // Make AJAX request to refresh
            $.ajax({
                url: "' . admin_url('admin-ajax.php') . '",
                type: "POST",
                data: {
                    action: "dfsoccer_refresh_match_results",
                    league_id: leagueId,
                    source_id: sourceId,
                    nonce: "' . wp_create_nonce('dfsoccer_refresh_results') . '"
                },
                success: function(response) {
                    if (response.success) {
                        statusSpan.text("Results refreshed! Reloading page...").css("color", "green");
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        statusSpan.text(response.data || "Error refreshing results.").css("color", "red");
                        button.prop("disabled", false);
                    }
                },
                error: function() {
                    statusSpan.text("Network error. Please try again.").css("color", "red");
                    button.prop("disabled", false);
                }
            });
        });
    });
    </script>';
    
    return $output;
}
add_shortcode('api_display_match_results', 'dfsoccer_display_api_match_results_shortcode');

/**
 * AJAX handler to refresh match results from the API
 */
function dfsoccer_refresh_match_results_callback() {
    // Check nonce for security
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dfsoccer_refresh_results')) {
        wp_send_json_error('Security check failed');
    }
    
    // Get parameters
    $league_id = isset($_POST['league_id']) ? intval($_POST['league_id']) : 0;
    $source_id = isset($_POST['source_id']) ? intval($_POST['source_id']) : 0;
    
    if (!$league_id || !$source_id) {
        wp_send_json_error('Missing required parameters');
    }
    
    // Check if user has permission to update this league
    $league_post = get_post($league_id);
    if (!$league_post || $league_post->post_author != get_current_user_id()) {
        wp_send_json_error('Permission denied');
    }
    
    // Make API request to get updated results
    $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $source_id . '/match-results?nocache=1&LSCWP_CTRL=NOCACHE';
    $response = wp_remote_get($api_url);
    
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        wp_send_json_error('Failed to fetch data from API');
    }
    
    $api_data = json_decode(wp_remote_retrieve_body($response), true);
    
    if (!isset($api_data['player_results']) || !is_array($api_data['player_results'])) {
        wp_send_json_error('Invalid API response format');
    }
    
    // Process the player results
    $updated_results = array();
    foreach ($api_data['player_results'] as $player_data) {
        if (isset($player_data['id']) && isset($player_data['stats'])) {
            $updated_results[$player_data['id']] = $player_data['stats'];
        }
    }
    
    if (empty($updated_results)) {
        wp_send_json_error('No player results found in API response');
    }
    
    // Update the match results in the database
    update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($updated_results));
    
    // Success response
    wp_send_json_success('Results updated successfully');
}
add_action('wp_ajax_dfsoccer_refresh_match_results', 'dfsoccer_refresh_match_results_callback');



function dfsoccer_append_match_results_to_league_content1($content) {
    if (is_single() && get_post_type() == 'dfsoccer_league') {  // Check if it's a single league post
        $league_id = get_the_ID();  // Get the current league post ID
        
        // Instead of using do_shortcode(), append the shortcode directly
        $content .= '[api_display_match_results league_id="' . $league_id . '"]';
    }
    return $content;
}
add_filter('the_content', 'dfsoccer_append_match_results_to_league_content1');

/**
 * Calculate points for a player using data from the API
 * 
 * @param int $player_id The player ID
 * @param array $player_stats The player's statistics
 * @param int $league_id The league ID
 * @return float The calculated points
 */
function calculate_points_from_api($player_id, $player_stats, $league_id) {
    // Get the source league ID
    $source_league_id = get_post_meta($league_id, 'dfsoccer_api_source_league_id', true);
    
    if (empty($source_league_id)) {
        error_log("API Points Calculation: No source league ID found for league $league_id");
        // Fall back to regular calculation
        return dfsoccercalculate_total_points($player_id, $player_stats, $league_id);
    }
    
    // Check cache first for position
    $cached_position = get_transient('dfsoccer_api_player_position_' . $player_id . '_' . $source_league_id);
    if (false !== $cached_position) {
        $position = $cached_position;
    } else {
        // Use static variables to hold API data during a single page load
        static $api_data = null;
        static $api_fetched = false;
        
        if (!$api_fetched) {
            // Very short cache (1 minute) to prevent hammering the API if multiple users load the page
            $api_data = get_transient('dfsoccer_api_match_results_short_' . $source_league_id);
            
            if (false === $api_data) {
                // Keep nocache parameter to get fresh data
                $api_url = "https://superfantasy.net/wp-json/dfsoccer/v1/league/{$source_league_id}/match-results?nocache=1&LSCWP_CTRL=NOCACHE";
                
                // Fetch data from API
                $response = wp_remote_get($api_url);
                
                if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                    $api_data = json_decode(wp_remote_retrieve_body($response), true);
                    
                    // Cache the API response for just 1 minute
                    // This prevents multiple API calls if several users load the page at once
                    set_transient('dfsoccer_api_match_results_short_' . $source_league_id, $api_data, 60); // 60 seconds
                } else {
                    // API request failed
                    $error_message = is_wp_error($response) ? $response->get_error_message() : "API returned status code: " . wp_remote_retrieve_response_code($response);
                    error_log("API Points Calculation Error: Failed to fetch API data for league $source_league_id. Error: $error_message");
                }
            }
            
            // Mark as fetched to prevent multiple attempts during this page load
            $api_fetched = true;
        }
        
        // Position starts as null
        $position = null;
        
        // Look for player in API data
        if ($api_data && isset($api_data['player_results']) && is_array($api_data['player_results'])) {
            foreach ($api_data['player_results'] as $player) {
                // Match by player ID
                if (isset($player['id']) && $player['id'] == $player_id && isset($player['position'])) {
                    $position = strtolower(trim($player['position']));
                    
                    // Cache the position for 5 minutes (short cache to stay relatively fresh)
                    set_transient('dfsoccer_api_player_position_' . $player_id . '_' . $source_league_id, $position, 300); // 5 minutes
                    
                    error_log("API Points Calculation: Found position '$position' in API for player $player_id");
                    break;
                }
            }
        }
    }
    
    // If position not found in API, fall back to regular calculation
    if (empty($position)) {
        error_log("API Points Calculation: Could not get position from API for player $player_id. Falling back to database method.");
        return dfsoccercalculate_total_points($player_id, $player_stats, $league_id);
    }
    
    // --- Get Rules ---
    $points_rules = get_post_meta($league_id, 'dfsoccer_points_rules', true);
    $points_rules = is_array($points_rules) ? $points_rules : []; // Ensure array

    // Default point rules
    $default_rules = [
        'goalkeeper' => ['goals' => 10, 'own' => -7, 'assists' => 7, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
        'defender'   => ['goals' => 7,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -2, 'penalties' => 8, 'missed' => -4],
        'midfielder' => ['goals' => 6,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => -1, 'penalties' => 8, 'missed' => -4],
        'attacker'   => ['goals' => 5,  'own' => -7, 'assists' => 5, 'minutes' => 0.02, 'red' => -4, 'yellow' => -1, 'conceded' => 0,  'penalties' => 8, 'missed' => -4]
    ];

    // --- Select Rules based on API Position ---
    // Check if the determined position is valid
    if (!array_key_exists($position, $default_rules)) {
        error_log("API Points Calculation Error: Position '$position' from API is invalid for player $player_id");
        // Fall back to regular calculation
        return dfsoccercalculate_total_points($player_id, $player_stats, $league_id);
    }

    // Get rules for this position
    $rules = (isset($points_rules[$position])) ? $points_rules[$position] : $default_rules[$position];

    // --- Calculate Points using API position ---
    $total_points = 0.0;

    // Check if we have rules and player stats
    if (is_array($rules) && !empty($rules) && is_array($player_stats)) {
        foreach ($rules as $stat_key => $points_per_stat) {
            if (isset($player_stats[$stat_key])) {
                $stat_value = is_numeric($player_stats[$stat_key]) ? floatval($player_stats[$stat_key]) : 0;
                $points_value = is_numeric($points_per_stat) ? floatval($points_per_stat) : 0;
                $total_points += $stat_value * $points_value;
            }
        }
    } else {
        error_log("API Points Calculation Error: Invalid rules or player stats for player $player_id");
        return 0.0;
    }

    error_log("API Points Calculation Result - Player ID: $player_id, API Position: '$position', Total Points: $total_points");
    return round($total_points, 2);
}

function dfsoccer_display_api_points_shortcode($atts) {
    // Extract attributes
    $atts = shortcode_atts(array(
        'league_id' => 0,
        'show_teams' => 1  // Default to showing teams
    ), $atts, 'display_api_points');
    
    $league_id = intval($atts['league_id']);
    $show_teams = intval($atts['show_teams']);
    
    if (!$league_id) {
        return '<p>Error: Please specify a valid league_id.</p>';
    }
    
    // Check if league is from API
    $league_source = is_league_from_api($league_id);
    $is_api_league = $league_source['from_api'];
    $source_league_id = $league_source['source_league_id'];
    
    if (!$is_api_league || empty($source_league_id)) {
        return '<p>This league is not connected to an API source or has no source ID.</p>';
    }
    
    // Get match results
    $match_results_json = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $match_results = !empty($match_results_json) ? json_decode($match_results_json, true) : array();
    
    // Get player names from API (stored separately)
    $player_names = get_post_meta($league_id, 'dfsoccer_api_player_names', true);
    $player_names = !empty($player_names) ? $player_names : array();
    
    // If no results yet, fetch and calculate them now
    if (empty($match_results) || empty($player_names)) {
        // Fetch player data from API
        $api_url = "https://superfantasy.net/wp-json/dfsoccer/v1/league/{$source_league_id}/match-results?nocache=1&LSCWP_CTRL=NOCACHE";
        $response = wp_remote_get($api_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $api_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($api_data['player_results']) && !empty($api_data['player_results'])) {
                $match_results = array();
                $player_names = array(); // Reset player names
                
                // Process each player
                foreach ($api_data['player_results'] as $player_data) {
                    if (isset($player_data['id']) && isset($player_data['stats'])) {
                        $player_id = $player_data['id'];
                        
                        // Store player stats
                        $match_results[$player_id] = $player_data['stats'];
                        
                        // Store player name if available
                        if (isset($player_data['name'])) {
                            $player_names[$player_id] = $player_data['name'];
                        } else {
                            $player_names[$player_id] = "Player #" . $player_id;
                        }
                        
                        // Add position if available
                        if (isset($player_data['position'])) {
                            $match_results[$player_id]['position'] = $player_data['position'];
                        }
                        
                        // Calculate points
                        if (function_exists('calculate_points_from_api')) {
                            $points = calculate_points_from_api($player_id, $match_results[$player_id], $league_id);
                            $match_results[$player_id]['total_points'] = $points;
                        }
                    }
                }
                
                // Save results and player names
                if (!empty($match_results)) {
                    update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($match_results));
                }
                if (!empty($player_names)) {
                    update_post_meta($league_id, 'dfsoccer_api_player_names', $player_names);
                }
            }
        } else {
            return '<p>Error: Could not fetch data from API.</p>';
        }
    }
    
    // Fetch player data with prices from API
    $player_price_api_url = "https://superfantasy.net/wp-json/dfsoccer/v1/league/{$source_league_id}/fixture-players";
    $player_price_response = wp_remote_get($player_price_api_url);
    $player_price_data = array();
    
    if (!is_wp_error($player_price_response) && wp_remote_retrieve_response_code($player_price_response) === 200) {
        $player_price_data = json_decode(wp_remote_retrieve_body($player_price_response), true);
    }
    
    // Helper function to calculate team budget
    function calculate_team_budget($selected_players, $api_data) {
        $budget = 0;
        
        // Check if we have player data from API
        if (!isset($api_data['players']) || empty($api_data['players'])) {
            return 0;
        }
        
        // Create a lookup map for faster access
        $players_map = array();
        foreach ($api_data['players'] as $player) {
            $players_map[$player['id']] = $player;
        }
        
        // Calculate total budget for team
        foreach ($selected_players as $player_id) {
            if (isset($players_map[$player_id]) && isset($players_map[$player_id]['price'])) {
                $budget += floatval($players_map[$player_id]['price']);
            }
        }
        
        return $budget;
    }
    
    // Helper function to get player name (ONLY from API names)
    function get_api_player_name($player_id, $player_names) {
        if (isset($player_names[$player_id])) {
            return $player_names[$player_id];
        } else {
            return "Player #" . $player_id;
        }
    }
    
    // Start building output
    $output = '<div class="dfsoccer-api-points" style="background-color: var(--color-green-800, #166534); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">';
    
    // ADDED SECTION: Show Teams (if enabled)
    if ($show_teams) {
        // Fetch users who have participated in this league
        $args = array(
            'meta_key'   => 'dfsoccer_selected_players_' . $league_id,
            'meta_value' => '', // Any non-empty value
            'meta_compare' => '!=',
            'fields' => 'all_with_meta'
        );
        $users = get_users($args);
        
        $output .= '<h3 style="color: white; text-align: center;">Teams in this League</h3>';
        
        if (empty($users)) {
            $output .= '<p style="color: white; text-align: center;">No teams have been selected for this league.</p>';
        } else {
            $teams = array();
            
            // Process each user's team
            foreach ($users as $user) {
                $selected_players = get_user_meta($user->ID, 'dfsoccer_selected_players_' . $league_id, true);
                
                if (!empty($selected_players) && is_array($selected_players)) {
                    $team_total_points = 0;
                    $player_list = '';
                    
                    // Calculate team budget
                    $team_budget = calculate_team_budget($selected_players, $player_price_data);
                    
                    // Process each player
                    foreach ($selected_players as $player_id) {
                        // Get player name ONLY from API
                        $player_name = get_api_player_name($player_id, $player_names);
                        
                        // Add points if we have them
                        $points = 0;
                        if (isset($match_results[$player_id]) && isset($match_results[$player_id]['total_points'])) {
                            $points = $match_results[$player_id]['total_points'];
                            $team_total_points += floatval($points);
                        }
                        
                        // Add to player list
                        $player_list .= '<li>' . esc_html($player_name) . ' (ID: ' . $player_id . ') - Points: ' . number_format($points, 2) . '</li>';
                    }
                    
                    // Add team to the array
                    $teams[] = array(
                        'user_name' => esc_html($user->display_name),
                        'user_id' => $user->ID,
                        'total_points' => $team_total_points,
                        'budget' => $team_budget,
                        'player_list' => $player_list,
                        'player_count' => count($selected_players)
                    );
                }
            }
            
            // Sort teams using the three-tiered tiebreaker system
            usort($teams, function($a, $b) {
                // First tiebreaker: points (higher points come first)
                if ($b['total_points'] != $a['total_points']) {
                    return $b['total_points'] <=> $a['total_points'];
                }
                
                // Second tiebreaker: budget (lower budget comes first)
                if ($a['budget'] != $b['budget']) {
                    return $a['budget'] <=> $b['budget'];
                }
                
                // Third tiebreaker: random
                return mt_rand(-1, 1);
            });
            
            // Display teams table
            $output .= '<table class="dfsoccer-teams-table">';
            $output .= '<tr><th>Team</th><th>User ID</th><th>Players</th><th>Budget</th><th>Total Points</th></tr>';
            
            foreach ($teams as $team) {
                $output .= '<tr class="team-row">';
                $output .= '<td>' . $team['user_name'] . '</td>';
                $output .= '<td>' . $team['user_id'] . '</td>';
                $output .= '<td>' . $team['player_count'];
                // Check if fixtures div exists and contains the "already started" message
                $fixture_div = '<script>
                document.addEventListener("DOMContentLoaded", function() {
                  const fixtureDiv = document.querySelector(".dfsoccer-fixture-and-player-selection");
                  // INVERTED LOGIC HERE - now we show buttons only if fixture has started
                  if (!(fixtureDiv && fixtureDiv.textContent.includes("The first fixture has already started"))) {
                    document.querySelectorAll(".toggle-players").forEach(btn => btn.style.display = "none");
                  }
                });
                </script>';
                // Still generate the button, but it will be hidden by JavaScript if needed
                $output .= ' <button class="toggle-players" data-user="' . $team['user_id'] . '">Show/Hide</button>';
                // Output the script just once (you can check with a flag variable)
                static $script_added = false;
                if (!$script_added) {
                    $output .= $fixture_div;
                    $script_added = true;
                }
                $output .= '</td>';
                $output .= '<td>' . number_format($team['budget'], 2) . '</td>';
                $output .= '<td>' . number_format($team['total_points'], 2) . '</td>';
                $output .= '</tr>';
                $output .= '<tr class="player-list-row" id="players-' . $team['user_id'] . '" style="display:none; color:white;"><td colspan="5"><ul>' . $team['player_list'] . '</ul></td></tr>';
            }
            
            $output .= '</table>';
        }
    }
    
    // ORIGINAL SECTION: Show all players and their points, now in accordion wrapper
    $output .= '<h3 style="color: white; text-align: center;">All Player Points (Calculated from API)</h3>';
    $output .= '<div class="api_players_results accordion-wrapper">';
    $output .= '<button class="accordion-toggle">Show/Hide All Players</button>';
    $output .= '<div class="accordion-content">';
    $output .= '<table class="dfsoccer-points-table">';
    $output .= '<tr><th>Player</th><th>Stats</th><th>Price</th><th>Points</th></tr>';

    // Display each player's points
    if (!empty($match_results)) {
        // Create a lookup map for player prices
        $player_prices = array();
        if (isset($player_price_data['players']) && !empty($player_price_data['players'])) {
            foreach ($player_price_data['players'] as $player) {
                if (isset($player['id']) && isset($player['price'])) {
                    $player_prices[$player['id']] = $player['price'];
                }
            }
        }
        
        // Get player information
        foreach ($match_results as $player_id => $player_data) {
            // Get player name ONLY from API
            $player_name = get_api_player_name($player_id, $player_names);
            
            // Get position
            $position = isset($player_data['position']) ? $player_data['position'] : 'Unknown';
            
            // Format stats
            $stats_html = '<ul class="player-stats">';
            foreach ($player_data as $stat_key => $stat_value) {
                if ($stat_key != 'position' && $stat_key != 'total_points') {
                    $stats_html .= '<li>' . esc_html($stat_key) . ': ' . esc_html($stat_value) . '</li>';
                }
            }
            $stats_html .= '</ul>';
            
            // Get price
            $price = isset($player_prices[$player_id]) ? $player_prices[$player_id] : 0;
            
            // Get points
            $points = isset($player_data['total_points']) ? $player_data['total_points'] : 0;
            
            // If points aren't calculated yet, do it now
            if ($points == 0 && function_exists('calculate_points_from_api')) {
                $points = calculate_points_from_api($player_id, $player_data, $league_id);
                // Update the stored points
                $match_results[$player_id]['total_points'] = $points;
                update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($match_results));
            }
            
            // Add table row
            $output .= '<tr>';
            $output .= '<td>' . esc_html($player_name) . ' <span class="player-position">(' . esc_html($position) . ')</span><span class="hidden-player-id" style="display:none;">' . esc_html($player_id) . '</span></td>';
            $output .= '<td>' . $stats_html . '</td>';
            $output .= '<td>' . number_format($price, 2) . '</td>';
            $output .= '<td>' . number_format($points, 2) . '</td>';
            $output .= '</tr>';
        }
    } else {
        $output .= '<tr><td colspan="4">No player data available.</td></tr>';
    }

    $output .= '</table>';
    $output .= '</div>'; // Close accordion-content
    $output .= '</div>'; // Close api_players_results
    $output .= '</div>';

    // Add some basic styling
    $output .= '<style>
    /* Variables (add these if not already defined) */
    :root {
        --color-bg-deep: #052e16;
        --color-bg-medium: #14532d;
        --color-bg-light: #166534;
        --color-bg-card: #15803d;
        --color-text-primary: #ffffff;
        --color-text-secondary: #bbf7d0;
        --color-text-accent: #86efac;
        --color-accent-yellow: #eab308;
        --color-accent-yellow-dark: #ca8a04;
        --color-accent-positive: #4ade80;
        --color-accent-negative: #f87171;
        --color-border-light: #166534;
        --color-border-medium: #15803d;
    }

    /* Main Container */
    .dfsoccer-api-points {
        background-color: var(--color-bg-light);
        padding: 1.5rem;
        border-radius: 8px;
        margin: 1.5rem 0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
    }

    .dfsoccer-api-points h3 {
        color: var(--color-text-accent);
        font-size: 1.2rem;
        text-align: center;
        margin-top: 0;
        margin-bottom: 1.5rem;
        padding-bottom: 0.5rem;
        border-bottom: 1px solid var(--color-border-medium);
    }

    /* Teams Table */
    .dfsoccer-teams-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 2rem;
        border-radius: 6px;
        overflow: hidden;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .dfsoccer-teams-table th {
        background-color: var(--color-bg-deep);
        color: var(--color-text-accent);
        font-weight: 600;
        text-align: left;
        padding: 0.75rem 1rem;
        font-size: 0.9rem;
    }

    .dfsoccer-teams-table td {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid var(--color-border-medium);
        color: var(--color-text-primary);
        font-size: 0.9rem;
    }

    .dfsoccer-teams-table tr:last-child td {
        border-bottom: none;
    }

    .team-row {
        background-color: rgba(5, 46, 22, 0.2);
        transition: background-color 0.2s;
    }

    .team-row:hover {
        background-color: rgba(22, 101, 52, 0.4);
    }

    .player-list-row {
        background-color: var(--color-bg-deep);
    }

    .player-list-row ul {
        list-style: none;
        padding: 0.5rem 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 0.5rem 1rem;
    }

    .player-list-row li {
        padding: 0.5rem;
        border-radius: 4px;
        transition: background-color 0.2s;
        display: flex;
        justify-content: space-between;
    }

    .player-list-row li:hover {
        background-color: rgba(22, 101, 52, 0.3);
    }

    .toggle-players {
        background-color: var(--color-accent-yellow);
        color: var(--color-bg-deep);
        border: none;
        border-radius: 4px;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.1s;
    }

    .toggle-players:hover {
        background-color: var(--color-accent-yellow-dark);
        transform: translateY(-1px);
    }

    /* Points Table */
    .accordion-wrapper {
        border-radius: 6px;
        overflow: hidden;
        margin-top: 1rem;
        box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .accordion-toggle {
        width: 100%;
        background-color: var(--color-bg-deep);
        color: var(--color-text-accent);
        border: none;
        padding: 1rem;
        font-size: 1rem;
        font-weight: 600;
        text-align: left;
        cursor: pointer;
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        transition: background-color 0.2s;
    }

    .accordion-toggle:hover {
        background-color: rgba(255, 255, 255, 1);
    }

    .accordion-toggle::after {
        content: "↓";
        font-size: 1.2rem;
        transition: transform 0.3s;
    }

    .accordion-toggle.active::after {
        transform: rotate(180deg);
    }

    .accordion-content {
        max-height: 0;
        display: none; /* Start hidden */
        overflow: hidden;
        transition: max-height 0.3s ease-out;
        background-color: #ffffff;
    }

    .accordion-content.active {
        max-height: 2000px; /* Arbitrary large value */
        /* display is controlled by jQuery slideToggle */
    }

    .dfsoccer-points-table {
        width: 100%;
        border-collapse: collapse;
    }

    .dfsoccer-points-table th {
        background-color: var(--color-bg-deep);
        padding: 0.75rem 1rem;
        color: var(--color-text-accent);
        text-align: left;
        font-size: 0.9rem;
        position: sticky;
        top: 0;
        z-index: 2;
    }

    .dfsoccer-points-table tr {
        transition: background-color 0.2s;
    }

    .dfsoccer-points-table tr:nth-child(even) {
        background-color: rgba(5, 46, 22, 0.2);
    }

    .dfsoccer-points-table tr:hover {
        background-color: rgba(22, 101, 52, 0.4);
    }

    .dfsoccer-points-table td {
        padding: 0.75rem 1rem;
        border-top: 1px solid var(--color-border-light);
        color: #000000;
        font-size: 0.9rem;
    }

    .player-position {
        display: inline-block;
        background-color: var(--color-bg-deep);
        color: var(--color-text-accent);
        padding: 0.15rem 0.4rem;
        border-radius: 4px;
        font-size: 0.7rem;
        margin-left: 0.5rem;
        vertical-align: middle;
    }

    .player-stats {
        list-style: none;
        padding: 0;
        margin: 0;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 0.3rem;
        font-size: 0.8rem;
    }

    .player-stats li {
        background-color: rgba(5, 46, 22, 0.3);
        padding: 0.2rem 0.4rem;
        border-radius: 3px;
        display: flex;
        justify-content: space-between;
    }

    /* Add search input for points table */
    .search-filter {
        display: flex;
        margin-bottom: 1rem;
        background-color: var(--color-bg-deep);
        padding: 0.75rem;
        border-radius: 6px;
    }

    .search-input {
        flex-grow: 1;
        padding: 0.5rem 0.75rem;
        border: 1px solid var(--color-border-medium);
        border-radius: 4px;
        background-color: var(--color-bg-medium);
        color: var(--color-text-primary);
        font-size: 0.9rem;
    }

    .search-input::placeholder {
        color: var(--color-text-secondary);
        opacity: 0.7;
    }

    /* Highlight player with points */
    tr[data-points="0"] {
        opacity: 0.8;
    }

    tr[data-points="0"]:hover {
        opacity: 1;
    }

    tr[data-points]:not([data-points="0"]) td:last-child {
        color: var(--color-accent-positive);
        font-weight: 600;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .dfsoccer-teams-table, 
        .dfsoccer-points-table {
            display: block;
            overflow-x: auto;
        }
        
        .player-list-row ul {
            grid-template-columns: 1fr;
        }
        
        .player-stats {
            grid-template-columns: repeat(2, 1fr);
        }
    }
    </style>';

    // Updated JavaScript for toggling the accordion
    $output .= '<script>
        jQuery(document).ready(function($) {
            // Toggle players functionality 
            $(".toggle-players").click(function(e) {
                e.preventDefault();
                var userId = $(this).data("user");
                $("#players-" + userId).toggle();
                
                // Just adding button text update
                $(this).text($("#players-" + userId).is(":visible") ? "Hide" : "Show");
            });
            
            // Fixed accordion functionality
            $(".accordion-toggle").click(function() {
                var $content = $(this).next(".accordion-content");
                
                // Toggle the active class
                $(this).toggleClass("active");
                $content.toggleClass("active");
                
                // Also handle the slide animation
                $content.slideToggle();
            });
            
            // Add search functionality for the points table
            var pointsTable = $(".dfsoccer-points-table");
            if (pointsTable.length) {
                // Create and insert search box with escaped quotes
                var searchBox = $("<div style=\"margin-bottom: 15px;\"><input type=\"text\" placeholder=\"Search players...\" style=\"padding: 8px; width: 100%; border-radius: 4px; border: 1px solid #ddd;\"></div>");
                pointsTable.before(searchBox);
                
                // Add search functionality
                var searchInput = searchBox.find("input");
                searchInput.on("input", function() {
                    var query = $(this).val().toLowerCase();
                    
                    pointsTable.find("tbody tr").each(function() {
                        var playerName = $(this).find("td:first").text().toLowerCase();
                        $(this).toggle(playerName.indexOf(query) > -1);
                    });
                });
            }
            
            // Add highlighting for players with points
            $(".dfsoccer-points-table tbody tr").each(function() {
                var pointsCell = $(this).find("td:last-child");
                var points = parseFloat(pointsCell.text().trim()) || 0;
                
                if (points > 0) {
                    pointsCell.css("color", "#4ade80").css("font-weight", "bold");
                }
            });
        });
    </script>';

    return $output;
}
add_shortcode('display_api_points', 'dfsoccer_display_api_points_shortcode');


function show_time_until_earliest_fixture_shortcode() {
    $current_time = current_time('Y-m-d H:i:s');
    
    // Get the league admin information
    $league_id = get_the_ID();
    $post = get_post($league_id);
    $post_author_id = $post ? $post->post_author : 0;
    $admin_user = $post_author_id ? get_user_by('id', $post_author_id) : null;
    $admin_name = $admin_user ? $admin_user->display_name : 'Unknown';
    
    ob_start();
    ?>
    <style>
        /* Countdown Timer Styles */
        .fixture-countdown {
            background-color: var(--color-green-800, #166534);
            padding: 0.75rem 0;
            text-align: center;
            border-radius: 0.5rem;
            margin: 1rem 0;
        }
        
        .countdown-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .countdown-label {
            font-weight: 500;
            color: #ffffff;
            margin-right: 0.5rem;
        }
        
        .countdown-units {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .countdown-unit {
            background-color: var(--color-green-950, #052e16);
            padding: 0.25rem 0.75rem;
            border-radius: 0.5rem;
            color: #ffffff;
        }
        
        .countdown-value {
            font-weight: bold;
        }
        
        .countdown-label-sm {
            font-size: 0.75rem;
            color: var(--color-green-300, #86efac);
            margin-left: 0.25rem;
        }
        
        .remind-btn {
            background-color: var(--color-yellow-500, #eab308);
            color: var(--color-green-950, #052e16);
            font-weight: bold;
            padding: 0.25rem 1rem;
            border-radius: 9999px;
            margin-left: 1rem;
            display: inline-flex;
            align-items: center;
            transition: background-color 0.2s;
            border: none;
            cursor: pointer;
        }
        
        .remind-btn:hover {
            background-color: var(--color-yellow-400, #facc15);
        }
        
        .countdown-icon {
            color: var(--color-green-300, #86efac);
            margin-right: 0.5rem;
        }
        
        @media (max-width: 768px) {
            .countdown-container {
                flex-direction: column;
                align-items: center;
            }
            
            .remind-btn {
                margin-left: 0;
                margin-top: 0.5rem;
            }
        }
    </style>

    <div class="fixture-countdown">
        <div class="countdown-container">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="countdown-icon">
                <circle cx="12" cy="12" r="10"></circle>
                <polyline points="12 6 12 12 16 14"></polyline>
            </svg>
            <span class="countdown-label">First Matchday Starts In:</span>
            <div class="countdown-units" id="fixture-countdown-units">
                <div class="countdown-unit">
                    <span class="countdown-value" id="days">--</span>
                    <span class="countdown-label-sm">DAYS</span>
                </div>
                <div class="countdown-unit">
                    <span class="countdown-value" id="hours">--</span>
                    <span class="countdown-label-sm">HRS</span>
                </div>
                <div class="countdown-unit">
                    <span class="countdown-value" id="minutes">--</span>
                    <span class="countdown-label-sm">MIN</span>
                </div>
                <div class="countdown-unit">
                    <span class="countdown-value" id="seconds">--</span>
                    <span class="countdown-label-sm">SEC</span>
                </div>
            </div>
            <button class="remind-btn" 
                    data-admin-id="<?php echo esc_attr($post_author_id); ?>" 
                    data-admin-name="<?php echo esc_attr($admin_name); ?>"
                    data-league-id="<?php echo esc_attr($league_id); ?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 4px;">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                League Admin
            </button>
        </div>
    </div>
    
    <div class="timertomertrmer-load-time" data-loadtime="<?php echo esc_attr($current_time); ?>" style="display: none;">
        <?php echo esc_html(date('Y-m-d\TH:i:s', strtotime($current_time))); ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get fixture elements and extract dates
            function timertomertrmerGetFixtureElements() {
                const container = document.querySelector('.dfsoccer-fixture-and-player-selection');
                return container ? Array.from(container.querySelectorAll('p')) : [];
            }
            
function timertomertrmerExtractDates(elements) {
    // Updated regex to match the new date format: "Apr 20, 2024 • 12:34"
    const dateRegex = /(\w{3}\s\d{1,2},\s\d{4})\s•\s(\d{1,2}:\d{1,2})/;
    
    return elements
        .map(el => {
            const match = el.textContent.match(dateRegex);
            if (match) {
                // Combine the date and time parts
                const dateTimeStr = `${match[1]} ${match[2]}`;
                // Parse the combined date and time string
                return new Date(dateTimeStr);
            }
            return null;
        })
        .filter(date => date instanceof Date && !isNaN(date));
}
            
            function timertomertrmerFindEarliestDate(dates) {
                if (!dates.length) return null;
                return new Date(Math.min(...dates.map(d => d.getTime())));
            }
            
            // Update countdown function that refreshes every second
            function updateCountdown(earliestDate, pageLoadDate) {
                const countdown = document.getElementById('fixture-countdown-units');
                
                // Calculate time remaining and update display every second
                const timerInterval = setInterval(function() {
                    const now = new Date();
                    const diffMs = earliestDate - now;
                    
                    if (diffMs <= 0) {
                        clearInterval(timerInterval);
                        countdown.innerHTML = '<div class="countdown-unit" style="width: 100%">The earliest fixture has started!</div>';
                        return;
                    }
                    
                    // Calculate days, hours, minutes, seconds
                    const days = Math.floor(diffMs / 86400000);
                    const hours = Math.floor((diffMs % 86400000) / 3600000);
                    const minutes = Math.floor((diffMs % 3600000) / 60000);
                    const seconds = Math.floor((diffMs % 60000) / 1000);
                    
                    // Update the countdown values
                    document.getElementById('days').textContent = days;
                    document.getElementById('hours').textContent = hours;
                    document.getElementById('minutes').textContent = minutes;
                    document.getElementById('seconds').textContent = seconds;
                }, 1000);
            }
            
            // Initialize countdown
            const pageLoadData = document.querySelector('.timertomertrmer-load-time');
            const pageLoadTime = pageLoadData ? new Date(pageLoadData.textContent.trim()) : new Date();
            
            const fixtures = timertomertrmerGetFixtureElements();
            const fixtureDates = timertomertrmerExtractDates(fixtures);
            const earliest = timertomertrmerFindEarliestDate(fixtureDates);
            
            if (earliest) {
                updateCountdown(earliest, pageLoadTime);
            } else {
                // If no fixtures found, show a message
                document.getElementById('fixture-countdown-units').innerHTML = 
                    '<div class="countdown-unit" style="width: 100%">No upcoming fixtures found</div>';
            }
            
            // League admin button functionality
            const remindBtn = document.querySelector('.remind-btn');
            if (remindBtn) {
                remindBtn.addEventListener('click', function() {
                    const adminId = this.getAttribute('data-admin-id');
                    const adminName = this.getAttribute('data-admin-name');
                    const leagueId = this.getAttribute('data-league-id');
                    
                    const message = `League Admin Information:

Name: ${adminName}
User ID: ${adminId}
League ID: ${leagueId}

API Endpoint for this user's leagues:
${window.location.origin}/wp-json/dfsoccer/v1/available-leagues/${adminId}

You can use this User ID (${adminId}) in the API to fetch leagues created by this admin.`;
                    
                    alert(message);
                });
            }
        });
    </script>
    <?php
    return ob_get_clean();
}
add_shortcode('time_until_fixture', 'show_time_until_earliest_fixture_shortcode');


 function display_league_rules_shortcode($atts) {
    $atts = shortcode_atts([
        'league_id' => ''
    ], $atts, 'league_rules');

    $league_id = intval($atts['league_id']);
    
    // Get league-specific rules if they exist
    $points_rules = get_post_meta($league_id, 'dfsoccer_points_rules', true);
    
    // Default rules (your existing rules array...)
    $default_rules = [
        'goalkeeper' => [
            'goals' => 10,
            'own' => -7,
            'assists' => 7,
            'minutes' => 0.02,
            'red' => -4,
            'yellow' => -1,
            'conceded' => -2,
            'penalties' => 8,
            'missed' => -4
        ],
        'defender' => [
            'goals' => 7,
            'own' => -7,
            'assists' => 5,
            'minutes' => 0.02,
            'red' => -4,
            'yellow' => -1,
            'conceded' => -2,
            'penalties' => 8,
            'missed' => -4
        ],
        'midfielder' => [
            'goals' => 6,
            'own' => -7,
            'assists' => 5,
            'minutes' => 0.02,
            'red' => -4,
            'yellow' => -1,
            'conceded' => -1,
            'penalties' => 8,
            'missed' => -4
        ],
        'attacker' => [
            'goals' => 5,
            'own' => -7,
            'assists' => 5,
            'minutes' => 0.02,
            'red' => -4,
            'yellow' => -1,
            'conceded' => 0,
            'penalties' => 8,
            'missed' => -4
        ]
    ];

    // Use league-specific rules if set, otherwise use default rules
    $rules = $points_rules ?: $default_rules;

    $output = '<div class="league-rules-container">';
    
    // Add styles
    $output .= '<style>
       .league-rules-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: var(--color-green-950);
}
.rules-accordion-header {
    background: #166534;
    padding: 15px 20px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid var(--color-green-300);
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.rules-accordion-header:hover {
    background: #19492b;
}
.rules-accordion-content {
    display: none;
}
.rules-toggle-icon {
    transition: transform 0.3s ease;
    color: var(--color-green-700);
}
.rules-accordion-header.active .rules-toggle-icon {
    transform: rotate(180deg);
}
.position-section {
    background: white;
    border: 1px solid var(--color-green-200);
    border-radius: 8px;
    margin-bottom: 20px;
    overflow: hidden;
}
.position-header {
    background: var(--color-green-700);
    color: white;
    padding: 12px 20px;
    font-weight: 600;
    font-size: 1.1em;
    border-bottom: 1px solid var(--color-green-600);
    text-transform: capitalize;
}
.rules-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 10px;
    padding: 15px;
}
.rule-item {
    background: var(--color-green-100, #f8fafc);
    padding: 12px;
    border-radius: 6px;
    border: 1px solid var(--color-green-200);
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.rule-label {
    font-weight: 500;
    color: var(--color-green-900);
}
.rule-value {
    font-weight: 600;
    color: var(--color-green-950);
    padding: 4px 8px;
    border-radius: 4px;
    background: white;
}
.rule-value.positive { color: var(--color-green-600); }
.rule-value.negative { color: #dc2626; } /* Kept as red for contrast */
.league-title {
    color: var(--color-green-800);
    margin: 0;
    font-size: 1.5em;
}
    </style>';

    // Add accordion header
    $output .= '<div class="rules-accordion-header">';
    $output .= '<h2 class="league-title" style="color:white">' . esc_html(get_the_title($league_id)) . ' - Point Rules</h2>';
    $output .= '<span class="rules-toggle-icon">▼</span>';
    $output .= '</div>';

    // Add accordion content
    $output .= '<div class="rules-accordion-content">';

    // Helper function to format rule names
    $formatRuleName = function($name) {
        $names = [
            'goals' => 'Goals Scored',
            'own' => 'Own Goals',
            'assists' => 'Assists',
            'minutes' => 'Minutes Played',
            'red' => 'Red Cards',
            'yellow' => 'Yellow Cards',
            'conceded' => 'Goals Conceded',
            'penalties' => 'Penalties Saved',
            'missed' => 'Penalties Missed'
        ];
        return $names[$name] ?? ucfirst($name);
    };

    // Display rules for each position
    foreach ($rules as $position => $position_rules) {
        $output .= '<div class="position-section">';
        $output .= '<div class="position-header">' . ucfirst($position) . '</div>';
        $output .= '<div class="rules-grid">';
        
        foreach ($position_rules as $rule => $points) {
            $valueClass = $points > 0 ? 'positive' : ($points < 0 ? 'negative' : '');
            $formattedPoints = $points;
            
            if ($rule === 'minutes') {
                $formattedPoints = $points . ' per minute';
            } else {
                $formattedPoints = ($points > 0 ? '+' : '') . $points;
            }

            $output .= '<div class="rule-item">';
            $output .= '<span class="rule-label">' . $formatRuleName($rule) . '</span>';
            $output .= '<span class="rule-value ' . $valueClass . '">' . $formattedPoints . '</span>';
            $output .= '</div>';
        }
        
        $output .= '</div></div>';
    }

    // Add clean sheet bonus information
    $output .= '<div class="position-section">';
    $output .= '<div class="position-header">Clean Sheet Bonus Points (60+ minutes played)</div>';
    $output .= '<div class="rules-grid">';
    $output .= '<div class="rule-item"><span class="rule-label">Goalkeeper</span><span class="rule-value positive">+5</span></div>';
    $output .= '<div class="rule-item"><span class="rule-label">Defender</span><span class="rule-value positive">+5</span></div>';
    $output .= '<div class="rule-item"><span class="rule-label">Midfielder</span><span class="rule-value positive">+3</span></div>';
    $output .= '</div></div>';

    $output .= '</div>'; // Close rules-accordion-content

    // Add JavaScript for accordion functionality
    $output .= '<script>
    document.addEventListener("DOMContentLoaded", function() {
        const header = document.querySelector(".rules-accordion-header");
        const content = document.querySelector(".rules-accordion-content");
        
        header.addEventListener("click", function() {
            this.classList.toggle("active");
            if (content.style.display === "block") {
                content.style.display = "none";
            } else {
                content.style.display = "block";
            }
        });
    });
    </script>';

    $output .= '</div>'; // Close league-rules-container

    return $output;
}
	


add_shortcode('league_rules', 'display_league_rules_shortcode');





// Register the new shortcode
if (function_exists('add_shortcode')) {
    add_shortcode('dfsoccer_layout_ux_optimized', 'dfsoccer_layout_ux_optimized_shortcode');
}






 function dfsoccer_display_players_for_fixtures_shortcode_refactored($atts) {
    // --- Security & Basic Checks ---


    $atts = shortcode_atts(array(
        'league_id' => '0'
    ), $atts, 'players_for_fixtures_refactored');

    $league_id = intval($atts['league_id']);
    // Assuming 'league' is your CPT slug for leagues. Adjust if needed.
    if (!$league_id || !get_post_type($league_id) || get_post_type($league_id) !== 'league') {
        // Try getting CPT from post ID if not 'league' - more robust check needed potentially
         if(!get_post($league_id)) {
             return '<p>Invalid League ID specified.</p>';
         }
         // If you have multiple CPTs for leagues, add more checks here.
    }

	$instance_id = ''; // or some appropriate default value
    $user_id = get_current_user_id();

    // --- Meta Keys & League Data ---
    $player_meta_key    = 'dfsoccer_selected_players_' . $league_id;
    $fixture_meta_key   = 'dfsoccer_saved_fixtures_' . $league_id;
    // IMPORTANT: Adjust 'dfsoccer_league_budget' if your meta key is different
    $budget             = floatval(get_post_meta($league_id, 'dfsoccer_league_budget', true));
    $saved_fixtures     = get_post_meta($league_id, $fixture_meta_key, true);
    $saved_player_ids   = (array) get_user_meta($user_id, $player_meta_key, true); // Get currently saved selections

    if (empty($saved_fixtures) || !is_array($saved_fixtures)) {
        // Try fetching fixtures differently if the above fails, or return error
         // Example: Maybe fixtures are stored elsewhere? Add logic here if needed.
         // For now, assume it's an error if not found via meta key:
         // return '<p>No fixtures found for this league to select players from.</p>';
         // Allow proceeding even without fixtures if players should always be selectable?
         // Let's assume fixtures are needed for now based on original code.
         // Check if the meta value *exists* but is empty vs not existing at all.
         $meta_exists = metadata_exists('post', $league_id, $fixture_meta_key);
         if(!$meta_exists || empty($saved_fixtures)) {
              return '<p>No fixtures found for this league (ID: '.esc_html($league_id).', Meta Key: '.esc_html($fixture_meta_key).'). Cannot select players.</p>';
         }
    }


    // --- Variables for Messages ---
    $error_message = '';
    $success_message = '';

    // --- Form Submission Handling ---
    // Use a unique name attribute for the submit button per league
// --- Form Submission Handling ---
// Use a unique name attribute for the submit button per league
$submit_button_name = 'submit_players_' . $league_id;
if (isset($_POST[$submit_button_name]) && isset($_POST['_wpnonce_player_selection_' . $league_id])) {
    // First check if user is logged in
    if (!is_user_logged_in()) {
        $error_message = 'You must be logged in to perform this action.';
    } 
    // Then check if the league has started
    else if (dfsoccer_has_league_started($league_id)) {
        $error_message = "This league has already started. Selections can no longer be saved.";
    }
    // Then proceed with normal validation if those checks pass
    else if (wp_verify_nonce($_POST['_wpnonce_player_selection_' . $league_id], 'dfsoccer_select_players_' . $league_id . '_' . $user_id)) {
        $submitted_player_ids = isset($_POST['selected_players']) && is_array($_POST['selected_players'])
                                ? array_map('intval', $_POST['selected_players'])
                                : [];

        $total_cost = 0;
        $valid_players = true;
        $player_post_type = 'dfsoccer_player'; // CORRECT: Use the name from your old code

        // Server-side validation
        foreach ($submitted_player_ids as $player_id) {
            // Basic check if it's a valid player post
            $post_status = get_post_status($player_id);
            $post_type = get_post_type($player_id);

            // Allow if post exists and is the correct type (and published or other valid status)
            if (!$post_status || $post_type !== $player_post_type || !in_array($post_status, ['publish', 'private'])) { // Add other valid statuses if necessary
                 $valid_players = false;
                 $error_message = 'Invalid player data submitted (ID: ' . esc_html($player_id) . '). Post type or status incorrect.';
                 break;
            }

            // IMPORTANT: Adjust 'dfsoccer_price' if your meta key is different
            $player_price_meta = get_post_meta($player_id, 'dfsoccer_price', true);
            if ($player_price_meta === '' || $player_price_meta === false) { // Check if meta key exists but might be empty
                 // Decide if missing price is an error or means 0 price
                 // Assuming it's an error for now:
                 $valid_players = false;
                 $error_message = 'Price not found for player ID: ' . esc_html($player_id) . '.';
                 break;
            }
            $player_price = floatval($player_price_meta);

            if (is_nan($player_price)) { // Should not happen if meta exists and is numeric, but check anyway
                 $valid_players = false;
                 $error_message = 'Invalid price found for player ID: ' . esc_html($player_id) . '.';
                 break;
            }
            $total_cost += $player_price;
        }

        if ($valid_players) {
            $player_count = count($submitted_player_ids);
            // --- Define Your Selection Rules Here ---
            $required_player_count = 6; // Example: Must select exactly 6 (Adjust!)
            // Add position limit validation if needed server-side here
            
            if ($player_count < $required_player_count) {
                $error_message = "You must select exactly {$required_player_count} players. You selected {$player_count}.";
            } elseif ($player_count > $required_player_count) {
                $error_message = "You cannot select more than {$required_player_count} players. You selected {$player_count}.";
            } elseif ($total_cost > $budget) {
                $error_message = 'Your selected team is over budget. Total Cost: $' . number_format($total_cost, 0) . ', Budget: $' . number_format($budget, 0);
            } else {
                // Success! Save the selection.
                update_user_meta($user_id, $player_meta_key, $submitted_player_ids);
                $success_message = 'Players selected successfully!';
                $saved_player_ids = $submitted_player_ids; // Update saved IDs for immediate display
            }
        } else if (empty($error_message)) {
            $error_message = 'Security check failed. Please try again.';
        }
    } else {
        $error_message = 'Security check failed. Please try again.';
    }
}

    // --- Fetch All Relevant Players ---
    $participating_club_ids = [];
    if (is_array($saved_fixtures) && !empty($saved_fixtures)) {
        $participating_club_ids = array_unique(array_merge(
            array_column($saved_fixtures, 'home_club_id'),
            array_column($saved_fixtures, 'away_club_id')
        ));
        // Ensure IDs are integers
        $participating_club_ids = array_map('intval', array_filter($participating_club_ids));
    } else {
        // Handle case where fixtures aren't used/found but players still need loading?
        // Maybe load all players? Or players from a default set of clubs?
        // For now, proceed assuming empty clubs is possible if no fixtures found/needed.
    }


    $all_players = [];
$player_post_type = 'dfsoccer_player'; // CORRECT: Use the name from your old code
$club_meta_key = 'dfsoccer_club_id'; // CORRECT: Use the name from your old code
    $price_meta_key = 'dfsoccer_price'; // IMPORTANT: Adjust price meta key if needed
    $position_meta_key = 'dfsoccer_position'; // IMPORTANT: Adjust position meta key if needed

    $player_query_args = array(
        'post_type' => $player_post_type,
        'posts_per_page' => -1, // Get all players
        'post_status' => 'publish', // Or array('publish', 'private') if needed
        'meta_query' => array(
            'relation' => 'AND',
             // Ensure player has a price and position
            array(
                'key' => $price_meta_key,
                'compare' => 'EXISTS',
            ),
             array( // Only include players with a non-empty price
                'key' => $price_meta_key,
                'value' => '',
                'compare' => '!='
            ),
            array(
                'key' => $position_meta_key,
                'compare' => 'EXISTS',
            ),
            array( // Only include players with a non-empty position
                'key' => $position_meta_key,
                'value' => '',
                'compare' => '!='
            ),
        ),
         'orderby' => 'meta_value_num', // Sort by price initially (DESC)
         'meta_key' => $price_meta_key,
         'order' => 'DESC',
    );

    // Conditionally add club filter only if participating_club_ids is not empty
    if (!empty($participating_club_ids)) {
         $player_query_args['meta_query'][] = array(
            'key' => $club_meta_key,
            'value' => $participating_club_ids,
            'compare' => 'IN',
         );
    } else {
        // If no clubs from fixtures, maybe add a check for the club meta key existing?
        // $player_query_args['meta_query'][] = array('key' => $club_meta_key, 'compare' => 'EXISTS');
        // Decide based on your logic if players *must* belong to a club from fixtures.
    }

    $player_query = new WP_Query($player_query_args);

    if ($player_query->have_posts()) {
        while ($player_query->have_posts()) {
            $player_query->the_post();
            $player_id = get_the_ID();
            $club_id = get_post_meta($player_id, $club_meta_key, true); // Fetch the club ID
            $price = get_post_meta($player_id, $price_meta_key, true);
            $position = get_post_meta($player_id, $position_meta_key, true);

            // Basic validation - skip player if essential data missing/invalid
            if ($price === '' || $price === false || $position === '' || $position === false) {
                continue; // Skip player if price or position is missing
            }

            $all_players[] = array(
                'id'        => $player_id,
                'name'      => get_the_title(),
                'price'     => floatval($price),
                'position'  => strtolower($position), // e.g., 'goalkeeper', 'defender'
                'club_id'   => $club_id ? intval($club_id) : 0,
                'club_name' => $club_id ? get_the_title($club_id) : 'N/A', // Get club name
            );
        }
        wp_reset_postdata();
    }

     // Prepare club data for filters (use all clubs found among loaded players)
     $club_data = [];
     $all_player_club_ids = array_unique(array_column($all_players, 'club_id'));
     $all_player_club_ids = array_filter($all_player_club_ids); // Remove 0 or empty IDs
     foreach($all_player_club_ids as $c_id) {
         $title = get_the_title($c_id);
         if ($title) { // Ensure club post exists and has a title
            $club_data[$c_id] = $title;
         }
     }
     // Sort clubs alphabetically by name for the filter dropdown
    asort($club_data);


    // --- Start Output Buffering ---
    ob_start();
    ?>

    <style>
	     /* --- Paste ALL the CSS rules from the previous complete example here --- */
            /* --- Base & Variables --- */
            :root {
                --color-bg-deep: #052e16;
                --color-bg-medium: #14532d;
                --color-bg-light: #166534;
                --color-bg-card: #15803d;
                --color-bg-field: linear-gradient(to bottom, #16a34a, #22c55e);
                --color-text-primary: #ffffff;
                --color-text-secondary: #bbf7d0;
                --color-text-accent: #86efac;
                --color-accent-yellow: #eab308;
                --color-accent-yellow-dark: #ca8a04;
                --color-accent-positive: #4ade80; /* Bright Green */
                --color-accent-negative: #f87171; /* Red */
                --color-border-light: #166534;
                --color-border-medium: #15803d;
                --color-modal-overlay: rgba(0, 0, 0, 0.7);

                --font-family-base: 'Inter', sans-serif;
                --header-height-desktop: 65px;
                --header-height-mobile: auto; /* Allow header to grow if needed */
                --footer-height: 60px;
            }

            *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

             /* Apply base styles directly to the container for isolation */
            .dfsoccer-app-container {
                font-family: var(--font-family-base);
                background-color: var(--color-bg-medium);
                color: var(--color-text-primary);
                line-height: 1.5;
                border: 1px solid var(--color-border-light); /* Optional: Frame the component */
                border-radius: 8px;
                overflow: hidden; /* Contain borders/shadows */
                margin-bottom: 1.5rem; /* Add some space below */
            }

            .dfsoccer-app-container button {
                font-family: inherit;
                cursor: pointer;
                border: none;
                background: none;
                color: inherit;
                padding: 0; /* Remove default padding */
            }
            .dfsoccer-app-container svg { display: block; } /* Prevent inline spacing issues */

            /* --- Layout --- */
            .dfsoccer-app-container { /* Use a specific class for the shortcode container */
                display: flex;
                flex-direction: column;
                /* height: 100%; Removed fixed height */
                width: 100%;
            }

            .dfsoccer-app-header {
                min-height: var(--header-height-desktop); /* Ensure minimum height */
                background-color: var(--color-bg-deep);
                border-bottom: 1px solid var(--color-border-light);
                display: flex;
                flex-wrap: wrap; /* Allow wrapping on small screens */
                align-items: center;
                padding: 0.75rem 1rem; /* Adjusted padding */
                gap: 0.75rem; /* Adjusted gap */
                flex-shrink: 0;
            }

            .dfsoccer-main-content {
                flex-grow: 1; /* Take remaining vertical space */
                display: flex;
                overflow: hidden; /* Prevent this container from scrolling */
            }

            .dfsoccer-team-view { /* Left Column / Top Stack */
                flex: 0 0 40%; /* Desktop width */
                max-width: 550px; /* Optional max width */
                padding: 1rem; /* Adjusted padding */
                display: flex;
                flex-direction: column;
                gap: 1rem;
                border-right: 1px solid var(--color-border-light); /* Desktop border */
                overflow-y: auto; /* Allow scrolling if content overflows */
                /* height: 100%; -- Let flex determine height */
            }

            .dfsoccer-player-pool { /* Right Column / Bottom Stack */
                flex: 1 1 auto; /* Desktop: Takes remaining space */
                display: flex;
                flex-direction: column;
                overflow: hidden; /* Crucial: This column manages its own scroll */
            /* height: 100%; -- Let flex determine height */
            }

            .dfsoccer-app-footer {
                height: var(--footer-height);
                background-color: var(--color-bg-deep);
                border-top: 1px solid var(--color-border-light);
                display: flex;
                align-items: center;
                justify-content: flex-end; /* Align button to right */
                padding: 0 1rem; /* Adjusted padding */
                flex-shrink: 0;
            }

            /* --- Header Components --- */
            .dfsoccer-fixture-countdown {
                background-color: var(--color-bg-light); padding: 0.4rem 0.8rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; order: 1; flex-shrink: 0;
            }
            .dfsoccer-countdown-icon { width: 14px; height: 14px; color: var(--color-text-accent); flex-shrink: 0; }
            .dfsoccer-countdown-label { font-weight: 500; color: var(--color-text-secondary); margin-right: 0.25rem; white-space: nowrap; }
            .dfsoccer-countdown-units { display: flex; gap: 0.3rem; }
            .dfsoccer-countdown-unit { background-color: var(--color-bg-deep); padding: 0.15rem 0.4rem; border-radius: 4px; }
            .dfsoccer-countdown-value { font-weight: bold; }
            .dfsoccer-countdown-label-sm { font-size: 0.65rem; color: var(--color-text-accent); margin-left: 0.1rem; }

            .dfsoccer-fixture-info-compact {
                font-size: 0.75rem; color: var(--color-text-secondary); order: 2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-grow: 1; min-width: 100px; text-align: left;
            }
            .dfsoccer-fixture-info-compact strong { color: var(--color-text-primary); }
            .dfsoccer-fixture-info-compact .teams { margin-right: 0.5rem; }
            .dfsoccer-fixture-info-compact .time { font-weight: 500; color: var(--color-text-accent); }

            .dfsoccer-app-nav { display: flex; gap: 0.4rem; order: 3; flex-shrink: 0; }
            .dfsoccer-nav-button {
                padding: 0.4rem 0.6rem; border-radius: 6px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem; transition: background-color 0.2s ease; white-space: nowrap;
            }
            .dfsoccer-nav-button:hover { background-color: var(--color-bg-card); color: var(--color-text-primary); }
            .dfsoccer-nav-button svg { width: 16px; height: 16px; fill: currentColor; flex-shrink: 0; }
            .dfsoccer-nav-button .button-text { display: inline; }

            /* --- Team View Components --- */
            .dfsoccer-team-view h3 {
                color: var(--color-text-accent); font-size: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--color-border-light); padding-bottom: 0.5rem;
            }
            .dfsoccer-soccer-field {
                position: relative; width: 100%; height: 350px; background: var(--color-bg-field); border: 2px solid var(--color-border-medium); border-radius: 8px; background-image: radial-gradient(circle at center, transparent 49px, rgba(255,255,255,0.3) 49px, rgba(255,255,255,0.3) 50px, transparent 50px), linear-gradient(to right, transparent calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% + 0.5px), transparent calc(50% + 0.5px)); background-size: 100px 100px, 100% 1px; background-position: center center, center center; background-repeat: no-repeat; transition: height 0.3s ease;
            }
            .dfsoccer-player-positions { position: absolute; inset: 0; display: grid; grid-template-rows: repeat(4, 1fr); grid-template-columns: repeat(5, 1fr); padding: 10px; gap: 5px; }
             /* Placeholder for player spots - you'll add these dynamically */
            .dfsoccer-player-spot { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; min-height: 50px; position: relative; }
            .dfsoccer-player-jersey-shape { width: 35px; height: 40px; clip-path: polygon(16% 20%, 35% 13%, 40% 20%, 60% 20%, 65% 13%, 86% 20%, 100% 31%, 85% 44%, 80% 40%, 80% 100%, 20% 100%, 20% 40%, 15% 44%, 0 31%); background-color: #555; margin-bottom: 3px; border: 1px solid var(--color-border-medium); flex-shrink: 0; transition: width 0.3s, height 0.3s, background-color 0.3s; }
            .dfsoccer-player-spot.filled .dfsoccer-player-jersey-shape { background-color: var(--color-accent-yellow); border-color: var(--color-accent-yellow-dark); }
            .dfsoccer-player-details { padding: 2px 3px; background-color: rgba(5, 46, 22, 0.7); border-radius: 4px; min-height: 1.8em; visibility: hidden; }
            .dfsoccer-player-spot.filled .dfsoccer-player-details { visibility: visible; }
            .dfsoccer-player-spot-name { font-size: 8px; font-weight: bold; color: var(--color-text-primary); line-height: 1.1; display: block; word-wrap: break-word; max-width: 100%; }
            .dfsoccer-player-spot-price { font-size: 7px; color: var(--color-text-accent); display: block; font-weight: 500; }
             /* Position grid-area rules */
            .dfsoccer-player-pos-gk { grid-area: 4 / 3 / 5 / 4; } .dfsoccer-player-pos-d1 { grid-area: 3 / 1 / 4 / 2; } .dfsoccer-player-pos-d2 { grid-area: 3 / 2 / 4 / 3; } .dfsoccer-player-pos-d3 { grid-area: 3 / 4 / 4 / 5; } .dfsoccer-player-pos-d4 { grid-area: 3 / 5 / 4 / 6; } .dfsoccer-player-pos-m1 { grid-area: 2 / 1 / 3 / 2; } .dfsoccer-player-pos-m2 { grid-area: 2 / 2 / 3 / 3; } .dfsoccer-player-pos-m3 { grid-area: 2 / 3 / 3 / 4; } .dfsoccer-player-pos-m4 { grid-area: 2 / 4 / 3 / 5; } .dfsoccer-player-pos-m5 { grid-area: 2 / 5 / 3 / 6; } .dfsoccer-player-pos-f1 { grid-area: 1 / 2 / 2 / 3; } .dfsoccer-player-pos-f2 { grid-area: 1 / 3 / 2 / 4; } .dfsoccer-player-pos-f3 { grid-area: 1 / 4 / 2 / 5; }


            .dfsoccer-budget-overview { background-color: rgba(20, 83, 45, 0.6); padding: 1rem; border-radius: 8px; }
            .dfsoccer-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
            .dfsoccer-stat-card { background-color: var(--color-bg-light); padding: 0.5rem; border-radius: 6px; text-align: center; }
            .dfsoccer-stat-label { display: block; font-size: 0.65rem; color: var(--color-text-secondary); margin-bottom: 0.2rem; text-transform: uppercase; }
            .dfsoccer-stat-value { display: block; font-size: 1rem; font-weight: bold; color: var(--color-text-primary); }
            .dfsoccer-stat-value.negative { color: var(--color-accent-negative); }
            .dfsoccer-stat-value.positive { color: var(--color-accent-positive); }

            .dfsoccer-position-summary { background-color: rgba(20, 83, 45, 0.6); padding: 0.75rem 1rem; border-radius: 8px; }
            .dfsoccer-selection-counts { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem; justify-content: center; min-height: 1.5em; /* Placeholder height */ }
            .dfsoccer-position-count { background-color: var(--color-bg-light); color: var(--color-text-secondary); padding: 0.25rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem; }
            .dfsoccer-position-count .icon-status { width: 8px; height: 8px; border-radius: 50%; background-color: var(--color-accent-negative); display: inline-block; transition: background-color 0.2s; }
            .dfsoccer-position-count.complete .icon-status { background-color: var(--color-accent-positive); }
            .dfsoccer-position-count.complete { background-color: var(--color-bg-card); color: var(--color-text-primary); }

            .dfsoccer-team-status-bar { padding: 0.6rem 1rem; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.85rem; margin-top: 0.5rem; transition: background-color 0.3s, color 0.3s; }
            .dfsoccer-team-status-bar.incomplete { background-color: var(--color-accent-yellow-dark); color: var(--color-text-primary); }
            .dfsoccer-team-status-bar.complete { background-color: var(--color-accent-positive); color: var(--color-bg-deep); }
            .dfsoccer-team-status-bar.overbudget { background-color: var(--color-accent-negative); color: var(--color-text-primary); }

            /* --- Player Pool Components --- */
            .dfsoccer-player-pool__header {
                padding: 0.75rem 1rem; background-color: var(--color-bg-medium); border-bottom: 1px solid var(--color-border-light); z-index: 10; flex-shrink: 0;
            }
            .dfsoccer-search-filter-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
            .dfsoccer-search-input-container { flex-grow: 1; position: relative; min-width: 150px; }
            .dfsoccer-search-input { width: 100%; padding: 0.5rem 0.7rem 0.5rem 2.2rem; border-radius: 6px; border: 1px solid var(--color-border-light); background-color: var(--color-bg-light); color: var(--color-text-primary); font-size: 0.85rem; }
            .dfsoccer-search-input::placeholder { color: var(--color-text-secondary); opacity: 0.8; }
            .dfsoccer-search-icon { position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; stroke: var(--color-text-secondary); stroke-width: 2; pointer-events: none; }
            .dfsoccer-filter-buttons { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; justify-content: center; }
            .dfsoccer-filter-btn { padding: 0.4rem 0.7rem; border-radius: 6px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.75rem; font-weight: 500; border: 1px solid transparent; transition: all 0.2s ease; }
            .dfsoccer-filter-btn:hover { background-color: var(--color-bg-card); color: var(--color-text-primary); }
            .dfsoccer-filter-btn.active { background-color: var(--color-accent-yellow); color: var(--color-bg-deep); border-color: var(--color-accent-yellow-dark); font-weight: 600; }
            .dfsoccer-filter-btn svg { width: 14px; height: 14px; fill: currentColor; vertical-align: middle; margin-left: 0.3rem; }
            .dfsoccer-filter-btn.more-filters { padding: 0.4rem; }
            .dfsoccer-filter-btn.more-filters svg { margin-left: 0; }

            .dfsoccer-player-list__header {
                display: grid; grid-template-columns: 3fr 1fr 1.5fr 1.5fr 0.5fr; gap: 0.75rem; padding: 0.5rem 1rem; background-color: var(--color-bg-light); color: var(--color-text-accent); font-size: 0.7rem; font-weight: 600; text-transform: uppercase; z-index: 9; border-bottom: 1px solid var(--color-border-medium); flex-shrink: 0;
            }
            .dfsoccer-player-list__header > div:nth-child(3), .dfsoccer-player-list__header > div:nth-child(4) { text-align: right; }
            .dfsoccer-player-list__header > div:nth-child(4) { display: block; }
            .dfsoccer-player-list__header > div:nth-child(5) { text-align: center; }

            .dfsoccer-player-list__scrollable {
                flex-grow: 1; overflow-y: auto; padding: 0 1rem; min-height: 200px; /* Placeholder area */
            }
            /* Add styles for player list item if you want basic hover effects even when empty */
            .dfsoccer-player-list__item {
                 /* Basic structure/padding if needed, but content will be added */
                 padding: 0.6rem 0; border-bottom: 1px solid var(--color-border-light);
            }


            /* --- Footer Components --- */
            .dfsoccer-save-button { padding: 0.6rem 1.5rem; background-color: var(--color-accent-yellow); color: var(--color-bg-deep); font-weight: bold; font-size: 0.9rem; border-radius: 6px; transition: background-color 0.2s ease, opacity 0.2s ease; }
            .dfsoccer-save-button:hover:not(:disabled) { background-color: var(--color-accent-yellow-dark); }
            .dfsoccer-save-button:disabled { opacity: 0.6; cursor: not-allowed; background-color: var(--color-bg-light); }

            /* --- Modal Styles --- */
            .dfsoccer-modal { position: fixed; inset: 0; background-color: var(--color-modal-overlay); display: flex; align-items: center; justify-content: center; z-index: 10000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0s linear 0.3s; padding: 1rem; }
            .dfsoccer-modal.is-visible { opacity: 1; visibility: visible; transition-delay: 0s; }
            .dfsoccer-modal-content { background-color: var(--color-bg-medium); color: var(--color-text-primary); padding: 1.5rem; border-radius: 8px; max-width: 95%; width: 800px; max-height: 90vh; overflow-y: auto; position: relative; z-index: 10001; border: 1px solid var(--color-border-light); box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
            .dfsoccer-modal-close { position: absolute; top: 8px; right: 10px; background: none; border: none; font-size: 1.8rem; color: var(--color-text-secondary); cursor: pointer; line-height: 1; padding: 0.25rem; }
            .dfsoccer-modal-close:hover { color: var(--color-text-primary); }
            .dfsoccer-modal-content h2 { font-size: 1.2rem; margin-bottom: 1rem; color: var(--color-text-accent); border-bottom: 1px solid var(--color-border-light); padding-bottom: 0.5rem; }
            .dfsoccer-modal-content table { font-size: 0.85rem; width: 100%; border-collapse: collapse; margin-top: 1rem; }
            .dfsoccer-modal-content th, .dfsoccer-modal-content td { padding: 0.5rem; border: 1px solid var(--color-border-light); text-align: left;}
            .dfsoccer-modal-content th { background-color: var(--color-bg-light); font-weight: 600; }
            .dfsoccer-modal-content iframe { width: 100%; min-height: 70vh; border: none; }
             /* More Filters Modal Specific Styles */
             .dfsoccer-filter-options { display: flex; flex-direction: column; gap: 0.75rem; }
             .dfsoccer-filter-options h4 { margin: 0.5rem 0 0.25rem 0; color: var(--color-text-accent); font-size: 0.9rem; }
             .dfsoccer-club-filter-select, .dfsoccer-price-filter-input {
                 width: 100%; padding: 0.5rem 0.7rem; border-radius: 6px; border: 1px solid var(--color-border-light); background-color: var(--color-bg-light); color: var(--color-text-primary); font-size: 0.85rem;
             }
             .dfsoccer-apply-more-filters-btn {
                 padding: 0.6rem 1.2rem; background-color: var(--color-accent-yellow); color: var(--color-bg-deep); font-weight: bold; font-size: 0.9rem; border-radius: 6px; transition: background-color 0.2s ease; align-self: flex-start;
             }
             .dfsoccer-apply-more-filters-btn:hover { background-color: var(--color-accent-yellow-dark); }

            /* --- Responsive Breakpoints --- */
            @media (max-width: 1024px) {
                .dfsoccer-main-content { flex-direction: column; height: auto; overflow-y: visible; overflow-x: hidden; }
                .dfsoccer-team-view { flex: 1 1 auto; max-width: none; border-right: none; border-bottom: 1px solid var(--color-border-light); height: auto; overflow-y: visible; padding-bottom: 1.5rem; }
                .dfsoccer-player-pool { flex: 1 1 auto; height: auto; min-height: 400px; overflow: visible; }
                .dfsoccer-player-list__scrollable { overflow-y: visible; max-height: none; }
                .dfsoccer-soccer-field { height: 300px; }
                .dfsoccer-player-jersey-shape { width: 30px; height: 35px; }
                .dfsoccer-player-spot-name { font-size: 7px; }
                .dfsoccer-player-spot-price { font-size: 6px; }
                .dfsoccer-stats-grid { grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); }
            }
            @media (max-width: 767px) {
                .dfsoccer-app-header { padding: 0.5rem; gap: 0.5rem; min-height: 0; }
                .dfsoccer-fixture-countdown { order: 1; width: 100%; justify-content: center; margin-bottom: 0.5rem; font-size: 0.7rem; padding: 0.3rem 0.6rem; }
                .dfsoccer-countdown-units { gap: 0.2rem;}
                .dfsoccer-countdown-unit { padding: 0.1rem 0.3rem; }
                .dfsoccer-fixture-info-compact { order: 2; width: 100%; text-align: center; margin-bottom: 0.5rem; font-size: 0.7rem; white-space: normal; }
                .dfsoccer-app-nav { order: 3; width: 100%; justify-content: center; gap: 0.25rem; }
                .dfsoccer-nav-button { padding: 0.5rem; gap: 0; }
                .dfsoccer-nav-button .button-text { display: none; }
                .dfsoccer-nav-button svg { width: 18px; height: 18px; }
                .dfsoccer-team-view { padding: 0.75rem; }
                .dfsoccer-player-pool { min-height: 300px; }
                .dfsoccer-soccer-field { height: 250px; }
                .dfsoccer-player-pool__header { padding: 0.5rem 0.75rem; }
                .dfsoccer-search-filter-bar { flex-direction: column; align-items: stretch; }
                .dfsoccer-filter-buttons { justify-content: space-around; width: 100%; margin-top: 0.5rem; }
                .dfsoccer-player-list__header { padding: 0.4rem 0.75rem; grid-template-columns: 3fr 1fr 1.5fr 0.5fr; gap: 0.5rem; font-size: 0.65rem; }
                .dfsoccer-player-list__header > div:nth-child(4) { display: none; }
                /* Adjust player item grid if needed for mobile */
                .dfsoccer-player-list__scrollable .dfsoccer-player-list__item {
                    /* Example: grid-template-columns: 3fr 1fr 1.5fr 0.5fr; gap: 0.5rem; font-size: 0.8rem; */
                }
                .dfsoccer-player-list__scrollable .dfsoccer-player-club { display: none; } /* Hide club column in list items too */
                .dfsoccer-save-button { width: 100%; text-align: center; font-size: 1rem; padding: 0.8rem; }
                .dfsoccer-app-footer { padding: 0.5rem; height: auto; min-height: var(--footer-height); }
                .dfsoccer-modal-content { padding: 1rem; max-width: 100%; width: 100%; height: 95vh; max-height: 95vh;}
                .dfsoccer-modal-content h2 { font-size: 1.1rem; }
            }
        /* --- Paste ALL the CSS rules from your target HTML here --- */
        /* --- Base & Variables --- */
        :root {
            --color-bg-deep: #052e16;
            --color-bg-medium: #14532d;
            --color-bg-light: #166534;
            --color-bg-card: #15803d;
            --color-bg-field: linear-gradient(to bottom, #16a34a, #22c55e);
            --color-text-primary: #ffffff;
            --color-text-secondary: #bbf7d0;
            --color-text-accent: #86efac;
            --color-accent-yellow: #eab308;
            --color-accent-yellow-dark: #ca8a04;
            --color-accent-positive: #4ade80; /* Bright Green */
            --color-accent-negative: #f87171; /* Red */
            --color-border-light: #166534;
            --color-border-medium: #15803d;
            --color-modal-overlay: rgba(0, 0, 0, 0.7);

            --font-family-base: 'Inter', sans-serif;
            --header-height-desktop: 65px;
            --header-height-mobile: auto; /* Allow header to grow if needed */
            --footer-height: 60px;

            /* Dynamic main content height */
             --header-height: var(--header-height-desktop);
            --main-content-height: calc(100vh - var(--header-height) - var(--footer-height));
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        /* Apply base styles directly to the container for isolation */
        .dfsoccer-app-container {
            font-family: var(--font-family-base);
            background-color: var(--color-bg-medium);
            color: var(--color-text-primary);
            line-height: 1.5;
            border: 1px solid var(--color-border-light); /* Optional: Frame the component */
            border-radius: 8px;
            overflow: hidden; /* Contain borders/shadows */
            margin-bottom: 1.5rem; /* Add some space below */
        }

        .dfsoccer-app-container button {
            font-family: inherit;
            cursor: pointer;
            border: none;
            background: none;
            color: inherit;
            padding: 0; /* Remove default padding */
        }
        .dfsoccer-app-container svg { display: block; } /* Prevent inline spacing issues */

        /* --- Layout --- */
        .dfsoccer-app-container { /* Use a specific class for the shortcode container */
            display: flex;
            flex-direction: column;
            /* height: 100%; Removed fixed height */
            width: 100%;
        }

        .dfsoccer-app-header {
            min-height: var(--header-height-desktop); /* Ensure minimum height */
            background-color: var(--color-bg-deep);
            border-bottom: 1px solid var(--color-border-light);
            display: flex;
            flex-wrap: wrap; /* Allow wrapping on small screens */
            align-items: center;
            padding: 0.75rem 1rem; /* Adjusted padding */
            gap: 0.75rem; /* Adjusted gap */
            flex-shrink: 0;
        }

        .dfsoccer-main-content {
            flex-grow: 1; /* Take remaining vertical space */
            display: flex;
            overflow: hidden; /* Prevent this container from scrolling */
        }

        .dfsoccer-team-view { /* Left Column / Top Stack */
            flex: 0 0 40%; /* Desktop width */
            max-width: 550px; /* Optional max width */
            padding: 1rem; /* Adjusted padding */
            display: flex;
            flex-direction: column;
            gap: 1rem;
            border-right: 1px solid var(--color-border-light); /* Desktop border */
            overflow-y: auto; /* Allow scrolling if content overflows */
            /* height: 100%; -- Let flex determine height */
        }

        .dfsoccer-player-pool { /* Right Column / Bottom Stack */
            flex: 1 1 auto; /* Desktop: Takes remaining space */
            display: flex;
            flex-direction: column;
            overflow: hidden; /* Crucial: This column manages its own scroll */
           /* height: 100%; -- Let flex determine height */
        }

        .dfsoccer-app-footer {
            height: var(--footer-height);
            background-color: var(--color-bg-deep);
            border-top: 1px solid var(--color-border-light);
            display: flex;
            align-items: center;
            justify-content: flex-end; /* Align button to right */
            padding: 0 1rem; /* Adjusted padding */
            flex-shrink: 0;
        }

         /* --- Notification Area --- */
        .dfsoccer-notifications {
            padding: 0.75rem 1rem;
            text-align: center;
            font-weight: 500;
            order: -1; /* Show messages at the top inside the container */
            background-color: var(--color-bg-deep); /* Match header */
            border-bottom: 1px solid var(--color-border-light); /* Separator */
            margin: -1px 0 0 0; /* Overlap border slightly */
        }
        .dfsoccer-notifications .error {
            background-color: var(--color-accent-negative);
            color: var(--color-text-primary);
            padding: 0.5rem 1rem;
            border-radius: 4px;
            display: inline-block; /* Prevent full width */
        }
        .dfsoccer-notifications .success {
             background-color: var(--color-accent-positive);
             color: var(--color-bg-deep);
             padding: 0.5rem 1rem;
             border-radius: 4px;
             display: inline-block; /* Prevent full width */
        }

        /* --- Header Components --- */
        .dfsoccer-fixture-countdown { /* Reusing existing structure slightly modified */
            background-color: var(--color-bg-light);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            display: flex;
            align-items: center;
            gap: 0.5rem; /* Reduced gap */
            font-size: 0.75rem; /* Slightly smaller */
            order: 1; /* Default order */
            flex-shrink: 0; /* Prevent shrinking */
        }
        .dfsoccer-countdown-icon { width: 14px; height: 14px; color: var(--color-text-accent); flex-shrink: 0; }
        .dfsoccer-countdown-label { font-weight: 500; color: var(--color-text-secondary); margin-right: 0.25rem; white-space: nowrap; }
        .dfsoccer-countdown-units { display: flex; gap: 0.3rem; }
        .dfsoccer-countdown-unit { background-color: var(--color-bg-deep); padding: 0.15rem 0.4rem; border-radius: 4px; }
        .dfsoccer-countdown-value { font-weight: bold; }
        .dfsoccer-countdown-label-sm { font-size: 0.65rem; color: var(--color-text-accent); margin-left: 0.1rem; }

        .dfsoccer-fixture-info-compact {
             font-size: 0.75rem; color: var(--color-text-secondary);
             order: 2; /* Default order */
             overflow: hidden; /* Prevent long names breaking layout */
             text-overflow: ellipsis; /* Add ... for overflow */
             white-space: nowrap; /* Keep on one line */
             flex-grow: 1; /* Allow to take space but shrink */
             min-width: 100px; /* Prevent collapsing too much */
             text-align: left; /* Align left */
        }
        .dfsoccer-fixture-info-compact strong { color: var(--color-text-primary); }
        .dfsoccer-fixture-info-compact .teams { margin-right: 0.5rem; }
        .dfsoccer-fixture-info-compact .time { font-weight: 500; color: var(--color-text-accent); }

        .dfsoccer-app-nav {
            /* margin-left: auto; -- Remove auto margin for flex wrap */
            display: flex;
            gap: 0.4rem; /* Reduced gap */
            order: 3; /* Default order */
            flex-shrink: 0; /* Prevent shrinking */
        }
        .dfsoccer-nav-button {
            padding: 0.4rem 0.6rem; /* Reduced padding */
            border-radius: 6px;
            background-color: var(--color-bg-light);
            color: var(--color-text-secondary);
            font-size: 0.75rem; /* Slightly smaller */
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 0.3rem; /* Reduced gap */
            transition: background-color 0.2s ease;
            white-space: nowrap; /* Prevent text wrapping initially */
        }
        .dfsoccer-nav-button:hover {
            background-color: var(--color-bg-card);
            color: var(--color-text-primary);
        }
        .dfsoccer-nav-button svg {
            width: 16px; height: 16px; /* Increased icon size slightly */
            fill: currentColor;
            flex-shrink: 0;
        }
        .dfsoccer-nav-button .button-text { display: inline; } /* Show text by default */


        /* --- Team View (Left Column / Top Stack) Components --- */
        .dfsoccer-team-view h3 {
            color: var(--color-text-accent);
            font-size: 1rem; /* Slightly smaller */
            margin-bottom: 0.5rem;
            border-bottom: 1px solid var(--color-border-light);
            padding-bottom: 0.5rem;
        }

        .dfsoccer-soccer-field {
            position: relative;
            width: 100%;
            height: 350px; /* Desktop height */
            background: var(--color-bg-field);
            border: 2px solid var(--color-border-medium);
            border-radius: 8px;
            background-image:
                radial-gradient(circle at center, transparent 49px, rgba(255,255,255,0.3) 49px, rgba(255,255,255,0.3) 50px, transparent 50px),
                linear-gradient(to right, transparent calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% + 0.5px), transparent calc(50% + 0.5px));
            background-size: 100px 100px, 100% 1px;
            background-position: center center, center center;
            background-repeat: no-repeat;
            transition: height 0.3s ease; /* Smooth height transition */
        }
        /* Styles for player-positions, player-spot, player-jersey-shape etc. from target HTML */
        .dfsoccer-player-positions { position: absolute; inset: 0; display: grid; grid-template-rows: repeat(4, 1fr); grid-template-columns: repeat(5, 1fr); padding: 10px; gap: 5px; }
        .dfsoccer-player-spot { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; min-height: 50px; position: relative; } /* Reduced min-height */
        .dfsoccer-player-jersey-shape { width: 35px; height: 40px; clip-path: polygon(16% 20%, 35% 13%, 40% 20%, 60% 20%, 65% 13%, 86% 20%, 100% 31%, 85% 44%, 80% 40%, 80% 100%, 20% 100%, 20% 40%, 15% 44%, 0 31%); background-color: #555; /* Default empty color */ margin-bottom: 3px; border: 1px solid var(--color-border-medium); flex-shrink: 0; transition: width 0.3s, height 0.3s, background-color 0.3s; } /* Reduced size, default color */
        .dfsoccer-player-spot.filled .dfsoccer-player-jersey-shape { background-color: var(--color-accent-yellow); border-color: var(--color-accent-yellow-dark); } /* Filled style */
        .dfsoccer-player-details { padding: 2px 3px; background-color: rgba(5, 46, 22, 0.7); border-radius: 4px; min-height: 1.8em; /* Reserve space */ visibility: hidden; /* Hide by default */ }
        .dfsoccer-player-spot.filled .dfsoccer-player-details { visibility: visible; } /* Show when filled */
        .dfsoccer-player-spot-name { font-size: 8px; font-weight: bold; color: var(--color-text-primary); line-height: 1.1; display: block; word-wrap: break-word; max-width: 100%; } /* Reduced size */
        .dfsoccer-player-spot-price { font-size: 7px; color: var(--color-text-accent); display: block; font-weight: 500; } /* Reduced size */
        /* Keep all position grid-area rules */
        .dfsoccer-player-pos-gk { grid-area: 4 / 3 / 5 / 4; } .dfsoccer-player-pos-d1 { grid-area: 3 / 1 / 4 / 2; } .dfsoccer-player-pos-d2 { grid-area: 3 / 2 / 4 / 3; } .dfsoccer-player-pos-d3 { grid-area: 3 / 4 / 4 / 5; } .dfsoccer-player-pos-d4 { grid-area: 3 / 5 / 4 / 6; } .dfsoccer-player-pos-m1 { grid-area: 2 / 1 / 3 / 2; } .dfsoccer-player-pos-m2 { grid-area: 2 / 2 / 3 / 3; } .dfsoccer-player-pos-m3 { grid-area: 2 / 3 / 3 / 4; } .dfsoccer-player-pos-m4 { grid-area: 2 / 4 / 3 / 5; } .dfsoccer-player-pos-m5 { grid-area: 2 / 5 / 3 / 6; } .dfsoccer-player-pos-f1 { grid-area: 1 / 2 / 2 / 3; } .dfsoccer-player-pos-f2 { grid-area: 1 / 3 / 2 / 4; } .dfsoccer-player-pos-f3 { grid-area: 1 / 4 / 2 / 5; }


        .dfsoccer-budget-overview { background-color: rgba(20, 83, 45, 0.6); padding: 1rem; border-radius: 8px; }
        .dfsoccer-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
        .dfsoccer-stat-card { background-color: var(--color-bg-light); padding: 0.5rem; border-radius: 6px; text-align: center; }
        .dfsoccer-stat-label { display: block; font-size: 0.65rem; color: var(--color-text-secondary); margin-bottom: 0.2rem; text-transform: uppercase; }
        .dfsoccer-stat-value { display: block; font-size: 1rem; font-weight: bold; color: var(--color-text-primary); }
        .dfsoccer-stat-value.negative { color: var(--color-accent-negative); }
        .dfsoccer-stat-value.positive { color: var(--color-accent-positive); }

        .dfsoccer-position-summary { background-color: rgba(20, 83, 45, 0.6); padding: 0.75rem 1rem; border-radius: 8px; }
        .dfsoccer-selection-counts { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem; justify-content: center; } /* Center counts */
        .dfsoccer-position-count { background-color: var(--color-bg-light); color: var(--color-text-secondary); padding: 0.25rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem; }
        .dfsoccer-position-count .icon-status { width: 8px; height: 8px; border-radius: 50%; background-color: var(--color-accent-negative); display: inline-block; transition: background-color 0.2s; }
        /* Add rules for position types (gk, def, mid, att) */
        .dfsoccer-position-count-goalkeeper .icon-status {}
        .dfsoccer-position-count-defender .icon-status {}
        .dfsoccer-position-count-midfielder .icon-status {}
        .dfsoccer-position-count-attacker .icon-status {} /* ATT was FWD in HTML, use ATT consistently */
        .dfsoccer-position-count.complete .icon-status { background-color: var(--color-accent-positive); }
        .dfsoccer-position-count.complete { background-color: var(--color-bg-card); color: var(--color-text-primary); }

        .dfsoccer-team-status-bar { padding: 0.6rem 1rem; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.85rem; margin-top: 0.5rem; transition: background-color 0.3s, color 0.3s; }
        .dfsoccer-team-status-bar.incomplete { background-color: var(--color-accent-yellow-dark); color: var(--color-text-primary); }
        .dfsoccer-team-status-bar.complete { background-color: var(--color-accent-positive); color: var(--color-bg-deep); }
        .dfsoccer-team-status-bar.overbudget { background-color: var(--color-accent-negative); color: var(--color-text-primary); }


        /* --- Player Pool (Right Column / Bottom Stack) Components --- */
        .dfsoccer-player-pool__header {
            padding: 0.75rem 1rem; /* Adjusted padding */
            background-color: var(--color-bg-medium);
            border-bottom: 1px solid var(--color-border-light);
            /* position: sticky; top: 0; -- Removed sticky */
            z-index: 10;
            flex-shrink: 0; /* Prevent shrinking */
        }
        .dfsoccer-search-filter-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .dfsoccer-search-input-container { flex-grow: 1; position: relative; min-width: 150px; /* Prevent input getting too small */ }
        .dfsoccer-search-input { width: 100%; padding: 0.5rem 0.7rem 0.5rem 2.2rem; border-radius: 6px; border: 1px solid var(--color-border-light); background-color: var(--color-bg-light); color: var(--color-text-primary); font-size: 0.85rem; }
        .dfsoccer-search-input::placeholder { color: var(--color-text-secondary); opacity: 0.8; }
        .dfsoccer-search-icon { position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; stroke: var(--color-text-secondary); stroke-width: 2; pointer-events: none; } /* Added pointer-events: none */
        .dfsoccer-filter-buttons { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; /* Allow buttons to wrap */ justify-content: center; /* Center buttons when wrapped */ }
        .dfsoccer-filter-btn { padding: 0.4rem 0.7rem; border-radius: 6px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.75rem; font-weight: 500; border: 1px solid transparent; transition: all 0.2s ease; }
        .dfsoccer-filter-btn:hover { background-color: var(--color-bg-card); color: var(--color-text-primary); }
        .dfsoccer-filter-btn.active { background-color: var(--color-accent-yellow); color: var(--color-bg-deep); border-color: var(--color-accent-yellow-dark); font-weight: 600; }
        .dfsoccer-filter-btn svg { width: 14px; height: 14px; fill: currentColor; vertical-align: middle; margin-left: 0.3rem; }
        .dfsoccer-filter-btn.more-filters { padding: 0.4rem; /* Icon only button */ } /* Style the filter icon button */
        .dfsoccer-filter-btn.more-filters svg { margin-left: 0; }
        /* Add club filter button styling if you implement it */
         .dfsoccer-filter-btn.club-filter { /* Example */ }


        .dfsoccer-player-list__header {
            display: grid;
            /* Desktop Columns */
            grid-template-columns: 3fr 1fr 1.5fr 1.5fr 0.5fr; /* Player, Pos, Price, Club, Sel */
            gap: 0.75rem; /* Reduced gap */
            padding: 0.5rem 1rem; /* Adjusted padding */
            background-color: var(--color-bg-light);
            color: var(--color-text-accent);
            font-size: 0.7rem; /* Smaller font */
            font-weight: 600;
            text-transform: uppercase;
            /* position: sticky; -- Removed sticky */
            top: 0px; /* Adjusted dynamically or via JS if header height varies drastically */
            z-index: 9;
            border-bottom: 1px solid var(--color-border-medium);
            flex-shrink: 0; /* Prevent shrinking */
        }
         .dfsoccer-player-list__header > div:nth-child(3), /* Price */
         .dfsoccer-player-list__header > div:nth-child(4) { text-align: right; } /* Club */
         .dfsoccer-player-list__header > div:nth-child(4) { /* Club */
            display: block; /* Show by default */
         }
         .dfsoccer-player-list__header > div:nth-child(5) { text-align: center; } /* Sel */


        .dfsoccer-player-list__scrollable {
            flex-grow: 1; /* Takes up remaining space in .player-pool */
            overflow-y: auto; /* THE SCROLLABLE AREA */
            padding: 0 1rem; /* Match header padding */
            min-height: 200px; /* Ensure it has some height */
        }

        .dfsoccer-player-list__item {
            display: grid;
            /* Desktop Columns */
            grid-template-columns: 3fr 1fr 1.5fr 1.5fr 0.5fr; /* Player, Pos, Price, Club, Sel */
            gap: 0.75rem; /* Reduced gap */
            align-items: center;
            padding: 0.6rem 0; /* Adjusted padding */
            border-bottom: 1px solid var(--color-border-light);
            font-size: 0.85rem; /* Smaller font */
            cursor: pointer;
            transition: background-color 0.15s ease, opacity 0.2s ease;
            position: relative; /* For hidden checkbox positioning */
        }
        .dfsoccer-player-list__item:hover:not(.disabled-selection) {
            background-color: rgba(22, 101, 52, 0.4);
        }
        .dfsoccer-player-list__item.selected {
            background-color: var(--color-bg-card);
            font-weight: 500;
        }
        .dfsoccer-player-list__item.disabled-selection { opacity: 0.5; cursor: not-allowed; background-color: transparent !important; }
        .dfsoccer-player-list__item.hidden-by-filter { display: none; } /* Hide items filtered out */

        .dfsoccer-player-name { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; } /* Prevent wrapping */
        .dfsoccer-player-position-cell { color: var(--color-text-accent); font-weight: 500; text-transform: uppercase; }
        .dfsoccer-player-price, .dfsoccer-player-club { text-align: right; color: var(--color-text-secondary); }
        .dfsoccer-player-price { font-weight: 600; color: var(--color-text-primary); }
        .dfsoccer-player-club { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; } /* Show by default, prevent wrapping */
        .dfsoccer-player-select-indicator { text-align: center; }
        .dfsoccer-player-select-indicator svg { width: 16px; height: 16px; stroke-width: 2.5; color: var(--color-accent-positive); visibility: hidden; margin: 0 auto; }
        .dfsoccer-player-list__item.selected .dfsoccer-player-select-indicator svg { visibility: visible; }

        /* Hidden Checkbox Styling */
        .dfsoccer-player-list__item input[type="checkbox"] {
             position: absolute;
             opacity: 0;
             width: 0;
             height: 0;
             pointer-events: none;
        }

        .dfsoccer-load-more { text-align: center; padding: 1rem 0; display: none; /* Hide by default, managed by JS pagination */ }
        .dfsoccer-load-more-btn { padding: 0.6rem 1.2rem; background-color: var(--color-bg-light); border-radius: 6px; font-weight: 600; font-size: 0.85rem; transition: background-color 0.2s ease; }
         .dfsoccer-load-more-btn:hover { background-color: var(--color-bg-card); }

        /* Pagination Styles */
        .dfsoccer-pagination {
            text-align: center;
            padding: 1rem 0;
            background-color: var(--color-bg-medium); /* Match pool header */
            border-top: 1px solid var(--color-border-light);
            margin: 0 -1rem; /* Counteract scrollable padding */
            padding: 0.75rem 1rem;
             flex-shrink: 0; /* Prevent shrinking */
        }
        .dfsoccer-pagination button {
            padding: 0.4rem 0.8rem;
            margin: 0 0.2rem;
            border-radius: 4px;
            background-color: var(--color-bg-light);
            color: var(--color-text-secondary);
            font-size: 0.8rem;
            font-weight: 500;
            border: 1px solid var(--color-border-medium);
            transition: background-color 0.2s ease;
        }
        .dfsoccer-pagination button:hover:not(:disabled) {
             background-color: var(--color-bg-card);
             color: var(--color-text-primary);
        }
        .dfsoccer-pagination button:disabled {
             background-color: var(--color-accent-yellow);
             color: var(--color-bg-deep);
             cursor: default;
             opacity: 1;
             font-weight: 600;
             border-color: var(--color-accent-yellow-dark);
        }


        /* --- Footer Components --- */
        .dfsoccer-save-button { padding: 0.6rem 1.5rem; background-color: var(--color-accent-yellow); color: var(--color-bg-deep); font-weight: bold; font-size: 0.9rem; border-radius: 6px; transition: background-color 0.2s ease, opacity 0.2s ease; }
        .dfsoccer-save-button:hover:not(:disabled) { background-color: var(--color-accent-yellow-dark); }
        .dfsoccer-save-button:disabled { opacity: 0.6; cursor: not-allowed; background-color: var(--color-bg-light); }


        /* --- Modal Styles --- */
        /* Ensure modals are above everything else */
        .dfsoccer-modal { position: fixed; inset: 0; background-color: var(--color-modal-overlay); display: flex; align-items: center; justify-content: center; z-index: 10000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0s linear 0.3s; padding: 1rem; }
        .dfsoccer-modal.is-visible { opacity: 1; visibility: visible; transition-delay: 0s; }
        .dfsoccer-modal-content { background-color: var(--color-bg-medium); color: var(--color-text-primary); padding: 1.5rem; border-radius: 8px; max-width: 95%; width: 800px; max-height: 90vh; overflow-y: auto; position: relative; z-index: 10001; border: 1px solid var(--color-border-light); box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
        .dfsoccer-modal-close { position: absolute; top: 8px; right: 10px; background: none; border: none; font-size: 1.8rem; color: var(--color-text-secondary); cursor: pointer; line-height: 1; padding: 0.25rem; }
        .dfsoccer-modal-close:hover { color: var(--color-text-primary); }
        .dfsoccer-modal-content h2 { font-size: 1.2rem; margin-bottom: 1rem; color: var(--color-text-accent); border-bottom: 1px solid var(--color-border-light); padding-bottom: 0.5rem; }
        .dfsoccer-modal-content table { font-size: 0.85rem; width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .dfsoccer-modal-content th, .dfsoccer-modal-content td { padding: 0.5rem; border: 1px solid var(--color-border-light); text-align: left;}
        .dfsoccer-modal-content th { background-color: var(--color-bg-light); font-weight: 600; }
        .dfsoccer-modal-content iframe { width: 100%; min-height: 70vh; border: none; }
        /* More Filters Modal Specific Styles */
        .dfsoccer-filter-options { display: flex; flex-direction: column; gap: 0.75rem; }
        .dfsoccer-filter-options h4 { margin: 0.5rem 0 0.25rem 0; color: var(--color-text-accent); font-size: 0.9rem; }
        .dfsoccer-club-filter-select, .dfsoccer-price-filter-input {
             width: 100%;
             padding: 0.5rem 0.7rem;
             border-radius: 6px;
             border: 1px solid var(--color-border-light);
             background-color: var(--color-bg-light);
             color: var(--color-text-primary);
             font-size: 0.85rem;
        }
        .dfsoccer-apply-more-filters-btn {
            padding: 0.6rem 1.2rem;
            background-color: var(--color-accent-yellow);
            color: var(--color-bg-deep);
            font-weight: bold;
            font-size: 0.9rem;
            border-radius: 6px;
            transition: background-color 0.2s ease;
            align-self: flex-start; /* Align button left */
        }
         .dfsoccer-apply-more-filters-btn:hover { background-color: var(--color-accent-yellow-dark); }


        /* ==================== RESPONSIVE BREAKPOINTS ==================== */

        /* --- Tablet & Smaller (Stack Columns) --- */
        @media (max-width: 1024px) {
            .dfsoccer-main-content {
                flex-direction: column; /* Stack columns */
                 height: auto; /* Let content define height */
                 overflow-y: visible; /* Allow main area to scroll if needed */
                 overflow-x: hidden; /* Prevent horizontal scroll */
            }
            .dfsoccer-team-view {
                flex: 1 1 auto; /* Allow to grow/shrink */
                max-width: none; /* Remove max width */
                border-right: none; /* Remove vertical border */
                border-bottom: 1px solid var(--color-border-light); /* Add bottom border */
                height: auto; /* Height based on content */
                overflow-y: visible; /* Don't need internal scroll */
                padding-bottom: 1.5rem; /* Add padding at bottom */
            }
            .dfsoccer-player-pool {
                flex: 1 1 auto; /* Allow to grow/shrink */
                 height: auto; /* Height determined by content */
                 min-height: 400px; /* Ensure it has some minimum height */
                 overflow: visible; /* Allow content to flow */
            }

             .dfsoccer-player-list__scrollable {
                 overflow-y: visible; /* Let the list flow naturally */
                 max-height: none; /* Remove max-height */
             }

             .dfsoccer-soccer-field {
                 height: 300px; /* Smaller pitch */
             }
             .dfsoccer-player-jersey-shape { width: 30px; height: 35px; }
             .dfsoccer-player-spot-name { font-size: 7px; }
             .dfsoccer-player-spot-price { font-size: 6px; }

            .dfsoccer-stats-grid { grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); } /* More flexible grid */
        }

         /* --- Mobile Phones --- */
        @media (max-width: 767px) {
             .dfsoccer-app-header {
                 padding: 0.5rem;
                 gap: 0.5rem;
                 min-height: 0; /* Allow shrinking */
             }
             .dfsoccer-fixture-countdown { order: 1; width: 100%; justify-content: center; margin-bottom: 0.5rem; font-size: 0.7rem; padding: 0.3rem 0.6rem; }
             .dfsoccer-countdown-units { gap: 0.2rem;}
             .dfsoccer-countdown-unit { padding: 0.1rem 0.3rem; }
             .dfsoccer-fixture-info-compact { order: 2; width: 100%; text-align: center; margin-bottom: 0.5rem; font-size: 0.7rem; white-space: normal; }
             .dfsoccer-app-nav { order: 3; width: 100%; justify-content: center; gap: 0.25rem; } /* Center nav */
             .dfsoccer-nav-button { padding: 0.5rem; gap: 0; } /* Icon only */
             .dfsoccer-nav-button .button-text { display: none; } /* Hide text */
             .dfsoccer-nav-button svg { width: 18px; height: 18px; } /* Make icons slightly larger */

             .dfsoccer-team-view { padding: 0.75rem; }
             .dfsoccer-player-pool { min-height: 300px; }

             .dfsoccer-soccer-field { height: 250px; } /* Even smaller pitch */

             .dfsoccer-player-pool__header { padding: 0.5rem 0.75rem; }
             .dfsoccer-search-filter-bar { flex-direction: column; align-items: stretch; } /* Stack search and filters */
             .dfsoccer-filter-buttons { justify-content: space-around; width: 100%; margin-top: 0.5rem; }

             .dfsoccer-player-list__header {
                 padding: 0.4rem 0.75rem;
                 grid-template-columns: 3fr 1fr 1.5fr 0.5fr; /* Hide Club */
                 gap: 0.5rem;
                 font-size: 0.65rem;
             }
             .dfsoccer-player-list__header > div:nth-child(4) { display: none; } /* Hide Club Header */

             .dfsoccer-player-list__item {
                 padding: 0.5rem 0;
                 grid-template-columns: 3fr 1fr 1.5fr 0.5fr; /* Hide Club */
                 gap: 0.5rem;
                 font-size: 0.8rem;
             }
             .dfsoccer-player-club { display: none; } /* Hide Club column */

             .dfsoccer-save-button { width: 100%; text-align: center; font-size: 1rem; padding: 0.8rem; }
             .dfsoccer-app-footer { padding: 0.5rem; height: auto; min-height: var(--footer-height); } /* Allow footer to grow */

             .dfsoccer-modal-content { padding: 1rem; max-width: 100%; width: 100%; height: 95vh; max-height: 95vh;} /* Full width modal */
             .dfsoccer-modal-content h2 { font-size: 1.1rem; }

             .dfsoccer-pagination { margin: 0 -0.75rem; } /* Adjust negative margin */
             .dfsoccer-pagination button { padding: 0.3rem 0.6rem; font-size: 0.75rem;}
        }
    </style>

    <div class="dfsoccer-app-wrapper"> <!-- Add a simple wrapper if needed -->
        <form id="player_selection_form_<?php echo esc_attr($league_id); ?>" method="post" class="dfsoccer-app-container">
            <?php wp_nonce_field('dfsoccer_select_players_' . $league_id . '_' . $user_id, '_wpnonce_player_selection_' . $league_id); ?>
            <input type="hidden" name="league_id" value="<?php echo esc_attr($league_id); ?>">

            <!-- Notification Area -->
            <?php if (!empty($error_message) || !empty($success_message)) : ?>
            <div class="dfsoccer-notifications">
                <?php if (!empty($error_message)) : ?>
                    <div class="error"><?php echo esc_html($error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)) : ?>
                    <div class="success"><?php echo esc_html($success_message); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ==================== HEADER ==================== -->
            <header class="dfsoccer-app-header">
                <!-- Static Placeholders - Add PHP to fetch dynamic data if needed -->
				            <?php 
echo flash_countdown_for_dfsoccer($league_id);
 ?>

                <div class="dfsoccer-fixture-countdown">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="dfsoccer-countdown-icon" fill="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14" fill="none" stroke="currentColor" stroke-width="2"></polyline></svg>
                    <span class="dfsoccer-countdown-label">Deadline:</span>
                    <div class="dfsoccer-countdown-units" id="fixture-countdown-units-<?php echo esc_attr($league_id); ?>">
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">D</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">H</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">M</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">S</span></div>
                    </div>
                </div>
                <div class="dfsoccer-fixture-info-compact">
                    <span class="teams">Next: <strong>Team A</strong> vs <strong>Team B</strong></span>
                    <span class="time">Date, Time</span>
                </div>
                <nav class="dfsoccer-app-nav">
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#rules-modal-<?php echo esc_attr($league_id); ?>" title="League Rules">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v2h-2zm0 4h2v6h-2z" fill="currentColor"></path></svg>
                        <span class="button-text">Rules</span>
                    </button>
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#standings-modal-<?php echo esc_attr($league_id); ?>" title="League Standings">
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11V3H8v8H2v10h20V11h-6zm-6-6h4v12h-4V5zm-6 6h4v8H4v-8zm16 8h-4v-8h4v8z" fill="currentColor"></path></svg>
                        <span class="button-text">Standings</span>
                    </button>
                     <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#all-points-modal-<?php echo esc_attr($league_id); ?>" title="All Player Points">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM4 18V6h2v12H4zm4 0V6h8v12H8zm10 0h-2V6h2v12z" fill="currentColor"></path></svg>
                        <span class="button-text">All Points</span>
                    </button>
                     <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#scorecard-modal-<?php echo esc_attr($league_id); ?>" title="Scorecard">
                         <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H5v-2h7v2zm0-4H5v-2h7v2zm5 4h-3v-2h3v2zm0-4h-3v-2h3v2zm0-4h-3V7h3v2z" fill="currentColor"></path></svg>
                        <span class="button-text">Scorecard</span>
                    </button>
                </nav>
            </header>

            <!-- ==================== MAIN CONTENT ==================== -->
            <main class="dfsoccer-main-content">
                <!-- --- Left Column / Top Stack: Team View --- -->
                <aside class="dfsoccer-team-view">
                    <h3>Your Team</h3>
                    <!-- Pitch Visualization -->
<div class="dfsoccer-soccer-field" id="soccer-field-<?php echo esc_attr($league_id); ?>">
    <div class="dfsoccer-player-positions">
        <!-- Goalkeepers (6 slots) -->
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk1" data-position-slot="goalkeeper_0"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk2" data-position-slot="goalkeeper_1"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk3" data-position-slot="goalkeeper_2"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk4" data-position-slot="goalkeeper_3"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk5" data-position-slot="goalkeeper_4"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-gk6" data-position-slot="goalkeeper_5"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        
        <!-- Defenders (6 slots) -->
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d1" data-position-slot="defender_0"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d2" data-position-slot="defender_1"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d3" data-position-slot="defender_2"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d4" data-position-slot="defender_3"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d5" data-position-slot="defender_4"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-d6" data-position-slot="defender_5"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        
        <!-- Midfielders (6 slots) -->
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m1" data-position-slot="midfielder_0"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m2" data-position-slot="midfielder_1"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m3" data-position-slot="midfielder_2"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m4" data-position-slot="midfielder_3"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m5" data-position-slot="midfielder_4"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-m6" data-position-slot="midfielder_5"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        
        <!-- Attackers (6 slots) -->
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f1" data-position-slot="attacker_0"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f2" data-position-slot="attacker_1"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f3" data-position-slot="attacker_2"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f4" data-position-slot="attacker_3"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f5" data-position-slot="attacker_4"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
        <div class="dfsoccer-player-spot dfsoccer-player-pos-f6" data-position-slot="attacker_5"><div class="dfsoccer-player-jersey-shape"></div><div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div></div>
    </div>
</div>

                    <!-- Budget Overview -->
                    <div class="dfsoccer-budget-overview">
                         <div class="dfsoccer-stats-grid">
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Budget</span><span class="dfsoccer-stat-value" id="budget-value-<?php echo esc_attr($league_id); ?>">$<?php echo number_format($budget, 0); ?></span></div>
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Price</span><span class="dfsoccer-stat-value" id="current-price-<?php echo esc_attr($league_id); ?>">$0</span></div>
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Left</span><span class="dfsoccer-stat-value positive" id="budget-left-<?php echo esc_attr($league_id); ?>">$<?php echo number_format($budget, 0); ?></span></div>
                        </div>
                    </div>

                    <!-- Position Summary -->
                    <div class="dfsoccer-position-summary">
                         <div class="dfsoccer-selection-counts" id="selection-counts-<?php echo esc_attr($league_id); ?>">
                            <!-- Counts will be generated by JS -->
                        </div>
                    </div>

                    <!-- Team Status -->
                    <div class="dfsoccer-team-status-bar incomplete" id="team-status-bar-<?php echo esc_attr($league_id); ?>">
                        0/<?php /* Adjust dynamically based on rules */ echo '6'; ?> Players Selected
                    </div>

                </aside>

                <!-- --- Right Column / Bottom Stack: Player Pool --- -->
                <section class="dfsoccer-player-pool">
                    <!-- Sticky Header for Search/Filter -->
                    <div class="dfsoccer-player-pool__header">
                        <div class="dfsoccer-search-filter-bar">
                            <div class="dfsoccer-search-input-container">
                                <input type="text" placeholder="Search players..." class="dfsoccer-search-input" id="player-search-<?php echo esc_attr($league_id); ?>">
                                 <svg xmlns="http://www.w3.org/2000/svg" class="dfsoccer-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <div class="dfsoccer-filter-buttons">
                                <button type="button" class="dfsoccer-filter-btn active" data-filter="all">All</button>
                                <button type="button" class="dfsoccer-filter-btn" data-filter="attacker">ATT</button>
                                <button type="button" class="dfsoccer-filter-btn" data-filter="midfielder">MID</button>
                                <button type="button" class="dfsoccer-filter-btn" data-filter="defender">DEF</button>
                                <button type="button" class="dfsoccer-filter-btn" data-filter="goalkeeper">GK</button>
                                 <button type="button" class="dfsoccer-filter-btn more-filters dfsoccer-modal-trigger" data-modal-target="#more-filters-modal-<?php echo esc_attr($league_id); ?>" title="More Filters">
                                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                 </button>
                            </div>
                        </div>
                    </div>

                    <!-- Sticky Player List Header -->
                     <div class="dfsoccer-player-list__header">
                        <div>Player</div>
                        <div>Pos</div>
                        <div>Price</div>
                        <div>Club</div> <!-- Will be hidden on mobile -->
                        <div>Sel</div>
                    </div>

                    <!-- Scrollable Player List -->
                    <div class="dfsoccer-player-list__scrollable" id="player-list-container-<?php echo esc_attr($league_id); ?>">
                        <?php if (!empty($all_players)): ?>
                            <?php foreach ($all_players as $player): ?>
                                <?php
                                    $is_selected = in_array($player['id'], $saved_player_ids);
                                    $selected_class = $is_selected ? 'selected' : '';
                                    // Prepare short position name
                                    $pos_short = 'N/A';
                                    if ($player['position']) {
                                        $pos_short = strtoupper(substr($player['position'], 0, 3));
                                        if ($player['position'] === 'goalkeeper') $pos_short = 'GK';
                                    }
                                ?>
                                <div class="dfsoccer-player-list__item <?php echo $selected_class; ?>"
                                     data-player-id="<?php echo esc_attr($player['id']); ?>"
                                     data-player-name="<?php echo esc_attr($player['name']); ?>"
                                     data-position="<?php echo esc_attr($player['position']); ?>"
                                     data-price="<?php echo esc_attr($player['price']); ?>"
                                     data-club-id="<?php echo esc_attr($player['club_id']); ?>"
                                     tabindex="0" role="button" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">

                                    <input type="checkbox" name="selected_players[]" value="<?php echo esc_attr($player['id']); ?>" <?php checked($is_selected); ?> id="player-checkbox-<?php echo esc_attr($player['id']); // Unique ID for checkbox itself ?>" >
                                    <div class="dfsoccer-player-name"><?php echo esc_html($player['name']); ?></div>
                                    <div class="dfsoccer-player-position-cell"><?php echo esc_html($pos_short); ?></div>
                                    <div class="dfsoccer-player-price">$<?php echo esc_html(number_format($player['price'], 0)); ?></div>
                                    <div class="dfsoccer-player-club"><?php echo esc_html($player['club_name']); ?></div>
                                    <div class="dfsoccer-player-select-indicator">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p style="padding: 1rem;">No players found matching the criteria for this league.</p>
                        <?php endif; ?>

                    </div> <!-- End Scrollable List -->

                    <!-- Pagination Controls Container -->
                     <div class="dfsoccer-pagination" id="pagination-<?php echo esc_attr($league_id); ?>"></div>

                </section> <!-- End Player Pool -->
            </main> <!-- End Main Content -->

            <!-- ==================== FOOTER / ACTION BAR ==================== -->
            <footer class="dfsoccer-app-footer">

    <button type="submit" name="<?php echo esc_attr($submit_button_name); ?>" class="dfsoccer-save-button" id="save-button-<?php echo esc_attr($league_id); ?>" disabled>Save Selection</button>
  
            </footer>

        </form> <!-- End Form -->
    </div> <!-- End Wrapper -->

    <!-- ==================== MODALS (Outside Form) ==================== -->
    <!-- Add unique IDs using league_id -->

<!-- Rules Modal -->
<div id="rules-modal-<?php echo esc_attr($league_id); ?>" class="dfsoccer-modal" aria-hidden="true">
    <div class="dfsoccer-modal-overlay" data-modal-close></div>
    <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="rules-modal-title-<?php echo esc_attr($league_id); ?>">
        <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
        <h2 id="rules-modal-title-<?php echo esc_attr($league_id); ?>">Fixtures and League Rules</h2>
        <!-- Add dynamic rules content here -->
		
					            <?php
            echo display_fixtures_only($league_id);
            ?>
		
        <p>Fantasy Points Scoring System
        </p>
        <style>
        h3 {
            color: white !important;
        }
        .goalkeeper-cell {
            color: white !important;
        }
        </style>


<?php
// Get league-specific rules if they exist
$points_rules = get_post_meta($league_id, 'dfsoccer_points_rules', true);

// Default rules
$default_rules = [
    'goalkeeper' => [
        'goals' => 10,
        'own' => -7,
        'assists' => 7,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -2,
        'penalties' => 8,
        'missed' => -4
    ],
    'defender' => [
        'goals' => 7,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -2,
        'penalties' => 8,
        'missed' => -4
    ],
    'midfielder' => [
        'goals' => 6,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -1,
        'penalties' => 8,
        'missed' => -4
    ],
    'attacker' => [
        'goals' => 5,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => 0,
        'penalties' => 8,
        'missed' => -4
    ]
];

// Use league-specific rules if set, otherwise use default rules
$rules = $points_rules ?: $default_rules;

// Helper function to format rule names
$formatRuleName = function($name) {
    $names = [
        'goals' => 'Goals Scored',
        'own' => 'Own Goals',
        'assists' => 'Assists',
        'minutes' => 'Minutes Played',
        'red' => 'Red Cards',
        'yellow' => 'Yellow Cards',
        'conceded' => 'Goals Conceded',
        'penalties' => 'Penalties Saved',
        'missed' => 'Penalties Missed'
    ];
    return $names[$name] ?? ucfirst($name);
};

// Display rules for each position
foreach ($rules as $position => $position_rules) : ?>
    <h3><?php echo ucfirst($position); ?></h3>
    <table class="league-rules-table">
        <tr>
            <th>Action</th>
            <th>Points</th>
        </tr>
        <?php foreach ($position_rules as $rule => $points) : 
            $valueClass = $points > 0 ? 'positive' : ($points < 0 ? 'negative' : '');
            $formattedPoints = $points;
            
            if ($rule === 'minutes') {
                $formattedPoints = $points . ' per minute';
            } else {
                $formattedPoints = ($points > 0 ? '+' : '') . $points;
            }
        ?>
            <tr>
                <td><?php echo $formatRuleName($rule); ?></td>
                <td class="rule-value <?php echo $valueClass; ?>"><?php echo $formattedPoints; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<!-- Clean sheet bonus information -->
<h3 class="goalkeeper-cell">Clean Sheet Bonus Points (60+ minutes played)</h3>
<table class="league-rules-table">
    <tr>
        <th>Position</th>
        <th>Points</th>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Goalkeeper</td>
        <td class="rule-value positive">+5</td>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Defender</td>
        <td class="rule-value positive">+5</td>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Midfielder</td>
        <td class="rule-value positive">+3</td>
    </tr>
</table>
            <?php
            // Example: Fetch rules from league meta
            // $rules_content = get_post_meta($league_id, 'dfsoccer_league_rules', true);
            // echo wp_kses_post(wpautop($rules_content)); // Make sure to sanitize and format

            ?>
        </div>
    </div>

    <!-- Standings Modal -->
    <div id="standings-modal-<?php echo esc_attr($league_id); ?>" class="dfsoccer-modal" aria-hidden="true">
         <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="standings-modal-title-<?php echo esc_attr($league_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
            <h2 id="standings-modal-title-<?php echo esc_attr($league_id); ?>">League Standings</h2>
            <!-- Add dynamic standings content here -->



			<?php
echo do_shortcode('[display_teams league_id="' . $league_id . '"]');
?>
           <p style="color: white; line-height: 1.6; margin: 20px 0;">
  <strong style="font-size: 1.2em; display: block; margin-bottom: 10px;">Tiebreaker Rules</strong>
  
  In our Fantasy Soccer League, team rankings are determined by the following criteria in order:<br><br>
  
  <strong>Total Points</strong> - Teams are primarily ranked by their total accumulated points<br><br>
  
  <strong>Budget Efficiency</strong> - If two or more teams have the same points, the team with the lower total budget wins<br><br>
  
  <strong>User Points</strong> - If teams are still tied after budget comparison, the team whose user has more points will be ranked higher<br><br>
  
  <strong>Random Draw</strong> - In the rare case of teams being equal in all previous criteria, positions will be determined by random draw<br><br>
  
  This system rewards not only effective team performance but also smart budget management and accurate match predictions.
</p>
            <?php
            // Example: Call a function to display standings
            // if (function_exists('your_plugin_display_standings')) {
            //    your_plugin_display_standings($league_id);
            // }
            ?>
        </div>
    </div>

    <!-- All Points Modal -->
    <div id="all-points-modal-<?php echo esc_attr($league_id); ?>" class="dfsoccer-modal" aria-hidden="true">
         <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="all-points-modal-title-<?php echo esc_attr($league_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
            <h2 id="all-points-modal-title-<?php echo esc_attr($league_id); ?>">All Player Points</h2>
            <!-- Add dynamic points table here -->
          

<?php
// Initialize the output variable first
$output = '';

$match_results = get_post_meta($league_id, 'dfsoccer_match_results', true);
if ($match_results) {
    $results_array = json_decode($match_results, true);
    
    // First, let's extract all players by total points
    $players_by_points = [];
    foreach ($results_array as $player_id => $player_stats) {
        if (isset($player_stats['total_points'])) {
            $players_by_points[] = [
                'id' => $player_id,
                'name' => get_the_title($player_id),
                'total_points' => $player_stats['total_points'],
                'stats' => $player_stats
            ];
        }
    }
    
    // Sort players by total points (descending)
    usort($players_by_points, function($a, $b) {
        return $b['total_points'] <=> $a['total_points'];
    });
    
    // Get top 3 players
    $top_players = array_slice($players_by_points, 0, 3);
    
    // Get bottom 3 players (if we have enough)
    $bottom_players = [];
    if (count($players_by_points) > 3) {
        $bottom_players = array_slice($players_by_points, -3);
        // Reverse so the worst is first
        $bottom_players = array_reverse($bottom_players);
    }
    
    // Helper function to generate news headlines for a player
    function generate_headline($player, $is_top = true) {
        $headlines = [];
        $stats = $player['stats'];
        $league_name = get_the_title(isset($_GET['league_id']) ? intval($_GET['league_id']) : 0);
        
        // Headline for top player
        if ($is_top) {
            if ($player['total_points'] > 0) {
                $headlines[] = "<strong>{$player['name']}</strong> shines with {$player['total_points']} points in {$league_name}!";
            }
            
            // Goals headline
            if (isset($stats['goals']) && $stats['goals'] > 0) {
                $goal_text = $stats['goals'] == 1 ? "goal" : "goals";
                $headlines[] = "Incredible performance! <strong>{$player['name']}</strong> scores {$stats['goals']} {$goal_text}";
            }
            
            // Assists headline
            if (isset($stats['assists']) && $stats['assists'] > 0) {
                $assist_text = $stats['assists'] == 1 ? "assist" : "assists";
                $headlines[] = "Playmaker alert! <strong>{$player['name']}</strong> provides {$stats['assists']} {$assist_text}";
            }
            
            // Minutes headline
            if (isset($stats['minutes']) && $stats['minutes'] >= 90) {
                $headlines[] = "Full {$stats['minutes']} minutes played by <strong>{$player['name']}</strong>!";
            }
            
            // Penalties saved
            if (isset($stats['penalties']) && $stats['penalties'] > 0) {
                $penalty_text = $stats['penalties'] == 1 ? "penalty" : "penalties";
                $headlines[] = "Heroic! <strong>{$player['name']}</strong> saves {$stats['penalties']} {$penalty_text}";
            }
        } 
        // Headlines for worst performers
        else {
            if ($player['total_points'] <= 0) {
                $headlines[] = "Tough day for <strong>{$player['name']}</strong> with {$player['total_points']} points";
            }
            
            // Own goals
            if (isset($stats['own']) && $stats['own'] > 0) {
                $own_text = $stats['own'] == 1 ? "own goal" : "own goals";
                $headlines[] = "Unfortunate! <strong>{$player['name']}</strong> scores {$stats['own']} {$own_text}";
            }
            
            // Red cards
            if (isset($stats['red']) && $stats['red'] > 0) {
                $headlines[] = "Discipline issues! <strong>{$player['name']}</strong> shown a red card";
            }
            
            // Missed penalties
            if (isset($stats['missed']) && $stats['missed'] > 0) {
                $missed_text = $stats['missed'] == 1 ? "penalty" : "penalties";
                $headlines[] = "Costly miss! <strong>{$player['name']}</strong> fails to convert {$stats['missed']} {$missed_text}";
            }
            
            // Goals conceded (for goalkeepers)
            if (isset($stats['conceded']) && $stats['conceded'] > 0) {
                $headlines[] = "Difficult match for <strong>{$player['name']}</strong> with {$stats['conceded']} goals conceded";
            }
        }
        
        // If we couldn't generate any specific headlines, use a generic one
        if (empty($headlines)) {
            if ($is_top) {
                $headlines[] = "<strong>{$player['name']}</strong> among the top performers this week!";
            } else {
                $headlines[] = "<strong>{$player['name']}</strong> hoping for better results next time";
            }
        }
        
        // Return a random headline from our generated list
        return $headlines[array_rand($headlines)];
    }
    
    // Display top players section
    if (!empty($top_players)) {
        $output .= '<div class="performance-container">';
        $output .= '<h2 class="section-heading">Top Performers</h2>';
        
        $output .= '<div class="performance-grid">';
        $output .= '<div class="player-cards">';
        
        foreach ($top_players as $index => $player) {
            $medal_class = ['gold', 'silver', 'bronze'][$index] ?? '';
            $output .= '<div class="player-card ' . $medal_class . '">';
            $output .= '<span class="player-rank">' . ($index + 1) . '</span>';
            $output .= '<h3>' . $player['name'] . '</h3>';
            $output .= '<div class="points-badge">' . $player['total_points'] . ' pts</div>';
            
            // Add some key stats
            $output .= '<ul class="key-stats">';
            if (isset($player['stats']['goals'])) {
                $output .= '<li><span class="stat-label">Goals:</span> ' . $player['stats']['goals'] . '</li>';
            }
            if (isset($player['stats']['assists'])) {
                $output .= '<li><span class="stat-label">Assists:</span> ' . $player['stats']['assists'] . '</li>';
            }
            if (isset($player['stats']['minutes'])) {
                $output .= '<li><span class="stat-label">Minutes:</span> ' . $player['stats']['minutes'] . '</li>';
            }
            if (isset($player['stats']['penalties']) && $player['stats']['penalties'] > 0) {
                $output .= '<li><span class="stat-label">Penalties Saved:</span> ' . $player['stats']['penalties'] . '</li>';
            }
            $output .= '</ul>';
            
            $output .= '</div>';
        }
        
        $output .= '</div>'; // Close player-cards
        
        // Add news headlines for top players
        $output .= '<div class="news-headlines">';
        $output .= '<h3 class="headlines-title">Fantasy Headlines</h3>';
        $output .= '<ul class="headline-list">';
        foreach ($top_players as $player) {
            $headline = generate_headline($player, true);
            $output .= '<li class="headline-item">' . $headline . '</li>';
        }
        $output .= '</ul>';
        $output .= '</div>'; // Close news-headlines
        
        $output .= '</div>'; // Close performance-grid
        $output .= '</div>'; // Close performance-container
        $output .= '<hr class="section-divider">';
    }
    
    // Display bottom players section if we have enough players
    if (!empty($bottom_players)) {
        $output .= '<div class="performance-container struggling-section">';
        $output .= '<h2 class="section-heading">Struggling Performers</h2>';
        
        $output .= '<div class="performance-grid">';
        $output .= '<div class="player-cards">';
        
        foreach ($bottom_players as $index => $player) {
            $output .= '<div class="player-card struggling">';
            $output .= '<span class="player-rank">' . ($index + 1) . '</span>';
            $output .= '<h3>' . $player['name'] . '</h3>';
            $output .= '<div class="points-badge negative">' . $player['total_points'] . ' pts</div>';
            
            // Add some key stats
            $output .= '<ul class="key-stats">';
            if (isset($player['stats']['own']) && $player['stats']['own'] > 0) {
                $output .= '<li><span class="stat-label">Own Goals:</span> ' . $player['stats']['own'] . '</li>';
            }
            if (isset($player['stats']['red']) && $player['stats']['red'] > 0) {
                $output .= '<li><span class="stat-label">Red Cards:</span> ' . $player['stats']['red'] . '</li>';
            }
            if (isset($player['stats']['yellow']) && $player['stats']['yellow'] > 0) {
                $output .= '<li><span class="stat-label">Yellow Cards:</span> ' . $player['stats']['yellow'] . '</li>';
            }
            if (isset($player['stats']['missed']) && $player['stats']['missed'] > 0) {
                $output .= '<li><span class="stat-label">Penalties Missed:</span> ' . $player['stats']['missed'] . '</li>';
            }
            if (isset($player['stats']['conceded']) && $player['stats']['conceded'] > 0) {
                $output .= '<li><span class="stat-label">Goals Conceded:</span> ' . $player['stats']['conceded'] . '</li>';
            }
            if (isset($player['stats']['minutes'])) {
                $output .= '<li><span class="stat-label">Minutes:</span> ' . $player['stats']['minutes'] . '</li>';
            }
            $output .= '</ul>';
            
            $output .= '</div>';
        }
        
        $output .= '</div>'; // Close player-cards
        
        // Add news headlines for bottom players
        $output .= '<div class="news-headlines">';
        $output .= '<h3 class="headlines-title">Fantasy Woes</h3>';
        $output .= '<ul class="headline-list">';
        foreach ($bottom_players as $player) {
            $headline = generate_headline($player, false);
            $output .= '<li class="headline-item">' . $headline . '</li>';
        }
        $output .= '</ul>';
        $output .= '</div>'; // Close news-headlines
        
        $output .= '</div>'; // Close performance-grid
        $output .= '</div>'; // Close performance-container
        $output .= '<hr class="section-divider">';
    }
    
    // Now include the original full player points table
    $output .= '<div id="results_container">';
    $output .= '<div class="results-header active" onclick="toggleResults()">';
    $output .= '<h2>All Player Points</h2>';
    $output .= '<span class="toggle-icon">▼</span>';
    $output .= '</div>';
    
    $output .= '<div class="results-content" style="display: block;">';
    $output .= '<input type="text" id="results_search" placeholder="Search in results...">';
    $stat_labels = [
        'goals' => 'Goals',
        'assists' => 'Assists',
        'own' => 'Own Goals',
        'penalties' => 'Penalties Saved',
        'missed' => 'Penalties Missed',
        'conceded' => 'Goals Conceded',
        'minutes' => 'Minutes Played',
        'red' => 'Red Cards Received',
        'yellow' => 'Yellow Cards Received',
        'total_points' => 'Total Points'
    ];
    foreach ($results_array as $player_id => $player_stats) {
        $player_name = get_the_title($player_id);
        $output .= '<div class="result-entry">';
        $output .= '<p class="result-player-name">Player: ' . $player_name . ' (ID: ' . $player_id . ')</p><ul>';
        foreach ($player_stats as $stat_key => $stat_value) {
            $stat_label = isset($stat_labels[$stat_key]) ? $stat_labels[$stat_key] : ucfirst($stat_key);
            $output .= '<li data-stat="' . $stat_label . '">';
            $output .= '<span class="stat-label">' . $stat_label . ': </span>';
            $output .= '<span class="stat-value">' . $stat_value . '</span>';
            $output .= '</li>';
        }
        $output .= '</ul></div>';
    }
    $output .= '</div></div>';
    
    // Add CSS for the player performance sections
    $output .= '
    <style>
 /* Fantasy League News and Performance Styles */
.section-heading {
    text-align: center;
    margin-bottom: 20px;
    font-size: 1.25rem;
    color: var(--color-green-800, #166534);
    font-weight: bold;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.section-divider {
    margin: 20px 0;
    border: 0;
    height: 1px;
    background-color: var(--color-green-300, #86efac);
    opacity: 0.5;
}

/* News Headlines Styles */
.fantasy-news-container {
    margin-bottom: 20px;
    padding: 15px;
    background-color: rgba(22, 101, 52, 0.05);
    border-radius: 8px;
    border: 1px solid rgba(22, 101, 52, 0.1);
}

.news-section {
    margin-bottom: 15px;
}

.news-section-title {
    font-size: 1rem;
    margin-bottom: 10px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--color-green-700, #15803d);
    color: var(--color-green-700, #15803d);
    font-weight: bold;
}

.bottom-news .news-section-title {
    border-bottom-color: #b91c1c;
    color: #b91c1c;
}

.news-list {
    list-style: none;
    padding-left: 0;
    margin: 0;
}

.news-item {
    margin-bottom: 8px;
    padding: 8px 12px;
    background-color: rgba(255, 255, 255, 0.7);
    border-radius: 4px;
    border-left: 3px solid var(--color-green-600, #16a34a);
    font-size: 0.9rem;
    line-height: 1.4;
}

.bottom-news .news-item {
    border-left-color: #dc2626;
}

/* Player Cards Styles */
.performance-container {
    margin-bottom: 20px;
    padding: 15px;
    background-color: rgba(22, 101, 52, 0.03);
    border-radius: 8px;
}

.player-cards {
    display: flex;
    justify-content: center;
    gap: 15px;
    flex-wrap: wrap;
}

.player-card {
    position: relative;
    background-color: #ffffff;
    border-radius: 6px;
    padding: 15px;
    width: 200px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    text-align: center;
    transition: transform 0.2s ease;
}

.player-card:hover {
    transform: translateY(-2px);
}

.player-rank {
    position: absolute;
    top: -8px;
    left: -8px;
    width: 26px;
    height: 26px;
    background-color: var(--color-green-700, #15803d);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.8rem;
}

.player-card.gold {
    background-color: #97a59d;
    border: 1px solid #fbbf24;
}

.player-card.silver {
    background-color: #97a59d;
    border: 1px solid #94a3b8;
}

.player-card.bronze {
    background-color: #97a59d;
    border: 1px solid #c2410c;
}

.player-card.struggling {
    background-color: #97a59d;
    border: 1px solid #f87171;
}

/* Add explicit color properties for all player cards */
.player-card, 
.player-card.gold, 
.player-card.silver, 
.player-card.bronze, 
.player-card.struggling {
    color: black; /* Ensures all text in player cards is black */
}


.struggling-section .player-rank {
    background-color: #b91c1c;
}

.player-card h3 {
    margin-top: 8px;
    margin-bottom: 12px;
    min-height: 40px; /* Ensure consistent height */
    font-size: 1rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #1f2937; /* Default dark text color */
}
.points-badge {
    background-color: var(--color-green-700, #15803d);
    color: var(--color-green-300, #86efac);
    border-radius: 16px;
    padding: 3px 10px;
    font-weight: bold;
    display: inline-block;
    margin-bottom: 12px;
    font-size: 0.85rem;
}

.points-badge.negative {
    background-color: #b91c1c;
    color: #fecaca;
}

.key-stats {
    text-align: left;
    padding-left: 0;
    list-style: none;
    margin: 0;
    font-size: 0.85rem;
}

.key-stats li {
    margin-bottom: 4px;
    padding-bottom: 4px;
    border-bottom: 1px dotted rgba(0, 0, 0, 0.1);
}

.key-stats li {
    color: black; /* This ensures all stats text is black */
}



.key-stats li:last-child {
    border-bottom: none;
    margin-bottom: 0;
}

.stat-label {
    font-weight: 600;
    color: #4b5563;
}

/* Mobile optimizations */
@media (max-width: 768px) {
    .fantasy-news-container,
    .performance-container {
        padding: 12px;
    }
    
    .player-card {
        width: calc(50% - 10px);
        padding: 12px;
    }
    
    .news-item {
        padding: 6px 10px;
    }
    
    .section-heading {
        font-size: 1.1rem;
    }
}

@media (max-width: 480px) {
    .player-card {
        width: 100%;
        max-width: 250px;
    }
    
    .player-cards {
        gap: 10px;
    }
    
    .key-stats {
        font-size: 0.8rem;
    }
    
    .news-item {
        font-size: 0.85rem;
    }
}

.player-card h3 {
    margin-top: 8px;
    margin-bottom: 12px;
    min-height: 40px; /* Ensure consistent height */
    font-size: 1rem;
    line-height: 1.3;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    color: #1f2937; /* Default dark text color */
}
    </style>
    ';
}

// Echo the output variable to display the content
echo $output;
?>
</div>
</div>



     <!-- Scorecard Modal -->
    <div id="scorecard-modal-<?php echo esc_attr($league_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="scorecard-modal-title-<?php echo esc_attr($league_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
             <h2 id="scorecard-modal-title-<?php echo esc_attr($league_id); ?>">Scorecard (League: <?php echo esc_html(get_the_title($league_id)); ?>)</h2>
             <div class="dfpro-scorecard-iframe-container">
<?php
// Get the post author (league admin)
$post = get_post($league_id);
$post_author_id = $post ? $post->post_author : 0;

// Get current user
$current_user_id = get_current_user_id();

// Check if current user is the league admin
if ($current_user_id == $post_author_id) {
    // User is the league admin - show the scorecard
    echo do_shortcode('[dfproscorecard league_id="' . $league_id . '"]');
} else {
    // User is not the league admin - show restriction message
    echo '<div class="league-admin-restriction" style="margin-top: 30px; padding: 15px; background-color: #f8f8f8; border-left: 4px solid #e74c3c; color: #333;">';
echo '<h3 style="margin-top: 0 !important; color: #e74c3c !important;">Restricted Access</h3>';
    echo '<p>Only the league admin can see and use this page. If you need to submit results, please contact the league administrator.</p>';
    echo '</div>';
}
?>
			</div>
        </div>
    </div>

     <!-- More Filters Modal -->
    <div id="more-filters-modal-<?php echo esc_attr($league_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="more-filters-modal-title-<?php echo esc_attr($league_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
             <h2 id="more-filters-modal-title-<?php echo esc_attr($league_id); ?>">More Filters</h2>
             <div class="dfsoccer-filter-options">
                 <h4>Filter by Club:</h4>
                 <select id="club-filter-<?php echo esc_attr($league_id); ?>" class="dfsoccer-club-filter-select">
                     <option value="">All Clubs</option>
                     <?php if (!empty($club_data)): ?>
                        <?php foreach ($club_data as $c_id => $c_name): ?>
                            <option value="<?php echo esc_attr($c_id); ?>"><?php echo esc_html($c_name); ?></option>
                        <?php endforeach; ?>
                     <?php endif; ?>
                 </select>

                 <h4 style="margin-top: 1rem;">Filter by Max Price:</h4>
                  <input type="number" id="price-filter-<?php echo esc_attr($league_id); ?>" placeholder="Enter max price (e.g., 50)" min="0" step="1" class="dfsoccer-price-filter-input">

                 <button type="button" style="margin-top: 1rem;" class="dfsoccer-apply-more-filters-btn" data-modal-close>Apply & Close</button>
             </div>
        </div>
    </div>


    <script>
	            // Basic Modal Functionality Only
            (function() {
                // Use a unique ID for the flag to support multiple instances
                 const instanceId = <?php echo json_encode($instance_id); ?>;
                 const listenerFlag = `dfsoccerModalListenersAdded_${instanceId}`;

                // Run when the DOM is fully loaded for this instance or page
                function initModalListeners() {
                    // Select triggers specific to this instance
                    const modalTriggers = document.querySelectorAll(`.dfsoccer-modal-trigger[data-modal-target^="#"][data-modal-target$="-${instanceId}"]`);

                    function openModal(modal) {
                        if (!modal || modal.classList.contains('is-visible')) return;
                        modal.classList.add('is-visible');
                        modal.setAttribute('aria-hidden', 'false');
                        // Lazy load iframe if present in this modal
                        const iframe = modal.querySelector('iframe[data-src]');
                        if (iframe && iframe.src === 'about:blank') {
                             iframe.src = iframe.dataset.src;
                        }
                        // Optional: Focus management can be added here
                    }

                    function closeModal(modal) {
                        if (!modal || !modal.classList.contains('is-visible')) return;
                        modal.classList.remove('is-visible');
                        modal.setAttribute('aria-hidden', 'true');
                    }

                    modalTriggers.forEach(trigger => {
                        trigger.addEventListener('click', (e) => {
                            e.preventDefault();
                            const targetSelector = trigger.dataset.modalTarget;
                            const modal = document.querySelector(targetSelector); // Find the modal by its ID
                            if (modal) {
                                openModal(modal);
                            } else {
                                console.error(`DFSoccer Layout: Modal with selector ${targetSelector} not found.`);
                            }
                        });
                    });

                    // Attach global close listeners only once per page load
                    if (!window.dfsoccerGlobalModalListenersAdded) {
                        document.body.addEventListener('click', function (event) {
                            // Close via overlay or [data-modal-close] attribute
                            if (event.target.matches('[data-modal-close]')) {
                                const modalToClose = event.target.closest('.dfsoccer-modal');
                                if (modalToClose) closeModal(modalToClose);
                            }
                        });

                        document.addEventListener('keydown', (e) => {
                            if (e.key === 'Escape') {
                                // Close the latest opened modal
                                const visibleModals = document.querySelectorAll('.dfsoccer-modal.is-visible');
                                if (visibleModals.length > 0) {
                                    closeModal(visibleModals[visibleModals.length - 1]); // Close the top-most one
                                }
                            }
                        });
                        window.dfsoccerGlobalModalListenersAdded = true; // Set global flag
                    }
                }

                 // Initialize listeners when the DOM is ready
                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initModalListeners);
                } else {
                    // DOMContentLoaded has already fired
                    initModalListeners();
                }

            })(); // End IIFE wrapper
    // Wrap in a function to avoid global scope pollution
    (function() {
        // Run when the DOM is fully loaded
        document.addEventListener('DOMContentLoaded', function() {
            // --- Configuration (Directly injected from PHP) ---
            const leagueId = <?php echo json_encode($league_id); ?>;
            const budget = <?php echo json_encode($budget); ?>;
            // IMPORTANT: Adjust these rules to match your league!
            const requiredPlayerCount = <?php echo json_encode(6); ?>; // Total players needed
            const positionLimits = <?php echo json_encode([
                'goalkeeper' => 6,
                'defender'   => 6, // Example: Max 2 defenders
                'midfielder' => 6, // Example: Max 2 midfielders
                'attacker'   => 6  // Example: Max 1 attacker
                // Ensure sum matches requiredPlayerCount if it's a fixed formation
            ]); ?>;
            const itemsPerPage = <?php echo json_encode(15); ?>; // Players per page

            // --- DOM Element References ---
            const containerId = `player_selection_form_${leagueId}`;
            const container = document.getElementById(containerId);
            if (!container) {
                console.error(`DFSoccer: Player selection container #${containerId} not found.`);
                return; // Stop if the main container isn't found
            }

            const searchInput = container.querySelector(`#player-search-${leagueId}`);
            const positionFilterBtns = container.querySelectorAll('.dfsoccer-filter-btn[data-filter]');
            const playerListContainer = container.querySelector(`#player-list-container-${leagueId}`);
            const paginationContainer = container.querySelector(`#pagination-${leagueId}`);
            const saveButton = container.querySelector(`#save-button-${leagueId}`);

            // UI Update Elements
            const budgetValueEl = container.querySelector(`#budget-value-${leagueId}`);
            const currentPriceEl = container.querySelector(`#current-price-${leagueId}`);
            const budgetLeftEl = container.querySelector(`#budget-left-${leagueId}`);
            const teamStatusBarEl = container.querySelector(`#team-status-bar-${leagueId}`);
            const selectionCountsContainer = container.querySelector(`#selection-counts-${leagueId}`);
            const pitchPositionsContainer = container.querySelector(`#soccer-field-${leagueId} .dfsoccer-player-positions`);

            // Modal Related Elements (Fetch globally as modals are outside the form)
            const modalTriggers = document.querySelectorAll(`.dfsoccer-modal-trigger[data-modal-target^="#"][data-modal-target$="-${leagueId}"]`); // More specific trigger selection
            const moreFiltersModal = document.getElementById(`more-filters-modal-${leagueId}`);
            const clubFilterSelect = moreFiltersModal ? moreFiltersModal.querySelector(`#club-filter-${leagueId}`) : null;
            const priceFilterInput = moreFiltersModal ? moreFiltersModal.querySelector(`#price-filter-${leagueId}`) : null;
            const applyFiltersBtn = moreFiltersModal ? moreFiltersModal.querySelector(`.dfsoccer-apply-more-filters-btn`) : null;

            // Check for essential elements
            if (!searchInput || !playerListContainer || !paginationContainer || !saveButton || !budgetValueEl || !currentPriceEl || !budgetLeftEl || !teamStatusBarEl || !selectionCountsContainer || !pitchPositionsContainer) {
                 console.error(`DFSoccer: One or more essential UI elements not found within #${containerId}. Check IDs and structure.`);
                 return;
            }


            // --- State Variables ---
            let currentPage = 1;
            let currentFilter = {
                search: '',
                position: 'all',
                club: '',
                maxPrice: ''
            };
            // Get *all* player items initially, including those potentially hidden by PHP pagination if it existed
            const allPlayerItems = Array.from(playerListContainer.querySelectorAll('.dfsoccer-player-list__item'));
            let filteredPlayerItems = [...allPlayerItems]; // Current list after filtering

            // --- Core Functions ---

            function updateDisplayedPlayers() {
                // 1. Apply Filters
                const searchTerm = currentFilter.search.toLowerCase().trim();
                const selectedPosition = currentFilter.position;
                const selectedClub = currentFilter.club;
                const maxPrice = currentFilter.maxPrice !== '' && !isNaN(parseFloat(currentFilter.maxPrice))
                                 ? parseFloat(currentFilter.maxPrice)
                                 : Infinity;

                filteredPlayerItems = allPlayerItems.filter(item => {
                    // Ensure item has necessary data attributes
                    if (!item.dataset.playerName || typeof item.dataset.position === 'undefined' || typeof item.dataset.clubId === 'undefined' || typeof item.dataset.price === 'undefined') {
                         console.warn('DFSoccer: Player item missing data attributes:', item);
                         return false; // Exclude items with missing data
                    }

                    const playerName = item.dataset.playerName.toLowerCase();
                    const playerPosition = item.dataset.position; // Already lowercase from PHP
                    const playerClub = item.dataset.clubId;
                    const playerPrice = parseFloat(item.dataset.price);

                    const matchesSearch = !searchTerm || playerName.includes(searchTerm);
                    const matchesPosition = selectedPosition === 'all' || playerPosition === selectedPosition;
                    const matchesClub = !selectedClub || playerClub === selectedClub;
                    const matchesPrice = !isNaN(playerPrice) && playerPrice <= maxPrice;

                    const match = [matchesSearch, matchesPosition, matchesClub, matchesPrice].every(Boolean);
                    item.classList.toggle('hidden-by-filter', !match);
                    return match;
                });

                // 2. Apply Sorting (Optional - PHP already sorts by price DESC)
                // Example: Add JS sorting options later if needed

                // 3. Reset to page 1 and Render Pagination/Page
                currentPage = 1;
                renderPagination();
                showPage(currentPage);
            }

            function showPage(page) {
                const startIndex = (page - 1) * itemsPerPage;
                const endIndex = startIndex + itemsPerPage;

                // Hide all items first (use the master list for hiding)
                allPlayerItems.forEach(item => item.style.display = 'none');

                // Show only items for the current page from the *filtered* list
                const itemsToShow = filteredPlayerItems.slice(startIndex, endIndex);
                 if (itemsToShow.length === 0 && filteredPlayerItems.length > 0) {
                     // Handle case where current page is beyond the filtered results (e.g., after filtering)
                     currentPage = Math.max(1, Math.ceil(filteredPlayerItems.length / itemsPerPage));
                     showPage(currentPage); // Re-render with the adjusted page
                     return;
                 }

                itemsToShow.forEach(item => {
                     // Revert to default display (grid) only if it wasn't hidden by filters
                     if (!item.classList.contains('hidden-by-filter')) {
                         item.style.display = '';
                     }
                });

                updatePaginationButtons(page);

                // Scroll player list back to top when changing pages (optional)
                if (playerListContainer) playerListContainer.scrollTop = 0;
            }


            function renderPagination() {
                if (!paginationContainer) return;
                paginationContainer.innerHTML = ''; // Clear existing buttons
                const totalPages = Math.ceil(filteredPlayerItems.length / itemsPerPage);

                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.textContent = i;
                        pageButton.type = 'button'; // Prevent form submission
                        pageButton.setAttribute('aria-label', `Go to page ${i}`);
                        pageButton.addEventListener('click', () => {
                            currentPage = i;
                            showPage(currentPage);
                        });
                        paginationContainer.appendChild(pageButton);
                    }
                    paginationContainer.style.display = '';
                    updatePaginationButtons(currentPage); // Set initial state
                } else {
                     paginationContainer.style.display = 'none'; // Hide if only one page or zero results
                }
            }

             function updatePaginationButtons(activePage) {
                if (!paginationContainer) return;
                const buttons = paginationContainer.querySelectorAll('button');
                buttons.forEach((button, index) => {
                    const pageNum = index + 1;
                    button.disabled = (pageNum === activePage);
                    if (pageNum === activePage) {
                        button.setAttribute('aria-current', 'page');
                    } else {
                        button.removeAttribute('aria-current');
                    }
                });
             }


            function updateTeamState() {
                const selectedItems = playerListContainer.querySelectorAll('.dfsoccer-player-list__item.selected');
                const selectedCount = selectedItems.length;
                let currentTotalCost = 0;
                const positionCounts = { goalkeeper: 0, defender: 0, midfielder: 0, attacker: 0 }; // Use the exact keys from player data-position
                const selectedPlayerData = []; // For pitch update

                selectedItems.forEach(item => {
                    const price = parseFloat(item.dataset.price);
                    if (!isNaN(price)) {
                         currentTotalCost += price;
                    }
                    const position = item.dataset.position;
                    if (position && positionLimits.hasOwnProperty(position)) { // Check if position is valid
                        positionCounts[position] = (positionCounts[position] || 0) + 1;
                    }
                    // Collect data for pitch
                     selectedPlayerData.push({
                         id: item.dataset.playerId,
                         name: item.dataset.playerName,
                         position: position,
                         price: price
                     });
                });

                const budgetLeft = budget - currentTotalCost;

                // Update Budget UI
                currentPriceEl.textContent = `$${currentTotalCost.toFixed(0)}`;
                budgetLeftEl.textContent = `$${budgetLeft.toFixed(0)}`;
                currentPriceEl.classList.toggle('negative', currentTotalCost > budget);
                currentPriceEl.classList.toggle('positive', currentTotalCost <= budget);
                budgetLeftEl.classList.toggle('negative', budgetLeft < 0);
                budgetLeftEl.classList.toggle('positive', budgetLeft >= 0);

                // Update Position Counts UI
                selectionCountsContainer.innerHTML = ''; // Clear old counts
                let positionsOk = true; // Track if all position limits are met exactly (if required)
                for (const [pos, limit] of Object.entries(positionLimits)) {
                     const count = positionCounts[pos] || 0;
                     // Use full position name for class consistency if needed, or stick to short
                     const posShort = pos === 'goalkeeper' ? 'GK' : pos.substring(0, 3).toUpperCase();
                     const isComplete = count >= limit; // Met or exceeded limit
                     const isExact = count === limit; // Exactly the limit

                     const countEl = document.createElement('div');
                     // Use full position name in class for consistency
                     countEl.className = `dfsoccer-position-count dfsoccer-position-count-${pos} ${isComplete ? 'complete' : ''}`;
                     countEl.innerHTML = `<span class="icon-status"></span> ${posShort}: ${count}/${limit}`;
                     selectionCountsContainer.appendChild(countEl);

                     // If validation requires exact counts per position for saving, track this
                     // if (!isExact) positionsOk = false; // Uncomment if exact count is required
                }

                // Update Team Status Bar & Save Button
                 let statusClass = 'incomplete';
                 let statusText = `${selectedCount}/${requiredPlayerCount} Players Selected`;
                 let isSaveDisabled = true;

                 // Check conditions for enabling save
                 const countMet = selectedCount === requiredPlayerCount;
                 const budgetOk = currentTotalCost <= budget;
                 // positionsOk check is optional based on rules (do you need EXACTLY 1 GK, 2 DEF etc.?)

                 if (!budgetOk) {
                     statusClass = 'overbudget';
                     statusText = `Team Over Budget ($${currentTotalCost.toFixed(0)})`;
                 } else if (countMet /* && positionsOk */) { // Add positionsOk here if needed
                      statusClass = 'complete';
                      statusText = 'Team Selection Complete!';
                      isSaveDisabled = false; // Enable save only if count, budget (and optionally positions) are OK
                 } else if (selectedCount > requiredPlayerCount) {
                     statusClass = 'overbudget'; // Or a specific "too many players" class
                     statusText = `Too many players selected (${selectedCount}/${requiredPlayerCount})`;
                 } else {
                    // Still incomplete (count wrong, or positions wrong if positionsOk is checked)
                     statusClass = 'incomplete';
                      statusText = `${selectedCount}/${requiredPlayerCount} Players Selected`;
                     // Could add more specific message if positionsOk check fails
                 }


                teamStatusBarEl.className = `dfsoccer-team-status-bar ${statusClass}`;
                teamStatusBarEl.textContent = statusText;
                saveButton.disabled = isSaveDisabled;

                // Enforce Max Selections / Position Limits on non-selected items
                enforceSelectionLimits(selectedCount, positionCounts);

                 // Update Pitch Visualization
                 updatePitchVisualization(selectedPlayerData);
            }

            function enforceSelectionLimits(selectedCount, positionCounts) {
                const maxTotalReached = selectedCount >= requiredPlayerCount;

                allPlayerItems.forEach(item => {
                    const checkbox = item.querySelector(`#player-checkbox-${item.dataset.playerId}`);
                    const position = item.dataset.position;
                    const isItemSelected = item.classList.contains('selected');

                    let isDisabled = false;
                    let disableReason = ''; // For potential tooltips later

                    // Reason 1: Max total players reached
                    if (maxTotalReached && !isItemSelected) {
                        isDisabled = true;
                        disableReason = `Maximum ${requiredPlayerCount} players allowed.`;
                    }

                    // Reason 2: Max for this specific position reached
                    if (!isDisabled && positionLimits.hasOwnProperty(position)) {
                        if ((positionCounts[position] >= positionLimits[position]) && !isItemSelected) {
                            isDisabled = true;
                             disableReason = `Maximum ${positionLimits[position]} ${position}(s) allowed.`;
                        }
                    }

                    item.classList.toggle('disabled-selection', isDisabled);
                    item.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
                    // Add title for tooltip explanation (optional)
                    if (isDisabled) {
                         item.title = disableReason;
                    } else {
                         item.removeAttribute('title');
                    }

                    // Also disable the actual checkbox for form submission safety
                    if (checkbox) {
                        checkbox.disabled = isDisabled;
                    }
                });
            }

            function togglePlayerSelection(playerItem) {
                 const checkbox = playerItem.querySelector(`#player-checkbox-${playerItem.dataset.playerId}`);
                 // Allow deselection even if the item *would* be disabled otherwise
                 if (playerItem.classList.contains('disabled-selection') && !playerItem.classList.contains('selected')) {
                     return; // Do not allow selecting a disabled item
                 }

                 const isSelected = playerItem.classList.toggle('selected');
                 if(checkbox) {
                      checkbox.checked = isSelected;
                      // Manually trigger change event if needed by other scripts (unlikely here)
                      // checkbox.dispatchEvent(new Event('change', { bubbles: true }));
                 }
                 playerItem.setAttribute('aria-pressed', isSelected ? 'true' : 'false');

                 // Trigger updates
                 updateTeamState();
             }

             // --- Pitch Update Function ---
             function updatePitchVisualization(selectedPlayersData) {
                 if (!pitchPositionsContainer) return;

                 // Get all available slots on the pitch
                 const slots = pitchPositionsContainer.querySelectorAll('.dfsoccer-player-spot[data-position-slot]');
                 const filledSlots = new Set(); // Track which slots are used

                 // Reset all slots first
                 slots.forEach(slot => {
                      slot.classList.remove('filled');
                      const nameEl = slot.querySelector('.dfsoccer-player-spot-name');
                      const priceEl = slot.querySelector('.dfsoccer-player-spot-price');
                      if (nameEl) nameEl.textContent = '';
                      if (priceEl) priceEl.textContent = '';
                      slot.dataset.playerId = ''; // Clear player ID if stored
                 });

                 // Fill slots with selected players
                 selectedPlayersData.forEach(player => {
                     const position = player.position;
                     if (!position) return; // Skip players with no position

                     // Find the first available slot for this player's position
                     let slotFound = false;
                     for (let i = 0; ; i++) { // Loop through potential slot indices (e.g., defender_0, defender_1)
                         const slotSelector = `[data-position-slot="${position}_${i}"]`;
                         const slot = pitchPositionsContainer.querySelector(slotSelector);
                         if (!slot) break; // No more slots defined for this position

                         const slotKey = `${position}_${i}`;
                         if (!filledSlots.has(slotKey)) {
                             // Found an empty slot for this position
                             slot.classList.add('filled');
                             const nameEl = slot.querySelector('.dfsoccer-player-spot-name');
                             const priceEl = slot.querySelector('.dfsoccer-player-spot-price');
                             if (nameEl) nameEl.textContent = player.name ? player.name.substring(0, 12) : ''; // Limit name length
                             if (priceEl) priceEl.textContent = player.price ? `$${player.price.toFixed(0)}` : '';
                             slot.dataset.playerId = player.id; // Store player ID in slot (optional)

                             filledSlots.add(slotKey); // Mark slot as filled
                             slotFound = true;
                             break; // Move to the next player
                         }
                     }
                     // if (!slotFound) { console.warn(`No available pitch slot found for ${position}`); }
                 });
             }


            // --- Event Listeners ---

            // Player Selection (using event delegation on the container)
            if(playerListContainer) {
                playerListContainer.addEventListener('click', function(event) {
                     const playerItem = event.target.closest('.dfsoccer-player-list__item');
                     if (playerItem) {
                         togglePlayerSelection(playerItem);
                     }
                 });
                 // Allow selection with Enter/Space key for accessibility
                 playerListContainer.addEventListener('keydown', function(event) {
                     if (event.key === 'Enter' || event.key === ' ') {
                         const playerItem = event.target.closest('.dfsoccer-player-list__item');
                         if (playerItem) {
                              event.preventDefault(); // Prevent default space scroll / enter form submit
                             togglePlayerSelection(playerItem);
                         }
                     }
                 });
             }


            // Search Input
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    currentFilter.search = this.value;
                    // Optional: Debounce this for performance on large lists
                    updateDisplayedPlayers();
                });
            }

            // Position Filter Buttons
            positionFilterBtns.forEach(button => {
                // Exclude the 'More Filters' button if it has the same base class
                 if (button.classList.contains('more-filters')) return;

                button.addEventListener('click', function() {
                    positionFilterBtns.forEach(btn => {
                        if (!btn.classList.contains('more-filters')) {
                            btn.classList.remove('active');
                        }
                    });
                    this.classList.add('active');
                    currentFilter.position = this.dataset.filter;
                    updateDisplayedPlayers();
                });
            });

            // "More Filters" Application (when modal closes or apply button clicked)
            function applyMoreFilters() {
                currentFilter.club = clubFilterSelect ? clubFilterSelect.value : '';
                currentFilter.maxPrice = priceFilterInput ? priceFilterInput.value : '';
                updateDisplayedPlayers();
            }

            // Apply button inside modal
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', applyMoreFilters);
            }
             // Optional: Apply live as filters change inside modal
             // if (clubFilterSelect) clubFilterSelect.addEventListener('change', applyMoreFilters);
             // if (priceFilterInput) priceFilterInput.addEventListener('input', applyMoreFilters); // Might need debounce


            // --- Modal Handling (Generic - Must handle modals outside the form container) ---
            const allModals = document.querySelectorAll('.dfsoccer-modal');

            function openModal(modal) {
                if (!modal || modal.classList.contains('is-visible')) return;
                modal.classList.add('is-visible');
                modal.setAttribute('aria-hidden', 'false');
                // Focus trapping could be added here for better accessibility
                // Lazy load iframe
                const iframe = modal.querySelector('iframe[data-src]');
                if (iframe && iframe.src === 'about:blank') {
                    iframe.src = iframe.dataset.src;
                }
            }

            function closeModal(modal) {
                if (!modal || !modal.classList.contains('is-visible')) return;
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
                // Optional: Reset iframe src to stop loading/playing
                // const iframe = modal.querySelector('iframe[data-src]');
                // if (iframe) iframe.src = 'about:blank';
            }

            // Event listeners for triggers specific to this league instance
            modalTriggers.forEach(trigger => {
                trigger.addEventListener('click', (e) => {
                    e.preventDefault();
                    const targetSelector = trigger.dataset.modalTarget;
                    const modal = document.querySelector(targetSelector);
                    if (modal) {
                        openModal(modal);
                    } else {
                        console.error(`DFSoccer: Modal with selector ${targetSelector} not found.`);
                    }
                });
            });

            // Close listeners (apply to all modals on the page once)
            // Use a flag to ensure these global listeners are added only once if the shortcode runs multiple times
             if (!window.dfsoccerModalListenersAdded) {
                 document.body.addEventListener('click', function (event) {
                     // Close via overlay or [data-modal-close] attribute
                     if (event.target.matches('[data-modal-close]')) {
                          const modalToClose = event.target.closest('.dfsoccer-modal');
                          if (modalToClose) closeModal(modalToClose);
                     }
                 });

                 document.addEventListener('keydown', (e) => {
                     if (e.key === 'Escape') {
                         const visibleModal = document.querySelector('.dfsoccer-modal.is-visible');
                         if (visibleModal) closeModal(visibleModal);
                     }
                 });
                 window.dfsoccerModalListenersAdded = true;
             }


            // --- Initial Setup ---
            updateTeamState(); // Calculate initial state based on pre-selected players
            updateDisplayedPlayers(); // Filter, sort (if needed), and paginate initially

        }); // End DOMContentLoaded listener
    })(); // End IIFE wrapper
    </script>

    <?php
    // --- Return Buffered Output ---
    return ob_get_clean();
}

// Register the shortcode (use the new function name)
// Place this registration part where your plugin registers shortcodes
// Example:
// add_action('init', function() {
//     add_shortcode('players_for_fixtures_refactored', 'dfsoccer_display_players_for_fixtures_shortcode_refactored');
//     // Add alias if you want the old shortcode [players_for_fixtures] to work with the new function
add_shortcode('players_for_fixtures', 'dfsoccer_display_players_for_fixtures_shortcode_refactored');




function display_fixtures_only($league_id) {
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
    
    // Get fixtures from either source
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    $api_saved_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
    
    // Use whichever fixtures are available
    $fixtures = !empty($saved_fixtures) ? $saved_fixtures : $api_saved_fixtures;
    
    if (empty($fixtures)) {
        return '<p>No fixtures available for this league.</p>';
    }
    
    // Initialize output variable
    $output = '';
    
    foreach ($fixtures as $fixture) {
        // Get club names
        $home_club_name = !empty($fixture['home_club_name']) 
            ? esc_html($fixture['home_club_name']) 
            : esc_html(get_the_title($fixture['home_club_id']));
            
        $away_club_name = !empty($fixture['away_club_name']) 
            ? esc_html($fixture['away_club_name']) 
            : esc_html(get_the_title($fixture['away_club_id']));
        
        $formatted_date = esc_html(date_i18n('Y-m-d H:i:s', strtotime($fixture['fixture_date'])));
        
        $output .= "<p style=\"color: #ffffff; font-size: 1rem; text-align: center; margin: 1rem 0; line-height: 1.5;\">
          Fixture: 
          <span style=\"font-weight: bold; color: #86efac; text-transform: uppercase; letter-spacing: 0.05em;\">{$home_club_name}</span> 
          <span style=\"color: #eab308; font-weight: bold; margin: 0 0.5rem;\">vs</span> 
          <span style=\"font-weight: bold; color: #86efac; text-transform: uppercase; letter-spacing: 0.05em;\">{$away_club_name}</span> 
          <br>
          <span style=\"display: inline-block; margin-top: 0.5rem; font-size: 0.9rem; color: #bbf7d0; background-color: rgba(22, 101, 52, 0.4); padding: 0.25rem 0.75rem; border-radius: 1rem;\">
            <svg xmlns=\"http://www.w3.org/2000/svg\" width=\"12\" height=\"12\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\" style=\"display: inline-block; vertical-align: middle; margin-right: 0.25rem;\">
              <circle cx=\"12\" cy=\"12\" r=\"10\"></circle>
              <polyline points=\"12 6 12 12 16 14\"></polyline>
            </svg>
            " . date('M d, Y', strtotime($formatted_date)) . " • " . date('H:i', strtotime($formatted_date)) . "
          </span>
        </p>";
    }
    
    return $output;
}

function flash_countdown_for_dfsoccer($league_id) {
    ob_start();
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to get fixture elements from the modal
        function getFixtureElementsFromModal() {
            const modalContent = document.querySelector('.dfsoccer-modal-content');
            return modalContent ? Array.from(modalContent.querySelectorAll('p')) : [];
        }
        
        // Function to extract dates and team names from fixture elements
        function extractFixtureData(elements) {
            // Match date format like "Apr 20, 2024 • 12:34"
            const dateRegex = /(\w{3}\s\d{1,2},\s\d{4})\s•\s(\d{1,2}:\d{2})/;
            // Extract team names
            // Extract team names - improved regex
const teamRegex = /Fixture:\s+([^vs]+)\s+vs\s+([^•\n]+)/i;

return elements
    .map(el => {
        const text = el.textContent;
        const dateMatch = text.match(dateRegex);
        const teamMatch = text.match(teamRegex);
        
        if (dateMatch) {
            // Combine date and time parts
            const dateTimeStr = `${dateMatch[1]} ${dateMatch[2]}`;
            const fixtureDate = new Date(dateTimeStr);
            
            return {
                date: fixtureDate,
                homeTeam: teamMatch ? teamMatch[1].trim() : 'TBD',
                awayTeam: teamMatch ? teamMatch[2].trim() : 'TBD',
                formattedDate: dateMatch[1],
                formattedTime: dateMatch[2]
            };
        }
        return null;
    })
                .filter(fixture => fixture && fixture.date instanceof Date && !isNaN(fixture.date));
        }
        
        // Find the earliest fixture from an array of fixture data
        function findEarliestFixture(fixtures) {
            if (!fixtures || !fixtures.length) return null;
            
            return fixtures.reduce((earliest, current) => {
                if (!earliest || current.date < earliest.date) {
                    return current;
                }
                return earliest;
            }, null);
        }
        
        // Update countdown display function
        function updateCountdown(fixture, leagueId) {
            const countdownElement = document.getElementById(`fixture-countdown-units-${leagueId}`);
            const fixtureInfoElement = document.querySelector('.dfsoccer-fixture-info-compact');
            
            if (!countdownElement || !fixtureInfoElement) return;
            
            // Update fixture info
            const teamsElement = fixtureInfoElement.querySelector('.teams');
            const timeElement = fixtureInfoElement.querySelector('.time');
            
            if (teamsElement) {
                teamsElement.innerHTML = `Next: <strong>${fixture.homeTeam}</strong> vs <strong>${fixture.awayTeam}</strong>`;
            }
            
            if (timeElement) {
                timeElement.textContent = `${fixture.formattedDate}, ${fixture.formattedTime}`;
            }
            
            // Set up countdown timer
            const countdownUnits = countdownElement.querySelectorAll('.dfsoccer-countdown-unit');
            const countdownValues = countdownElement.querySelectorAll('.dfsoccer-countdown-value');
            
            function updateTimer() {
                const now = new Date();
                const diffMs = fixture.date - now;
                
                if (diffMs <= 0) {
                    // If fixture has started
                    countdownValues.forEach(value => {
                        value.textContent = '0';
                    });
                    clearInterval(timerInterval);
                    return;
                }
                
                // Calculate days, hours, minutes, seconds
                const days = Math.floor(diffMs / 86400000);
                const hours = Math.floor((diffMs % 86400000) / 3600000);
                const minutes = Math.floor((diffMs % 3600000) / 60000);
                const seconds = Math.floor((diffMs % 60000) / 1000);
                
                // Update the countdown values
                const values = [days, hours, minutes, seconds];
                countdownValues.forEach((value, index) => {
                    value.textContent = values[index];
                });
            }
            
            // Update immediately
            updateTimer();
            
            // Then update every second
            const timerInterval = setInterval(updateTimer, 1000);
        }
        
        // Get fixture data from modal
        const fixtureElements = getFixtureElementsFromModal();
        const fixturesData = extractFixtureData(fixtureElements);
        const earliestFixture = findEarliestFixture(fixturesData);
        
        // If we found a fixture, update the countdown
        if (earliestFixture) {
            updateCountdown(earliestFixture, <?php echo json_encode($league_id); ?>);
        } else {
            // Handle case when no fixtures are found
            const fixtureInfoElement = document.querySelector('.dfsoccer-fixture-info-compact');
            if (fixtureInfoElement) {
                const teamsElement = fixtureInfoElement.querySelector('.teams');
                if (teamsElement) {
                    teamsElement.textContent = 'No upcoming fixtures found';
                }
            }
        }
    });
    </script>
    <?php
    return ob_get_clean();
}



/**
 * Refactored Fantasy Manager Shortcode (API Version)
 * Uses the visual layout from the non-API version but fetches data via API.
 * Supports different modes for team setup (e.g., mode="11").
 *
 * @param array $atts Shortcode attributes: league_id, src, mode, default_budget, rules_required_players.
 * @return string HTML output for the fantasy manager interface.
 */
function dfsoccer_fantasy_manager_shortcode_v2($atts) {


    $atts = shortcode_atts([
        'league_id'              => '0',     // The league the user is participating in
        'src'                    => '0',     // The source league ID for fetching player data FROM API
        'mode'                   => 'standard', // New: 'standard' or '11' (or others in future)
        'default_budget'         => 700.00,  // Default budget if not set in league post meta
        'rules_required_players' => 6,       // Default required players (standard mode) if not set in rules meta
        // Add attributes for modal content if needed
    ], $atts, 'dfsoccer_fantasy_manager_v2'); // Updated shortcode tag name

    $league_id = filter_var($atts['league_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $source_league_id = filter_var($atts['src'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $mode = sanitize_key($atts['mode']); // Sanitize the mode input
    $user_id = get_current_user_id();
    $instance_id = 'dfm_api_' . $league_id . '_' . $mode . '_' . uniqid(); // Unique ID including mode

    // Validate League ID and Source ID
    if ($league_id === false) {
        return '<p class="dfsoccer-notice error">Error: Invalid league_id provided.</p>';
    }
     if ($source_league_id === false) {
        return '<p class="dfsoccer-notice error">Error: Invalid src (API source league ID) provided.</p>';
    }
     // Optional: Check if the $league_id corresponds to a valid 'league' post type
     if (!get_post($league_id) || get_post_type($league_id) !== 'league') {
         // return '<p class="dfsoccer-notice error">Error: League ID does not correspond to a valid league post.</p>';
     }


    // --- 2. Fetch League Data (Budget, Rules) ---
    $player_meta_key    = 'dfsoccer_selected_players_' . $league_id; // Meta key can stay the same per league
    $budget_meta_key    = 'dfsoccer_league_budget';
    $rules_meta_key     = 'dfsoccer_points_rules';

    // Fetch Budget (Mode independent)
    $budget = floatval(get_post_meta($league_id, $budget_meta_key, true));
    if (!$budget || $budget <= 0) {
        $budget = floatval($atts['default_budget']);
    }

    // Initialize rule variables
    $rules = [];
    $js_position_limits_output = [];
    $required_player_count = 0;

    // --- MODE-SPECIFIC RULES ---
    if ($mode === '11') {
        // Hardcoded rules for mode 11 (1 GK, 4 DEF, 4 MID, 2 ATT = 11 players)
        // NOTE: User asked for 11 players total but specified positions summing to 13. Implementing 13 based on position counts.
        $required_player_count = 11;
        $js_position_limits_output = [
            'goalkeeper' => 1,
            'defender'   => 4,
            'midfielder' => 4,
            'attacker'   => 2
        ];
        // Create a compatible $rules array structure for potential use in validation/display
        $rules = [
             'goalkeeper' => ['limit' => 1, 'min_required' => 1],
             'defender'   => ['limit' => 4, 'min_required' => 4], // Assuming min = max for this mode
             'midfielder' => ['limit' => 4, 'min_required' => 4], // Assuming min = max for this mode
             'attacker'   => ['limit' => 2, 'min_required' => 2]  // Assuming min = max for this mode
        ];
        // error_log("DFSoccer API v2: Using Mode 11 rules (1/4/4/4 = 13 players) for league ID $league_id");

    } else {
        // --- Standard Mode: Fetch Rules from Meta or Use Defaults ---
        $rules = get_post_meta($league_id, $rules_meta_key, true);
        if (!$rules || !is_array($rules)) {
            // Standard Default rules if not found in meta
            $rules = [
                 'goalkeeper' => ['limit' => 1, 'min_required' => 1],
                 'defender'   => ['limit' => 2, 'min_required' => 2],
                 'midfielder' => ['limit' => 2, 'min_required' => 2],
                 'attacker'   => ['limit' => 1, 'min_required' => 1]
            ];
             $required_player_count = array_reduce($rules, function($sum, $pos_rules) {
                 return $sum + ($pos_rules['min_required'] ?? 0);
             }, 0);
             if ($required_player_count <= 0) {
                 $required_player_count = intval($atts['rules_required_players']); // Fallback to attribute
             }
             // error_log("DFSoccer API v2: Using default standard rules/player count for league ID $league_id");
        } else {
            // Process fetched standard rules
            $temp_limits = [];
            $calculated_count = 0;
            foreach ($rules as $pos => $pos_rules) {
                 if (!is_array($pos_rules)) continue; // Skip malformed rules
                $limit = $pos_rules['limit'] ?? 1;
                $min_required = $pos_rules['min_required'] ?? $limit;
                $temp_limits[strtolower($pos)] = intval($limit);
                $calculated_count += intval($min_required);
            }
            $js_position_limits_output = $temp_limits; // Assign processed limits
            $required_player_count = ($calculated_count > 0) ? $calculated_count : intval($atts['rules_required_players']);
        }

        // Ensure all standard positions have a limit defined for JS, even if 0
        if (!isset($js_position_limits_output['goalkeeper'])) $js_position_limits_output['goalkeeper'] = $rules['goalkeeper']['limit'] ?? 1;
        if (!isset($js_position_limits_output['defender']))   $js_position_limits_output['defender']   = $rules['defender']['limit'] ?? 2;
        if (!isset($js_position_limits_output['midfielder'])) $js_position_limits_output['midfielder'] = $rules['midfielder']['limit'] ?? 2;
        if (!isset($js_position_limits_output['attacker']))   $js_position_limits_output['attacker']   = $rules['attacker']['limit'] ?? 1;
    }
    // --- End Rule Logic ---


    // --- 3. Fetch Player Data (API Call - Mode independent) ---
    $api_players = [];
    $api_error_message = '';
    // ... (Keep existing API fetching logic - it's not affected by the mode) ...
    $api_url = 'https://superfantasy.net/wp-json/dfsoccer/v1/league/' . $source_league_id . '/fixture-players?nocache=1&LSCWP_CTRL=NOCACHE';
    $response = wp_remote_get($api_url, ['timeout' => 20]);

    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $api_body = wp_remote_retrieve_body($response);
        $api_data = json_decode($api_body, true);
        if (isset($api_data['players']) && is_array($api_data['players'])) {
            $api_players_raw = $api_data['players'];
            usort($api_players_raw, function($a, $b) {
                $priceA = isset($a['price']) ? floatval($a['price']) : 0;
                $priceB = isset($b['price']) ? floatval($b['price']) : 0;
                return $priceB <=> $priceA; // Descending order
            });

             foreach ($api_players_raw as $api_player) {
                 $player_id = filter_var($api_player['id'] ?? null, FILTER_VALIDATE_INT);
                 if (!$player_id) continue;
                 $pos_raw = strtolower($api_player['position'] ?? 'unknown');
                 if (strpos($pos_raw, 'goal') !== false) $pos_normalized = 'goalkeeper';
                 elseif (strpos($pos_raw, 'defen') !== false) $pos_normalized = 'defender';
                 elseif (strpos($pos_raw, 'midfield') !== false) $pos_normalized = 'midfielder';
                 elseif (strpos($pos_raw, 'attack') !== false || strpos($pos_raw, 'forward') !== false || strpos($pos_raw, 'striker') !== false) $pos_normalized = 'attacker';
                 else $pos_normalized = 'unknown';

                  $api_players[] = [
                    'id'        => $player_id,
                    'name'      => esc_html($api_player['name'] ?? 'Unknown Player'),
                    'price'     => floatval($api_player['price'] ?? 0),
                    'position'  => $pos_normalized,
                    'club_id'   => intval($api_player['club_id'] ?? 0),
                    'club_name' => esc_html($api_player['club_name'] ?? 'N/A'),
                  ];
             }
        } else { $api_error_message = 'Invalid API response format.'; }
    } else { $api_error_message = is_wp_error($response) ? $response->get_error_message() : 'Failed to fetch players from API. Status: ' . wp_remote_retrieve_response_code($response); }


    // --- 4. Fetch User's Saved Selections ---
    $saved_player_ids = (array) get_user_meta($user_id, $player_meta_key, true);


    // --- 5. Handle Form Submission ---
    $error_message = '';
    $success_message = '';
    $submit_button_name = 'submit_players_' . $league_id; // Submit name remains league-specific

if (isset($_POST[$submit_button_name]) && isset($_POST['_wpnonce_player_selection_' . $league_id])) {
    if (!is_user_logged_in()) {
        $error_message = 'You must be logged in.';
    } elseif (function_exists('api_has_fixtures_started_league')) {
        $message = '';
        $use_cache = false; // Skip cache to always get fresh result
        if (api_has_fixtures_started_league($league_id, $message, $use_cache)) {
            $error_message = "This league has already started. Selections locked.";
        } elseif (wp_verify_nonce($_POST['_wpnonce_player_selection_' . $league_id], 'dfsoccer_select_players_' . $league_id . '_' . $user_id)) {
            $submitted_player_ids = isset($_POST['selected_players']) && is_array($_POST['selected_players'])
                                    ? array_map('intval', $_POST['selected_players'])
                                    : [];

            // Server-side validation uses the $required_player_count and $js_position_limits_output
            // variables which are already set based on the mode.
            $total_cost = 0;
            $valid_players = true;
            $submitted_positions = ['goalkeeper' => 0, 'defender' => 0, 'midfielder' => 0, 'attacker' => 0, 'unknown' => 0];
            $api_player_map = array_column($api_players, null, 'id');

            foreach ($submitted_player_ids as $player_id) {
                if (!isset($api_player_map[$player_id])) {
                    $valid_players = false;
                    $error_message = 'Invalid player ID submitted (' . esc_html($player_id) . ').';
                    break;
                }
                $player_data = $api_player_map[$player_id];
                $total_cost += floatval($player_data['price']);
                $position = $player_data['position'] ?? 'unknown';
                if (isset($submitted_positions[$position])) { // Only count known positions
                    $submitted_positions[$position]++;
                } else {
                    $submitted_positions['unknown']++; // Count unknowns separately if needed
                }
            }

            if ($valid_players) {
                $player_count = count($submitted_player_ids);

                // Validation logic automatically uses mode-adjusted counts/limits
                if ($player_count !== $required_player_count) {
                    $error_message = "You must select exactly {$required_player_count} players. You selected {$player_count}.";
                } elseif ($total_cost > $budget) {
                    $error_message = 'Team over budget. Cost: $' . number_format($total_cost, 0) . ', Budget: $' . number_format($budget, 0);
                } else {
                    $position_validation_failed = false;
                    foreach ($js_position_limits_output as $pos => $limit) {
                         // Check MAX limit
                        if (($submitted_positions[$pos] ?? 0) > $limit) {
                             $position_validation_failed = true;
                             $error_message = "Too many " . ucfirst($pos) . "s (Max: {$limit}, Selected: {$submitted_positions[$pos]}).";
                             break;
                         }
                         // Check MIN required (using $rules array which is also mode-adjusted)
                         $min_req = $rules[$pos]['min_required'] ?? 0;
                         if (($submitted_positions[$pos] ?? 0) < $min_req) {
                             $position_validation_failed = true;
                              $error_message = "Not enough " . ucfirst($pos) . "s (Min: {$min_req}, Selected: {$submitted_positions[$pos]}).";
                             break;
                         }
                    }

                    if (!$position_validation_failed) {
                        update_user_meta($user_id, $player_meta_key, $submitted_player_ids);
                        $success_message = 'Players selected successfully!';
                        $saved_player_ids = $submitted_player_ids;
                    }
                    // else: error_message already set by position validation
                }
            }
        } else {
            $error_message = 'Security check failed. Please refresh.';
        }
    } else {
        // Fallback to old method if new function doesn't exist
        if (function_exists('dfsoccer_has_league_started') && dfsoccer_has_league_started($league_id)) {
            $error_message = "This league has already started. Selections locked.";
        } elseif (wp_verify_nonce($_POST['_wpnonce_player_selection_' . $league_id], 'dfsoccer_select_players_' . $league_id . '_' . $user_id)) {
            $submitted_player_ids = isset($_POST['selected_players']) && is_array($_POST['selected_players'])
                                    ? array_map('intval', $_POST['selected_players'])
                                    : [];

            // Server-side validation uses the $required_player_count and $js_position_limits_output
            // variables which are already set based on the mode.
            $total_cost = 0;
            $valid_players = true;
            $submitted_positions = ['goalkeeper' => 0, 'defender' => 0, 'midfielder' => 0, 'attacker' => 0, 'unknown' => 0];
            $api_player_map = array_column($api_players, null, 'id');

            foreach ($submitted_player_ids as $player_id) {
                if (!isset($api_player_map[$player_id])) {
                    $valid_players = false;
                    $error_message = 'Invalid player ID submitted (' . esc_html($player_id) . ').';
                    break;
                }
                $player_data = $api_player_map[$player_id];
                $total_cost += floatval($player_data['price']);
                $position = $player_data['position'] ?? 'unknown';
                if (isset($submitted_positions[$position])) { // Only count known positions
                    $submitted_positions[$position]++;
                } else {
                    $submitted_positions['unknown']++; // Count unknowns separately if needed
                }
            }

            if ($valid_players) {
                $player_count = count($submitted_player_ids);

                // Validation logic automatically uses mode-adjusted counts/limits
                if ($player_count !== $required_player_count) {
                    $error_message = "You must select exactly {$required_player_count} players. You selected {$player_count}.";
                } elseif ($total_cost > $budget) {
                    $error_message = 'Team over budget. Cost: $' . number_format($total_cost, 0) . ', Budget: $' . number_format($budget, 0);
                } else {
                    $position_validation_failed = false;
                    foreach ($js_position_limits_output as $pos => $limit) {
                         // Check MAX limit
                        if (($submitted_positions[$pos] ?? 0) > $limit) {
                             $position_validation_failed = true;
                             $error_message = "Too many " . ucfirst($pos) . "s (Max: {$limit}, Selected: {$submitted_positions[$pos]}).";
                             break;
                         }
                         // Check MIN required (using $rules array which is also mode-adjusted)
                         $min_req = $rules[$pos]['min_required'] ?? 0;
                         if (($submitted_positions[$pos] ?? 0) < $min_req) {
                             $position_validation_failed = true;
                              $error_message = "Not enough " . ucfirst($pos) . "s (Min: {$min_req}, Selected: {$submitted_positions[$pos]}).";
                             break;
                         }
                    }

                    if (!$position_validation_failed) {
                        update_user_meta($user_id, $player_meta_key, $submitted_player_ids);
                        $success_message = 'Players selected successfully!';
                        $saved_player_ids = $submitted_player_ids;
                    }
                    // else: error_message already set by position validation
                }
            }
        } else {
            $error_message = 'Security check failed. Please refresh.';
        }
    }
}


    // --- 6. Prepare Data for Filters (from API players - Mode independent) ---
    $club_data = [];
    $all_positions_found = [];
    // ... (Keep existing filter data preparation logic) ...
    if (!empty($api_players)) {
         $club_ids = array_unique(array_column($api_players, 'club_id'));
         $club_ids = array_filter($club_ids);
         foreach($club_ids as $c_id) {
             $club_name = 'N/A';
             foreach($api_players as $p) {
                 if ($p['club_id'] == $c_id && !empty($p['club_name']) && $p['club_name'] !== 'N/A') {
                     $club_name = $p['club_name'];
                     break;
                 }
             }
             if ($club_name !== 'N/A') { $club_data[$c_id] = $club_name; }
         }
         asort($club_data);

         $all_positions_found = array_unique(array_column($api_players, 'position'));
         $all_positions_found = array_filter($all_positions_found, fn($p) => $p !== 'unknown');
         usort($all_positions_found, function($a, $b) {
             $order = ['goalkeeper' => 0, 'defender' => 1, 'midfielder' => 2, 'attacker' => 3];
             return ($order[$a] ?? 99) <=> ($order[$b] ?? 99);
         });
    }


    // --- 7. Start Output Buffering ---
    ob_start();
    ?>
    <!-- CSS: Includes updated pitch position rules -->
    <style>

        /* --- PASTE ALL CSS RULES FROM THE TARGET dfsoccer_display_players_for_fixtures_shortcode_refactored HERE --- */
        /* Base & Variables */
        :root {
            --color-bg-deep: #052e16;
            --color-bg-medium: #14532d;
            --color-bg-light: #166534;
            --color-bg-card: #15803d;
            --color-bg-field: linear-gradient(to bottom, #16a34a, #22c55e);
            --color-text-primary: #ffffff;
            --color-text-secondary: #bbf7d0;
            --color-text-accent: #86efac;
            --color-accent-yellow: #eab308;
            --color-accent-yellow-dark: #ca8a04;
            --color-accent-positive: #4ade80; /* Bright Green */
            --color-accent-negative: #f87171; /* Red */
            --color-border-light: #166534;
            --color-border-medium: #15803d;
            --color-modal-overlay: rgba(0, 0, 0, 0.7);

            --font-family-base: 'Inter', sans-serif; /* Example font */
            --header-height-desktop: 65px;
            --header-height-mobile: auto; /* Allow header to grow if needed */
            --footer-height: 60px;
        }

        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        .dfsoccer-app-container {
            font-family: var(--font-family-base);
            background-color: var(--color-bg-medium);
            color: var(--color-text-primary);
            line-height: 1.5;
            border: 1px solid var(--color-border-light);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 1.5rem;
        }

        .dfsoccer-app-container button { font-family: inherit; cursor: pointer; border: none; background: none; color: inherit; padding: 0; }
        .dfsoccer-app-container svg { display: block; }

        /* Layout */
        .dfsoccer-app-container { display: flex; flex-direction: column; width: 100%; }
        .dfsoccer-app-header { min-height: var(--header-height-desktop); background-color: var(--color-bg-deep); border-bottom: 1px solid var(--color-border-light); display: flex; flex-wrap: wrap; align-items: center; padding: 0.75rem 1rem; gap: 0.75rem; flex-shrink: 0; }
        .dfsoccer-main-content { flex-grow: 1; display: flex; overflow: hidden; }
        .dfsoccer-team-view { flex: 0 0 40%; max-width: 550px; padding: 1rem; display: flex; flex-direction: column; gap: 1rem; border-right: 1px solid var(--color-border-light); overflow-y: auto; }
        .dfsoccer-player-pool { flex: 1 1 auto; display: flex; flex-direction: column; overflow: hidden; }
        .dfsoccer-app-footer { height: var(--footer-height); background-color: var(--color-bg-deep); border-top: 1px solid var(--color-border-light); display: flex; align-items: center; justify-content: flex-end; padding: 0 1rem; flex-shrink: 0; }

        /* Notifications */
        .dfsoccer-notifications { padding: 0.75rem 1rem; text-align: center; font-weight: 500; order: -1; background-color: var(--color-bg-deep); border-bottom: 1px solid var(--color-border-light); margin: -1px 0 0 0; }
        .dfsoccer-notifications .error, .dfsoccer-notice.error { background-color: var(--color-accent-negative); color: var(--color-text-primary); padding: 0.5rem 1rem; border-radius: 4px; display: inline-block; }
        .dfsoccer-notifications .success, .dfsoccer-notice.success { background-color: var(--color-accent-positive); color: var(--color-bg-deep); padding: 0.5rem 1rem; border-radius: 4px; display: inline-block; }

        /* Header Components */
        .dfsoccer-fixture-countdown { background-color: var(--color-bg-light); padding: 0.4rem 0.8rem; border-radius: 6px; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; order: 1; flex-shrink: 0; }
        .dfsoccer-countdown-icon { width: 14px; height: 14px; color: var(--color-text-accent); flex-shrink: 0; }
        .dfsoccer-countdown-label { font-weight: 500; color: var(--color-text-secondary); margin-right: 0.25rem; white-space: nowrap; }
        .dfsoccer-countdown-units { display: flex; gap: 0.3rem; }
        .dfsoccer-countdown-unit { background-color: var(--color-bg-deep); padding: 0.15rem 0.4rem; border-radius: 4px; }
        .dfsoccer-countdown-value { font-weight: bold; }
        .dfsoccer-countdown-label-sm { font-size: 0.65rem; color: var(--color-text-accent); margin-left: 0.1rem; }
        .dfsoccer-fixture-info-compact { font-size: 0.75rem; color: var(--color-text-secondary); order: 2; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex-grow: 1; min-width: 100px; text-align: left; }
        .dfsoccer-fixture-info-compact strong { color: var(--color-text-primary); }
        .dfsoccer-fixture-info-compact .teams { margin-right: 0.5rem; }
        .dfsoccer-fixture-info-compact .time { font-weight: 500; color: var(--color-text-accent); }
        .dfsoccer-app-nav { display: flex; gap: 0.4rem; order: 3; flex-shrink: 0; }
        .dfsoccer-nav-button { padding: 0.4rem 0.6rem; border-radius: 6px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem; transition: background-color 0.2s ease; white-space: nowrap; }
        .dfsoccer-nav-button:hover { background-color: var(--color-bg-card); color: var(--color-text-primary); }
        .dfsoccer-nav-button svg { width: 16px; height: 16px; fill: currentColor; flex-shrink: 0; }
        .dfsoccer-nav-button .button-text { display: inline; }

        /* Team View Components */
        .dfsoccer-team-view h3 { color: var(--color-text-accent); font-size: 1rem; margin-bottom: 0.5rem; border-bottom: 1px solid var(--color-border-light); padding-bottom: 0.5rem; }
        .dfsoccer-soccer-field { position: relative; width: 100%; height: 350px; background: var(--color-bg-field); border: 2px solid var(--color-border-medium); border-radius: 8px; background-image: radial-gradient(circle at center, transparent 49px, rgba(255,255,255,0.3) 49px, rgba(255,255,255,0.3) 50px, transparent 50px), linear-gradient(to right, transparent calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% - 0.5px), rgba(255,255,255,0.3) calc(50% + 0.5px), transparent calc(50% + 0.5px)); background-size: 100px 100px, 100% 1px; background-position: center center; background-repeat: no-repeat; transition: height 0.3s ease; }
        .dfsoccer-player-positions { position: absolute; inset: 0; display: grid; grid-template-rows: repeat(4, 1fr); grid-template-columns: repeat(5, 1fr); padding: 10px; gap: 5px; }
        .dfsoccer-player-spot { display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; min-height: 50px; position: relative; }
        .dfsoccer-player-jersey-shape { width: 35px; height: 40px; clip-path: polygon(16% 20%, 35% 13%, 40% 20%, 60% 20%, 65% 13%, 86% 20%, 100% 31%, 85% 44%, 80% 40%, 80% 100%, 20% 100%, 20% 40%, 15% 44%, 0 31%); background-color: #555; margin-bottom: 3px; border: 1px solid var(--color-border-medium); flex-shrink: 0; transition: width 0.3s, height 0.3s, background-color 0.3s; }
        .dfsoccer-player-spot.filled .dfsoccer-player-jersey-shape { background-color: var(--color-accent-yellow); border-color: var(--color-accent-yellow-dark); }
        .dfsoccer-player-details { padding: 2px 3px; background-color: rgba(5, 46, 22, 0.7); border-radius: 4px; min-height: 1.8em; visibility: hidden; }
        .dfsoccer-player-spot.filled .dfsoccer-player-details { visibility: visible; }
        .dfsoccer-player-spot-name { font-size: 8px; font-weight: bold; color: var(--color-text-primary); line-height: 1.1; display: block; word-wrap: break-word; max-width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 50px; }
        .dfsoccer-player-spot-price { font-size: 7px; color: var(--color-text-accent); display: block; font-weight: 500; }
        /* Position grid-area rules */
        .dfsoccer-player-pos-gk { grid-area: 4 / 3 / 5 / 4; } .dfsoccer-player-pos-d1 { grid-area: 3 / 1 / 4 / 2; } .dfsoccer-player-pos-d2 { grid-area: 3 / 2 / 4 / 3; } .dfsoccer-player-pos-d3 { grid-area: 3 / 3 / 4 / 5; } .dfsoccer-player-pos-d4 { grid-area: 3 / 4 / 4 / 6; } .dfsoccer-player-pos-m1 { grid-area: 2 / 1 / 3 / 2; } .dfsoccer-player-pos-m2 { grid-area: 2 / 2 / 3 / 3; } .dfsoccer-player-pos-m3 { grid-area: 2 / 3 / 3 / 4; } .dfsoccer-player-pos-m4 { grid-area: 2 / 4 / 3 / 5; } .dfsoccer-player-pos-m5 { grid-area: 2 / 5 / 3 / 6; } .dfsoccer-player-pos-f1 { grid-area: 1 / 2 / 2 / 3; } .dfsoccer-player-pos-f2 { grid-area: 1 / 3 / 2 / 4; } .dfsoccer-player-pos-f3 { grid-area: 1 / 4 / 2 / 5; }
        .dfsoccer-player-pos-unknown { grid-area: 1 / 1 / 2 / 2; background-color: red !important; } /* Style unknown positions */


        .dfsoccer-budget-overview { background-color: rgba(20, 83, 45, 0.6); padding: 1rem; border-radius: 8px; }
        .dfsoccer-stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 0.75rem; }
        .dfsoccer-stat-card { background-color: var(--color-bg-light); padding: 0.5rem; border-radius: 6px; text-align: center; }
        .dfsoccer-stat-label { display: block; font-size: 0.65rem; color: var(--color-text-secondary); margin-bottom: 0.2rem; text-transform: uppercase; }
        .dfsoccer-stat-value { display: block; font-size: 1rem; font-weight: bold; color: var(--color-text-primary); }
        .dfsoccer-stat-value.negative { color: var(--color-accent-negative); }
        .dfsoccer-stat-value.positive { color: var(--color-accent-positive); }

        .dfsoccer-position-summary { background-color: rgba(20, 83, 45, 0.6); padding: 0.75rem 1rem; border-radius: 8px; }
        .dfsoccer-selection-counts { display: flex; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.5rem; justify-content: center; min-height: 1.5em; }
        .dfsoccer-position-count { background-color: var(--color-bg-light); color: var(--color-text-secondary); padding: 0.25rem 0.6rem; border-radius: 1rem; font-size: 0.75rem; font-weight: 500; display: inline-flex; align-items: center; gap: 0.3rem; }
        .dfsoccer-position-count .icon-status { width: 8px; height: 8px; border-radius: 50%; background-color: var(--color-accent-negative); display: inline-block; transition: background-color 0.2s; }
        .dfsoccer-position-count.complete .icon-status { background-color: var(--color-accent-positive); }
        .dfsoccer-position-count.complete { background-color: var(--color-bg-card); color: var(--color-text-primary); }

        .dfsoccer-team-status-bar { padding: 0.6rem 1rem; border-radius: 8px; text-align: center; font-weight: 600; font-size: 0.85rem; margin-top: 0.5rem; transition: background-color 0.3s, color 0.3s; }
        .dfsoccer-team-status-bar.incomplete { background-color: var(--color-accent-yellow-dark); color: var(--color-text-primary); }
        .dfsoccer-team-status-bar.complete { background-color: var(--color-accent-positive); color: var(--color-bg-deep); }
        .dfsoccer-team-status-bar.overbudget { background-color: var(--color-accent-negative); color: var(--color-text-primary); }

        /* Player Pool Components */
        .dfsoccer-player-pool__header { padding: 0.75rem 1rem; background-color: var(--color-bg-medium); border-bottom: 1px solid var(--color-border-light); z-index: 10; flex-shrink: 0; }
        .dfsoccer-search-filter-bar { display: flex; flex-wrap: wrap; gap: 0.5rem; align-items: center; }
        .dfsoccer-search-input-container { flex-grow: 1; position: relative; min-width: 150px; }
        .dfsoccer-search-input { width: 100%; padding: 0.5rem 0.7rem 0.5rem 2.2rem; border-radius: 6px; border: 1px solid var(--color-border-light); background-color: var(--color-bg-light); color: var(--color-text-primary); font-size: 0.85rem; }
        .dfsoccer-search-input::placeholder { color: var(--color-text-secondary); opacity: 0.8; }
        .dfsoccer-search-icon { position: absolute; left: 0.6rem; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; stroke: var(--color-text-secondary); stroke-width: 2; pointer-events: none; }
        .dfsoccer-filter-buttons { display: flex; gap: 0.4rem; align-items: center; flex-wrap: wrap; justify-content: center; }
        .dfsoccer-filter-btn { padding: 0.4rem 0.7rem; border-radius: 6px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.75rem; font-weight: 500; border: 1px solid transparent; transition: all 0.2s ease; }
        .dfsoccer-filter-btn:hover { background-color: var(--color-bg-card); color: var(--color-text-primary); }
        .dfsoccer-filter-btn.active { background-color: var(--color-accent-yellow); color: var(--color-bg-deep); border-color: var(--color-accent-yellow-dark); font-weight: 600; }
        .dfsoccer-filter-btn svg { width: 14px; height: 14px; fill: currentColor; vertical-align: middle; margin-left: 0.3rem; }
        .dfsoccer-filter-btn.more-filters { padding: 0.4rem; }
        .dfsoccer-filter-btn.more-filters svg { margin-left: 0; }

        .dfsoccer-player-list__header { display: grid; grid-template-columns: 3fr 1fr 1.5fr 1.5fr 0.5fr; gap: 0.75rem; padding: 0.5rem 1rem; background-color: var(--color-bg-light); color: var(--color-text-accent); font-size: 0.7rem; font-weight: 600; text-transform: uppercase; z-index: 9; border-bottom: 1px solid var(--color-border-medium); flex-shrink: 0; }
        .dfsoccer-player-list__header > div:nth-child(3), .dfsoccer-player-list__header > div:nth-child(4) { text-align: right; }
        .dfsoccer-player-list__header > div:nth-child(4) { display: block; }
        .dfsoccer-player-list__header > div:nth-child(5) { text-align: center; }

        .dfsoccer-player-list__scrollable { flex-grow: 1; overflow-y: auto; padding: 0 1rem; min-height: 200px; }
        .dfsoccer-player-list__item { display: grid; grid-template-columns: 3fr 1fr 1.5fr 1.5fr 0.5fr; gap: 0.75rem; align-items: center; padding: 0.6rem 0; border-bottom: 1px solid var(--color-border-light); font-size: 0.85rem; cursor: pointer; transition: background-color 0.15s ease, opacity 0.2s ease; position: relative; }
        .dfsoccer-player-list__item:hover:not(.disabled-selection) { background-color: rgba(22, 101, 52, 0.4); }
        .dfsoccer-player-list__item.selected { background-color: var(--color-bg-card); font-weight: 500; }
        .dfsoccer-player-list__item.disabled-selection { opacity: 0.5; cursor: not-allowed; background-color: transparent !important; }
        .dfsoccer-player-list__item.hidden-by-filter { display: none; }
        .dfsoccer-player-name { font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dfsoccer-player-position-cell { color: var(--color-text-accent); font-weight: 500; text-transform: uppercase; }
        .dfsoccer-player-price, .dfsoccer-player-club { text-align: right; color: var(--color-text-secondary); }
        .dfsoccer-player-price { font-weight: 600; color: var(--color-text-primary); }
        .dfsoccer-player-club { display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .dfsoccer-player-select-indicator { text-align: center; }
        .dfsoccer-player-select-indicator svg { width: 16px; height: 16px; stroke-width: 2.5; color: var(--color-accent-positive); visibility: hidden; margin: 0 auto; }
        .dfsoccer-player-list__item.selected .dfsoccer-player-select-indicator svg { visibility: visible; }
        /* Hidden Checkbox */
        .dfsoccer-player-list__item input[type="checkbox"] { position: absolute; opacity: 0; width: 0; height: 0; pointer-events: none; }

        /* Pagination */
        .dfsoccer-pagination { text-align: center; padding: 0.75rem 1rem; background-color: var(--color-bg-medium); border-top: 1px solid var(--color-border-light); margin: 0 -1rem; flex-shrink: 0; }
        .dfsoccer-pagination button { padding: 0.4rem 0.8rem; margin: 0 0.2rem; border-radius: 4px; background-color: var(--color-bg-light); color: var(--color-text-secondary); font-size: 0.8rem; font-weight: 500; border: 1px solid var(--color-border-medium); transition: background-color 0.2s ease; }
        .dfsoccer-pagination button:hover:not(:disabled) { background-color: var(--color-bg-card); color: var(--color-text-primary); }
        .dfsoccer-pagination button:disabled { background-color: var(--color-accent-yellow); color: var(--color-bg-deep); cursor: default; opacity: 1; font-weight: 600; border-color: var(--color-accent-yellow-dark); }

        /* Footer Components */
        .dfsoccer-save-button { padding: 0.6rem 1.5rem; background-color: var(--color-accent-yellow); color: var(--color-bg-deep); font-weight: bold; font-size: 0.9rem; border-radius: 6px; transition: background-color 0.2s ease, opacity 0.2s ease; }
        .dfsoccer-save-button:hover:not(:disabled) { background-color: var(--color-accent-yellow-dark); }
        .dfsoccer-save-button:disabled { opacity: 0.6; cursor: not-allowed; background-color: var(--color-bg-light); }

        /* Modal Styles */
        .dfsoccer-modal { position: fixed; inset: 0; background-color: var(--color-modal-overlay); display: flex; align-items: center; justify-content: center; z-index: 10000; opacity: 0; visibility: hidden; transition: opacity 0.3s ease, visibility 0s linear 0.3s; padding: 1rem; }
        .dfsoccer-modal.is-visible { opacity: 1; visibility: visible; transition-delay: 0s; }
        .dfsoccer-modal-content { background-color: var(--color-bg-medium); color: var(--color-text-primary); padding: 1.5rem; border-radius: 8px; max-width: 95%; width: 800px; max-height: 90vh; overflow-y: auto; position: relative; z-index: 10001; border: 1px solid var(--color-border-light); box-shadow: 0 5px 20px rgba(0,0,0,0.4); }
        .dfsoccer-modal-close { position: absolute; top: 8px; right: 10px; background: none; border: none; font-size: 1.8rem; color: var(--color-text-secondary); cursor: pointer; line-height: 1; padding: 0.25rem; }
        .dfsoccer-modal-close:hover { color: var(--color-text-primary); }
        .dfsoccer-modal-content h2 { font-size: 1.2rem; margin-bottom: 1rem; color: var(--color-text-accent); border-bottom: 1px solid var(--color-border-light); padding-bottom: 0.5rem; }
        .dfsoccer-modal-content table { font-size: 0.85rem; width: 100%; border-collapse: collapse; margin-top: 1rem; }
        .dfsoccer-modal-content th, .dfsoccer-modal-content td { padding: 0.5rem; border: 1px solid var(--color-border-light); text-align: left;}
        .dfsoccer-modal-content th { background-color: var(--color-bg-light); font-weight: 600; }
        .dfsoccer-modal-content iframe { width: 100%; min-height: 70vh; border: none; }
        /* More Filters Modal Specific Styles */
        .dfsoccer-filter-options { display: flex; flex-direction: column; gap: 0.75rem; }
        .dfsoccer-filter-options h4 { margin: 0.5rem 0 0.25rem 0; color: var(--color-text-accent); font-size: 0.9rem; }
        .dfsoccer-club-filter-select, .dfsoccer-price-filter-input { width: 100%; padding: 0.5rem 0.7rem; border-radius: 6px; border: 1px solid var(--color-border-light); background-color: var(--color-bg-light); color: var(--color-text-primary); font-size: 0.85rem; }
        .dfsoccer-apply-more-filters-btn { padding: 0.6rem 1.2rem; background-color: var(--color-accent-yellow); color: var(--color-bg-deep); font-weight: bold; font-size: 0.9rem; border-radius: 6px; transition: background-color 0.2s ease; align-self: flex-start; }
        .dfsoccer-apply-more-filters-btn:hover { background-color: var(--color-accent-yellow-dark); }


        /* Responsive Breakpoints */
        @media (max-width: 1024px) {
            .dfsoccer-main-content { flex-direction: column; height: auto; overflow-y: visible; overflow-x: hidden; }
            .dfsoccer-team-view { flex: 1 1 auto; max-width: none; border-right: none; border-bottom: 1px solid var(--color-border-light); height: auto; overflow-y: visible; padding-bottom: 1.5rem; }
            .dfsoccer-player-pool { flex: 1 1 auto; height: auto; min-height: 400px; overflow: visible; }
            .dfsoccer-player-list__scrollable { overflow-y: visible; max-height: none; }
            .dfsoccer-soccer-field { height: 300px; }
            .dfsoccer-player-jersey-shape { width: 30px; height: 35px; }
            .dfsoccer-player-spot-name { font-size: 7px; }
            .dfsoccer-player-spot-price { font-size: 6px; }
            .dfsoccer-stats-grid { grid-template-columns: repeat(auto-fit, minmax(100px, 1fr)); }
        }
        @media (max-width: 767px) {
            .dfsoccer-app-header { padding: 0.5rem; gap: 0.5rem; min-height: 0; }
            .dfsoccer-fixture-countdown { order: 1; width: 100%; justify-content: center; margin-bottom: 0.5rem; font-size: 0.7rem; padding: 0.3rem 0.6rem; }
            .dfsoccer-countdown-units { gap: 0.2rem;}
            .dfsoccer-countdown-unit { padding: 0.1rem 0.3rem; }
            .dfsoccer-fixture-info-compact { order: 2; width: 100%; text-align: center; margin-bottom: 0.5rem; font-size: 0.7rem; white-space: normal; }
            .dfsoccer-app-nav { order: 3; width: 100%; justify-content: center; gap: 0.25rem; }
            .dfsoccer-nav-button { padding: 0.5rem; gap: 0; }
            .dfsoccer-nav-button .button-text { display: none; }
            .dfsoccer-nav-button svg { width: 18px; height: 18px; }
            .dfsoccer-team-view { padding: 0.75rem; }
            .dfsoccer-player-pool { min-height: 300px; }
            .dfsoccer-soccer-field { height: 250px; }
            .dfsoccer-player-pool__header { padding: 0.5rem 0.75rem; }
            .dfsoccer-search-filter-bar { flex-direction: column; align-items: stretch; }
            .dfsoccer-filter-buttons { justify-content: space-around; width: 100%; margin-top: 0.5rem; }
            .dfsoccer-player-list__header { padding: 0.4rem 0.75rem; grid-template-columns: 3fr 1fr 1.5fr 0.5fr; gap: 0.5rem; font-size: 0.65rem; }
            .dfsoccer-player-list__header > div:nth-child(4) { display: none; } /* Hide Club Header */
            .dfsoccer-player-list__item { padding: 0.5rem 0; grid-template-columns: 3fr 1fr 1.5fr 0.5fr; gap: 0.5rem; font-size: 0.8rem; }
            .dfsoccer-player-club { display: none; } /* Hide Club column */
            .dfsoccer-save-button { width: 100%; text-align: center; font-size: 1rem; padding: 0.8rem; }
            .dfsoccer-app-footer { padding: 0.5rem; height: auto; min-height: var(--footer-height); }
            .dfsoccer-modal-content { padding: 1rem; max-width: 100%; width: 100%; height: 95vh; max-height: 95vh;}
            .dfsoccer-modal-content h2 { font-size: 1.1rem; }
             .dfsoccer-pagination { margin: 0 -0.75rem; } /* Adjust negative margin */
            .dfsoccer-pagination button { padding: 0.3rem 0.6rem; font-size: 0.75rem;}
        }
        /* Add any other specific styles needed */

        /* Style for iframe container in scorecard modal */
        .dfpro-scorecard-iframe-container {
            width: 100%;
            height: 75vh; /* Adjust height as needed */
            overflow: hidden; /* Hide scrollbars if iframe fits */
            border: 1px solid var(--color-border-medium);
            border-radius: 6px;
        }
         .dfpro-scorecard-iframe-container iframe {
             width: 100%;
             height: 100%;
             border: none;
         }


        /* --- PASTE ALL EXISTING CSS RULES HERE --- */
        /* ... (Your extensive CSS) ... */

        /* --- ADD/UPDATE Pitch Position Rules --- */
        /* Ensure all needed slots have grid-area defined */
        .dfsoccer-player-pos-gk { grid-area: 4 / 3 / 5 / 4; }
        .dfsoccer-player-pos-d1 { grid-area: 3 / 1 / 4 / 2; }
        .dfsoccer-player-pos-d2 { grid-area: 3 / 2 / 4 / 3; }
        .dfsoccer-player-pos-d3 { grid-area: 3 / 3 / 4 / 5; }
        .dfsoccer-player-pos-d4 { grid-area: 3 / 4 / 4 / 6; } /* 4th Defender Slot */
        .dfsoccer-player-pos-m1 { grid-area: 2 / 1 / 3 / 2; }
        .dfsoccer-player-pos-m2 { grid-area: 2 / 2 / 3 / 3; }
        .dfsoccer-player-pos-m3 { grid-area: 2 / 3 / 3 / 5; } /* Adjusted Midfielder Slot */
        .dfsoccer-player-pos-m4 { grid-area: 2 / 4 / 3 / 6; } /* 4th Midfielder Slot */
        /* .dfsoccer-player-pos-m5 { grid-area: 2 / 5 / 3 / 6; }  Remove if only 4 MID needed */
        .dfsoccer-player-pos-f1 { grid-area: 1 / 2 / 2 / 2; } /* Adjusted Attacker Slot */
        .dfsoccer-player-pos-f2 { grid-area: 1 / 3 / 2 / 3; }
        .dfsoccer-player-pos-f3 { grid-area: 1 / 3 / 2 / 5; } /* Adjusted Attacker Slot */
        .dfsoccer-player-pos-f4 { grid-area: 1 / 4 / 2 / 6; } /* NEW: 4th Attacker Slot */

        .dfsoccer-player-pos-unknown { grid-area: 1 / 1 / 2 / 2; background-color: red !important; } /* Style unknown positions */


        /* ... (Rest of your CSS) ... */
         .dfpro-scorecard-iframe-container iframe {
             width: 100%;
             height: 100%;
             border: none;
         }
    </style>

    <div class="dfsoccer-app-wrapper"> <!-- Wrapper -->
        <?php // Form ID might include league_id if needed, but instance_id should be used for JS targeting ?>
        <form id="player_selection_form_<?php echo esc_attr($league_id); ?>" method="post" class="dfsoccer-app-container">
            <?php wp_nonce_field('dfsoccer_select_players_' . $league_id . '_' . $user_id, '_wpnonce_player_selection_' . $league_id); ?>
            <input type="hidden" name="league_id" value="<?php echo esc_attr($league_id); ?>">
             <?php // Optional: Pass mode if needed on server side beyond rule setting ?>
             <!-- <input type="hidden" name="selection_mode" value="<?php echo esc_attr($mode); ?>"> -->

            <!-- Notification Area -->
            <?php
            $combined_error_message = $api_error_message ? $api_error_message . ($error_message ? '<br>' . $error_message : '') : $error_message;
            ?>
            <?php if (!empty($combined_error_message) || !empty($success_message)) : ?>
            <div class="dfsoccer-notifications">
                <?php if (!empty($combined_error_message)) : ?>
                    <div class="error"><?php echo wp_kses_post($combined_error_message); ?></div>
                <?php endif; ?>
                <?php if (!empty($success_message)) : ?>
                    <div class="success"><?php echo esc_html($success_message); ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- ==================== HEADER ==================== -->
            <header class="dfsoccer-app-header">
                   <?php 
echo flash_countdown_for_dfsoccer($league_id);
 ?>

                <div class="dfsoccer-fixture-countdown">
                     <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="dfsoccer-countdown-icon" fill="currentColor"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14" fill="none" stroke="currentColor" stroke-width="2"></polyline></svg>
                    <span class="dfsoccer-countdown-label">Deadline:</span>
                    <div class="dfsoccer-countdown-units" id="fixture-countdown-units-<?php echo esc_attr($league_id); ?>">
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">D</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">H</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">M</span></div>
                        <div class="dfsoccer-countdown-unit"><span class="dfsoccer-countdown-value">--</span><span class="dfsoccer-countdown-label-sm">S</span></div>
                    </div>
                </div>
                <div class="dfsoccer-fixture-info-compact">
                    <span class="teams">Next: <strong>Team A</strong> vs <strong>Team B</strong></span>
                    <span class="time">Date, Time</span>
                </div>
                <nav class="dfsoccer-app-nav">
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#rules-modal-<?php echo esc_attr($instance_id); ?>" title="Rules"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm-1-13h2v2h-2zm0 4h2v6h-2z" fill="currentColor"/></svg><span class="button-text">Rules</span></button>
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#standings-modal-<?php echo esc_attr($instance_id); ?>" title="Standings"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M16 11V3H8v8H2v10h20V11h-6zm-6-6h4v12h-4V5zm-6 6h4v8H4v-8zm16 8h-4v-8h4v8z" fill="currentColor"/></svg><span class="button-text">Standings</span></button>
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#all-points-modal-<?php echo esc_attr($instance_id); ?>" title="Points"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zM4 18V6h2v12H4zm4 0V6h8v12H8zm10 0h-2V6h2v12z" fill="currentColor"/></svg><span class="button-text">Points</span></button>
                    <button type="button" class="dfsoccer-nav-button dfsoccer-modal-trigger" data-modal-target="#scorecard-modal-<?php echo esc_attr($instance_id); ?>" title="Scorecard"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 14H5v-2h7v2zm0-4H5v-2h7v2zm5 4h-3v-2h3v2zm0-4h-3v-2h3v2zm0-4h-3V7h3v2z" fill="currentColor"/></svg><span class="button-text">Scorecard</span></button>
                </nav>
            </header>

            <!-- ==================== MAIN CONTENT ==================== -->
            <main class="dfsoccer-main-content">
                 <!-- --- Left Column / Top Stack: Team View --- -->
                <aside class="dfsoccer-team-view">
                    <h3>Your Team <?php echo ($mode === '11' ? '(Mode: 11)' : ''); ?></h3>
                    <!-- Pitch Visualization -->
                    <div class="dfsoccer-soccer-field" id="soccer-field-<?php echo esc_attr($instance_id); ?>">
                        <div class="dfsoccer-player-positions">
                            <?php
                            // Define grid position classes with UPDATED grid-area values
                            $grid_positions = [
                                'player-pos-gk' => 'grid-area: 4 / 3 / 5 / 4;',
                                'player-pos-d1' => 'grid-area: 3 / 1 / 4 / 2;',
                                'player-pos-d2' => 'grid-area: 3 / 2 / 4 / 3;',
                                'player-pos-d3' => 'grid-area: 3 / 3 / 4 / 5;',
                                'player-pos-d4' => 'grid-area: 3 / 4 / 4 / 6;', // 4th Defender Slot
                                'player-pos-m1' => 'grid-area: 2 / 1 / 3 / 2;',
                                'player-pos-m2' => 'grid-area: 2 / 2 / 3 / 3;',
                                'player-pos-m3' => 'grid-area: 2 / 3 / 3 / 5;', // Adjusted Mid Slot
                                'player-pos-m4' => 'grid-area: 2 / 4 / 3 / 6;', // 4th Mid Slot
                                // Removed m5 as we only need 4 MID slots now
                                'player-pos-f1' => 'grid-area: 1 / 2 / 2 / 2;', // Adjusted Att Slot
                                'player-pos-f2' => 'grid-area: 1 / 3 / 2 / 3;',
                                'player-pos-f3' => 'grid-area: 1 / 3 / 2 / 5;', // Adjusted Att Slot
                                'player-pos-f4' => 'grid-area: 1 / 4 / 2 / 6;'  // 4th Att Slot
                            ];

                            // Define which specific slots to use per position (ensure enough slots)
                            $pitch_positions_map = [
                                'goalkeeper' => ['player-pos-gk'],
                                'defender'   => ['player-pos-d1', 'player-pos-d2', 'player-pos-d3', 'player-pos-d4'],
                                'midfielder' => ['player-pos-m1', 'player-pos-m2', 'player-pos-m3', 'player-pos-m4'],
                                'attacker'   => ['player-pos-f1', 'player-pos-f2', 'player-pos-f3', 'player-pos-f4']
                            ];

                            // Generate placeholders dynamically based on mode-adjusted $js_position_limits_output
                            foreach ($js_position_limits_output as $pos => $limit) {
                                $css_classes = $pitch_positions_map[$pos] ?? [];
                                for ($i = 0; $i < $limit; $i++) {
                                    // Use the defined slot class or fallback if map is incomplete
                                    $slot_class = $css_classes[$i] ?? 'player-pos-unknown';
                                    // Get the style from the grid_positions map
                                    $grid_style = $grid_positions[$slot_class] ?? ''; // Default to no style if class not found
                                    if (empty($grid_style)) {
                                         error_log("DFSoccer Warning: No grid style defined for pitch slot class: " . $slot_class);
                                    }

                                    echo '<div class="dfsoccer-player-spot ' . esc_attr($slot_class) . '" data-position-slot="' . esc_attr($pos . '_' . $i) . '" style="' . esc_attr($grid_style) . '">';
                                    echo '<div class="dfsoccer-player-jersey-shape"></div>';
                                    echo '<div class="dfsoccer-player-details"><span class="dfsoccer-player-spot-name"></span><span class="dfsoccer-player-spot-price"></span></div>';
                                    echo '</div>';
                                }
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Budget Overview -->
                    <div class="dfsoccer-budget-overview">
                         <div class="dfsoccer-stats-grid">
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Budget</span><span class="dfsoccer-stat-value" id="budget-value-<?php echo esc_attr($instance_id); ?>">$<?php echo number_format($budget, 0); ?></span></div>
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Price</span><span class="dfsoccer-stat-value" id="current-price-<?php echo esc_attr($instance_id); ?>">$0</span></div>
                            <div class="dfsoccer-stat-card"><span class="dfsoccer-stat-label">Left</span><span class="dfsoccer-stat-value positive" id="budget-left-<?php echo esc_attr($instance_id); ?>">$<?php echo number_format($budget, 0); ?></span></div>
                        </div>
                    </div>

                    <!-- Position Summary -->
                    <div class="dfsoccer-position-summary">
                         <div class="dfsoccer-selection-counts" id="selection-counts-<?php echo esc_attr($instance_id); ?>">
                             <!-- JS Populated -->
                         </div>
                    </div>

                    <!-- Team Status -->
                    <div class="dfsoccer-team-status-bar incomplete" id="team-status-bar-<?php echo esc_attr($instance_id); ?>">
                        0/<?php echo esc_html($required_player_count); ?> Players Selected
                    </div>
                </aside>

                <!-- --- Right Column / Bottom Stack: Player Pool --- -->
                <section class="dfsoccer-player-pool">
                     <!-- Player Pool Header (Search/Filter) - Mode Independent -->
                     <div class="dfsoccer-player-pool__header">
                        <div class="dfsoccer-search-filter-bar">
                            <div class="dfsoccer-search-input-container">
                                <input type="text" placeholder="Search players..." class="dfsoccer-search-input" id="player-search-<?php echo esc_attr($instance_id); ?>">
                                <svg xmlns="http://www.w3.org/2000/svg" class="dfsoccer-search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            </div>
                            <div class="dfsoccer-filter-buttons">
                                <button type="button" class="dfsoccer-filter-btn active" data-filter="all">All</button>
                                <?php foreach ($all_positions_found as $pos_key): // Use positions found in API data ?>
                                    <button type="button" class="dfsoccer-filter-btn" data-filter="<?php echo esc_attr($pos_key); ?>">
                                        <?php echo esc_html( ($pos_key === 'goalkeeper') ? 'GK' : strtoupper(substr($pos_key, 0, 3)) ); ?>
                                    </button>
                                <?php endforeach; ?>
                                <button type="button" class="dfsoccer-filter-btn more-filters dfsoccer-modal-trigger" data-modal-target="#more-filters-modal-<?php echo esc_attr($instance_id); ?>" title="More Filters">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Player List Header -->
                    <div class="dfsoccer-player-list__header">
                        <div>Player</div><div>Pos</div><div>Price</div><div>Club</div><div>Sel</div>
                    </div>

                    <!-- Scrollable Player List -->
                    <div class="dfsoccer-player-list__scrollable" id="player-list-container-<?php echo esc_attr($instance_id); ?>">
                         <?php if (!empty($api_players)): ?>
                            <?php foreach ($api_players as $player): ?>
                                <?php
                                    $is_selected = in_array($player['id'], $saved_player_ids);
                                    $selected_class = $is_selected ? 'selected' : '';
                                    $pos_short = ($player['position'] === 'goalkeeper') ? 'GK' : strtoupper(substr($player['position'], 0, 3));
                                    // Use unique ID for checkbox including instance
                                    $checkbox_id = 'player-checkbox-' . esc_attr($player['id'] . '_' . $instance_id);
                                ?>
                                <div class="dfsoccer-player-list__item <?php echo $selected_class; ?>"
                                     data-player-id="<?php echo esc_attr($player['id']); ?>"
                                     data-player-name="<?php echo esc_attr($player['name']); ?>"
                                     data-position="<?php echo esc_attr($player['position']); ?>"
                                     data-price="<?php echo esc_attr($player['price']); ?>"
                                     data-club-id="<?php echo esc_attr($player['club_id']); ?>"
                                     data-club-name="<?php echo esc_attr($player['club_name']); ?>"
                                     tabindex="0" role="button" aria-pressed="<?php echo $is_selected ? 'true' : 'false'; ?>">

                                    <input type="checkbox" name="selected_players[]" value="<?php echo esc_attr($player['id']); ?>" <?php checked($is_selected); ?> id="<?php echo $checkbox_id; ?>" class="dfsoccer-hidden-player-checkbox">

                                    <div class="dfsoccer-player-name"><?php echo esc_html($player['name']); ?></div>
                                    <div class="dfsoccer-player-position-cell"><?php echo esc_html($pos_short); ?></div>
                                    <div class="dfsoccer-player-price">$<?php echo esc_html(number_format($player['price'], 0)); ?></div>
                                    <div class="dfsoccer-player-club"><?php echo esc_html($player['club_name']); ?></div>
                                    <div class="dfsoccer-player-select-indicator">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php elseif (empty($api_error_message)) : ?>
                            <p style="padding: 1rem;">No players available.</p>
                         <?php else: // Show API error if players couldn't be fetched ?>
                             <p style="padding: 1rem; color: var(--color-accent-negative);"><?php echo esc_html($api_error_message); ?></p>
                         <?php endif; ?>
                    </div> <!-- End Scrollable List -->

                    <!-- Pagination Controls -->
                    <div class="dfsoccer-pagination" id="pagination-<?php echo esc_attr($instance_id); ?>"></div>

                </section> <!-- End Player Pool -->
            </main> <!-- End Main Content -->

            <!-- ==================== FOOTER / ACTION BAR ==================== -->
            <footer class="dfsoccer-app-footer">
                 <button type="submit" name="<?php echo esc_attr($submit_button_name); ?>" class="dfsoccer-save-button" id="save-button-<?php echo esc_attr($instance_id); ?>" disabled>Save Selection</button>
            </footer>

        </form> <!-- End Form -->
    </div> <!-- End Wrapper -->


     <!-- ==================== MODALS (Outside Form) ==================== -->
     <?php // Modals use $instance_id for uniqueness ?>
    <div id="rules-modal-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="rules-modal-title-<?php echo esc_attr($instance_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
            <h2 id="rules-modal-title-<?php echo esc_attr($instance_id); ?>">League Rules & Fixtures</h2>

			 
			 
			 
					            <?php
            echo display_fixtures_only($league_id);
            ?>
		
        <p>Fantasy Points Scoring System
        </p>
        <style>
        h3 {
            color: white !important;
        }
        .goalkeeper-cell {
            color: white !important;
        }
        </style>


<?php
// Get league-specific rules if they exist
$points_rules = get_post_meta($league_id, 'dfsoccer_points_rules', true);

// Default rules
$default_rules = [
    'goalkeeper' => [
        'goals' => 10,
        'own' => -7,
        'assists' => 7,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -2,
        'penalties' => 8,
        'missed' => -4
    ],
    'defender' => [
        'goals' => 7,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -2,
        'penalties' => 8,
        'missed' => -4
    ],
    'midfielder' => [
        'goals' => 6,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => -1,
        'penalties' => 8,
        'missed' => -4
    ],
    'attacker' => [
        'goals' => 5,
        'own' => -7,
        'assists' => 5,
        'minutes' => 0.02,
        'red' => -4,
        'yellow' => -1,
        'conceded' => 0,
        'penalties' => 8,
        'missed' => -4
    ]
];

// Use league-specific rules if set, otherwise use default rules
$rules = $points_rules ?: $default_rules;

// Helper function to format rule names
$formatRuleName = function($name) {
    $names = [
        'goals' => 'Goals Scored',
        'own' => 'Own Goals',
        'assists' => 'Assists',
        'minutes' => 'Minutes Played',
        'red' => 'Red Cards',
        'yellow' => 'Yellow Cards',
        'conceded' => 'Goals Conceded',
        'penalties' => 'Penalties Saved',
        'missed' => 'Penalties Missed'
    ];
    return $names[$name] ?? ucfirst($name);
};

// Display rules for each position
foreach ($rules as $position => $position_rules) : ?>
    <h3><?php echo ucfirst($position); ?></h3>
    <table class="league-rules-table">
        <tr>
            <th>Action</th>
            <th>Points</th>
        </tr>
        <?php foreach ($position_rules as $rule => $points) : 
            $valueClass = $points > 0 ? 'positive' : ($points < 0 ? 'negative' : '');
            $formattedPoints = $points;
            
            if ($rule === 'minutes') {
                $formattedPoints = $points . ' per minute';
            } else {
                $formattedPoints = ($points > 0 ? '+' : '') . $points;
            }
        ?>
            <tr>
                <td><?php echo $formatRuleName($rule); ?></td>
                <td class="rule-value <?php echo $valueClass; ?>"><?php echo $formattedPoints; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
<?php endforeach; ?>

<!-- Clean sheet bonus information -->
<h3 class="goalkeeper-cell">Clean Sheet Bonus Points (60+ minutes played)</h3>
<table class="league-rules-table">
    <tr>
        <th>Position</th>
        <th>Points</th>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Goalkeeper</td>
        <td class="rule-value positive">+5</td>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Defender</td>
        <td class="rule-value positive">+5</td>
    </tr>
    <tr>
        <td class="goalkeeper-cell">Midfielder</td>
        <td class="rule-value positive">+3</td>
    </tr>
</table>
            <?php
            // Example: Fetch rules from league meta
            // $rules_content = get_post_meta($league_id, 'dfsoccer_league_rules', true);
            // echo wp_kses_post(wpautop($rules_content)); // Make sure to sanitize and format

            ?>
        </div>
    </div>

			 
			 
        </div>
    </div>
    <div id="standings-modal-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content"><button class="dfsoccer-modal-close" data-modal-close>×</button><h2>League Standings</h2><p>Standings for <?php echo esc_html($league_id); ?>.</p>
		
		
						<?php
echo do_shortcode('[display_api_points league_id="' . $league_id . '"]');
?>



		
		</div>
    </div>
    <div id="all-points-modal-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content"><button class="dfsoccer-modal-close" data-modal-close>×</button><h2>All Player Points</h2><p>Points for <?php echo esc_html($league_id); ?>.</p>
			
			<?php echo dfsoccer_display_sorted_players($league_id); ?>

</div>
    </div>
    <div id="scorecard-modal-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content"><button class="dfsoccer-modal-close" data-modal-close>×</button><h2>Scorecard</h2>
			
		<?php
// Get the post author (league admin)
$post = get_post($league_id);
$post_author_id = $post ? $post->post_author : 0;

// Get current user
$current_user_id = get_current_user_id();

// Check if current user is the league admin
if ($current_user_id == $post_author_id) {
    // User is the league admin - show the scorecard
    echo do_shortcode('[dfproscorecard league_id="' . $league_id . '"]');
} else {
    // User is not the league admin - show restriction message
    echo '<div class="league-admin-restriction" style="margin-top: 30px; padding: 15px; background-color: #f8f8f8; border-left: 4px solid #e74c3c; color: #333;">';
echo '<h3 style="margin-top: 0 !important; color: #e74c3c !important;">Restricted Access</h3>';
    echo '<p>Only the league admin can see and use this page. If you need to submit results, please contact the league administrator.</p>';
    echo '</div>';
}
?>
		</div>
    </div>
    <div id="more-filters-modal-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-modal" aria-hidden="true">
        <div class="dfsoccer-modal-overlay" data-modal-close></div>
        <div class="dfsoccer-modal-content" role="dialog" aria-modal="true" aria-labelledby="more-filters-modal-title-<?php echo esc_attr($instance_id); ?>">
            <button class="dfsoccer-modal-close" aria-label="Close Modal" data-modal-close>×</button>
             <h2 id="more-filters-modal-title-<?php echo esc_attr($instance_id); ?>">More Filters</h2>
             <div class="dfsoccer-filter-options">
                 <h4>Filter by Club:</h4>
                 <select id="club-filter-<?php echo esc_attr($instance_id); ?>" class="dfsoccer-club-filter-select">
                     <option value="">All Clubs</option>
                     <?php if (!empty($club_data)): foreach ($club_data as $c_id => $c_name): ?>
                         <option value="<?php echo esc_attr($c_id); ?>"><?php echo esc_html($c_name); ?></option>
                     <?php endforeach; endif; ?>
                 </select>
                 <h4 style="margin-top: 1rem;">Filter by Max Price:</h4>
                 <input type="number" id="price-filter-<?php echo esc_attr($instance_id); ?>" placeholder="Enter max price" min="0" step="any" class="dfsoccer-price-filter-input">
                 <button type="button" style="margin-top: 1rem;" class="dfsoccer-apply-more-filters-btn" data-modal-close>Apply & Close</button>
             </div>
        </div>
    </div>

    <!-- ==================== JAVASCRIPT ==================== -->
    <script>
    // Wrap in IIFE
    (function() {
        document.addEventListener('DOMContentLoaded', function() {
            // --- Configuration (Injected from PHP, now mode-aware) ---
            const leagueId = <?php echo json_encode($league_id); ?>;
            const instanceId = <?php echo json_encode($instance_id); ?>;
            const budget = <?php echo json_encode($budget); ?>;
            const requiredPlayerCount = <?php echo json_encode($required_player_count); ?>; // Correct count based on mode
            const positionLimits = <?php echo json_encode($js_position_limits_output); ?>; // Correct limits based on mode
            const itemsPerPage = 15;
            const currentMode = <?php echo json_encode($mode); ?>; // Pass mode if needed in JS logic

            // --- DOM Element References ---
            // Use instanceId for selectors to ensure uniqueness
            const container = document.querySelector(`.dfsoccer-app-container form#player_selection_form_${leagueId}`); // Adjust selector if needed
             const formElement = document.getElementById(`player_selection_form_${leagueId}`); // More specific form targeting
             if (!formElement) {
                 console.error(`DFSoccer API v2: Form element not found for instance ${instanceId}.`);
                 return;
             }

            const searchInput = formElement.querySelector(`#player-search-${instanceId}`);
            const positionFilterBtns = formElement.querySelectorAll('.dfsoccer-filter-btn[data-filter]');
            const playerListContainer = formElement.querySelector(`#player-list-container-${instanceId}`);
            const paginationContainer = formElement.querySelector(`#pagination-${instanceId}`);
            const saveButton = formElement.querySelector(`#save-button-${instanceId}`);
            const budgetValueEl = formElement.querySelector(`#budget-value-${instanceId}`);
            const currentPriceEl = formElement.querySelector(`#current-price-${instanceId}`);
            const budgetLeftEl = formElement.querySelector(`#budget-left-${instanceId}`);
            const teamStatusBarEl = formElement.querySelector(`#team-status-bar-${instanceId}`);
            const selectionCountsContainer = formElement.querySelector(`#selection-counts-${instanceId}`);
            const pitchPositionsContainer = formElement.querySelector(`#soccer-field-${instanceId} .dfsoccer-player-positions`);

            // Modal Filter Elements (scoped outside form, use document)
            const moreFiltersModal = document.getElementById(`more-filters-modal-${instanceId}`);
            const clubFilterSelect = moreFiltersModal?.querySelector(`#club-filter-${instanceId}`);
            const priceFilterInput = moreFiltersModal?.querySelector(`#price-filter-${instanceId}`);
            const applyFiltersBtn = moreFiltersModal?.querySelector(`.dfsoccer-apply-more-filters-btn`);


            // Check essential elements
            if (!searchInput || !playerListContainer || !paginationContainer || !saveButton || !budgetValueEl || !currentPriceEl || !budgetLeftEl || !teamStatusBarEl || !selectionCountsContainer || !pitchPositionsContainer) {
                 console.error(`DFSoccer API v2: Essential UI elements missing for instance ${instanceId}.`);
                 // return; // Might allow partial functionality if some elements missing
            }

            // --- State Variables ---
            let currentPage = 1;
            let currentFilter = { search: '', position: 'all', club: '', maxPrice: '' };
            const allPlayerItems = Array.from(playerListContainer?.querySelectorAll('.dfsoccer-player-list__item') || []);
            let filteredPlayerItems = [...allPlayerItems];

            // --- Core Functions (Should work with mode-adjusted config) ---

            function updateDisplayedPlayers() {
                 if (!playerListContainer) return;
                const searchTerm = currentFilter.search.toLowerCase().trim();
                const selectedPosition = currentFilter.position;
                const selectedClub = currentFilter.club;
                 const maxPrice = currentFilter.maxPrice !== '' && !isNaN(parseFloat(currentFilter.maxPrice))
                                 ? parseFloat(currentFilter.maxPrice) : Infinity;

                filteredPlayerItems = allPlayerItems.filter(item => {
                     if (!item.dataset.playerName || typeof item.dataset.position === 'undefined' || typeof item.dataset.clubId === 'undefined' || typeof item.dataset.price === 'undefined') {
                         return false;
                     }
                     const playerName = item.dataset.playerName.toLowerCase();
                     const playerPosition = item.dataset.position;
                     const playerClub = item.dataset.clubId;
                     const playerPrice = parseFloat(item.dataset.price);

                     const matchesSearch = !searchTerm || playerName.includes(searchTerm);
                     const matchesPosition = selectedPosition === 'all' || playerPosition === selectedPosition;
                     const matchesClub = !selectedClub || playerClub === selectedClub;
                     const matchesPrice = !isNaN(playerPrice) && playerPrice <= maxPrice;

                     const match = [matchesSearch, matchesPosition, matchesClub, matchesPrice].every(Boolean);
                     item.classList.toggle('hidden-by-filter', !match);
                     return match;
                });

                currentPage = 1;
                renderPagination();
                showPage(currentPage);
            }

            function showPage(page) {
                if (!playerListContainer) return;
                 const startIndex = (page - 1) * itemsPerPage;
                 const endIndex = startIndex + itemsPerPage;

                 allPlayerItems.forEach(item => item.style.display = 'none'); // Hide all

                 const itemsToShow = filteredPlayerItems.slice(startIndex, endIndex);
                 itemsToShow.forEach(item => {
                      if (!item.classList.contains('hidden-by-filter')) {
                          item.style.display = 'grid'; // Show items for the current page
                      }
                 });

                 // Adjust if current page becomes empty after filtering/paging
                  if (itemsToShow.length === 0 && filteredPlayerItems.length > 0 && currentPage > 1) {
                       currentPage = Math.max(1, Math.ceil(filteredPlayerItems.length / itemsPerPage));
                       showPage(currentPage); // Re-render with the adjusted last page
                       return; // Prevent further updates on this call
                  }

                 updatePaginationButtons(page);
                 playerListContainer.scrollTop = 0; // Scroll to top of list
            }

             function renderPagination() {
                if (!paginationContainer) return;
                paginationContainer.innerHTML = '';
                const totalPages = Math.ceil(filteredPlayerItems.length / itemsPerPage);

                if (totalPages > 1) {
                    for (let i = 1; i <= totalPages; i++) {
                        const pageButton = document.createElement('button');
                        pageButton.textContent = i;
                        pageButton.type = 'button'; // Important: prevent form submission
                        pageButton.setAttribute('aria-label', `Go to page ${i}`);
                        pageButton.addEventListener('click', (e) => {
                            e.preventDefault(); // Prevent potential form submission if button inside form
                            currentPage = i;
                            showPage(currentPage);
                        });
                        paginationContainer.appendChild(pageButton);
                    }
                    paginationContainer.style.display = ''; // Show pagination
                    updatePaginationButtons(currentPage);
                } else {
                    paginationContainer.style.display = 'none'; // Hide if only one page or less
                }
            }

             function updatePaginationButtons(activePage) {
                 if (!paginationContainer) return;
                 const buttons = paginationContainer.querySelectorAll('button');
                 buttons.forEach((button, index) => {
                     const pageNum = index + 1;
                     button.disabled = (pageNum === activePage);
                     button.setAttribute('aria-current', pageNum === activePage ? 'page' : null);
                 });
            }

            function updateTeamState() {
                if (!playerListContainer || !currentPriceEl || !budgetLeftEl || !selectionCountsContainer || !teamStatusBarEl || !saveButton) return; // Need core elements

                const selectedItems = playerListContainer.querySelectorAll('.dfsoccer-player-list__item.selected');
                const selectedCount = selectedItems.length;
                let currentTotalCost = 0;
                const positionCounts = { goalkeeper: 0, defender: 0, midfielder: 0, attacker: 0, unknown: 0 };
                const selectedPlayerData = [];

                selectedItems.forEach(item => {
                    const price = parseFloat(item.dataset.price);
                    currentTotalCost += isNaN(price) ? 0 : price;
                    const position = item.dataset.position || 'unknown';
                    positionCounts[position] = (positionCounts[position] || 0) + 1;
                    selectedPlayerData.push({
                         id: item.dataset.playerId,
                         name: item.dataset.playerName,
                         position: position,
                         price: price
                     });
                });

                const budgetLeft = budget - currentTotalCost;

                // Update Budget UI
                currentPriceEl.textContent = `$${Math.round(currentTotalCost)}`; // Use Math.round for display
                budgetLeftEl.textContent = `$${Math.round(budgetLeft)}`;
                currentPriceEl.classList.toggle('negative', currentTotalCost > budget);
                currentPriceEl.classList.toggle('positive', currentTotalCost <= budget);
                budgetLeftEl.classList.toggle('negative', budgetLeft < 0);
                budgetLeftEl.classList.toggle('positive', budgetLeft >= 0);

                // Update Position Counts UI
                selectionCountsContainer.innerHTML = '';
                let allPositionLimitsOk = true;
                // Use the mode-aware positionLimits from PHP
                for (const [pos, limit] of Object.entries(positionLimits)) {
                    if (limit === 0) continue; // Skip positions not allowed in this mode
                    const count = positionCounts[pos] || 0;
                    const posShort = pos === 'goalkeeper' ? 'GK' : (pos.substring(0, 3) || 'UNK').toUpperCase();
                    // Mode 11 might imply min=max, standard mode might have min != max
                    // For JS display, we mainly care about hitting the *limit*
                    const isComplete = count >= limit; // Reaching the max limit shown visually
                    const isOverLimit = count > limit;

                    const countEl = document.createElement('div');
                     // Use standard class names, JS logic handles the numbers
                    countEl.className = `dfsoccer-position-count dfsoccer-position-count-${pos} ${isComplete ? 'complete' : ''}`;
                    // Display count / limit
                    countEl.innerHTML = `<span class="icon-status ${isOverLimit ? 'over' : ''}"></span> ${posShort}: ${count}/${limit}`;
                    selectionCountsContainer.appendChild(countEl);

                     if (isOverLimit) {
                        allPositionLimitsOk = false;
                     }
                     // Min required check is primarily for save button enablement
                }

                // Update Status Bar & Save Button
                let statusClass = 'incomplete';
                let statusText = `${selectedCount}/${requiredPlayerCount} Players Selected`;
                let isSaveDisabled = true;

                const countMet = selectedCount === requiredPlayerCount;
                const budgetOk = budgetLeft >= 0;
                // Add check for minimum requirements if they differ from max (relevant for standard mode)
                let minRequirementsMet = true;
                if (currentMode !== '11') { // Only check explicit mins if not mode 11 (where min=max assumed)
                    const minRules = <?php echo json_encode(array_map(fn($r) => $r['min_required'] ?? $r['limit'] ?? 0, $rules)); ?>;
                    for (const [pos, min] of Object.entries(minRules)) {
                        if ((positionCounts[pos] || 0) < min) {
                            minRequirementsMet = false;
                            break;
                        }
                    }
                }


                if (!budgetOk) {
                    statusClass = 'overbudget';
                    statusText = `Team Over Budget! ($${Math.round(currentTotalCost)})`;
                } else if (!allPositionLimitsOk) {
                     statusClass = 'overbudget'; // Use same style for over limit
                     statusText = 'Position limit exceeded!';
                } else if ([countMet, budgetOk, allPositionLimitsOk, minRequirementsMet].every(Boolean)) { // All conditions met
                    statusClass = 'complete';
                    statusText = 'Team Selection Complete!';
                    isSaveDisabled = false;
                } else if (selectedCount > requiredPlayerCount) {
                     statusClass = 'overbudget';
                     statusText = `Too many players! (${selectedCount}/${requiredPlayerCount})`;
                } else if (countMet && !minRequirementsMet) { // Right total, but wrong position mix
                    statusClass = 'incomplete'; // Keep as incomplete or add new status
                    statusText = `Formation incorrect (check position minimums).`;
                }
                // Default is incomplete if count < requiredPlayerCount

                teamStatusBarEl.className = `dfsoccer-team-status-bar ${statusClass}`;
                teamStatusBarEl.textContent = statusText;
                saveButton.disabled = isSaveDisabled;

                enforceSelectionLimits(selectedCount, positionCounts);
                updatePitchVisualization(selectedPlayerData);
            }

            function enforceSelectionLimits(selectedCount, positionCounts) {
                if (!playerListContainer) return;
                const maxTotalReached = selectedCount >= requiredPlayerCount;

                allPlayerItems.forEach(item => {
                     // Generate expected checkbox ID
                     const checkboxId = `player-checkbox-${item.dataset.playerId}_${instanceId}`;
                     const checkbox = formElement.querySelector(`#${checkboxId}`); // Search within form

                    const position = item.dataset.position || 'unknown';
                    const isItemSelected = item.classList.contains('selected');
                    // Use the mode-aware positionLimits
                    const positionLimit = positionLimits[position] ?? Infinity;

                    let isDisabled = false;
                    let disableReason = '';

                    if (!isItemSelected) { // Only disable non-selected items
                        if (maxTotalReached) {
                            isDisabled = true;
                            disableReason = `Maximum ${requiredPlayerCount} players allowed.`;
                        } else if (positionCounts[position] >= positionLimit) {
                             // Handle case where limit might be 0 for a position in this mode
                             if (positionLimit === 0) {
                                 isDisabled = true;
                                 disableReason = `${position} players are not allowed in this mode.`;
                             } else {
                                 isDisabled = true;
                                 disableReason = `Maximum ${positionLimit} ${position}(s) allowed.`;
                             }
                        }
                    }

                    item.classList.toggle('disabled-selection', isDisabled);
                    item.setAttribute('aria-disabled', isDisabled ? 'true' : 'false');
                    item.title = isDisabled ? disableReason : ''; // Tooltip for disabled state

                     // Also disable the hidden checkbox if found
                    if (checkbox) {
                        checkbox.disabled = isDisabled;
                    } else if(isDisabled) {
                        // console.warn(`Checkbox not found for disabled item: ${item.dataset.playerId}`);
                    }
                });
            }

            function togglePlayerSelection(playerItem) {
                 if (playerItem.classList.contains('disabled-selection') && !playerItem.classList.contains('selected')) {
                     return; // Do nothing if trying to select a disabled item
                 }

                  // Generate expected checkbox ID and find it within the form
                 const checkboxId = `player-checkbox-${playerItem.dataset.playerId}_${instanceId}`;
                 const checkbox = formElement.querySelector(`#${checkboxId}`);

                 const isSelected = playerItem.classList.toggle('selected');

                 if (checkbox) {
                     checkbox.checked = isSelected; // Sync checkbox state
                 } else {
                     console.warn(`Checkbox #${checkboxId} not found for player ${playerItem.dataset.playerId}`);
                 }

                 playerItem.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
                 updateTeamState(); // Update UI, limits, pitch etc.
             }

            function updatePitchVisualization(selectedPlayersData) {
                if (!pitchPositionsContainer) return;

                const slots = pitchPositionsContainer.querySelectorAll('.dfsoccer-player-spot[data-position-slot]');
                const filledSlots = new Set();

                // Reset all slots
                slots.forEach(slot => {
                    slot.classList.remove('filled');
                    const nameEl = slot.querySelector('.dfsoccer-player-spot-name');
                    const priceEl = slot.querySelector('.dfsoccer-player-spot-price');
                    if (nameEl) nameEl.textContent = '';
                    if (priceEl) priceEl.textContent = '';
                    slot.dataset.playerId = ''; // Clear player ID from slot
                });

                // Sort players for consistent placement (GK -> DEF -> MID -> ATT)
                selectedPlayersData.sort((a, b) => {
                    const order = { goalkeeper: 0, defender: 1, midfielder: 2, attacker: 3, unknown: 4 };
                    return (order[a.position] ?? 9) - (order[b.position] ?? 9);
                });

                 // Fill slots based on sorted players and available slots for their position
                 selectedPlayersData.forEach(player => {
                     const position = player.position;
                     if (!position || position === 'unknown') return; // Skip unknown positions

                     let slotFound = false;
                     // Find the next available slot for this player's position
                     for (let i = 0; ; i++) {
                         const slotSelector = `[data-position-slot="${position}_${i}"]`;
                         const slot = pitchPositionsContainer.querySelector(slotSelector);

                         // If no more predefined slots exist for this position, stop trying
                         if (!slot) break;

                         const slotKey = `${position}_${i}`; // Unique identifier for the slot
                         if (!filledSlots.has(slotKey)) { // Check if this specific slot is already taken
                             slot.classList.add('filled');
                             const nameEl = slot.querySelector('.dfsoccer-player-spot-name');
                             const priceEl = slot.querySelector('.dfsoccer-player-spot-price');
                             if (nameEl) nameEl.textContent = player.name ? player.name.substring(0, 10) : ''; // Shorten name
                             if (priceEl) priceEl.textContent = player.price ? `$${Math.round(player.price)}` : '';
                             slot.dataset.playerId = player.id; // Store player ID in the filled slot
                             filledSlots.add(slotKey); // Mark this slot as filled
                             slotFound = true;
                             break; // Move to the next player
                         }
                     }
                     // Optional: Log if a player couldn't be placed (shouldn't happen if limits enforced)
                     // if (!slotFound) { console.warn(`No pitch slot available for ${player.name} (${position})`); }
                 });
            }


            // --- Event Listeners Setup ---

            // Player Selection (using event delegation on the container)
            if (playerListContainer) {
                 playerListContainer.addEventListener('click', function(event) {
                     const playerItem = event.target.closest('.dfsoccer-player-list__item');
                     if (playerItem) {
                         togglePlayerSelection(playerItem);
                     }
                 });
                 playerListContainer.addEventListener('keydown', function(event) {
                     if (event.key === 'Enter' || event.key === ' ') {
                         const playerItem = event.target.closest('.dfsoccer-player-list__item');
                         if (playerItem) {
                             event.preventDefault(); // Prevent spacebar scrolling/enter submitting form
                             togglePlayerSelection(playerItem);
                         }
                     }
                 });
             } else { console.error("Player list container not found."); }


            // Search Input
            if (searchInput) {
                let searchDebounceTimer;
                searchInput.addEventListener('input', function() {
                    clearTimeout(searchDebounceTimer);
                    searchDebounceTimer = setTimeout(() => {
                        currentFilter.search = this.value;
                        updateDisplayedPlayers();
                    }, 250);
                });
            }

            // Position Filter Buttons
             positionFilterBtns.forEach(button => {
                 if (!button.classList.contains('more-filters')) { // Exclude the "More Filters" button
                     button.addEventListener('click', function(e) {
                         e.preventDefault(); // Prevent default button action
                         // Deactivate other position buttons
                          positionFilterBtns.forEach(btn => {
                              if (!btn.classList.contains('more-filters')) {
                                  btn.classList.remove('active');
                              }
                          });
                         // Activate this button
                         this.classList.add('active');
                         currentFilter.position = this.dataset.filter;
                         updateDisplayedPlayers();
                     });
                 }
             });

            // More Filters Application (when Apply button in modal is clicked)
            function applyMoreFilters() {
                currentFilter.club = clubFilterSelect ? clubFilterSelect.value : '';
                currentFilter.maxPrice = priceFilterInput ? priceFilterInput.value : '';
                updateDisplayedPlayers();
                // closeModal is handled by data-modal-close on the button itself
            }
            if (applyFiltersBtn) {
                applyFiltersBtn.addEventListener('click', applyMoreFilters);
            } else if (moreFiltersModal) {
                // console.warn("Apply Filters button not found inside modal.");
            }


             // --- Modal Handling ---
             // Generic modal open/close functions (should be safe to run per instance)
             function openModal(modal) {
                if (!modal || modal.classList.contains('is-visible')) return;
                modal.style.display = 'flex'; // Make it flex *before* transition
                 // Delay adding class slightly to allow display change to render
                 requestAnimationFrame(() => {
                    modal.classList.add('is-visible');
                    modal.setAttribute('aria-hidden', 'false');
                 });
             }
             function closeModal(modal) {
                if (!modal || !modal.classList.contains('is-visible')) return;
                modal.classList.remove('is-visible');
                modal.setAttribute('aria-hidden', 'true');
                // Optional: Reset display after transition ends
                 modal.addEventListener('transitionend', () => {
                    if (!modal.classList.contains('is-visible')) {
                         modal.style.display = 'none';
                     }
                 }, { once: true });
             }

             // Attach listeners specific to this instance's triggers
             const instanceModalTriggers = document.querySelectorAll(`.dfsoccer-modal-trigger[data-modal-target$="-${instanceId}"]`);
             instanceModalTriggers.forEach(trigger => {
                 trigger.addEventListener('click', (e) => {
                     e.preventDefault();
                     const targetSelector = trigger.dataset.modalTarget;
                     const modal = document.querySelector(targetSelector);
                     if (modal) {
                         openModal(modal);
                     } else {
                         console.error(`DFSoccer: Modal ${targetSelector} not found.`);
                     }
                 });
             });

              // Global close listeners (add only ONCE per page load, ideally outside this script or guarded)
              // Use a flag to ensure these are added only once, even if multiple shortcodes are on the page.
             if (!window.dfsoccerGlobalModalListenersAttached) {
                 document.body.addEventListener('click', function (event) {
                     // Close if clicking the overlay or a close button within *any* dfsoccer modal
                     if (event.target.matches('.dfsoccer-modal[data-modal-close]') || event.target.matches('.dfsoccer-modal-overlay[data-modal-close]') || event.target.closest('[data-modal-close]')) {
                         // Find the closest modal parent to close
                         const modalToClose = event.target.closest('.dfsoccer-modal');
                         if (modalToClose && modalToClose.classList.contains('is-visible')) {
                             closeModal(modalToClose);
                         }
                     }
                 });
                 document.addEventListener('keydown', (e) => {
                     if (e.key === 'Escape') {
                         // Close the topmost visible dfsoccer modal
                         const visibleModal = document.querySelector('.dfsoccer-modal.is-visible');
                         if (visibleModal) {
                             closeModal(visibleModal);
                         }
                     }
                 });
                 window.dfsoccerGlobalModalListenersAttached = true; // Set the flag
             }


            // --- Initialisation Call ---
            if (allPlayerItems.length > 0) {
                 updateTeamState(); // Set initial state based on saved selections
                 updateDisplayedPlayers(); // Apply initial filters/pagination
            } else if (!playerListContainer){
                 console.log(`DFSoccer API Manager (${instanceId}): Player list container missing, initialization skipped.`);
            } else {
                console.log(`DFSoccer API Manager (${instanceId}): No players found in list, initial update skipped.`);
                 // Still update status bar for empty state if needed
                 updateTeamState();
                 renderPagination(); // Show pagination controls if needed (likely won't be)
            }

             console.log(`DFSoccer API Manager Initialized for Instance: ${instanceId}, Mode: ${currentMode}`);

        }); // End DOMContentLoaded
    })(); // End IIFE
    </script>

    <?php
    // --- 8. Return Buffered Output ---
    return ob_get_clean();
}

// Register the shortcode
add_shortcode('dfsoccer_fantasy_manager', 'dfsoccer_fantasy_manager_shortcode_v2');


// Add helper function if not already defined elsewhere
if (!function_exists('dfsoccer_has_league_started')) {
    function dfsoccer_has_league_started($league_id) {
        $start_date_str = get_post_meta($league_id, 'dfsoccer_league_start_date', true);
        if (empty($start_date_str)) {
            return false; // Assume not started if no date set
        }
        try {
            // Use WordPress timezone settings
            $start_date = new DateTimeImmutable($start_date_str, wp_timezone());
            $now = new DateTimeImmutable('now', wp_timezone());
            return $now >= $start_date;
        } catch (Exception $e) {
            error_log("DFSoccer Error: Invalid start date format for league $league_id: $start_date_str");
            return false; // Treat invalid date as not started
        }
    }
}

function dfsoccer_display_sorted_players($league_id) {
    // Generate truly unique identifiers
    $unique_id = 'dfs_' . uniqid();
    $container_class = 'dfsoccer_sorted_container_' . $unique_id;
    $table_class = 'dfsoccer_sorted_table_' . $unique_id;
    $search_class = 'dfsoccer_sorted_search_' . $unique_id;
    
    // Check if league is from API
    $league_source = is_league_from_api($league_id);
    $is_api_league = $league_source['from_api'];
    $source_league_id = $league_source['source_league_id'];
    
    if (!$is_api_league || empty($source_league_id)) {
        return '<p>This league is not connected to an API source or has no source ID.</p>';
    }
    
    // Get match results
    $match_results_json = get_post_meta($league_id, 'dfsoccer_match_results', true);
    $match_results = !empty($match_results_json) ? json_decode($match_results_json, true) : array();
    
    // Get player names from API (stored separately)
    $player_names = get_post_meta($league_id, 'dfsoccer_api_player_names', true);
    $player_names = !empty($player_names) ? $player_names : array();
    
    // If no results yet, fetch and calculate them
    if (empty($match_results) || empty($player_names)) {
        // Fetch player data from API
        $api_url = "https://superfantasy.net/wp-json/dfsoccer/v1/league/{$source_league_id}/match-results?nocache=1&LSCWP_CTRL=NOCACHE";
        $response = wp_remote_get($api_url);
        
        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $api_data = json_decode(wp_remote_retrieve_body($response), true);
            
            if (isset($api_data['player_results']) && !empty($api_data['player_results'])) {
                $match_results = array();
                $player_names = array(); // Reset player names
                
                // Process each player
                foreach ($api_data['player_results'] as $player_data) {
                    if (isset($player_data['id']) && isset($player_data['stats'])) {
                        $player_id = $player_data['id'];
                        
                        // Store player stats
                        $match_results[$player_id] = $player_data['stats'];
                        
                        // Store player name if available
                        if (isset($player_data['name'])) {
                            $player_names[$player_id] = $player_data['name'];
                        } else {
                            $player_names[$player_id] = "Player #" . $player_id;
                        }
                        
                        // Add position if available
                        if (isset($player_data['position'])) {
                            $match_results[$player_id]['position'] = $player_data['position'];
                        }
                        
                        // Calculate points
                        if (function_exists('calculate_points_from_api')) {
                            $points = calculate_points_from_api($player_id, $match_results[$player_id], $league_id);
                            $match_results[$player_id]['total_points'] = $points;
                        }
                    }
                }
                
                // Save results and player names
                if (!empty($match_results)) {
                    update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($match_results));
                }
                if (!empty($player_names)) {
                    update_post_meta($league_id, 'dfsoccer_api_player_names', $player_names);
                }
            }
        } else {
            return '<p>Error: Could not fetch data from API.</p>';
        }
    }
    
    // Fetch player data with prices from API
    $player_price_api_url = "https://superfantasy.net/wp-json/dfsoccer/v1/league/{$source_league_id}/fixture-players";
    $player_price_response = wp_remote_get($player_price_api_url);
    $player_price_data = array();
    
    if (!is_wp_error($player_price_response) && wp_remote_retrieve_response_code($player_price_response) === 200) {
        $player_price_data = json_decode(wp_remote_retrieve_body($player_price_response), true);
    }
    
    // Create a lookup map for player prices
    $player_prices = array();
    if (isset($player_price_data['players']) && !empty($player_price_data['players'])) {
        foreach ($player_price_data['players'] as $player) {
            if (isset($player['id']) && isset($player['price'])) {
                $player_prices[$player['id']] = $player['price'];
            }
        }
    }
    
    // Create an array with all player data for sorting
    $players = array();
    foreach ($match_results as $player_id => $player_data) {
        // Get player name
        $player_name = isset($player_names[$player_id]) ? $player_names[$player_id] : "Player #" . $player_id;
        
        // Get position
        $position = isset($player_data['position']) ? $player_data['position'] : 'Unknown';
        
        // Get price
        $price = isset($player_prices[$player_id]) ? $player_prices[$player_id] : 0;
        
        // Get points
        $points = isset($player_data['total_points']) ? $player_data['total_points'] : 0;
        
        // If points aren't calculated yet, do it now
        if ($points == 0 && function_exists('calculate_points_from_api')) {
            $points = calculate_points_from_api($player_id, $player_data, $league_id);
            // Update the stored points
            $match_results[$player_id]['total_points'] = $points;
            update_post_meta($league_id, 'dfsoccer_match_results', wp_json_encode($match_results));
        }
        
        // Add to players array
        $players[] = array(
            'id' => $player_id,
            'name' => $player_name,
            'position' => $position,
            'price' => $price,
            'points' => $points,
            'stats' => $player_data
        );
    }
    
    // Sort players by points (highest to lowest)
    usort($players, function($a, $b) {
        return $b['points'] <=> $a['points'];
    });
    
    // Start building output with unique classes
    $output = '<div class="' . $container_class . '" style="background-color: var(--color-green-800, #166534); padding: 1rem; border-radius: 0.5rem; margin: 1rem 0; box-shadow: 0 2px 8px rgba(0,0,0,0.2);">';
    $output .= '<h3 style="color: white; text-align: center;">Players Sorted by Points (Highest to Lowest)</h3>';
    
    // Add search with unique class
    $output .= '<div style="margin-bottom: 15px; text-align: center;">
                    <input type="text" class="' . $search_class . '" placeholder="Search for players..." 
                           style="width: 80%; padding: 8px; border-radius: 4px; border: none; background-color: rgba(255,255,255,0.9); color: #333;">
                </div>';
    
    $output .= '<table class="' . $table_class . '" style="width: 100%; border-collapse: collapse; color: white;">';
    $output .= '<thead><tr style="background-color: rgba(0,0,0,0.3);">
                    <th style="padding: 8px; text-align: left;">Rank</th>
                    <th style="padding: 8px; text-align: left;">Player</th>
                    <th style="padding: 8px; text-align: left;">Position</th>
                    <th style="padding: 8px; text-align: right;">Price</th>
                    <th style="padding: 8px; text-align: right;">Points</th>
                </tr></thead>';
    
    $output .= '<tbody>';
    
    // Display each player's points
    if (!empty($players)) {
        $rank = 1;
        foreach ($players as $player) {
            $row_class = $unique_id . '_row_' . ($rank % 2 == 0 ? 'even' : 'odd');
            $bg_color = ($rank % 2 == 0) ? 'rgba(0,0,0,0.2)' : 'rgba(0,0,0,0.1)';
            
            $output .= '<tr class="' . $row_class . '" style="border-bottom: 1px solid rgba(255,255,255,0.1); background-color: ' . $bg_color . ';">';
            $output .= '<td style="padding: 8px;">' . $rank . '</td>';
            $output .= '<td style="padding: 8px;" class="' . $unique_id . '_player_name">' . esc_html($player['name']) . '</td>';
            $output .= '<td style="padding: 8px;">' . esc_html($player['position']) . '</td>';
            $output .= '<td style="padding: 8px; text-align: right;">' . number_format($player['price'], 2) . '</td>';
            $output .= '<td style="padding: 8px; text-align: right;">' . number_format($player['points'], 2) . '</td>';
            $output .= '</tr>';
            $rank++;
        }
    } else {
        $output .= '<tr><td colspan="5" style="padding: 8px; text-align: center; color: white;">No player data available.</td></tr>';
    }
    
    $output .= '</tbody>';
    $output .= '</table>';
    
    // Add isolated jQuery script in the footer
    add_action('wp_footer', function() use ($unique_id, $container_class, $table_class, $search_class) {
        ?>
        <script>
        (function(jQuery) {
            // Use immediately invoked function expression (IIFE) to avoid global namespace pollution
            jQuery(document).ready(function($) {
                var searchClass = '<?php echo $search_class; ?>';
                var nameClass = '<?php echo $unique_id; ?>_player_name';
                var containerClass = '<?php echo $container_class; ?>';
                
                $('.' + searchClass).on('keyup', function() {
                    var searchValue = $(this).val().toLowerCase();
                    $('.' + containerClass + ' tbody tr').each(function() {
                        var playerName = $(this).find('.' + nameClass).text().toLowerCase();
                        if (playerName.indexOf(searchValue) > -1) {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });
            });
        })(jQuery);
        </script>
        <?php
    }, 999); // Very high priority to ensure it loads after other scripts
    
    // Add some isolated CSS
    $output .= '<style>
        .' . $container_class . ' {
            font-family: Arial, sans-serif;
        }
        .' . $table_class . ' {
            color: white;
            width: 100%;
        }
        .' . $table_class . ' th {
            background-color: rgba(0,0,0,0.3);
            position: sticky;
            top: 0;
        }
        .' . $table_class . ' tr:hover {
            background-color: rgba(255,255,255,0.1) !important;
        }
        .' . $search_class . ':focus {
            background-color: white;
            box-shadow: 0 0 8px rgba(255,255,255,0.5);
            outline: none;
        }
    </style>';
    
    $output .= '</div>';
    
    return $output;
}

// Example usage
// echo dfsoccer_display_sorted_players(YOUR_LEAGUE_ID_HERE);

/**
 * Check if fixtures have started for a specified league
 * 
 * @param int $league_id The ID of the league to check
 * @param string &$message Optional parameter to return a message if fixtures have started
 * @param bool $use_cache Whether to use cached results (default: true)
 * @return bool True if fixtures have started, false otherwise
 */
function api_has_fixtures_started_league($league_id, &$message = '', $use_cache = true) {
    // Check for cached result first
    if ($use_cache) {
        $cached_result = get_post_meta($league_id, 'dfsoccer_fixtures_started_cache', true);
        if (!empty($cached_result)) {
            $cached_data = maybe_unserialize($cached_result);
            if (isset($cached_data['started']) && $cached_data['started']) {
                $message = isset($cached_data['message']) ? $cached_data['message'] : 'The first fixture has already started. Any changes to your player selection may not be counted for scoring purposes.';
                return true;
            }
        }
    }
    
    // Get fixtures for the league
    $fixture_meta_key = 'dfsoccer_saved_fixtures_' . $league_id;
    $api_fixture_meta_key = 'dfsoccer_api_saved_fixtures_' . $league_id;
    
    $saved_fixtures = get_post_meta($league_id, $fixture_meta_key, true);
    $api_saved_fixtures = get_post_meta($league_id, $api_fixture_meta_key, true);
    
    // Determine which fixtures to use - prefer manually entered ones if both exist
    $active_fixtures = !empty($saved_fixtures) ? $saved_fixtures : $api_saved_fixtures;
    
    // If no fixtures, return false
    if (empty($active_fixtures)) {
        return false;
    }
    
    // Sort fixtures by date (earliest first)
    usort($active_fixtures, function($a, $b) {
        $date_a = isset($a['fixture_date']) ? $a['fixture_date'] : (isset($a['date']) ? $a['date'] : '');
        $date_b = isset($b['fixture_date']) ? $b['fixture_date'] : (isset($b['date']) ? $b['date'] : '');
        return strtotime($date_a) - strtotime($date_b);
    });
    
    // Get the date of the first fixture
    $first_fixture = $active_fixtures[0];
    $fixture_date_key = isset($first_fixture['fixture_date']) ? 'fixture_date' : (isset($first_fixture['date']) ? 'date' : '');
    
    // If no valid date field found, return false
    if (empty($fixture_date_key) || empty($first_fixture[$fixture_date_key])) {
        return false;
    }
    
    $first_fixture_date = strtotime($first_fixture[$fixture_date_key]);
    $current_time = current_time('timestamp');
    
    // Check if the first fixture has started
    $fixtures_started = ($first_fixture_date <= $current_time);
    
    if ($fixtures_started) {
        $message = 'The first fixture has already started. Any changes to your player selection may not be counted for scoring purposes.';
        
        // Cache the result for future checks
        $cache_data = array(
            'started' => true,
            'message' => $message,
            'checked_time' => $current_time
        );
        update_post_meta($league_id, 'dfsoccer_fixtures_started_cache', $cache_data);
    }
    
    return $fixtures_started;
}


// User Voting System Shortcode
function dfsoccer_user_voting_shortcode($atts) {
    $atts = shortcode_atts(array(
        'user_id' => '0',
        'display_mode' => 'full', // full, compact, rating_only
        'show_vote_buttons' => 'true',
        'show_vote_count' => 'true'
    ), $atts, 'user_voting');
    
    $target_user_id = intval($atts['user_id']);
    $current_user_id = get_current_user_id();
    $display_mode = sanitize_text_field($atts['display_mode']);
    $show_vote_buttons = ($atts['show_vote_buttons'] === 'true');
    $show_vote_count = ($atts['show_vote_count'] === 'true');
    
    // If no user_id specified, try to get from URL parameter
    if ($target_user_id === 0) {
        $target_user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
    }
    
    // Validate target user exists
    if ($target_user_id === 0 || !get_user_by('id', $target_user_id)) {
        return '<div class="user-voting-error">Invalid user specified.</div>';
    }
    
    // Handle AJAX vote submission
    if (isset($_POST['submit_vote']) && is_user_logged_in() && wp_verify_nonce($_POST['vote_nonce'], 'user_vote_' . $target_user_id)) {
        $vote_type = sanitize_text_field($_POST['vote_type']);
        dfsoccer_process_user_vote($current_user_id, $target_user_id, $vote_type);
    }
    
    // Get user data
    $target_user = get_user_by('id', $target_user_id);
    $user_votes = dfsoccer_get_user_votes($target_user_id);
    $current_user_vote = is_user_logged_in() ? dfsoccer_get_user_vote($current_user_id, $target_user_id) : null;
    
    // Calculate rating
    $total_votes = $user_votes['upvotes'] + $user_votes['downvotes'];
    $rating_percentage = $total_votes > 0 ? round(($user_votes['upvotes'] / $total_votes) * 100) : 0;
    
    ob_start();
    ?>
    
    <div class="dfsoccer-user-voting" data-user-id="<?php echo esc_attr($target_user_id); ?>">
        
        <?php if ($display_mode === 'full' || $display_mode === 'compact'): ?>
            <div class="user-voting-header">
                <h4 class="voted-user-name">
                    <?php echo esc_html($target_user->display_name); ?>
                    <span class="user-rating-badge rating-<?php echo $rating_percentage >= 70 ? 'good' : ($rating_percentage >= 40 ? 'neutral' : 'poor'); ?>">
                        <?php echo $rating_percentage; ?>% positive
                    </span>
                </h4>
            </div>
        <?php endif; ?>
        
        <?php if ($show_vote_count && ($display_mode === 'full' || $display_mode === 'compact')): ?>
            <div class="vote-statistics">
                <div class="vote-stats-grid">
                    <div class="vote-stat upvotes">
                        <span class="vote-icon">👍</span>
                        <span class="vote-number"><?php echo $user_votes['upvotes']; ?></span>
                        <span class="vote-label">Upvotes</span>
                    </div>
                    <div class="vote-stat downvotes">
                        <span class="vote-icon">👎</span>
                        <span class="vote-number"><?php echo $user_votes['downvotes']; ?></span>
                        <span class="vote-label">Downvotes</span>
                    </div>
                    <div class="vote-stat total">
                        <span class="vote-icon">📊</span>
                        <span class="vote-number"><?php echo $total_votes; ?></span>
                        <span class="vote-label">Total</span>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <?php if ($display_mode === 'rating_only'): ?>
            <div class="rating-only-display">
                <span class="user-name"><?php echo esc_html($target_user->display_name); ?></span>
                <span class="rating-badge rating-<?php echo $rating_percentage >= 70 ? 'good' : ($rating_percentage >= 40 ? 'neutral' : 'poor'); ?>">
                    <?php echo $rating_percentage; ?>%
                </span>
            </div>
        <?php endif; ?>
        
        <?php if ($show_vote_buttons && is_user_logged_in() && $current_user_id !== $target_user_id): ?>
            <div class="vote-buttons">
                <form method="post" class="vote-form">
                    <?php wp_nonce_field('user_vote_' . $target_user_id, 'vote_nonce'); ?>
                    <input type="hidden" name="target_user_id" value="<?php echo esc_attr($target_user_id); ?>">
                    
                    <button type="submit" name="submit_vote" value="submit_vote" 
                            onclick="this.form.vote_type.value='upvote'" 
                            class="vote-btn upvote-btn <?php echo $current_user_vote === 'upvote' ? 'active' : ''; ?>"
                            <?php echo $current_user_vote === 'upvote' ? 'disabled' : ''; ?>>
                        <span class="vote-icon">👍</span>
                        <span class="vote-text">
                            <?php echo $current_user_vote === 'upvote' ? 'Upvoted' : 'Upvote'; ?>
                        </span>
                    </button>
                    
                    <button type="submit" name="submit_vote" value="submit_vote" 
                            onclick="this.form.vote_type.value='downvote'" 
                            class="vote-btn downvote-btn <?php echo $current_user_vote === 'downvote' ? 'active' : ''; ?>"
                            <?php echo $current_user_vote === 'downvote' ? 'disabled' : ''; ?>>
                        <span class="vote-icon">👎</span>
                        <span class="vote-text">
                            <?php echo $current_user_vote === 'downvote' ? 'Downvoted' : 'Downvote'; ?>
                        </span>
                    </button>
                    
                    <?php if ($current_user_vote): ?>
                        <button type="submit" name="submit_vote" value="submit_vote" 
                                onclick="this.form.vote_type.value='remove'" 
                                class="vote-btn remove-vote-btn">
                            <span class="vote-icon">❌</span>
                            <span class="vote-text">Remove Vote</span>
                        </button>
                    <?php endif; ?>
                    
                    <input type="hidden" name="vote_type" value="">
                </form>
            </div>
        <?php elseif ($show_vote_buttons && !is_user_logged_in()): ?>
            <div class="vote-login-message">
                <p><a href="<?php echo wp_login_url(get_permalink()); ?>">Log in</a> to vote for this user.</p>
            </div>
        <?php elseif ($show_vote_buttons && $current_user_id === $target_user_id): ?>
            <div class="vote-self-message">
                <p>You cannot vote for yourself.</p>
            </div>
        <?php endif; ?>
        
    </div>
    
    <style>
    .dfsoccer-user-voting {
        background: #f8f9fa;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 20px;
        margin: 15px 0;
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    }
    
    .user-voting-header h4 {
        margin: 0 0 15px 0;
        color: #2c3e50;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-rating-badge {
        font-size: 0.8em;
        padding: 4px 8px;
        border-radius: 12px;
        font-weight: bold;
    }
    
    .rating-good {
        background-color: #d4edda;
        color: #155724;
    }
    
    .rating-neutral {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .rating-poor {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .vote-stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .vote-stat {
        text-align: center;
        padding: 12px;
        background: white;
        border-radius: 6px;
        border: 1px solid #dee2e6;
    }
    
    .vote-icon {
        display: block;
        font-size: 1.5em;
        margin-bottom: 5px;
    }
    
    .vote-number {
        display: block;
        font-size: 1.2em;
        font-weight: bold;
        color: #2c3e50;
    }
    
    .vote-label {
        display: block;
        font-size: 0.8em;
        color: #6c757d;
        margin-top: 2px;
    }
    
    .vote-buttons {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .vote-btn {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 8px 16px;
        border: 2px solid transparent;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        font-size: 0.9em;
        transition: all 0.2s ease;
    }
    
    .upvote-btn {
        border-color: #28a745;
        color: #28a745;
    }
    
    .upvote-btn:hover, .upvote-btn.active {
        background: #28a745;
        color: white;
    }
    
    .downvote-btn {
        border-color: #dc3545;
        color: #dc3545;
    }
    
    .downvote-btn:hover, .downvote-btn.active {
        background: #dc3545;
        color: white;
    }
    
    .remove-vote-btn {
        border-color: #6c757d;
        color: #6c757d;
    }
    
    .remove-vote-btn:hover {
        background: #6c757d;
        color: white;
    }
    
    .vote-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    .rating-only-display {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    .user-name {
        font-weight: bold;
        color: #2c3e50;
    }
    
    .rating-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        font-weight: bold;
    }
    
    .vote-login-message, .vote-self-message {
        text-align: center;
        padding: 10px;
        background: #e9ecef;
        border-radius: 4px;
        color: #6c757d;
    }
    
    .vote-login-message a {
        color: #007bff;
        text-decoration: none;
    }
    
    .user-voting-error {
        color: #dc3545;
        background: #f8d7da;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #f5c6cb;
    }
    </style>
    
    <?php
    return ob_get_clean();
}
add_shortcode('user_voting', 'dfsoccer_user_voting_shortcode');

// Function to process user votes
function dfsoccer_process_user_vote($voter_id, $target_user_id, $vote_type) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dfsoccer_user_votes';
    
    // Create table if it doesn't exist
    dfsoccer_create_user_votes_table();
    
    if ($vote_type === 'remove') {
        // Remove existing vote
        $wpdb->delete(
            $table_name,
            array(
                'voter_id' => $voter_id,
                'target_user_id' => $target_user_id
            ),
            array('%d', '%d')
        );
    } else {
        // Check if vote already exists
        $existing_vote = $wpdb->get_var($wpdb->prepare(
            "SELECT vote_type FROM $table_name WHERE voter_id = %d AND target_user_id = %d",
            $voter_id, $target_user_id
        ));
        
        if ($existing_vote) {
            // Update existing vote
            $wpdb->update(
                $table_name,
                array(
                    'vote_type' => $vote_type,
                    'vote_date' => current_time('mysql')
                ),
                array(
                    'voter_id' => $voter_id,
                    'target_user_id' => $target_user_id
                ),
                array('%s', '%s'),
                array('%d', '%d')
            );
        } else {
            // Insert new vote
            $wpdb->insert(
                $table_name,
                array(
                    'voter_id' => $voter_id,
                    'target_user_id' => $target_user_id,
                    'vote_type' => $vote_type,
                    'vote_date' => current_time('mysql')
                ),
                array('%d', '%d', '%s', '%s')
            );
        }
    }
}

// Function to get user votes
function dfsoccer_get_user_votes($user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dfsoccer_user_votes';
    
    $votes = $wpdb->get_results($wpdb->prepare(
        "SELECT vote_type, COUNT(*) as count FROM $table_name WHERE target_user_id = %d GROUP BY vote_type",
        $user_id
    ));
    
    $result = array(
        'upvotes' => 0,
        'downvotes' => 0
    );
    
    foreach ($votes as $vote) {
        if ($vote->vote_type === 'upvote') {
            $result['upvotes'] = intval($vote->count);
        } elseif ($vote->vote_type === 'downvote') {
            $result['downvotes'] = intval($vote->count);
        }
    }
    
    return $result;
}

// Function to get a specific user's vote for another user
function dfsoccer_get_user_vote($voter_id, $target_user_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dfsoccer_user_votes';
    
    return $wpdb->get_var($wpdb->prepare(
        "SELECT vote_type FROM $table_name WHERE voter_id = %d AND target_user_id = %d",
        $voter_id, $target_user_id
    ));
}

// Function to create the user votes table
function dfsoccer_create_user_votes_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'dfsoccer_user_votes';
    
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        voter_id bigint(20) NOT NULL,
        target_user_id bigint(20) NOT NULL,
        vote_type varchar(20) NOT NULL,
        vote_date datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_vote (voter_id, target_user_id),
        KEY target_user_idx (target_user_id),
        KEY voter_idx (voter_id)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Hook to create table on plugin activation
register_activation_hook(__FILE__, 'dfsoccer_create_user_votes_table');