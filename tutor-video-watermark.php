<?php
/**
 * Plugin Name: Tutor LMS Video Watermark
 * Description: Adds a dynamic watermark with the user's display name to Tutor LMS videos.
 * Version: 1.0.0
 * Author: Hossam Omran
 * Author URI: https://www.linkedin.com/in/hossam-omran-cms/
 * Text Domain: tutor-video-watermark
 * Domain Path: /languages
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Handle user ban via AJAX with nonce check
function tvw_handle_ban_user() {
    if ( ! isset( $_POST['user_id'] ) || ! isset( $_POST['tvw_nonce'] ) || ! wp_verify_nonce( $_POST['tvw_nonce'], 'tvw_ban_user' ) || ! is_user_logged_in() ) {
        wp_send_json_error( 'Invalid request.' );
    }

    $user_id = intval( sanitize_text_field( $_POST['user_id'] ) );
    $user = get_user_by( 'id', $user_id );

    if ( ! in_array( 'administrator', (array) $user->roles ) && ! in_array( 'editor', (array) $user->roles ) ) {
        update_user_meta( $user_id, 'tvw_banned', 1 );
        wp_send_json_success();
    } else {
        wp_send_json_error( 'Cannot ban administrators or editors.' );
    }

    wp_die();
}
add_action( 'wp_ajax_tvw_ban_user', 'tvw_handle_ban_user' );
add_action( 'wp_ajax_nopriv_tvw_ban_user', 'tvw_handle_ban_user' );

// Enqueue the script and localize data, including nonce
function tvw_enqueue_scripts() {
    wp_enqueue_script( 'tvw-script', plugin_dir_url( __FILE__ ) . 'assets/script.js', array('jquery'), null, true );

    $current_user = wp_get_current_user();
    $user_display_name = ( $current_user->exists() ) ? $current_user->display_name : __('Visitor', 'tutor-video-watermark');

    wp_localize_script( 'tvw-script', 'tvwData', array(
        'user_display_name' => esc_js( $user_display_name ),
        'user_id' => $current_user->ID,
        'ban_user' => ! current_user_can( 'administrator' ) && ! current_user_can( 'editor' ),
        'ajax_url' => admin_url( 'admin-ajax.php' ),
        'home_url' => home_url('/?banned=true'), 
        'tvw_nonce' => wp_create_nonce('tvw_ban_user')
    ));
}
add_action( 'wp_enqueue_scripts', 'tvw_enqueue_scripts' );

// Check if the user is banned when accessing single course pages
function tvw_add_tutor_watermark() {
    $current_user = wp_get_current_user();
    if ( is_singular('tutor_lesson') ) {
        $is_banned = get_user_meta($current_user->ID, 'tvw_banned', true);

        if ( $is_banned ) {
            // Redirect to the homepage with a query parameter for the error message
            wp_redirect(home_url('/?banned=true'));
            exit;
        }

        ?>
        <div id="watermark"><?php echo esc_html( $current_user->display_name ?: __('Visitor', 'tutor-video-watermark') ); ?></div>
        <?php
    }
}
add_action( 'tutor_lesson/single/after/video/html5', 'tvw_add_tutor_watermark' );
add_action( 'tutor_lesson/single/after/video/youtube', 'tvw_add_tutor_watermark' );
add_action( 'tutor_lesson/single/after/video/external_ur', 'tvw_add_tutor_watermark' );
add_action( 'tutor_lesson/single/after/video/viemo', 'tvw_add_tutor_watermark' );
// Check for banned status on login
function tvw_check_banned_user_on_login($user_login, $user) {
    $is_banned = get_user_meta($user->ID, 'tvw_banned', true);

    if ($is_banned && ! in_array('administrator', (array) $user->roles)) {
        wp_redirect(home_url('/?banned=true'));
        exit;
    }
}
add_action('wp_login', 'tvw_check_banned_user_on_login', 10, 2);

// Display banned message
function tvw_display_banned_message() {
    if ( isset($_GET['banned']) && $_GET['banned'] == 'true' ) {
        echo '<div style="color: red; font-size: 16px; text-align: center; margin: 20px 0;">' 
             . esc_html__('You have been banned. Please contact the administrator to unban your account.', 'tutor-video-watermark') 
             . '</div>';
    }
}
add_action('wp_footer', 'tvw_display_banned_message');
