<?php

function enqueue_konfigurator_assets()
{
    // Zarejestruj i dołącz styl
    wp_register_style('konfigurator-style', '/wp-content/plugins/konfigurator-fastar/assets/css/konfigurator.css', array(), '1.0', 'all');
    wp_enqueue_style('konfigurator-style');

    // Zarejestruj i dołącz skrypt JavaScript zależny od jQuery
    wp_register_script('konfigurator-script', '/wp-content/plugins/konfigurator-fastar/assets/js/konfigurator.js', array('jquery'), '1.0', true);
    wp_enqueue_script('konfigurator-script');
}

add_action('wp_enqueue_scripts', 'enqueue_konfigurator_assets');



function display_usluga_posts_by_taxonomy_shortcode()
{
    ob_start(); // Rozpocznij buforowanie wyjścia
?>
<div class="konfigurator-wrapper">
    <div class="konfigurator-column">
        <?php
            // Pobierz wszystkie termy dla taxonomii
            $terms = get_terms(array(
                'taxonomy' => 'rodzaj-uslugi',
                'hide_empty' => true,
            ));

            if ($terms && !is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $args = array(
                        'post_type' => 'usluga',
                        'posts_per_page' => -1,
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'rodzaj-uslugi',
                                'field' => 'term_id',
                                'terms' => $term->term_id,
                            ),
                        ),
                    );

                    $query = new WP_Query($args);

                    // Sprawdzanie, czy taksonomia ma opcję "multiple"
                    $lista_kanalow = get_term_meta($term->term_id, 'lista_kanalow', true);
                    $multiple = get_term_meta($term->term_id, 'multiple', true);
                    $input_type = $multiple ? 'checkbox' : 'radio';

            ?>

        <div class="wrapper">
            <div class="title container">
                <strong class="term-title"><?php echo $term->name; ?></strong>
                <?php if (!empty($lista_kanalow)) : ?>
                <a href="<?php echo $lista_kanalow; ?>"><strong>Lista kanałów</strong></a>
                <?php endif; ?>
            </div>
            <div class="usluga-block-container">
                <?php if ($query->have_posts()) : ?>
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                <?php
                                    $cena_miesieczna_uslugi = get_post_meta(get_the_ID(), 'cena_miesieczna_uslugi', true);
                                    $cena_aktywacji_uslugi = get_post_meta(get_the_ID(), 'cena_aktywacji_uslugi', true);
                                    $cena_miesieczna_uslugi_2 = get_post_meta(get_the_ID(), 'cena_miesieczna_uslugi_2', true);
                                    $cena_aktywacji_uslugi_2 = get_post_meta(get_the_ID(), 'cena_aktywacji_uslgui_2', true);
                                    $zaznaczony = get_post_meta(get_the_ID(), 'zaznaczony', true);
                                    $ustaw_ceny_2 = get_post_meta(get_the_ID(), 'ustaw_ceny_2', true);
                                    $pakiet_terms = get_the_terms(get_the_ID(), 'pakiet');
                                    $pakiet_slugs = $pakiet_terms && !is_wp_error($pakiet_terms) ? implode(',', wp_list_pluck($pakiet_terms, 'slug')) : '';
                                    ?>

                <div class="usluga-block" data-input-type="<?php echo $input_type; ?>"
                    data-rodzaj="<?php echo esc_attr($term->name); ?>" data-tytul="<?php the_title(); ?>"
                    data-cena-uslugi="<?php echo esc_attr($cena_miesieczna_uslugi); ?>"
                    data-cena-aktywacji="<?php echo esc_attr($cena_aktywacji_uslugi); ?>"
                    data-pakiet="<?php echo esc_attr($pakiet_slugs); ?>"
                    data-cena-uslugi-2="<?php echo esc_attr($cena_miesieczna_uslugi_2); ?>"
                    data-cena-aktywacji-2="<?php echo esc_attr($cena_aktywacji_uslugi_2); ?>"
                    data-cena-uslugi-3="<?php echo esc_attr($cena_miesieczna_uslugi); ?>"
                    data-cena-aktywacji-3="<?php echo esc_attr($cena_aktywacji_uslugi); ?>"
                    data-ustaw-ceny-2="<?php echo esc_attr($ustaw_ceny_2); ?>">
                    <input id="input_<?php echo get_the_ID(); ?>" name="usluga_choice_<?php echo $term->term_id; ?>"
                        type="<?php echo $input_type; ?>" class="hidden"
                        <?php if ($zaznaczony == '1') echo 'checked'; ?>>
                    <label for="input_<?php echo get_the_ID(); ?>">
                        <span class="ulsuga-title"><strong><?php the_title(); ?></strong></span>
                        <div class="gradient"></div>
                        <?php if (!empty($cena_miesieczna_uslugi)) : ?>
                        <span><strong class="cena-miesiczna-uslugi"><?php echo $cena_miesieczna_uslugi; ?>
                                zł</strong><span>/miesięcznie</span></span>
                        <?php endif; ?>
                        <?php if (!empty($cena_aktywacji_uslugi)) : ?>
                        <span>aktywacja od <strong class="cena-aktywacyjna-uslugi"><?php echo $cena_aktywacji_uslugi; ?>
                                zł</strong></span>
                        <?php endif; ?>
                    </label>
                </div>

                <?php endwhile; ?>
            </div>
            <?php else : ?>
            <div>Brak wpisów w kategorii <?php echo $term->name; ?></div>
            <?php endif; ?>
        </div>
        <?php
                }
            }

            // Resetowanie zapytania
            wp_reset_postdata();
            ?>
    </div>
    <div class="formularz-column">
        <div class="sticky">
            <strong>Podsumowanie</strong>
            <div class="podsumowanie"></div>
            <div class="suma"><strong>Suma:</strong>
                <p style="margin: 0px;"></p>
                <div><span class="suma_miesiecznie"><strong>0.00zł</strong>/miesięcznie</span><br
                        style="display: none;"><span class="suma_aktywacyjna">aktywacja jednorazowa
                        <strong>0.00zł</strong></span></div>
            </div>
            <strong>Wyślij zgłoszenie</strong>
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <input type="hidden" name="action" value="send_konfigurator_form">
                <input type="text" name="imie" placeholder="Imię" required />
                <input type="text" name="nazwisko" placeholder="Nazwisko" required />
                <input type="text" name="kod-pocztowy" placeholder="Kod pocztowy" required />
                <input type="text" name="miasto" placeholder="Miasto" required />
                <input type="text" name="ulica" placeholder="Ulica" required />
                <input type="text" name="numer-domu" placeholder="Numer domu" required />
                <input type="text" name="numer-telefonu" placeholder="Numer telefonu" required />
                <input type="email" name="email" placeholder="Email" required />
                <input type="text" name="kod_rabatowy" placeholder="Kod rabatowy" />
                <label>
                    <input type="checkbox" name="zgoda-danych" required />
                    <strong>Wyrażam zgodę na przetwarzanie moich danych osobowych*</strong>
                </label>
                <input type="submit" value="Wyślij" />
            </form>
        </div>
    </div>

