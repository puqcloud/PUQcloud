@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <div class="app-page-title app-page-title-simple">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div>
                    <div class="page-title-head center-elem">
                                            <span class="d-inline-block pe-2">
                                                <i class="fas fa-tasks"></i>
                                            </span>
                        <span class="d-inline-block">{{__('main.General')}}</span>
                    </div>
                    <div class="page-title-subheading opacity-10">
                        <nav class="" aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item">
                                    <a>
                                        <i aria-hidden="true" class="fa fa-home"></i>
                                    </a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="{{route('admin.web.dashboard')}}">{{ __('main.Dashboard') }}</a>
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.Settings')}}
                                </li>
                                <li class="active breadcrumb-item" aria-current="page">
                                    {{__('main.General')}}
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>
            </div>
            <div class="page-title-actions">
                @if($admin->hasPermission('general-settings-management'))
                    <button id="save" type="button"
                            class="mb-2 me-2 btn-icon btn-outline-2x btn btn-outline-success">
                        <i class="fa fa-save"></i> {{__('main.Save')}}
                    </button>
                @endif
            </div>
        </div>
    </div>

    <div class="main-card mb-3 card">

        <div class="card-header card-header-tab-animation">
            <ul class="nav nav-justified">
                @foreach ($settings as $group => $options)
                    <li class="nav-item">
                        <a data-bs-toggle="tab" href="#tab-{{ $group }}"
                           class="nav-link {{ $loop->first ? 'active' : '' }}"
                           data-group="{{ $group }}"
                        >{{ __('main.'.$group) }}</a>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="card-body">
            <div class="tab-content ">
                @foreach ($settings as $group => $options)
                    <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="tab-{{ $group }}" role="tabpanel">
                        <form method="POST" id="form-{{ $group }}">

                            <div class="row mb-3">
                                @foreach ($options as $name => $option)

                                    @if ($option['type'] === 'text' || $option['type'] === 'number' )

                                        <div class="{{ $option['class'] }}">
                                            <div class="position-relative mb-3">
                                                <label for="{{ $name }}"
                                                       class="form-label">{{ $option['label'] }}</label>
                                                <input name="{{ $name }}" id="{{ $name }}"
                                                       type="{{ $option['type'] }}" class="form-control">
                                                <small
                                                    class="form-text text-muted">{{ $option['description'] }}</small>
                                            </div>
                                        </div>

                                    @elseif ($option['type'] === 'textarea')

                                        <div class="{{ $option['class'] }}">
                                            <label for="{{ $name }}"
                                                   class="form-label">{{ $option['label'] }}</label>
                                            <textarea
                                                name="{{ $name }}"
                                                id="{{ $name }}"
                                                class="form-control"></textarea>
                                            <small
                                                class="form-text text-muted">{{ $option['description'] }}</small>
                                        </div>

                                    @elseif ($option['type'] === 'checkbox')

                                        <div class="{{ $option['class'] }}">
                                            <div class="position-relative form-check">
                                                <br>
                                                <input name="{{ $name }}" id="{{ $name }}"
                                                       type="checkbox" class="form-check-input">
                                                <label for="{{ $name }}"
                                                       class="form-label form-check-label">{{ $option['description'] }}</label>
                                            </div>
                                            <small
                                                class="form-text text-muted">{{ $option['description'] }}</small>
                                        </div>

                                    @elseif ($option['type'] === 'select')

                                        <div class="{{ $option['class'] }}">
                                            <label for="{{ $name }}"
                                                   class="form-label ">{{ $option['label'] }}</label>
                                            <select name="{{ $name }}" id="{{ $name }}"
                                                    class="form-control form-select">
                                                @foreach ($option['options'] as $value => $label)
                                                    <option value="{{ $value }}">
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small
                                                class="form-text text-muted">{{ $option['description'] }}</small>
                                        </div>
                                    @endif

                                @endforeach
                            </div>

                        </form>
                    </div>
                @endforeach
            </div>

        </div>
    </div>
@endsection

@section('js')
    @parent
    <script>
        $(document).ready(function () {

            function loadFormData(group) {
                blockUI('tab-' + group);
                const $form = $('#form-' + group);
                $form.data('validator', null);
                $form.validate().resetForm();

                PUQajax('{{route('admin.api.general_settings.get')}}?group=' + group, {}, 50, null, 'GET')
                    .then(function (response) {
                        $.each(response.data, function (key, value) {
                            const $element = $form.find(`[name="${key}"]`);
                            if ($element.length) {

                                if ($element.is('select')) {
                                    $element.find(`option[value="${value}"]`).prop('selected', true);
                                    return;
                                }

                                if ($element.is('textarea')) {
                                    if (value !== null) {
                                        $element.val(value);
                                    }
                                    $element.textareaAutoSize().trigger('autosize');
                                    return;
                                }

                                if ($element.is(':checkbox')) {
                                    $element.prop('checked', value === 'yes');
                                    return;
                                }

                                $element.val(value);
                            }
                        });
                        unblockUI('tab-' + group);
                    })
                    .catch(function (error) {
                        console.error('Error loading form data:', error);
                    });
            }

            loadFormData($('.nav-link.active').data('group'));

            $('.nav-link').on('click', function () {
                const group = $(this).data('group');
                loadFormData(group);
            });

            $("#save").on("click", function (event) {
                const group = $('.nav-link.active').data('group')
                const $form = $(`#form-` + group);
                event.preventDefault();
                const formData = serializeForm($form);
                PUQajax('{{route('admin.api.general_settings.put')}}?group=' + group, formData, 5000, $(this), 'PUT', $form)
                    .then(function (response) {
                        loadFormData(group);
                    });
            });
        });

    </script>
@endsection
