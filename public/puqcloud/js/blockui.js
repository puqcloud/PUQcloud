// BlockUI Loading

$(document).ready(() => {
    $.blockUI.defaults = {
        timeout: 2000,
        fadeIn: 200,
        fadeOut: 400,
    };

    $(".block-page-btn-example-1").click(function () {
        $.blockUI({ message: $(".body-block-example-1") });
    });

    $(".block-page-btn-example-2").click(function () {
        $.blockUI({ message: $(".body-block-example-2") });
    });

    $(".block-page-btn-example-3").click(function () {
        $.blockUI({ message: $(".body-block-example-3") });
    });

    $(".block-element-btn-example-1").click(function () {
        $(".element-block-example").block({
            message: $(
                '<div class="loader mx-auto">\n' +
                '                            <div class="ball-grid-pulse">\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                '                                <div class="bg-white"></div>\n' +
                "                            </div>\n" +
                "                        </div>"
            ),
        });
    });

    $(".block-element-btn-example-2").click(function () {
        $(".element-block-example").block({
            message: $(
                "" +
                '<div class="loader mx-auto">\n' +
                '                            <div class="line-scale-pulse-out">\n' +
                '                                <div class="bg-success"></div>\n' +
                '                                <div class="bg-success"></div>\n' +
                '                                <div class="bg-success"></div>\n' +
                '                                <div class="bg-success"></div>\n' +
                '                                <div class="bg-success"></div>\n' +
                "                            </div>\n" +
                "                        </div>"
            ),
        });
    });

    $(".block-element-btn-example-3").click(function () {
        $(".element-block-example").block({
            message: $(
                '<div class="loader mx-auto">\n' +
                '                            <div class="ball-pulse-sync">\n' +
                '                                <div class="bg-warning"></div>\n' +
                '                                <div class="bg-warning"></div>\n' +
                '                                <div class="bg-warning"></div>\n' +
                "                            </div>\n" +
                "                        </div>"
            ),
        });
    });
});
