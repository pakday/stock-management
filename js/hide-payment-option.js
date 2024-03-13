jQuery("input[name='billing_particulier_of_bedrijf']").on(
  "change",
  function () {
    var selectedValue = jQuery(this).val();
    // console.log("selectedValue", selectedValue);

    jQuery.ajax({
      type: "POST",
      url: my_ajax_object.ajax_url,
      data: {
        action: "my_get_settings_data",
        settings_data: "Dummy Text",
      },

      success: function (response) {
        var backend_data;

        try {
          backend_data = jQuery.parseJSON(response);
        } catch (e) {
          backend_data = response;
        }

        // console.log(backend_data.data);
      },
    });
  }
);
