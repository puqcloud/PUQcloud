@extends(config('template.admin.view') . '.layout.layout')

@if(isset($title))
    @section('title', $title)
@endif

@section('head')
    @parent
@endsection

@section('content')

    <style>
        .permission-denied-page {
            background-color: #000000;
            color: #ff0000;
            font-family: 'Courier New', Courier, monospace;
            text-align: center;
            height: 90vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .permission-denied-page h1 {
            font-size: 10rem;
            font-weight: bold;
            text-shadow: 0px 0px 20px #ff0000, 0px 0px 30px #ff0000;
            animation: flicker 1.5s infinite alternate;
        }

        .permission-denied-page p {
            font-size: 1.5rem;
            text-shadow: 0px 0px 5px #ff0000;
            margin-top: -30px;
        }

        .permission-denied-page a {
            color: #ffffff;
            text-decoration: none;
            font-size: 1.5rem;
            text-shadow: 0px 0px 10px #ffffff;
        }

        .permission-denied-page a:hover {
            color: #ff0000;
            text-shadow: 0px 0px 20px #ff0000;
        }

        @keyframes flicker {
            0% {
                opacity: 1;
            }
            50% {
                opacity: 0.8;
            }
            100% {
                opacity: 0.4;
            }
        }

        .permission-denied-page .skull {
            font-size: 10rem;
            margin-bottom: 20px;
            animation: skullShake 1.5s infinite;
        }

        @keyframes skullShake {
            0% {
                transform: rotate(0deg);
            }
            25% {
                transform: rotate(10deg);
            }
            50% {
                transform: rotate(-10deg);
            }
            75% {
                transform: rotate(10deg);
            }
            100% {
                transform: rotate(0deg);
            }
        }
    </style>
    <div class="permission-denied-page">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <div class="skull">💀</div>
                    <h1>403</h1>
                    <p>{{__('error.Permission Denied! You shall not pass...')}}</p>
                    <a href="{{ route('admin.web.dashboard') }}">{{__('error.Return to safety')}}</a>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @parent
@endsection
