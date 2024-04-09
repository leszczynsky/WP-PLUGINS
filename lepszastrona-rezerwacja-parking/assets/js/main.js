jQuery(document).ready(function ($) {
  $("#ls-reservation-form form").on("submit", function (e) {
    e.preventDefault();

    var form = $(this),
      formData = {
        action: "ls_calculate_and_add_to_cart",
        security: form.find("#ls_security").val(),
        product_id: 118, // Upewnij się, że to jest prawidłowe ID produktu.
        arrival: form.find("#arrival").val(),
        departure: form.find("#departure").val(),
        first_name: form.find("#first_name").val(),
        last_name: form.find("#last_name").val(),
        people_count: form.find("#people_count").val(),
        email_address: form.find("#email_address").val(),
        parking_type: form.find("#parking_type").val(),
        car_plate: form.find("#car_plate").val(),
        phone_number: form.find("#phone_number").val(),
        cadillac_transfer: form.find("#cadillac_transfer").val(),
        data_processing_consent: form
          .find("#data_processing_consent")
          .is(":checked")
          ? "Yes"
          : "No", // Zgoda na przetwarzanie danych
      };

    $.ajax({
      url: ls_ajax.ajax_url,
      type: "post",
      data: formData,
      beforeSend: function () {
        form
          .find('input[type="submit"]')
          .val("Rezerwowanie...")
          .prop("disabled", true);
      },
      success: function (response) {
        if (response.success) {
          // Opcjonalnie: przekieruj do koszyka lub wyświetl komunikat o sukcesie
          alert("Rezerwacja parkingu została dodana do koszyka.");
          window.location.href = "/koszyk/"; // Przykład przekierowania do strony koszyka
        } else {
          // Pokaż błąd, jeśli operacja nie powiedzie się
          $("#ls-price-calculation")
            .text(
              response.data.message ||
                "Nie udało się dodać rezerwacji do koszyka."
            )
            .show();
        }
      },
      error: function () {
        $("#ls-price-calculation")
          .text("Wystąpił błąd. Spróbuj ponownie.")
          .show();
      },
      complete: function () {
        form
          .find('input[type="submit"]')
          .val("Zarezerwuj")
          .prop("disabled", false);
      },
    });
  });
});
