// jQuery("input[name='billing_particulier_of_bedrijf']").on(
//   "change",
//   function () {
//     var selectedValue = jQuery(this).val();
//     // console.log("selectedValue", selectedValue);

//     jQuery.ajax({
//       type: "POST",
//       url: ajax_object.ajax_url,
//       data: {
//         action: "my_get_settings_data",
//         settings_data: "Dummy Text",
//       },

//       success: function (response) {
//         var backend_data;

//         try {
//           backend_data = jQuery.parseJSON(response);
//         } catch (e) {
//           backend_data = response;
//         }

//         // console.log(backend_data.data);
//       },
//     });
//   }
// );

jQuery(document).ready(function ($) {
  $(document).on("click", "#check__updates", function (event) {
    event.preventDefault();

    var update_notice = $("#update_notice");
    var update_message = $("#update_message");

    $.ajax({
      type: "POST",
      url: ajax_object.ajax_url,
      data: {
        action: "update_plugin",
        settings_data: "Dummy Text",
      },
      beforeSend: function () {
        update_notice.addClass("updating-message");
        update_message.empty().text("Checking...");
      },
      success: function (response) {
        var { data } = response;

        if (response.success == true) {
          update_message.empty().append(createUpdateElements($, data));
        } else {
          update_message.text(data.message);
        }
      },
      error: function (error) {
        console.error("AJAX request failed:", error);
      },
      complete: function () {
        update_notice.removeClass("updating-message");
      },
    });
  });

  $(document).on("click", "#apply__updates", function (event) {
    event.preventDefault();

    var update_notice = $("#update_notice");
    var update_message = $("#update_message");

    $.ajax({
      type: "POST",
      url: ajax_object.ajax_url,
      data: {
        action: "apply_update",
        settings_data: "Dummy Text",
      },
      beforeSend: function () {
        update_notice.addClass("updating-message");
        update_message.empty().text("Updating...");
      },
      success: function (response) {
        var { data } = response;

        console.log("response", response);

        if (response.success == true) {
          update_notice.addClass("notice-success updated-message");
          update_message.empty().text("Updated!");
        } else {
          update_message.empty().text(data.message);
          update_notice.addClass("notice-error");
        }
      },
      error: function (error) {
        console.error("AJAX request failed:", error);
        update_notice.addClass("notice-error");
      },
      complete: function () {
        update_notice.removeClass("updating-message notice-warning");
      },
    });
  });

  function createUpdateElements($, data) {
    var spanElement = $("<span>").attr("id", "update_text").text(data.message);

    var anchorElement = $("<span>")
      .attr("id", "apply__updates")
      .text("Update now.")
      .addClass("link");

    return $(spanElement).add(anchorElement);
  }
});
