// Image Cropper

$(document).ready(() => {
  setTimeout(function () {
    var $image = $("#image-crop"),
      height = $image.height() + 4;

    $(".preview").css({
      width: "100%", //width,  sets the starting size to the same as orig image
      overflow: "hidden",
      height: height,
      maxWidth: $image.width(),
      maxHeight: height,
    });

    $image.cropper({
      preview: ".preview",
      ready: function (e) {
        $(this).cropper("setData", {
          height: 222,
          rotate: 0,
          scaleX: 1,
          scaleY: 1,
          width: 222,
          x: 55,
          y: 19,
        });
      },
    });
  }, 2000);
});
