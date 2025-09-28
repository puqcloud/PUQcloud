// SweetAlerts2

$(document).ready(() => {
  $(".btn-show-swal").each(function () {
    $(this).click(function () {
      var alertType = $(this).attr("data-type");

      Swal.fire({
        title: "Type: " + alertType,
        text: "Do you want to continue",
        type: alertType,
        confirmButtonText: "Cool",
      });
    });
  });

  $(".btn-show-swal-basic").click(function () {
    Swal.fire({
      text: "The Internet?",
      title: "That thing is still around?",
      type: "question",
    });
  });

  $(".btn-show-swal-basic-2").click(function () {
    Swal.fire({
      type: "error",
      title: "Oops...",
      text: "Something went wrong!",
      footer: "<a href>Why do I have this issue?</a>",
    });
  });

  $(".btn-show-swal-basic-3").click(function () {
    Swal.fire({
      title: "Custom animation with Animate.css",
      animation: false,
      customClass: "animated lightSpeedIn",
    });
  });
});
