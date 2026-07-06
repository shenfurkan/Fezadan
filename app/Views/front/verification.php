<?php
$siteBase = defined('SITE_URL') ? rtrim(SITE_URL, '/') : 'https://fezadan.org';
$lang = App::getLang();
$isEn = ($lang === 'EN');
$page_title       = $isEn ? 'Verification & Fact-Checking | FEZADAN' : 'Teyit & Doğruluk Kontrolü | FEZADAN';
$page_description = $isEn
    ? 'FEZADAN verification and fact-checking principles — accuracy, transparency, and source citation in every published article.'
    : 'FEZADAN teyit ve doğruluk kontrolü ilkeleri — yayınlanan her makalede doğruluk, şeffaflık ve kaynak gösterme prensipleri.';
$page_canonical   = langUrl('/teyit');
$og_url           = $page_canonical;
require_once ROOT . '/app/Views/inc/header.php';
?>

<div class="max-w-3xl mx-auto px-4 py-16">
    <h1 class="text-4xl md:text-5xl font-bold mb-8 font-['Syne']">
        <?= $isEn ? 'Verification & Fact-Checking' : 'Teyit & Doğruluk Kontrolü' ?>
    </h1>

    <div class="prose prose-lg max-w-none">
        <p>
            <?= $isEn
                ? 'FEZADAN is committed to accuracy and transparency in all published content. Every article undergoes a thorough review process before publication.'
                : 'FEZADAN, yayınlanan tüm içeriklerde doğruluk ve şeffaflık ilkesine bağlıdır. Her makale yayınlanmadan önce kapsamlı bir inceleme sürecinden geçer.' ?>
        </p>

        <h2 class="text-2xl font-bold mt-8 mb-4">
            <?= $isEn ? 'Our Principles' : 'İlkelerimiz' ?>
        </h2>
        <ul class="list-disc pl-6 space-y-2">
            <li><?= $isEn ? 'All claims are supported by cited sources.' : 'Tüm iddialar kaynak gösterilerek desteklenir.' ?></li>
            <li><?= $isEn ? 'Corrections are published promptly when errors are identified.' : 'Hatalar tespit edildiğinde düzeltmeler hızlıca yayınlanır.' ?></li>
            <li><?= $isEn ? 'Content is regularly reviewed and updated.' : 'İçerik düzenli olarak gözden geçirilir ve güncellenir.' ?></li>
            <li><?= $isEn ? 'We clearly distinguish between fact, analysis, and opinion.' : 'Olay, analiz ve görüş arasında net ayrım yaparız.' ?></li>
        </ul>

        <h2 class="text-2xl font-bold mt-8 mb-4">
            <?= $isEn ? 'Report an Error' : 'Hata Bildir' ?>
        </h2>
        <p>
            <?= $isEn
                ? 'If you find an error in our content, please contact us at <strong>teyit@fezadan.org</strong>. We review all reports and publish corrections when necessary.'
                : 'İçeriğimizde bir hata bulursanız, lütfen <strong>teyit@fezadan.org</strong> adresinden bize ulaşın. Tüm bildirimleri inceler ve gerekli düzeltmeleri yayınlarız.' ?>
        </p>
    </div>
</div>

<?php require_once ROOT . '/app/Views/inc/footer.php'; ?>
