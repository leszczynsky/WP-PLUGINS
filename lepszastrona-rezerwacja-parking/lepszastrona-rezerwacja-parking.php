<?php
/*
 * Plugin Name: Rezerwacja Parking
 * Author: LepszaStrona.net
 * Description: Wtyczka do rezerwacji parkingu przy lotnisku z dynamicznym obliczaniem ceny i przekazywaniem danych formularza do zamówienia WooCommerce jako metadane.
 * Version: 1.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

function ls_enqueue_scripts() {
    wp_enqueue_style('ls-main-css', plugin_dir_url(__FILE__) . 'assets/css/style.css');
    wp_enqueue_script('ls-main-js', plugin_dir_url(__FILE__) . 'assets/js/main.js', array('jquery'), null, true);
    wp_localize_script('ls-main-js', 'ls_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'), 
        'security' => wp_create_nonce('ls_calculate_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'ls_enqueue_scripts');

function ls_parking_reservation_form_shortcode() {
    ob_start(); ?>
<div id="ls-reservation-form">
    <form id="ls-reservation-form" action="" method="post">
        <?php wp_nonce_field('ls_calculate_nonce', 'ls_security'); ?>
        <label for="arrival">Data i Godzina przyjazdu *</label>
        <input type="datetime-local" id="arrival" name="arrival" required>
        <label for="departure">Data i Godzina wyjazdu *</label>
        <input type="datetime-local" id="departure" name="departure" required>
        <label for="first_name">Imię *</label>
        <input type="text" id="first_name" name="first_name" required>
        <label for="last_name">Nazwisko *</label>
        <input type="text" id="last_name" name="last_name" required>
        <label for="people_count">Liczba osób *</label>
        <input type="number" id="people_count" name="people_count" required min="1">
        <label for="email_address">Adres email *</label>
        <input type="email" id="email_address" name="email_address" required>
        <label for="parking_type">Typ parkingu:*</label>
        <select id="parking_type" name="parking_type" required>
            <option value="outside">Na zewnątrz</option>
            <option value="garage">W garażu</option>
        </select>
        <label for="car_plate">Nr rejestracyjny *</label>
        <input type="text" id="car_plate" name="car_plate" required>
        <label for="phone_number">Telefon*</label>
        <input type="tel" id="phone_number" name="phone_number" required>
        <label for="cadillac_transfer">Transfer zabytkowym Cadillaciem: <br />(usługa dodatkowo płatna na
            miejscu)</label>
        <select id="cadillac_transfer" name="cadillac_transfer" required>
            <option value="Nie">Nie</option>
            <option value="w 1 stronę">w 1 stronę</option>
            <option value="w 2 strony">w 2 strony</option>
        </select>
        <div class="consent">
            <input type="checkbox" id="data_processing_consent" name="data_processing_consent" required>
            <label for="data_processing_consent">Wyrażam zgodę na przetwarzanie danych i otrzymywanie informacji
                handlowych e-mail i sms*</label>
        </div>
        <input type="submit" value="Zarezerwuj">
    </form>
    <div id="ls-price-calculation"></div>
</div>
<?php
    return ob_get_clean();
}
add_shortcode('ls_parking_reservation_form', 'ls_parking_reservation_form_shortcode');

function ls_handle_ajax_calculate_and_add_to_cart() {
    check_ajax_referer('ls_calculate_nonce', 'security');

    // Pobieranie danych z formularza
    $arrival = strtotime($_POST['arrival']);
    $departure = strtotime($_POST['departure']);
    $parking_type = sanitize_text_field($_POST['parking_type']);
    $cadillac_transfer = sanitize_text_field($_POST['cadillac_transfer']);
    $datediff = max(round(($departure - $arrival) / (60 * 60 * 24)), 1);

    // Cennik
    $pricing = [
        'outside' => [99, 119, 139, 159, 179, 199, 219, 229, 229, 259, 259, 269, 279, 289, 299, 319, 329, 335, 340, 345, 350, 355, 360, 365, 370, 375, 380, 385, 390, 395, 395],
        'garage' => [199, 219, 239, 259, 279, 299, 319, 329, 329, 359, 359, 369, 379, 389, 399, 429, 429, 435, 440, 450, 455, 460, 465, 470, 480, 485, 490, 495, 500, 500, 500],
    ];

    // Dodatkowa opłata po 30 dniach
    $dailyPriceAfter30Days = [
        'outside' => 14,
        'garage' => 17,
    ];

    // Obliczanie ceny
    if ($datediff <= 30) {
        $calculated_price = $pricing[$parking_type][$datediff - 1];
    } else {
        $calculated_price = $pricing[$parking_type][29] + ($datediff - 30) * $dailyPriceAfter30Days[$parking_type];
    }

    $product_id = intval($_POST['product_id']);
    $cart_item_data = array(
        'ls_custom_data' => array(
            'arrival' => $_POST['arrival'],
            'departure' => $_POST['departure'],
            'first_name' => sanitize_text_field($_POST['first_name']),
            'last_name' => sanitize_text_field($_POST['last_name']),
            'people_count' => intval($_POST['people_count']),
            'email_address' => sanitize_email($_POST['email_address']),
            'parking_type' => $parking_type,
            'car_plate' => sanitize_text_field($_POST['car_plate']),
            'phone_number' => sanitize_text_field($_POST['phone_number']),
            'cadillac_transfer' => sanitize_text_field($_POST['cadillac_transfer']),
        ),
        'ls_custom_price' => $calculated_price,
    );

    $cart_item_key = WC()->cart->add_to_cart($product_id, 1, 0, array(), $cart_item_data);

    if ($cart_item_key) {
        wp_send_json_success(array('message' => 'Rezerwacja parkingu została dodana do Twojego koszyka.'));
    } else {
        wp_send_json_error(array('message' => 'Nie udało się dodać rezerwacji do koszyka.'));
    }

    wp_die();
}
add_action('wp_ajax_ls_calculate_and_add_to_cart', 'ls_handle_ajax_calculate_and_add_to_cart');
add_action('wp_ajax_nopriv_ls_calculate_and_add_to_cart', 'ls_handle_ajax_calculate_and_add_to_cart');

function ls_set_custom_cart_item_prices($cart) {
    if (is_admin() && !defined('DOING_AJAX')) return;

    foreach ($cart->get_cart() as $cart_item_key => $cart_item) {
        if (isset($cart_item['ls_custom_price'])) {
            $cart_item['data']->set_price($cart_item['ls_custom_price']);
        }
    }
}
add_action('woocommerce_before_calculate_totals', 'ls_set_custom_cart_item_prices');

function ls_add_order_item_meta($item, $cart_item_key, $values, $order) {
    if (isset($values['ls_custom_data'])) {
        // Mapa dla lepszego wyświetlania kluczy metadanych
        $labels = array(
            'arrival' => 'Przyjazd',
            'departure' => 'Wyjazd',
            'first_name' => 'Imię',
            'last_name' => 'Nazwisko',
            'people_count' => 'Liczba osób',
            'email_address' => 'Adres email',
            'parking_type' => 'Typ parkingu',
            'car_plate' => 'Nr rejestracyjny',
            'phone_number' => 'Telefon',
            'cadillac_transfer' => 'Transfer Cadillaciem',
            'data_processing_consent' => 'Zgoda na przetwarzanie danych',
        );

        foreach ($values['ls_custom_data'] as $key => $value) {
            // Sprawdzenie, czy istnieje bardziej czytelna etykieta dla klucza
            $label = isset($labels[$key]) ? $labels[$key] : $key;
            $item->add_meta_data(__($label, 'lepszastrona-rezerwacja-parking'), $value);
        }
    }
}
add_action('woocommerce_checkout_create_order_line_item', 'ls_add_order_item_meta', 10, 4);

function ls_woocommerce_header_add_to_cart_fragment($fragments) {
    ob_start();
    ?>
<a class="cart-contents" href="<?php echo wc_get_cart_url(); ?>" title="<?php _e('View your shopping cart'); ?>">
    <?php echo sprintf(_n('%d item', '%d items', WC()->cart->get_cart_contents_count(), 'lepszastrona-rezerwacja-parking'), WC()->cart->get_cart_contents_count()); ?>
    - <?php echo WC()->cart->get_cart_total(); ?>
</a>
<?php
    $fragments['a.cart-contents'] = ob_get_clean();
    return $fragments;
}
add_filter('woocommerce_add_to_cart_fragments', 'ls_woocommerce_header_add_to_cart_fragment');