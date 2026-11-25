<?php

/** Reads accounts, creates a NEW one on invitation, and changes a password only on explicit superadmin request. Never deletes. */
class CpanelMailService
{
    public function configured(): bool
    {
        return filter_var(env_value('MAIL_INVITES_ENABLED', '0'), FILTER_VALIDATE_BOOLEAN)
            && filter_var(env_value('CPANEL_MAIL_HOST'), FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) !== false
            && env_value('CPANEL_MAIL_HOST') !== ''
            && preg_match('/\A[a-z0-9_]+\z/iD', env_value('CPANEL_MAIL_USER')) === 1
            && preg_match('/\A[a-zA-Z0-9_-]{16,512}\z/D', env_value('CPANEL_MAIL_TOKEN')) === 1
            && function_exists('curl_init');
    }

    public function accountExists(string $localPart): bool
    {
        MailInvitationService::validateLocalPart($localPart);
        $result = $this->request('list_pops', ['skip_main' => 1,
            'regex' => '(?i)\\A' . preg_quote($localPart . '@fezadan.org', '/') . '\\z']);
        if (!is_array($result['data'] ?? null)) {
            throw new RuntimeException('mail_account_list_unavailable');
        }
        foreach ($result['data'] as $account) {
            if (!is_array($account) || !is_string($account['email'] ?? null)) {
                throw new RuntimeException('mail_account_list_invalid');
            }
            if (strtolower($account['email']) === $localPart . '@fezadan.org') {
                return true;
            }
        }
        return false;
    }

    public function createAccount(string $localPart, string $password, int $quota): void
    {
        MailInvitationService::validateLocalPart($localPart);
        MailInvitationService::validatePassword($password);
        if ($quota < 25 || $quota > 1024) {
            throw new InvalidArgumentException('invalid_quota');
        }
        $result = $this->request('add_pop', ['domain' => 'fezadan.org', 'email' => $localPart,
            'password' => $password, 'quota' => $quota, 'send_welcome_email' => 0]);
        // UAPI returns the created mailbox as username+domain, not just a status.
        if (($result['data'] ?? null) !== $localPart . '+fezadan.org') {
            throw new RuntimeException('mail_creation_unconfirmed');
        }
    }

    /** Superadmin-initiated password change for an existing mailbox. */
    public function changePassword(string $localPart, string $password): void
    {
        MailInvitationService::validateLocalPart($localPart);
        MailInvitationService::validatePassword($password);
        $this->request('passwd_pop', ['domain' => 'fezadan.org', 'email' => $localPart,
            'password' => $password]);
    }

    protected function request(string $function, array $parameters): array
    {
        if (!$this->configured()) {
            throw new RuntimeException('mail_service_disabled');
        }
        $curl = curl_init('https://' . env_value('CPANEL_MAIL_HOST') . ':2083/execute/Email/' . $function);
        if ($curl === false) {
            throw new RuntimeException('mail_transport_unavailable');
        }
        // Credentials only travel in HTTPS headers/body; no query-string secrets,
        // redirects, raw API errors, verbose logging or user-selected API hosts.
        $response = '';
        curl_setopt_array($curl, [CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => ['Authorization: cpanel ' . env_value('CPANEL_MAIL_USER') . ':' . env_value('CPANEL_MAIL_TOKEN'),
                'Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'],
            CURLOPT_POSTFIELDS => http_build_query($parameters), CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => 15, CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$response): int {
                if (strlen($response) + strlen($chunk) > 2097152) {
                    return 0;
                }
                $response .= $chunk;
                return strlen($chunk);
            }]);
        $ok = curl_exec($curl);
        $status = (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);
        if ($ok === false || $status !== 200) {
            throw new RuntimeException('mail_transport_failed');
        }
        $json = json_decode($response, true, 32);
        // Direct /execute responses can omit the CLI/LiveAPI result envelope.
        $result = is_array($json) ? ($json['result'] ?? $json) : null;
        if (!is_array($result) || ($result['status'] ?? null) !== 1) {
            // Do not propagate cPanel responses: they can include submitted fields.
            throw new RuntimeException('mail_api_rejected');
        }
        return $result;
    }
}
