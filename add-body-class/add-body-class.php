<?php
/*
Plugin Name: Add Class to Body
Description: Add desired class to body.
Version: 1.2
Author: Sheryar Shahzad
*/

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Activation Hook
register_activation_hook(__FILE__, 'ssbwp_plugin_activate');

function ssbwp_plugin_activate()
{
    // Activation code, if needed
}

// Function to add the new option in WordPress settings
function ssbwp_custom_options_add_option()
{
    add_options_page(
        'Custom Options',      // Page title
        'Add Class to Body',   // Menu title
        'manage_options',      // Capability required to access
        'ssbwp_custom_options_page', // Page slug
        'ssbwp_custom_options_page'  // Callback function to render the page
    );
}

// Callback function to render the custom options page
function ssbwp_custom_options_page()
{
    $success_message = '';

    // Verify nonce
    if (isset($_POST['save_settings']) && wp_verify_nonce($_POST['ssbwp_settings_nonce'], 'ssbwp_settings')) {
        $selected_post_types = isset($_POST['post_types']) ? $_POST['post_types'] : array();
        update_option('ssbwp_hidden_post_types', $selected_post_types);

        $global_class = sanitize_text_field($_POST['global_class']);
        update_option('ssbwp_global_class', $global_class);

        $success_message = 'Changes saved successfully!';
    }

    $hidden_post_types = get_option('ssbwp_hidden_post_types', array());
    $all_post_types = get_post_types(array('public' => true, '_builtin' => false), 'names');
    $registered_post_types = array_merge(array('post', 'page'), $all_post_types);
    $global_class = get_option('ssbwp_global_class', '');

    // Generate nonce field
    $nonce = wp_create_nonce('ssbwp_settings');
    ?>

    <div class="wrap">
        <div class="wpmd-plugin-settings-title">
            <h2>Add Class to Body Settings</h2>
        </div><br />
        <?php if (!empty($success_message)) : ?>
            <div id="message" class="updated notice is-dismissible below-h2">
                <p><?php echo esc_html($success_message); ?></p>
                <button type="button" class="notice-dismiss"></button>
            </div>
        <?php endif; ?>

        <form class="wpmd-global-class-form" method="post" action="">
            <!-- Add nonce field -->
            <?php wp_nonce_field('ssbwp_settings', 'ssbwp_settings_nonce'); ?>
            
            <label for="global_class">Global Class for Body (Only 1 Class Allowed):</label>
            <input type="text" id="global_class" name="global_class" value="<?php echo esc_attr($global_class); ?>" style="width: 100%;"><br /><br />

            <label>Select Post Types where you want to hide the custom class option:</label><br />
            <?php
            foreach ($registered_post_types as $post_type) {
                $checked = in_array($post_type, $hidden_post_types) ? 'checked' : '';
                echo '<div class="wpmd-post-type-holder"> <div class="wpmd-post-type-name">' . esc_html(ucfirst($post_type)) . '</div><label class="wpmd-checkBox-switch"><input type="checkbox" name="post_types[]" value="' . esc_attr($post_type) . '" ' . esc_attr($checked) . '><span class="wpmd-checkBox-slider"></span></label></div><br />';
            }
            ?>
            <br />
            <input type="submit" class="button-primary" name="save_settings" value="Save Changes">
        </form>
    </div>
    <?php
}

// Hook to add the custom option in the WordPress settings menu
add_action('admin_menu', 'ssbwp_custom_options_add_option');

// Enqueue styles for the admin dashboard
function ssbwp_enqueue_dashboard_styles()
{
    // Adjust the path to your stylesheet file if needed
    $stylesheet_url = plugin_dir_url(__FILE__) . 'admin/css/admin-styles.css';

    // Enqueue the stylesheet only on the WordPress admin pages
    wp_enqueue_style('ssbwp-dashboard-styles-css', $stylesheet_url, array(), '1.0.0');
}

add_action('admin_enqueue_scripts', 'ssbwp_enqueue_dashboard_styles');

// Add meta box to posts, pages, and custom post types
function ssbwp_add_custom_meta_box()
{
    $hidden_post_types = get_option('ssbwp_hidden_post_types', array());
    $post_types = array_diff(get_post_types(), $hidden_post_types);

    foreach ($post_types as $post_type) {
        add_meta_box('ssbwp_custom_class_meta_box', 'Custom Classes', 'ssbwp_render_custom_class_meta_box', $post_type, 'side', 'default');
    }
}

add_action('add_meta_boxes', 'ssbwp_add_custom_meta_box');

// Callback function to render the meta box content
function ssbwp_render_custom_class_meta_box($post)
{
    $custom_classes = get_post_meta($post->ID, '_ssbwp_custom_classes', true);
    // Generate nonce field
    $nonce = wp_create_nonce('ssbwp_custom_classes');
    ?>
    <input type="hidden" name="ssbwp_custom_classes_nonce" value="<?php echo esc_attr($nonce); ?>">
    <label for="custom_classes">Enter custom classes <br />(Add comma-separator or space for multiple classes):</label><br /><br />
    <input type="text" class="wpmd-custom-input" id="custom_classes" name="custom_classes" value="<?php echo esc_attr($custom_classes); ?>" style="width: 100%;">
    <?php
}

// Save the custom class meta data
function ssbwp_save_custom_class_meta_data($post_id)
{
    // Verify nonce
    if ( !isset( $_POST['ssbwp_custom_classes_nonce'] ) || !wp_verify_nonce( $_POST['ssbwp_custom_classes_nonce'], 'ssbwp_custom_classes' ) ) {
        return $post_id;
    }

    $hidden_post_types = get_option('ssbwp_hidden_post_types', array());
    $current_post_type = get_post_type($post_id);

    if (!in_array($current_post_type, $hidden_post_types)) {
        if (isset($_POST['custom_classes'])) {
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
                    update_post_meta($post_id, '_ssbwp_custom_classes', $custom_classes);
                }
            } else {
                // Remove the custom class meta data if the input field is empty
                delete_post_meta($post_id, '_ssbwp_custom_classes');
            }
        }
    }
}

add_action('save_post', 'ssbwp_save_custom_class_meta_data');

// Add custom classes to the body tag
function ssbwp_add_custom_classes_to_body($classes)
{
    $global_class = get_option('ssbwp_global_class', '');

    if ($global_class) {
        $classes[] = sanitize_html_class($global_class);
    }

    global $post;
    $hidden_post_types = get_option('ssbwp_hidden_post_types', array());

    if (!in_array($post->post_type, $hidden_post_types)) {
        $custom_classes = get_post_meta($post->ID, '_ssbwp_custom_classes', true);

        if ($custom_classes) {
            $custom_classes_array = explode(',', $custom_classes);
            $classes = array_merge($classes, array_map('trim', $custom_classes_array));
        }
    }

    return $classes;
}

add_filter('body_class', 'ssbwp_add_custom_classes_to_body');
?>
