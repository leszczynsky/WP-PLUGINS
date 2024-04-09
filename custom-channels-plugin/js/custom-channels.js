jQuery(document).ready(function ($) {
  // AJAX request to load channels based on the selected package
  function loadChannels(package) {
    $.ajax({
      type: "POST",
      url: customChannels.ajaxurl,
      data: {
        action: "load_channels",
        package: package,
      },
      success: function (response) {
        $("#channels-container").html(response);
      },
      error: function (error) {
        console.log(error);
      },
    });
  }

  // Package selection click event
  $(".package-button").on("click", function () {
    $(".package-button").removeClass("active");
    $(this).addClass("active");

    var selectedPackage = $(this).data("package");
    loadChannels(selectedPackage);
  });

  // Initially load channels for the default package (Economy)
  loadChannels("economy");

  // Automatically select and load channels for the "economy" package on page load
  $(document).ready(function () {
    $('.package-button[data-package="economy"]').trigger("click");
  });
});
