<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>FEZADAN | Yönetim</title>
    <link rel="icon" type="image/x-icon" href="/cdn/dark-favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="/cdn/dark-favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/cdn/dark-favicon-16x16.png">
    <link rel="light-apple-touch-icon" href="/cdn/dark-apple-touch-icon.png">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    
    <script>
        (function () {
            const userTheme = localStorage.getItem('theme');
            if (userTheme === 'dark') {
                document.documentElement.setAttribute('data-theme', 'dark');
            }
        })();
    </script>

    <style>
        :root { 
            --bg-paper: #FEF9E1; 
            --bg-secondary: #E5D0AC;
            --text-main: #6D2323; 
            --text-accent: #A31D1D;
        }
        [data-theme="dark"] {
            --bg-paper: #120A0A;
            --bg-secondary: #1F1212;
            --text-main: #E5D0AC;
            --text-accent: #FF5C5C;
        }
        
        body { 
            background-color: var(--bg-paper); 
            color: var(--text-main); 
            font-family: 'Space Grotesk', sans-serif; 
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        .font-syne { font-family: 'Syne', sans-serif; }
        
        input { 
            background: transparent; 
            border: 1px solid var(--text-main); 
            padding: 1rem; 
            width: 100%; 
            outline: none; 
            color: var(--text-main);
            transition: background-color 0.3s, border-color 0.3s;
        }
        input:focus {
            background: var(--bg-secondary);
        }
        
        button { 
            background: var(--text-main); 
            color: var(--bg-paper); 
            width: 100%; 
            padding: 1rem; 
            font-weight: bold; 
            text-transform: uppercase; 
            transition: background-color 0.3s, color 0.3s;
        }
        button:hover {
            background: var(--text-accent);
            color: #fff;
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center p-6 relative">
    <div class="w-full max-w-md border border-[var(--text-main)] p-8 relative bg-[var(--bg-paper)] shadow-[8px_8px_0px_var(--text-main)] transition-colors">
        <h1 class="font-syne text-4xl font-bold uppercase text-center mb-2">FEZADAN</h1>
        <p class="text-xs uppercase tracking-widest text-center mb-8 opacity-70">Yönetim Frekansı</p>

        <?php if(isset($_GET['error'])): ?>
            <div class="mb-6 p-4 font-mono text-xs uppercase flex-shrink-0 border-l-4 <?php echo $_GET['error'] == 'locked' ? 'bg-red-900 text-red-100 border-red-500' : 'bg-[var(--text-main)] text-[var(--bg-paper)] border-[var(--bg-secondary)]'; ?>">
                <?php 
                    if($_GET['error'] == 'locked') {
                        echo '⚠️ GÜVENLİK İHLALİ: ÇOK FAZLA HATALI DENEME YAPTINIZ. ERİŞİMİNİZ 3 SAAT BOYUNCA ENGELLENMİŞTİR.';
                    } else {
                        echo '⚠️ GEÇERSİZ KULLANICI ADI VEYA ŞİFRE.';
                    }
                ?>
            </div>
        <?php endif; ?>
        
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