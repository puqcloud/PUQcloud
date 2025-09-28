// Form Wizard

$(document).ready(() => {
  setTimeout(function () {
    $("#smartwizard").smartWizard({
      selected: 0,
      transitionEffect: "fade",
      toolbarSettings: {
        toolbarPosition: "none",
      },
    });

    // External Button Events
    $("#reset-btn").on("click", function () {
      // Reset wizard
      $("#smartwizard").smartWizard("reset");
      return true;
    });

    $("#prev-btn").on("click", function () {
      // Navigate previous
      $("#smartwizard").smartWizard("prev");
      return true;
    });

    $("#next-btn").on("click", function () {
      // Navigate next
      $("#smartwizard").smartWizard("next");
      return true;
    });

    $("#smartwizard2").smartWizard({
      selected: 0,
      transitionEffect: "slide",
      toolbarSettings: {
        toolbarPosition: "none",
      },
    });

    // External Button Events
    $("#reset-btn2").on("click", function () {
      // Reset wizard
      $("#smartwizard2").smartWizard("reset");
      return true;
    });

    $("#prev-btn2").on("click", function () {
      // Navigate previous
      $("#smartwizard2").smartWizard("prev");
      return true;
    });

    $("#next-btn2").on("click", function () {
      // Navigate next
      $("#smartwizard2").smartWizard("next");
      return true;
    });

    $("#smartwizard3").smartWizard({
      selected: 0,
      transitionEffect: "fade",
      toolbarSettings: {
        toolbarPosition: "none",
      },
      anchorSettings: {
        enableAllAnchors: true, // Activates all anchors clickable all times
    },
    });

    // External Button Events
    $("#reset-btn22").on("click", function () {
      // Reset wizard
      $("#smartwizard3").smartWizard("reset");
      return true;
    });

    $("#prev-btn22").on("click", function () {
      // Navigate previous
      $("#smartwizard3").smartWizard("prev");
      return true;
    });

    $("#next-btn22").on("click", function () {
      // Navigate next
      $("#smartwizard3").smartWizard("next");
      return true;
    });
  }, 2000);
});
