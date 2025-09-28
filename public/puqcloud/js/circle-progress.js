// Circle Progress

$(document).ready(() => {
  $(".circle-progress-primary")
    .circleProgress({
      value: 0.64,
      size: 52,
      lineCap: "round",
      fill: { color: "#3f6ad8" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-success")
    .circleProgress({
      value: 0.81,
      size: 52,
      lineCap: "round",
      fill: { color: "#3ac47d" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-danger")
    .circleProgress({
      value: 0.23,
      size: 52,
      lineCap: "round",
      fill: { color: "#d92550" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-info")
    .circleProgress({
      value: 0.69,
      size: 52,
      lineCap: "round",
      fill: { color: "#16aaff" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-alternate")
    .circleProgress({
      value: 0.69,
      size: 52,
      lineCap: "round",
      fill: { color: "#6f42c1" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-dark")
    .circleProgress({
      value: 0.69,
      size: 52,
      lineCap: "round",
      fill: { color: "#6c757d" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-warning")
    .circleProgress({
      value: 0.23,
      size: 52,
      lineCap: "round",
      fill: { color: "#fd7e14" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-success-xl")
    .circleProgress({
      value: 0.16,
      size: 114,
      lineCap: "round",
      fill: { color: "#3ac47d" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html(
          '<span class="fsize-2">' + stepValue.toFixed(2).substr(2) + "%<span>"
        );
    });

  $(".circle-progress-gradient-xl")
    .circleProgress({
      value: 0.51,
      size: 114,
      lineCap: "round",
      fill: { gradient: ["#fdb83a", "#fd7e14"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html(
          '<span class="fsize-2">' + stepValue.toFixed(2).substr(2) + "%<span>"
        );
    });

  $(".circle-progress-danger-xl")
    .circleProgress({
      value: 0.51,
      size: 114,
      lineCap: "round",
      fill: { gradient: ["#ff1e41", "#ff8130"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html(
          '<span class="fsize-2">' + stepValue.toFixed(2).substr(2) + "%<span>"
        );
    });

  $(".circle-progress-gradient")
    .circleProgress({
      value: 0.72,
      size: 52,
      lineCap: "round",
      fill: { gradient: ["#ff1e41", "#ff8130"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-gradient-lg")
    .circleProgress({
      value: 0.64,
      size: 64,
      lineCap: "round",
      fill: { gradient: ["#ff1e41", "#ff8130"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-gradient-alt-lg")
    .circleProgress({
      value: 0.58,
      size: 64,
      lineCap: "round",
      fill: { gradient: ["#007bff", "#16aaff"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "%<span>");
    });

  $(".circle-progress-gradient-alt-sm")
    .circleProgress({
      value: 0.58,
      size: 46,
      lineCap: "round",
      fill: { gradient: ["#007bff", "#16aaff"] },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "<span>");
    });

  $(".circle-progress-danger-sm")
    .circleProgress({
      value: 0.62,
      size: 46,
      lineCap: "round",
      fill: { color: "#d92550" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "<span>");
    });

  $(".circle-progress-warning-sm")
    .circleProgress({
      value: 0.72,
      size: 46,
      lineCap: "round",
      fill: { color: "#fd7e14" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "<span>");
    });

  $(".circle-progress-success-sm")
    .circleProgress({
      value: 0.81,
      size: 46,
      lineCap: "round",
      fill: { color: "#3ac47d" },
    })
    .on("circle-animation-progress", function (event, progress, stepValue) {
      $(this)
        .find("small")
        .html("<span>" + stepValue.toFixed(2).substr(2) + "<span>");
    });
});
