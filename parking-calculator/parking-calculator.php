<?php
/**
 * Plugin Name: Kalkulator Kosztów Parkingu
 * Plugin URI: https://lepszastrona.net/kalkulator-kosztow-parkingu
 * Description: Dynamiczny kalkulator kosztów parkingu z opcją wyboru miejsca parkowania.
 * Version: 1.0
 * Author: LepszaStrona.net
 * Author URI: https://lepszastrona.net
 */

function parking_calculator_enqueue_scripts() {
    wp_enqueue_script('parking-calculator-js', plugin_dir_url(__FILE__) . 'parking-calculator.js', array('jquery'), null, true);
    wp_enqueue_style('parking-calculator-css', plugin_dir_url(__FILE__) . 'parking-calculator.css');
}

add_action('wp_enqueue_scripts', 'parking_calculator_enqueue_scripts');

function parking_calculator_shortcode() {
    ob_start();
    ?>
<div id="parking-calculator">
    <label for="arrival-date">Data przyjazdu</label>
    <input type="date" id="arrival-date" name="arrival-date" required placeholder="Wybierz datę">

    <label for="departure-date">Data wyjazdu</label>
    <input type="date" id="departure-date" name="departure-date" required placeholder="Wybierz datę">

    <label for="parking-type">Typ parkingu</label>
    <select id="parking-type" name="parking-type">
        <option value="outside">Na zewnątrz</option>
        <option value="garage">W garażu</option>
    </select>

    <div id="calculated-price">Cena:</div>
    <button id="reserve-button" class="button-reserve" href="#rezerwacja">Zarezerwuj!</button>
</div>
<?php
    return ob_get_clean();
}

add_shortcode('kalkulator_parkingu', 'parking_calculator_shortcode');