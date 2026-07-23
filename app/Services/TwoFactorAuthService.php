<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TwoFactorAuthService
{
    private const ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function generateSecretKey(int $length = 16): string
    {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::ALPHABET[random_int(0, 31)];
        }
        return $secret;
    }

    public function getQrCodeUrl(string $issuer, string $email, string $secret): string
    {
        $otpauth = $this->getOtpAuthUri($issuer, $email, $secret);
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($otpauth);
    }

    public function getOtpAuthUri(string $issuer, string $email, string $secret): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            rawurlencode($issuer), rawurlencode($email), $secret, rawurlencode($issuer)
        );
    }

    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{6}$/', $code)) {
            return false;
        }

        $timeSlice = (int) floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals($this->generateOtp($secret, $timeSlice + $i), $code)) {
                return true;
            }
        }
        return false;
    }

    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn ($c) => Hash::make($c), $plainCodes);
    }

    public function verifyAndConsumeRecoveryCode(User $user, string $code): bool
    {
        $hashed = $user->two_factor_recovery_codes ?? [];
        foreach ($hashed as $i => $hash) {
            if (Hash::check($code, $hash)) {
                unset($hashed[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($hashed)])->save();
                return true;
            }
        }
        return false;
    }

    private function base32Decode(string $b32): string
    {
        $b32 = strtoupper(preg_replace('/[^A-Z2-7]/i', '', $b32));
        $buffer = 0; $bitsLeft = 0; $result = '';
        foreach (str_split($b32) as $char) {
            $val = strpos(self::ALPHABET, $char);
            if ($val === false) continue;
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        return $result;
    }

    private function generateOtp(string $secret, int $counter): string
    {
        $key = $this->base32Decode($secret);
        $bin = pack('N', 0) . pack('N', $counter);
        $hash = hash_hmac('sha1', $bin, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $truncated =
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF);
        return str_pad((string) ($truncated % 1000000), 6, '0', STR_PAD_LEFT);
    }
}