<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Mail-Bestätigung</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            line-height: 1.6;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #196428;
            padding: 20px;
            text-align: center;
        }
        .content {
            padding: 30px 20px;
            background-color: #fff;
        }
        .footer {
            background-color: #f7f7f7;
            padding: 15px 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            background-color: #196428;
            color: white !important;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 4px;
            font-weight: bold;
            margin: 20px 0;
        }
        .button:hover {
            background-color: #145020;
        }
        .highlights {
            background-color: #f7f7f7;
            border-left: 4px solid #196428;
            padding: 15px;
            margin: 20px 0;
        }
        h1, h2 {
            color: #196428;
        }
        .social {
            margin-top: 20px;
        }
        .social a {
            display: inline-block;
            margin: 0 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1 style="color: white; margin-top: 10px;">{{ config('app.name') }}</h1>
    </div>

    <div class="content">
        <h2>Hallo {{ $user->name }},</h2>

        <p>vielen Dank für deine Registrierung bei {{ config('app.name') }}! Wir freuen uns, dass du dabei bist.</p>

        <p>Um dein Konto zu aktivieren und Zugriff auf alle Funktionen zu erhalten, bestätige bitte deine E-Mail-Adresse.</p>

        <div style="text-align: center;">
            <a href="{{ $url }}" class="button">E-Mail-Adresse bestätigen</a>
        </div>

        <div class="highlights">
            <p><strong>Was dich in unserer App erwartet:</strong></p>
            <ul>
                <li>Effizientes Projekt- und Aufgabenmanagement</li>
                <li>Teamkollaboration in Echtzeit</li>
                <li>Übersichtliche Dashboard-Analysen</li>
                <li>Anpassbare Formulare und Vorlagen</li>
            </ul>
        </div>


        <p>Solltest du diese E-Mail nicht angefordert haben, kannst du sie einfach ignorieren.</p>

        <p>Vielen Dank und herzliche Grüße,<br>
            Dein {{ config('app.name') }}-Team</p>
    </div>

    <div class="footer">
        <p>© {{ date('Y') }} {{ \App\Support\OpenFm::attribution() }}</p>
        <p style="margin-top: 15px;">
            <small>Diese E-Mail wurde automatisch generiert. Bitte antworte nicht darauf.</small>
        </p>
    </div>
</div>
</body>
</html>
