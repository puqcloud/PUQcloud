// Forms Multi Select

$(document).ready(() => {
  setTimeout(function () {
    $(".multiselect-dropdown").select2({
      theme: "bootstrap4",
      placeholder: "Select an option",
    });

    $("#example-single").multiselect({
      inheritClass: true,
    });

    $("#example-multi").multiselect({
      inheritClass: true,
    });

    $("#example-multi-check").multiselect({
      inheritClass: true,
    });
  }, 2000);
});
