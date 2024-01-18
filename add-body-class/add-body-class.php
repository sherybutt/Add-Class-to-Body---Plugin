<?php
/*
Plugin Name: Add Class to Body
Description: Add desired class to body.
Version: 1.0
Author: Sheryar
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Activation Hook
register_activation_hook(__FILE__, 'your_plugin_activate');

function your_plugin_activate()
{
    // Activation code, if needed
}

// Function to add the new option in WordPress settings
function custom_options_add_option()
{
    add_options_page(
        'Custom Options',      // Page title
        'Add Class to Body',   // Menu title
        'manage_options',      // Capability required to access
        'custom_options_page', // Page slug
        'custom_options_page'  // Callback function to render the page
    );
}

// Callback function to render the custom options page
function custom_options_page()
{
    if (isset($_POST['global_class'])) {
        $global_class = sanitize_text_field($_POST['global_class']);
        update_option('global_class', $global_class);
    }

    $global_class = get_option('global_class', '');
    ?>
    <div class="wrap">
        <h2>Add Class to Body Settings</h2>
        <form method="post" action="">
            <label for="global_class">Global Class for Body:</label><br />
            <input type="text" id="global_class" name="global_class" value="<?php echo esc_attr($global_class); ?>" style="width: 100%;"><br /><br />
            <input type="submit" class="button-primary" value="Save Changes">
        </form>
    </div>
    <?php
}

// Hook to add the custom option in the WordPress settings menu
add_action('admin_menu', 'custom_options_add_option');

// Enqueue styles for the admin dashboard
function enqueue_dashboard_styles()
{
    // Adjust the path to your stylesheet file if needed
    $stylesheet_url = plugin_dir_url(__FILE__) . 'admin/css/admin-styles.css';

    // Enqueue the stylesheet only on the WordPress admin pages
    wp_enqueue_style('dashboard-styles-css', $stylesheet_url);
}

add_action('admin_enqueue_scripts', 'enqueue_dashboard_styles');

// Add meta box to posts, pages, and custom post types
function add_custom_meta_box()
{
    $post_types = get_post_types();
    foreach ($post_types as $post_type) {
        add_meta_box('custom_class_meta_box', 'Custom Classes', 'render_custom_class_meta_box', $post_type, 'side', 'default');
    }
}

add_action('add_meta_boxes', 'add_custom_meta_box');

// Render the meta box content
function render_custom_class_meta_box($post)
{
    $custom_classes = get_post_meta($post->ID, '_custom_classes', true);
    ?>
    <label for="custom_classes">Enter custom classes <br />(Add comma-separator or space for multiple classes):</label><br /><br />
    <input type="text" class="ssb-custom-input" id="custom_classes" name="custom_classes" value="<?php echo esc_attr($custom_classes); ?>" style="width: 100%;">
    <?php
}

// Save the custom class meta data
function save_custom_class_meta_data($post_id)
{
    if (array_key_exists('custom_classes', $_POST)) {
        $custom_classes = sanitize_text_field($_POST['custom_classes']);

        // Update post meta only if custom classes are not empty
        if (!empty($custom_classes)) {
            // Validate class names
            $class_names = explode(',', $custom_classes);
            $valid_class_names = array_filter($class_names, function ($class) {
                // Check if the class starts with an alphabetic character
                return preg_match('/^[a-zA-Z]/', trim($class));
            });

            // Update post meta only if all class names are valid
            if (count($class_names) === count($valid_class_names)) {
                update_post_meta($post_id, '_custom_classes', $custom_classes);
            }
        } else {
            // Remove the custom class meta data if the input field is empty
            delete_post_meta($post_id, '_custom_classes');
        }
    }
}

add_action('save_post', 'save_custom_class_meta_data');

// Add custom classes to the body tag
function add_custom_classes_to_body($classes)
{
    $global_class = get_option('global_class', '');

    if ($global_class) {
        $classes[] = sanitize_html_class($global_class);
    }

    global $post;
    $custom_classes = get_post_meta($post->ID, '_custom_classes', true);

    if ($custom_classes) {
        $custom_classes_array = explode(',', $custom_classes);
        $classes = array_merge($classes, array_map('trim', $custom_classes_array));
    }

    return $classes;
}

add_filter('body_class', 'add_custom_classes_to_body');
?>
