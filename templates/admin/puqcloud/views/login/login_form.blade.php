@extends(config('template.admin.view') . '.login.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')
    <div class="h-100 bg-plum-plate bg-animation">
        <div class="d-flex h-100 justify-content-center align-items-center">
            <div class="mx-auto app-login-box col-md-8">
                <div class="text-center ">
                    <img class="img-fluid" src="{{ asset_admin('images/logo.png') }}" alt="PUQcloud"
                         style="max-width: 300px;">
                </div>

                <div class="modal-dialog w-100 mx-auto">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="h5 modal-title text-center">
                                <h4 class="mt-2">
                                    <div>{{__('login.Login')}}</div>
                                    <span>{{__('login.Please sign in to your account below')}}</span>
                                </h4>
                            </div>
                            <form id="loginForm">
                                <div class="">
                                    <div class="col-md-12">
                                        <div class="position-relative mb-3">
                                            <input name="email" placeholder="{{__('login.Email')}}" type="email" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-12">
                                        <div class="position-relative mb-3">
                                            <input name="password" placeholder="{{__('login.Password')}}" type="password"
                                                   class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="float-start">
                                    <div class="position-relative form-check mb-3">
                                        <input name="remember" id="remember" type="checkbox" class="form-check-input">
                                        <label for="remember" class="form-label form-check-label">{{__('login.Remember Me')}}</label>
                                    </div>
                                </div>
                            </form>

                            <div class="float-end">
                                <button id="login"
                                        class="mb-2 me-2 btn-icon btn-shadow btn-dashed btn btn-outline-primary">
                                    <i class="fas fa-sign-in-alt"></i>
                                    {{__('login.Login')}}
                                </button>
                            </div>
                        </div>

                        <div class="d-block text-center modal-footer">
                            <a href="" class="btn-sm btn btn-link">{{__('login.Forgot Password?')}}</a>
                        </div>
                    </div>
                </div>
                <div class="text-center text-white opacity-8 mt-3"><a class="text-white" href="https://puqcloud.com/"
                                                                      target="_blank">Powered by PUQcloud</a></div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent

@endsection
