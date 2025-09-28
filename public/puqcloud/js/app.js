$(document).ready(() => {
    // Sidebar Menu

    setTimeout(function () {
        $(".vertical-nav-menu").metisMenu();
    }, 100);

    // Search wrapper trigger

    $(".search-icon").click(function () {
        $(this).parent().parent().addClass("active");
    });

    $(".search-wrapper .btn-close").click(function () {
        $(this).parent().removeClass("active");
    });

    // BS4 Popover

    $('[data-toggle="popover-custom-content"]').each(function (i, obj) {
        $(this).popover({
            html: true,
            placement: "auto",
            template:
                '<div class="popover popover-custom" role="tooltip"><div class="arrow"></div><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
            content: function () {
                var id = $(this).attr("popover-id");
                return $("#popover-content-" + id).html();
            },
        });
    });

    // Stop Bootstrap 4 Dropdown for closing on click inside

    $(".dropdown-menu").on("click", function (event) {
        var events = $._data(document, "events") || {};
        events = events.click || [];
        for (var i = 0; i < events.length; i++) {
            if (events[i].selector) {
                if ($(event.target).is(events[i].selector)) {
                    events[i].handler.call(event.target, event);
                }

                $(event.target)
                    .parents(events[i].selector)
                    .each(function () {
                        events[i].handler.call(this, event);
                    });
            }
        }
        event.stopPropagation(); //Always stop propagation
    });

    $('[data-toggle="popover-custom-bg"]').each(function (i, obj) {
        var popClass = $(this).attr("data-bg-class");

        $(this).popover({
            trigger: "focus",
            placement: "top",
            template:
                '<div class="popover popover-bg ' +
                popClass +
                '" role="tooltip"><h3 class="popover-header"></h3><div class="popover-body"></div></div>',
        });
    });

    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl)
    })

    $('[data-toggle="popover-custom"]').each(function (i, obj) {
        $(this).popover({
            html: true,
            container: $(this).parent().find(".rm-max-width"),
            content: function () {
                return $(this)
                    .next(".rm-max-width")
                    .find(".popover-custom-content")
                    .html();
            },
        });
    });

    $("body").on("click", function (e) {
        $('[rel="popover-focus"]').each(function () {
            if (
                !$(this).is(e.target) &&
                $(this).has(e.target).length === 0 &&
                $(".popover").has(e.target).length === 0
            ) {
                $(this).popover("hide");
            }
        });
    });

    $(".header-megamenu.nav > li > .nav-link").on("click", function (e) {
        $('[data-toggle="popover-custom"]').each(function () {
            if (
                !$(this).is(e.target) &&
                $(this).has(e.target).length === 0 &&
                $(".popover").has(e.target).length === 0
            ) {
                $(this).popover("hide");
            }
        });
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
        new Tooltip(el);
    });

    document.querySelectorAll('[data-bs-toggle="tooltip-light"]').forEach(el => {
        new Tooltip(el, {
            template: '<div class="tooltip tooltip-light"><div class="tooltip-inner"></div></div>'
        });
    });

    // Drawer

    $(".open-right-drawer").click(function () {
        $(this).addClass("is-active");
        $(".app-drawer-wrapper").addClass("drawer-open");
        $(".app-drawer-overlay").removeClass("d-none");
    });

    $(".drawer-nav-btn").click(function () {
        $(".app-drawer-wrapper").removeClass("drawer-open");
        $(".app-drawer-overlay").addClass("d-none");
        $(".open-right-drawer").removeClass("is-active");
    });

    $(".app-drawer-overlay").click(function () {
        $(this).addClass("d-none");
        $(".app-drawer-wrapper").removeClass("drawer-open");
        $(".open-right-drawer").removeClass("is-active");
    });

    $(".mobile-toggle-nav").click(function () {
        $(this).toggleClass("is-active");
        $(".app-container").toggleClass("sidebar-mobile-open");
    });

    $(".mobile-toggle-header-nav").click(function () {
        $(this).toggleClass("active");
        $(".app-header__content").toggleClass("header-mobile-open");
    });

    $(".mobile-app-menu-btn").click(function () {
        $(".hamburger", this).toggleClass("is-active");
        $(".app-inner-layout").toggleClass("open-mobile-menu");
    });

    // Responsive

    var container = $(".app-container");
    var resizeClass = function () {
        var win = document.body.clientWidth;
        if (win < 1250) {
            container.addClass("closed-sidebar-mobile closed-sidebar");
        } else {
            container.removeClass("closed-sidebar-mobile closed-sidebar");
        }
    };


    $(window).on("resize", function () {
        resizeClass();
    });

    resizeClass();
});
