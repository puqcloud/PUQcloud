@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('content')
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon">
                    <i class="pe-7s-plugin icon-gradient bg-tempting-azure"></i>
                </div>
                <div>{{ $info['name'] }} <small class="text-muted">v{{ $info['version'] }}</small></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="text-center mb-4">
                <img class="img-fluid" src="{{ asset_admin('images/logo.png') }}" alt="PUQcloud" style="max-width: 200px;">
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.Template Information') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.Description') }}:</strong> {{ $info['description'] }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.Author Information') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.Name') }}:</strong> {{ $info['author']['name'] }}</p>
                    <p><strong>{{ __('admin_template.Email') }}:</strong> <a href="mailto:{{ $info['author']['email'] }}">{{ $info['author']['email'] }}</a></p>
                    <p><strong>{{ __('admin_template.Website') }}:</strong> <a href="{{ $info['author']['website'] }}" target="_blank">{{ $info['author']['website'] }}</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.Requirements') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.PUQcloud Version') }}:</strong> {{ $info['requirements']['PUQcloud'] }}</p>
                    <p><strong>{{ __('admin_template.PHP Version') }}:</strong> {{ $info['requirements']['php'] }}</p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.License') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.Type') }}:</strong> {{ $info['license']['type'] }}</p>
                    <p><strong>{{ __('admin_template.License URL') }}:</strong> <a href="{{ $info['license']['url'] }}" target="_blank">{{ $info['license']['url'] }}</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.Support Information') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.Documentation') }}:</strong> <a href="{{ $info['support']['documentation'] }}" target="_blank">{{ $info['support']['documentation'] }}</a></p>
                    <p><strong>{{ __('admin_template.Changelog') }}:</strong> <a href="{{ $info['support']['changelog'] }}" target="_blank">{{ $info['support']['changelog'] }}</a></p>
                    <p><strong>{{ __('admin_template.Support Email') }}:</strong> <a href="mailto:{{ $info['support']['support_email'] }}">{{ $info['support']['support_email'] }}</a></p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="card-title">{{ __('admin_template.Timestamps') }}</h5>
                </div>
                <div class="card-body">
                    <p><strong>{{ __('admin_template.Created At') }}:</strong> {{ $info['timestamps']['created_at'] }}</p>
                    <p><strong>{{ __('admin_template.Updated At') }}:</strong> {{ $info['timestamps']['updated_at'] }}</p>
                </div>
            </div>
        </div>
    </div>
@endsection
