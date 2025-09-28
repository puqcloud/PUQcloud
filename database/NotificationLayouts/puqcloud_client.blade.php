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
            background: linear-gradient(135deg, #74ebd5 0%, #ACB6E5 100%);
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 50px auto;
            padding: 0;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #6e45e2 0%, #88d3ce 100%);
            padding: 40px 20px;
            text-align: center;
            color: #fff;
        }

        .header img {
            width: 240px;
            height: auto;
            margin-bottom: 20px;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .message {
            padding: 30px;
            font-size: 16px;
            line-height: 1.8;
            color: #555;
            text-align: left;
        }

        .message p {
            margin-bottom: 20px;
        }

        .button-container {
            text-align: center;
            margin-bottom: 40px;
        }

        .button {
            padding: 15px 30px;
            background-color: #6e45e2;
            color: #fff;
            font-size: 16px;
            font-weight: 600;
            text-transform: uppercase;
            border-radius: 50px;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(110, 69, 226, 0.5);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: inline-block;
        }

        .button:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(110, 69, 226, 0.7);
            color: #b2a2de;
        }

        .signature {
            padding: 0 30px;
            font-size: 14px;
            text-align: right;
            color: #6e45e2;
            font-style: italic;
        }

        .membership-info {
            text-align: center;
            padding: 10px 20px;
            background-color: #f4f7fc;
            color: #999;
            font-size: 13px;
            font-weight: 400;
            border-top: 1px solid #ddd;
        }

        .footer {
            padding: 20px;
            text-align: center;
            font-size: 12px;
            color: #aaa;
        }

        .footer a {
            color: #6e45e2;
            text-decoration: none;
        }

        .footer a:hover {
            text-decoration: underline;
        }

        @media screen and (max-width: 600px) {
            .header h1 {
                font-size: 22px;
            }

            .message {
                padding: 20px;
                font-size: 14px;
            }

            .button {
                padding: 12px 25px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <img src="{{$logo_url}}" alt="Logo">
        <h1>{{$title}}</h1>
    </div>

    <div class="message">
        {!! $content !!}
    </div>

    <div class="button-container">
        <a href="{{ route('client.web.panel.login') }}" class="button">Visit Client area</a>
    </div>

    <div class="signature">
        {!! $signature !!}
    </div>
</div>
</body>
</html>
