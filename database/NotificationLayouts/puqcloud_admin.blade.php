<!DOCTYPE html>
<html lang="{{$locale}}">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{$title}}</title>
    <style>
        body {
            font-family: "Arial", sans-serif;
            margin: 0;
            padding: 0;
            background-color: #75AADAFF;
            color: #1E194FFF;
        }

        .container {
            max-width: 1024px;
            margin: 0 auto;
            padding: 20px;
            background-color: #ffffff;
            border: 2px solid #61439AFF;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            overflow-wrap: break-word;
        }


        .logo img {
            max-width: 200px;
            width: 100%;
            height: auto;
        }

        .message {
            text-align: center;
            margin-bottom: 30px;
        }

        .signature {
            text-align: right;
            font-style: italic;
            color: #61439AFF;
        }

        .title {
            text-align: left;
            font-style: italic;
            color: #61439AFF;
        }

        @media screen and (max-width: 600px) {
            .container {
                padding: 5px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="logo">
        <img src="{{$logo_url}}" alt="Logo">
    </div>
    <div class="title">
        {{$title}}
    </div>
    <hr>
    <div class="message">
        {!! $content !!}
    </div>

    <div class="signature">
        Best regards<br>
        PUQcloud
    </div>
</div>
</body>
</html>
