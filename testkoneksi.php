<?php
    $host = '10.1.3.175';
    $db = 'whatsapp_api';
    $user = 'whatsapp_api';
    $pass = 'Setwapres@2024!#';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
        echo "Koneksi berhasil!";
    } catch (PDOException $e) {
        echo "Koneksi gagal: " . $e->getMessage();
    }
?>