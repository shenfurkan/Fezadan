<?php
/** @var string $linkHtml Escaped reset URL */
/** @var string $homeHtml Escaped site URL */
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="referrer" content="no-referrer">
    <title>FEZADAN — Şifrenizi yenileyin</title>
    <style>
        @media screen and (max-width: 480px) {
            .mail-outer { padding: 20px 12px !important; }
            .mail-padding { padding: 24px 20px !important; }
            .mail-heading { font-size: 25px !important; }
        }
    </style>
</head>
<body style="margin:0;padding:0;background-color:#f5f5f5;color:#202020;font-family:Arial,Helvetica,sans-serif;">
    <div style="display:none;font-size:1px;line-height:1px;color:#f5f5f5;max-height:0;max-width:0;overflow:hidden;mso-hide:all;">Şifrenizi yenilemek için bağlantıyı 15 dakika içinde kullanın.</div>
    <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="background-color:#f5f5f5;">
        <tr>
            <td class="mail-outer" align="center" style="padding:40px 16px;">
                <!--[if mso]><table role="presentation" width="520" border="0" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
                <table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="max-width:520px;background-color:#ffffff;border:1px solid #e4e4e4;border-radius:8px;">
                    <tr>
                        <td class="mail-padding" style="padding:32px;">
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td width="44" style="vertical-align:middle;"><img src="cid:fezadan-logo" width="44" height="24" alt="" style="display:block;border:0;"></td>
                                    <td style="padding-left:12px;vertical-align:middle;font-size:16px;line-height:24px;font-weight:bold;letter-spacing:0.06em;color:#202020;">FEZADAN</td>
                                </tr>
                            </table>
                            <p style="margin:32px 0 10px;font-size:13px;line-height:20px;color:#656565;">Yönetim hesabı</p>
                            <h1 class="mail-heading" style="margin:0 0 16px;font-size:28px;line-height:1.2;letter-spacing:-0.02em;font-weight:bold;color:#202020;">Şifrenizi yenileyin</h1>
                            <p style="margin:0;font-size:15px;line-height:24px;color:#656565;">FEZADAN yönetim hesabınız için bir şifre sıfırlama isteği aldık. Yeni şifrenizi aşağıdaki bağlantıdan belirleyebilirsiniz.</p>
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="margin:28px 0;">
                                <tr>
                                    <td bgcolor="#202020" style="background-color:#202020;border-radius:6px;text-align:center;">
                                        <a href="<?= $linkHtml ?>" target="_blank" rel="noopener noreferrer" style="display:inline-block;padding:14px 24px;border:1px solid #202020;border-radius:6px;color:#ffffff;font-size:15px;line-height:20px;font-weight:bold;text-decoration:none;">Şifreyi yenile</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:13px;line-height:21px;color:#656565;">Bu bağlantı <strong style="color:#202020;">15 dakika</strong> geçerlidir ve yalnızca bir kez kullanılabilir.</p>
                            <p style="margin:24px 0 8px;padding-top:24px;border-top:1px solid #e4e4e4;font-size:13px;line-height:21px;color:#656565;">Buton açılmıyorsa bu adresi tarayıcınıza kopyalayın:</p>
                            <p style="margin:0;font-size:12px;line-height:20px;overflow-wrap:anywhere;word-break:break-all;"><a href="<?= $linkHtml ?>" target="_blank" rel="noopener noreferrer" style="color:#656565;text-decoration:underline;word-break:break-all;"><?= $linkHtml ?></a></p>
                        </td>
                    </tr>
                    <tr>
                        <td class="mail-padding" style="padding:24px 32px;border-top:1px solid #e4e4e4;background-color:#fafafa;border-radius:0 0 8px 8px;">
                            <p style="margin:0 0 6px;font-size:13px;line-height:21px;font-weight:bold;color:#202020;">Bu isteği siz yapmadınız mı?</p>
                            <p style="margin:0;font-size:13px;line-height:21px;color:#656565;">Bu e-postayı yok sayabilirsiniz. Bağlantıyı kullanmadığınız sürece şifreniz değişmez. Sıfırlama bağlantısını kimseyle paylaşmayın.</p>
                        </td>
                    </tr>
                </table>
                <!--[if mso]></td></tr></table><![endif]-->
                <p style="margin:20px 0 0;font-size:12px;line-height:20px;color:#656565;"><a href="<?= $homeHtml ?>" target="_blank" rel="noopener noreferrer" style="color:#656565;text-decoration:underline;">fezadan.org</a> · Yönetim paneli</p>
            </td>
        </tr>
    </table>
</body>
</html>