</div>
<?php
    return ob_get_clean(); // Pobierz buforowane wyjście i wyczyść bufor
}

add_shortcode('display_usluga_by_taxonomy', 'display_usluga_posts_by_taxonomy_shortcode');


function handle_konfigurator_form()
{
    // Gather post data
    $imie = sanitize_text_field($_POST['imie']);
    $nazwisko = sanitize_text_field($_POST['nazwisko']);
    $kod_pocztowy = sanitize_text_field($_POST['kod-pocztowy']);
    $miasto = sanitize_text_field($_POST['miasto']);
    $ulica = sanitize_text_field($_POST['ulica']);
    $numer_domu = sanitize_text_field($_POST['numer-domu']);
    $numer_telefonu = sanitize_text_field($_POST['numer-telefonu']);
    $email = sanitize_email($_POST['email']);
    $kod_rabatowy = sanitize_text_field($_POST['kod_rabatowy']);


    // Pobierz zawartość diva .podsumowanie
    $podsumowanie_html = isset($_POST['podsumowanie']) ? $_POST['podsumowanie'] : 'Brak podsumowania';
    $suma_html = isset($_POST['suma']) ? $_POST['suma'] : 'Brak sumy';

    // Tu dodaj kod do obsługi wysyłki email
    $to = 'formularz@fastar.pl';
    $subject = 'Nowe zgłoszenie z konfiguratora';
    $headers = 'From: Fastar <konfigurator@fastarswiatlowod.pl>' . "\r\n";
    $headers .= 'Content-Type: text/html; charset=utf-8'; // Ustawienie Content-Type na text/html
    $message = "<html><body>";
    $message .= "<strong>Dane klienta:</strong>";
    $message .= "<p></p>";
    $message .= "<div><strong>Imię:</strong> {$imie}</div>";
    $message .= "<div><strong>Nazwisko:</strong> {$nazwisko}</div>";
    $message .= "<div><strong>Kod pocztowy:</strong> {$kod_pocztowy}</div>";
    $message .= "<div><strong>Miasto:</strong> {$miasto}</div>";
    $message .= "<div><strong>Ulica:</strong> {$ulica}</div>";
    $message .= "<div><strong>Numer domu:</strong> {$numer_domu}</div>";
    $message .= "<div><strong>Numer telefonu:</strong> {$numer_telefonu}</div>";
    $message .= "<div><strong>Email:</strong> {$email}</div>";
    $message .= "<div><strong>Kod rabatowy:</strong> {$kod_rabatowy}</div>";
    $message .= "<p></p>";
    $message .= "<strong>Usługi wybrane przez klienta:</strong>";
    $message .= "<p></p>";
    $message .= $podsumowanie_html;
    $message .= $suma_html;
    $message .= "</body></html>";

    mail($to, $subject, $message, $headers);

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        // Obsługa wysłania formularza
        // ...
    
        // Po zakończeniu przetwarzania formularza przekieruj na '/dziekujemy'
        wp_redirect(home_url('/dziekujemy'));
        exit; // Zawsze wywołaj exit po wp_redirect
    }
    
}



add_action('admin_post_nopriv_send_konfigurator_form', 'handle_konfigurator_form');
add_action('admin_post_send_konfigurator_form', 'handle_konfigurator_form');