<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Yönetim</title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/fonts.css">
    <style>
        :root { --bg-paper: #FEF9E1; --text-main: #6D2323; }
        body { background-color: var(--bg-paper); color: var(--text-main); font-family: 'Space Grotesk', sans-serif; }
        .font-syne { font-family: 'Syne', sans-serif; }
        input { background: transparent; border: 1px solid #6D2323; padding: 1rem; width: 100%; outline: none; }
        button { background: #6D2323; color: #FEF9E1; width: 100%; padding: 1rem; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6">
    <div class="w-full max-w-md border border-[var(--text-main)] p-8 relative">
        <h1 class="font-syne text-4xl font-bold uppercase text-center mb-2">FEZADAN</h1>
        <p class="text-xs uppercase tracking-widest text-center mb-8 opacity-70">Yönetim Frekansı</p>

        <form action="/admin/login" method="POST" class="space-y-6">
            <div>
                <label class="text-xs uppercase tracking-widest block mb-2">Kullanıcı Adı</label>
                <input type="text" name="username" required>
            </div>
            <div>
                <label class="text-xs uppercase tracking-widest block mb-2">Şifre</label>
                <input type="password" name="password" required>
            </div>
            <button type="submit">Giriş Yap</button>
        </form>
    </div>
</body>
</html>