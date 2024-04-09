<?php
/**
 * Plugin Name: Kanały TV - Fastar
 * Description: Zarządzaj ofertą kanałów telewizyjnych
 * Version: 1.0
 * Author: <a href="https://lepszastrona.net">LepszaStrona.net</a>
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register Custom Post Type
function custom_channels_post_type() {
    $labels = array(
        'name'               => 'Lista Kanałów',
        'singular_name'      => 'Kanał',
        'menu_name'          => 'Kanały Telewizyjne',
        'name_admin_bar'     => 'Kanał Telewizyjny',
        'archives'           => 'Archiwum Kanałów',
        'attributes'         => 'Atrybuty Kanału',
        'parent_item_colon'  => 'Nadrzędny Kanał:',
        'all_items'          => 'Wszystkie Kanały',
        'add_new_item'       => 'Dodaj nowy kanał',
        'add_new'            => 'Dodaj nowy',
        'new_item'           => 'Nowy Kanał',
        'edit_item'          => 'Edytuj Kanał',
        'update_item'        => 'Zaktualizuj Kanał',
        'view_item'          => 'Zobacz Kanał',
        'view_items'         => 'Zobacz Kanały',
        'search_items'       => 'Szukaj Kanałów',
        'not_found'          => 'Nie znaleziono kanałów',
        'not_found_in_trash' => 'Nie znaleziono kanałów w koszu',
    );

    $args = array(
        'label'               => 'Lista Kanałów',
        'description'         => 'Lista Kanałów Telewizyjnych',
        'labels'              => $labels,
        'supports'            => array('title', 'thumbnail', 'custom-fields'), // Dodaj custom-fields do obsługi niestandardowych pól
        'hierarchical'        => false,
        'public'              => true,
        'show_ui'             => true,
        'show_in_menu'        => true,
        'menu_position'       => 5,
        'menu_icon'           => 'dashicons-video-alt3',
        'show_in_admin_bar'   => true,
        'show_in_nav_menus'   => true,
        'can_export'          => true,
        'has_archive'         => true,
        'exclude_from_search' => false,
        'publicly_queryable'  => true,
        'capability_type'     => 'page',
    );

    register_post_type('custom_channels', $args);
}

add_action('init', 'custom_channels_post_type', 0);

// Register ACF Fields
if (function_exists('acf_add_local_field_group')) {
    acf_add_local_field_group(array(
        'key' => 'group_5fd3f0c946f3a',
        'title' => 'Pakiety Dostępu',
        'fields' => array(
            array(
                'key' => 'field_5fd3f0d75f7eb',
                'label' => 'Pakiety Dostępu',
                'name' => 'access_packages',
                'type' => 'checkbox',
                'instructions' => 'Wybierz pakiety, w których dostępny jest ten kanał.',
                'choices' => array(
                    'economy' => 'Economy',
                    'standard' => 'Standard',
                    'master' => 'Master',
                    'vip' => 'VIP',
                ),
                'layout' => 'horizontal',
                'return_format' => 'value',
            ),
            array(
                'key' => 'field_5fd3f1c05f7ec',
                'label' => 'Obrazek Kanału',
                'name' => 'channel_image',
                'type' => 'image',
                'instructions' => 'Dodaj obrazek reprezentujący kanał.',
                'return_format' => 'url',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param' => 'post_type',
                    'operator' => '==',
                    'value' => 'custom_channels',
                ),
            ),
        ),
    ));
}

// Shortcode for Displaying Channels
function display_channels_shortcode($atts) {
    ob_start();

    $packages = array('economy', 'standard', 'master', 'vip');

    // Display Package Selection Panel
    echo '<div id="package-selection">';
    foreach ($packages as $package) {
        echo '<button class="package-button" data-package="' . $package . '">' . ucfirst($package) . '</button>';
    }
    echo '</div>';

    // Display Channels Container
    echo '<div id="channels-container"></div>';

    // Enqueue Styles and JavaScript
    wp_enqueue_style('custom-channels-style', plugin_dir_url(__FILE__) . 'css/custom-channels.css');
    wp_enqueue_script('custom-channels-script', plugin_dir_url(__FILE__) . 'js/custom-channels.js', array('jquery'), '1.0', true);
    wp_localize_script('custom-channels-script', 'customChannels', array('ajaxurl' => admin_url('admin-ajax.php')));

    $output = ob_get_clean();
    return $output;
}

add_shortcode('display_channels', 'display_channels_shortcode');

// AJAX Handler for Loading Channels
function load_channels() {
    $package = sanitize_text_field($_POST['package']);

    $channels = new WP_Query(array(
        'post_type' => 'custom_channels',
        'posts_per_page' => -1, // Wyświetl wszystkie kanały
        'meta_query' => array(
            array(
                'key' => 'access_packages',
                'value' => $package,
                'compare' => 'LIKE',
            ),
        ),
    ));

    while ($channels->have_posts()) {
        $channels->the_post();
        $thumbnail = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail');
        $channel_image = get_field('channel_image'); // Pobierz wartość pola ACF dla obrazka kanału

        echo '<div class="channel-item" data-tooltip="' . get_the_title() . '">';
        if ($channel_image) {
            echo '<img src="' . esc_url($channel_image) . '" alt="' . get_the_title() . '">';
        } else {
            echo '<img src="' . esc_url($thumbnail) . '" alt="' . get_the_title() . '">';
        }
        echo '</div>';
    }

    wp_reset_postdata();

    die();
}

add_action('wp_ajax_load_channels', 'load_channels');
add_action('wp_ajax_nopriv_load_channels', 'load_channels');

?>