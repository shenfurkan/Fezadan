<?php
require_once dirname(__DIR__, 2) . '/Services/MailInvitationService.php';

trait AdminMailInvitations
{
    public function mailInvitations(): void
    {
        $this->requireRole(['superadmin']);
        header('Cache-Control: private, no-store');
        header('Referrer-Policy: no-referrer');
        $created = $_SESSION['mail_invitation_created'] ?? null;
        unset($_SESSION['mail_invitation_created']);
        $service = new MailInvitationService(Db::pdo());
        $this->view('admin/mail-invitations', ['invitations' => $service->listing(),
            'created' => is_array($created) && (int)($created['display_until'] ?? 0) >= time() ? $created : null,
            'configured' => $service->configured()]);
    }

    public function storeMailInvitation(): void
    {
        $this->requirePost();
        $this->requireRole(['superadmin']);
        $localPart = is_string($_POST['local_part'] ?? null) ? strtolower(trim($_POST['local_part'])) : '';
        $quota = filter_var($_POST['quota_mb'] ?? '', FILTER_VALIDATE_INT);
        $hours = filter_var($_POST['hours'] ?? '', FILTER_VALIDATE_INT);
        try {
            $service = new MailInvitationService(Db::pdo());
            $created = $service->create($localPart, $quota === false ? 0 : $quota,
                $hours === false ? 0 : $hours, (int)$_SESSION['admin_id']);
            // One-time display, not a URL parameter or an audit-log field.
            $_SESSION['mail_invitation_created'] = $created + ['display_until' => time() + 60];
            AdminLog::write('info', 'Posta davetiyesi oluşturuldu.', ['invitation_id' => $created['id']]);
        } catch (InvalidArgumentException $e) {
            Flash::set(in_array($e->getMessage(), ['address_exists', 'invitation_exists'], true)
                ? 'Bu adres için zaten bir hesap veya açık davetiye var.'
                : 'Adres, kota veya davet süresi geçersiz.');
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) {
                throw $e;
            }
            Flash::set('Posta sunucusu kontrol edilemedi. Davetiye oluşturulmadı.');
        }
        header('Location: /yonetim/mail-invitations', true, 303);
        exit;
    }

    public function revokeMailInvitation(): void
    {
        $this->requirePost();
        $this->requireRole(['superadmin']);
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        $changed = $id !== false && $id > 0 && (new MailInvitationService(Db::pdo()))->revoke($id);
        if ($changed) {
            AdminLog::write('info', 'Posta davetiyesi iptal edildi.', ['invitation_id' => $id]);
        }
        Flash::set($changed ? 'Davetiye iptal edildi. Posta hesapları değiştirilmedi.' : 'Bu davetiye iptal edilemiyor.');
        header('Location: /yonetim/mail-invitations', true, 303);
        exit;
    }

    public function changeMailPassword(): void
    {
        $this->requirePost();
        $this->requireRole(['superadmin']);
        $localPart = is_string($_POST['local_part'] ?? null) ? strtolower(trim($_POST['local_part'])) : '';
        $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';
        $confirm = is_string($_POST['password_confirm'] ?? null) ? $_POST['password_confirm'] : '';
        try {
            MailInvitationService::validateLocalPart($localPart);
            MailInvitationService::validatePassword($password);
            if (!hash_equals($password, $confirm)) {
                throw new InvalidArgumentException('password_mismatch');
            }
            $mail = new CpanelMailService();
            if (!$mail->configured()) {
                throw new RuntimeException('mail_service_disabled');
            }
            if (!$mail->accountExists($localPart)) {
                throw new InvalidArgumentException('address_missing');
            }
            $mail->changePassword($localPart, $password);
            AdminLog::write('warning', 'Posta hesabı şifresi değiştirildi.', ['local_part' => $localPart]);
            Flash::set('Şifre değiştirildi. Yeni şifreyi kullanıcıya güvenli bir kanaldan iletin; posta uygulamalarında da güncellemesi gerekir.');
        } catch (InvalidArgumentException $e) {
            $messages = [
                'password_mismatch' => 'Şifreler aynı değil.',
                'invalid_password' => 'Şifre en az 12 karakter olmalı; çok uzun şifreler ve kontrol karakterleri kabul edilmez.',
                'address_missing' => 'Bu adreste bir posta hesabı bulunamadı.',
            ];
            Flash::set($messages[$e->getMessage()] ?? 'Posta hesabı adı geçersiz.');
        } catch (RuntimeException $e) {
            Flash::set($e->getMessage() === 'mail_service_disabled'
                ? 'Posta sunucusu bağlantısı yapılandırılmamış.'
                : 'Posta sunucusu şifreyi değiştirmedi. Hesabı ve bağlantı ayarlarını kontrol edin.');
        }
        header('Location: /yonetim/mail-invitations', true, 303);
        exit;
    }

    public function reconcileMailInvitation(): void
    {
        $this->requirePost();
        $this->requireRole(['superadmin']);
        $id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT);
        try {
            $result = $id !== false && $id > 0 ? (new MailInvitationService(Db::pdo()))->reconcile($id) : 'wait';
            AdminLog::write('info', 'Posta davetiyesi sunucuda kontrol edildi.', ['invitation_id' => $id, 'result' => $result]);
            $messages = ['exists' => 'Adres cPanel’de var; davetiye kapatıldı. Hesap ve şifresi değiştirilmedi.',
                'retry' => 'Adres cPanel’de yok; aynı davetiye yeniden kullanılabilir.',
                'expired' => 'Adres cPanel’de yok; davetiyenin süresi dolmuş. Yeni davetiye oluşturabilirsiniz.',
                'disabled' => 'cPanel bağlantısı yapılandırılmamış.',
                'wait' => 'Aktif işlem yok veya işlem henüz çok yeni. Kontrol için en az 2 dakika bekleyin.'];
            Flash::set($messages[$result]);
        } catch (RuntimeException $e) {
            if ($e instanceof PDOException) {
                throw $e;
            }
            Flash::set('Sunucu kontrol edilemedi. Davetiyenin durumu değiştirilmedi.');
        }
        header('Location: /yonetim/mail-invitations', true, 303);
        exit;
    }
}
