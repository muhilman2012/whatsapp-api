<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Lapor Mas Wapres! - API</title>
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
        .container {
            max-width: 600px;
            padding: 20px;
            border: 1px solid #e1e1e1;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        .logo {
            max-width: 120px;
            margin-bottom: 20px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        p {
            margin-bottom: 10px;
            font-size: 16px;
            line-height: 1.5;
        }
        a {
            color: #007bff;
            text-decoration: none;
        }
        .footer {
            margin-top: 15px;
            font-size: 0.9em;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="{{ asset('images/LaporMasWapres.png') }}" alt="Logo Lapor Mas Wapres!" class="logo" />
        <h1>Lapor Mas Wapres! API</h1>
        <p>Selamat datang di API layanan pengaduan <strong>"Lapor Mas Wapres!"</strong>.</p>
        <p>API ini digunakan untuk:</p>
        <ul style="text-align: left; display: inline-block;">
            <li>Menerima laporan pengaduan baru</li>
            <li>Mengecek status pengaduan</li>
            <li>Mengirimkan dokumen tambahan pengaduan</li>
        </ul>
        <p>Integrasi saat ini dilakukan melalui Qontak WhatsApp Official Partner.</p>
        <p>Jika Anda membutuhkan bantuan atau memiliki pertanyaan, silakan hubungi Administrator:</p>
        <p>
            📧 <a href="mailto:lmsspv@set.wapresri.go.id">lmsspv@set.wapresri.go.id</a>
        </p>
        <div class="footer">© 2025 Lapor Mas Wapres! API</div>
    </div>
</body>
</html>