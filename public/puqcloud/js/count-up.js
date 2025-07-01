$(document).ready(() => {
  const optionsCountUp = {
    useEasing: true,
    separator: ",",
    decimal: ".",
    prefix: "$",
  };

  const optionsCountUp2 = {
    useEasing: true,
    separator: ",",
    decimal: ".",
    prefix: "",
    suffix: "k",
  };

  const optionsCountUp3 = {
    useEasing: true,
    separator: ",",
    decimal: ".",
    prefix: "",
    suffix: " %",
  };

  const optionsCountUp4 = {
    useEasing: true,
    separator: ",",
    decimal: ".",
    prefix: "",
    suffix: " ",
  };

  const minNumber = 0;
  const maxNumber = 983;
  const maxNumber2 = 97;
  const maxNumber3 = 187;

  const randomNumber = randomNumberFromRange(minNumber, maxNumber);
  const randomNumber2 = randomNumberFromRange(minNumber, maxNumber2);
  const randomNumber3 = randomNumberFromRange(minNumber, maxNumber3);

  function randomNumberFromRange(min, max) {
    return Math.floor(Math.random() * (max - min + 1) + min);
  }

  $(".count-up-wrapper").each(function () {
    const numAnim = new CountUp(
      $(this)[0],
      0,
      randomNumber,
      0,
      8,
      optionsCountUp
    );
    if (!numAnim.error) {
      numAnim.start();
    }
  });

  $(".count-up-wrapper-2").each(function () {
    const numAnim = new CountUp(
      $(this)[0],
      0,
      randomNumber2,
      0,
      15,
      optionsCountUp2
    );
    if (!numAnim.error) {
      numAnim.start();
    }
  });

  $(".count-up-wrapper-3").each(function () {
    const numAnim = new CountUp(
      $(this)[0],
      0,
      randomNumber3,
      0,
      12,
      optionsCountUp3
    );
    if (!numAnim.error) {
      numAnim.start();
    }
  });

  $(".count-up-wrapper-4").each(function () {
    const numAnim = new CountUp(
      $(this)[0],
      0,
      randomNumber,
      0,
      12,
      optionsCountUp4
    );
    if (!numAnim.error) {
      numAnim.start();
    }
  });
});
