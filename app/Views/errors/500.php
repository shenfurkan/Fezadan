<?php
// Generic 500 sayfası — detay sızdırmaz.
?><!doctype html>
<html lang="tr">
<head>
<meta charset="utf-8">
<title>500 — Sistem Hatası | Fezadan</title>
<meta name="robots" content="noindex,nofollow">
<style>
    html,body{margin:0;height:100%}
    body{display:flex;flex-direction:column;justify-content:center;align-items:center;
         background:#FEF9E1;color:#6D2323;font-family:ui-sans-serif,system-ui,sans-serif;text-align:center;padding:24px}
    h1{font-size:5rem;margin:0;letter-spacing:.05em}
    p{font-size:1.1rem;letter-spacing:3px;text-transform:uppercase;margin:.75rem 0 1.5rem}
    a{color:#A31D1D;text-decoration:underline;font-weight:600}
</style>
</head>
<body>
    <h1>500</h1>
    <p>Sistem geçici olarak yanıt veremiyor</p>
    <?php if (isset($e) && $e instanceof \Throwable): ?>
        <div style="background:#fff; color:#a00; padding:15px; border:2px solid #a00; max-width:800px; text-align:left; word-wrap:break-word; margin-bottom: 20px;">
            <strong>HATA DETAYI:</strong><br>
            <?= htmlspecialchars($e->getMessage()) ?><br><br>
            <small><?= htmlspecialchars($e->getFile() . ':' . $e->getLine()) ?></small>
        </div>
    <?php endif; ?>
    <a href="/">Merkeze Dön</a>
</body>
</html>
