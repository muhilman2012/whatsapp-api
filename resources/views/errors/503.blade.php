<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>503 - Sedang Dalam Pemeliharaan</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 100px;
            background-color: #fafafa;
            opacity: 0;
            animation: fadeIn 1s ease-in-out forwards;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 50px;
            color: #f39c12;
        }
        p {
            font-size: 18px;
            margin-bottom: 10px;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
            }
        }
    </style>
</head>
<body>
    <img src="{{ asset('images/LaporMasWapres.png') }}" alt="Logo Lapor Mas Wapres!" class="logo" />
    <h1>503</h1>
    <p>Saat ini layanan sedang dalam pemeliharaan.</p>
    <p>Silakan coba beberapa saat lagi.</p>
    <p><a href="{{ url('/') }}">Kembali ke Halaman Utama</a></p>
</body>
</html>