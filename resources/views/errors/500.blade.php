<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>500 - Terjadi Kesalahan</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9fafb;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            text-align: center;
            opacity: 0;
            animation: fadeIn 1s ease-in-out forwards;
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
        .logo {
            max-width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 50px;
            color: #e74c3c;
        }
        p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <img src="{{ asset('images/LaporMasWapres.png') }}" alt="Logo Lapor Mas Wapres!" class="logo" />
    <h1>500</h1>
    <p>Terjadi kesalahan pada server kami.</p>
    <p><a href="{{ url('/') }}">Kembali ke Halaman Utama</a></p>
</body>
</html>