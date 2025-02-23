<?php
/*
Plugin Name: visitmyjoburg - beta
Description: Displays a new message each day using a shortcode and provides an endpoint accessible with a secret key.
Version: 1.4
Author: Alec Shelembe
*/

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Enqueue WordPress default styles and Font Awesome
function enqueue_assets() {
    wp_enqueue_style('wp-default-style', get_stylesheet_uri());
    wp_enqueue_script('font-awesome-kit', 'https://kit.fontawesome.com/06f647569e.js', [], null, true);
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css');
}
add_action('wp_enqueue_scripts', 'enqueue_assets');

// Function to get website info
function get_website_info() {
    return [
        'site_name' => get_bloginfo('name'),
        'site_tagline' => get_bloginfo('description'),
        'site_url' => get_bloginfo('url'),
        'admin_email' => get_bloginfo('admin_email'),
        'theme_name' => wp_get_theme()->get('Name'),
        'theme_version' => wp_get_theme()->get('Version'),
        'wp_version' => get_bloginfo('version'),
        'favicon_url' => get_site_icon_url(),
        'physical_address' => get_option('blogaddress') ? get_option('blogaddress') : 'Address not available'
    ];
}

// REST API endpoint restricted by secret key
function register_website_info_endpoint() {
    register_rest_route('visitmyjoburg/v1', '/info', [
        'methods' => 'GET',
        'callback' => 'handle_website_info_request',
        'permission_callback' => '__return_true'
    ]);
}
add_action('rest_api_init', 'register_website_info_endpoint');

function handle_website_info_request(WP_REST_Request $request) {
    $allowed_origin = 'https://visitmyjoburg.co.za';
    $secret_key = '1qazsw34dc'; // Replace with your secret key

    $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
    $provided_key = $request->get_param('key');

    if ($origin === $allowed_origin && $provided_key === $secret_key) {
        return get_website_info();
    } else {
        return new WP_REST_Response(['error' => 'Unauthorized'], 403);
    }
}

// Shortcode function
function website_info_shortcode() {
    $info = get_website_info();
    $search_link = "https://visitmyjoburg.co.za/search-for-posts?query=" . urlencode($info['site_name']);

    $output = "
    <div id='website-info-wrapper' style='position: fixed; bottom: 16px; left: 16px; z-index: 50;'>
        <div id='website-info-button' style='display: flex; align-items: center; gap: 8px;'>
            <button onclick='toggleInfoCard()' class='wp-block-button__link wp-element-button'>
                <i class='fa-solid fa-fire-flame-curved'></i> Visitmyjoburg!
            </button>
            <button onclick='removeButton()' class='wp-block-button__link wp-element-button' style='background-color: transparent; border: none;'>
                <i class='fas fa-times'></i>
            </button>
        </div>
        <div id='website-info-card' style='display: none; margin-top: 8px; padding: 16px; background-color: #fff; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); border-radius: 8px; width: 288px;'>
            <div style='display: flex; align-items: center; gap: 12px;'>
                <img src='{$info['favicon_url']}' alt='Favicon' style='width: 50px; height: 50px; border-radius: 50%; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);'/>
                <div>
                    <p style='font-weight: bold;'>{$info['site_name']}</p>
                    <p style='color: #555;'>{$info['site_tagline']}</p>
                    <!--<p style='font-size: 12px; color: #888;'>URL: {$info['site_url']}</p>-->
                    <p style='font-size: 12px; color: #888;'>Promotor: {$info['admin_email']}</p>
                    <p style='margin-top: 12px; text-align: center;'><a href='{$search_link}' target='_blank' style='color: #0073aa; text-decoration: none;'>Open</a></p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleInfoCard() {
            var card = document.getElementById('website-info-card');
            card.style.display = card.style.display === 'none' ? 'block' : 'none';
        }

        function removeButton() {
            var wrapper = document.getElementById('website-info-wrapper');
            wrapper.remove();
        }
    </script>
    ";

    return $output;
}
add_shortcode('visitmyjoburg', 'website_info_shortcode');
