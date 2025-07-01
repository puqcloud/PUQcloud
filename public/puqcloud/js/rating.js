// Rating

$(document).ready(() => {
  $(function () {
    $("#bars-horizontal").barrating({
      theme: "bars-horizontal",
    });
  });

  $(function () {
    $("#bars-movie").barrating({
      theme: "bars-movie",
      showValues: false,
    });
  });

  $(function () {
    $("#bars-pill").barrating({
      theme: "bars-pill",
      initialRating: "A",
      showValues: true,
      showSelectedRating: false,
      allowEmpty: true,
      emptyValue: "-- no rating selected --",
      onSelect: function (value, text) {
        alert("Selected rating: " + value);
      },
    });
  });

  $(function () {
    $("#css-stars").barrating({
      theme: "css-stars",
    });
  });

  $(function () {
    $("#bars-1to10").barrating({
      theme: "bars-1to10",
    });
  });
});
