jQuery(document).ready(function ($) {
  function calculatePrice() {
    var arrivalDate = $("#arrival-date").val();
    var departureDate = $("#departure-date").val();
    var parkingType = $("#parking-type").val();
    if (arrivalDate && departureDate) {
      var startDate = new Date(arrivalDate);
      var endDate = new Date(departureDate);
      var diffTime = Math.abs(endDate - startDate);
      var diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
      var price = calculateParkingPrice(diffDays, parkingType);
      $("#calculated-price").text("Cena: " + price.toFixed(2) + " zł");
    }
  }

  function calculateParkingPrice(days, parkingType) {
    // Ceny dla parkowania na zewnątrz
    var priceListOutside = [
      99, 119, 139, 159, 179, 199, 219, 229, 229, 259, 259, 269, 279, 289, 299,
      319, 329, 335, 340, 345, 350, 355, 360, 365, 370, 375, 380, 385, 390, 395,
      395,
    ];

    // Ceny dla parkowania w garażu - przykładowe, do wypełnienia właściwymi wartościami
    var priceListGarage = [
      199, 219, 239, 259, 279, 299, 319, 329, 329, 359, 359, 369, 379, 389, 399,
      429, 429, 435, 440, 450, 455, 460, 465, 470, 480, 485, 490, 495, 500, 500,
    ];

    // Doliczanie dodatkowej opłaty po 30 dniach
    var dailyPriceAfter30DaysOutside = 14;
    var dailyPriceAfter30DaysGarage = 17;

    // Wybór odpowiedniej listy cen i dodatkowej opłaty na podstawie wybranego typu parkingu
    var priceList =
      parkingType === "garage" ? priceListGarage : priceListOutside;
    var dailyPriceAfter30Days =
      parkingType === "garage"
        ? dailyPriceAfter30DaysGarage
        : dailyPriceAfter30DaysOutside;

    // Obliczenie ceny
    if (days <= 30) {
      return priceList[days - 1];
    } else {
      return priceList[29] + (days - 30) * dailyPriceAfter30Days;
    }
  }

  $("#arrival-date, #departure-date, #parking-type").on(
    "change",
    calculatePrice
  );
});
