// Sliders & Carousels

$(document).ready(() => {
  setTimeout(function () {
    $(".slick-slider").slick({
      dots: true,
      slidesToShow: 1,
      slidesToScroll: 1,
    });

    $(".slick-slider-3").slick({
      dots: true,
      slidesToShow: 1,
      slidesToScroll: 1,
    });

    $(".slick-slider-2").slick({
      className: "center",
      centerMode: true,
      infinite: true,
      centerPadding: "60px",
      slidesToShow: 3,
      speed: 500,
      dots: true,
    });

    $(".slick-slider-variable").slick({
      className: "slider variable-width",
      dots: true,
      infinite: true,
      centerMode: true,
      slidesToShow: 1,
      slidesToScroll: 1,
      variableWidth: true,
    });

    $(".slick-slider-responsive").slick({
      dots: true,
      infinite: false,
      speed: 500,
      slidesToShow: 4,
      slidesToScroll: 4,
      initialSlide: 0,
      responsive: [
        {
          breakpoint: 1024,
          settings: {
            slidesToShow: 3,
            slidesToScroll: 3,
            infinite: true,
            dots: true,
          },
        },
        {
          breakpoint: 600,
          settings: {
            slidesToShow: 2,
            slidesToScroll: 2,
            initialSlide: 2,
          },
        },
        {
          breakpoint: 480,
          settings: {
            slidesToShow: 1,
            slidesToScroll: 1,
          },
        },
      ],
    });

    $(".slick-slider-inverted").slick({
      infinite: true,
      slidesToShow: 1,
      speed: 500,
      dots: true,
      adaptiveHeight: true,
    });
  }, 2000);
});
