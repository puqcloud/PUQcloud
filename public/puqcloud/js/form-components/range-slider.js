// Forms Rangeslider

$(document).ready(() => {
  var sliderRange1 = document.getElementById("rangeslider-1");
  var sliderRange2 = document.getElementById("rangeslider-2");
  var sliderRange3 = document.getElementById("rangeslider-3");
  var sliderRange4 = document.getElementById("rangeslider-4");

  var sliderRange11 = document.getElementById("rangeslider-11");
  var sliderRange21 = document.getElementById("rangeslider-21");
  var sliderRange31 = document.getElementById("rangeslider-31");
  var sliderRange41 = document.getElementById("rangeslider-41");

  var range_all_sliders = {
    min: [0],
    "10%": [500, 500],
    "50%": [4000, 1000],
    max: [10000],
  };

  var pipsRange = document.getElementById("pips-steps");

  if (sliderRange1) {
    noUiSlider.create(sliderRange1, {
      start: [20, 80],
      connect: true,
      range: {
        min: 0,
        max: 100,
      },
    });
  }

  if (sliderRange2) {
    noUiSlider.create(sliderRange2, {
      start: [20, 40, 60],
      connect: [true, false, true, true],
      range: {
        min: 0,
        max: 80,
      },
    });
  }

  if (sliderRange3) {
    noUiSlider.create(sliderRange3, {
      start: [20, 80],
      step: 10,
      range: {
        min: 0,
        max: 100,
      },
    });
  }

  if (sliderRange4) {
    noUiSlider.create(sliderRange4, {
      start: [20, 80, 120],
      tooltips: [false, wNumb({ decimals: 1 }), true],
      range: {
        min: 0,
        max: 200,
      },
    });
  }

  if (sliderRange11) {
    noUiSlider.create(sliderRange11, {
      start: [20, 80],
      connect: true,
      range: {
        min: 0,
        max: 100,
      },
    });
  }

  if (sliderRange21) {
    noUiSlider.create(sliderRange21, {
      start: [20, 40, 60],
      connect: [true, false, true, true],
      range: {
        min: 0,
        max: 80,
      },
    });
  }

  if (sliderRange31) {
    noUiSlider.create(sliderRange31, {
      start: [20, 80],
      step: 10,
      connect: true,
      range: {
        min: 0,
        max: 100,
      },
    });
  }

  if (sliderRange41) {
    noUiSlider.create(sliderRange41, {
      start: [20, 80, 120],
      connect: true,
      tooltips: [false, wNumb({ decimals: 1 }), true],
      range: {
        min: 0,
        max: 200,
      },
    });
  }

  if (pipsRange) {
    noUiSlider.create(pipsRange, {
      range: range_all_sliders,
      start: 0,
      pips: {
        mode: "range",
        density: 3,
      },
    });
  }
});
