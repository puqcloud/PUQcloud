<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta http-equiv="Content-Language" content="en">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, shrink-to-fit=no">

    <meta name="msapplication-tap-highlight" content="no">
    <title>@yield('title')</title>
    <link rel="icon" href="{{ asset_admin('images/favicon.png') }}" type="image/png">
    <link rel="stylesheet" href="{{ mix('/css/app.css') }}">
    <link rel="stylesheet" href="{{ asset_admin('styles/css/base.css') }}">
    @yield('head')
    <script>
        window.translations = {!! json_encode($js_translations) !!};
    </script>
</head>
<body>
<div class="app-container app-theme-white body-tabs-shadow">
    <div class="app-container">
        @yield('content')
    </div>
</div>

<script src="{{ mix('/js/app.js') }}"></script>
<script type="text/javascript" src="{{ asset_admin('js/app.js') }}"></script>
<script src="{{ route('admin.web.static.template.puqcloud.js') }}"></script>

<script>

    function validateLogin() {
        var form = $("#loginForm");
        form.data('validator', null);
        form.validate({
            rules: {
                email: {
                    required: true,
                    email: true,
                },
                password: {
                    required: true,
                    minlength: 5,
                },
            },
            messages: {
                email: translate("Please enter a valid email address"),
                password: {
                    required: translate("Please provide a password"),
                    minlength: translate("Your password must be at least 5 characters long"),
                },
            },
            errorElement: "em",
            errorPlacement: function (error, element) {
                error.addClass("invalid-feedback");
                if (element.prop("type") === "checkbox") {
                    error.insertAfter(element.next("label"));
                } else {
                    error.insertAfter(element);
                }
            },
            highlight: function (element, errorClass, validClass) {
                $(element).addClass("is-invalid").removeClass("is-valid");
            },
            unhighlight: function (element, errorClass, validClass) {
                $(element).removeClass("is-invalid").addClass("is-valid");
            }
        });
        form.validate().resetForm();

        return form.valid();
    }

    $(document).ready(function () {

        $("#login").on("click", function (event) {
            event.preventDefault();
            handleLogin();
        });

        $("#loginForm input").on("keypress", function (event) {
            if (event.which === 13) {
                event.preventDefault();
                handleLogin();
            }
        });

        function handleLogin() {
            if (!validateLogin()) {
                return;
            }
            var form = $("#loginForm");
            var url = '{{ route('admin.api.login') }}';
            var formData = form.serializeArray().reduce(function (obj, item) {
                obj[item.name] = item.value;
                return obj;
            }, {});
            var json = JSON.stringify(formData);

            login(url, json, 500, $("#login"));
        }
    });

    function login(url, json, timeOut, button) {
        var originalText = button.html();
        var loadingText = '<i class="fa fa-fw fa-spin"></i> ' + translate("Loading...");
        button.prop('disabled', true);
        button.html(loadingText);

        $.ajax({
            url: url,
            type: 'POST',
            contentType: 'application/json',
            data: json,
            success: function (response) {
                if (response.status === 'error') {
                    timeOut = timeOut * 10;
                    alert_error('Error', response.errors, timeOut);
                } else if (response.status === 'success') {
                    alert_success('Success', [response.message], timeOut);
                    window.location.href = '{{ route('admin.web.dashboard') }}';
                }
            },
            error: function (jqXHR) {
                timeOut = timeOut * 10;
                let response;
                var errors = [];

                try {
                    response = jqXHR.responseJSON || JSON.parse(jqXHR.responseText);
                } catch (e) {
                    response = {};
                }

                if (response.errors) {
                    errors = response.errors;
                }else{
                    errors = [translate('No response from the server. Try again later.')];
                }

                if (isJsonObject(response.message)) {
                    showFormErrors($("#loginForm"), response);
                }

                alert_error(translate('Error'), errors, timeOut);
            },
            complete: function () {
                button.prop('disabled', false);
                button.html(originalText);
            }
        });
    }

</script>
@yield('js')
</body>
</html>
