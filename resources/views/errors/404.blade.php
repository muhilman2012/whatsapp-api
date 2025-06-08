<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <title>404 - Tidak Ditemukan</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 100px;
            background-color: #fafafa;
        }
        .logo {
            max-width: 100px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 50px;
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
    <h1>404</h1>
    <p>Halaman yang Anda cari tidak ditemukan.</p>
    <p><a href="{{ url('/') }}">Kembali ke Halaman Utama</a></p>
</body>
</html>