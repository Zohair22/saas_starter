<?php

namespace Modules\User\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\User\Models\User;

class MfaService
{
    /**
     * @return array{secret: string, recovery_codes: array<int, string>}
     */
    public function setup(User $user): array
    {
        $secret = $this->generateSecret();
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => $secret,
            'mfa_recovery_codes' => $this->hashRecoveryCodes($recoveryCodes),
        ])->save();

        return [
            'secret' => $secret,
            'recovery_codes' => $recoveryCodes,
        ];
    }

    public function enable(User $user, string $code): bool
    {
        if (! $this->verifyTotpCode($user, $code)) {
            return false;
        }

        $user->forceFill([
            'mfa_enabled' => true,
        ])->save();

        return true;
    }

    public function disable(User $user): void
    {
        $user->forceFill([
            'mfa_enabled' => false,
            'mfa_secret' => null,
            'mfa_recovery_codes' => null,
        ])->save();
    }

    public function challengePassed(User $user, ?string $totpCode, ?string $recoveryCode): bool
    {
        if (! $user->mfa_enabled) {
            return true;
        }

        if ($totpCode !== null && $this->verifyTotpCode($user, $totpCode)) {
            return true;
        }

        if ($recoveryCode !== null && $this->verifyAndConsumeRecoveryCode($user, $recoveryCode)) {
            return true;
        }

        return false;
    }

    public function currentCodeForSecret(string $secret, ?int $timestamp = null): string
    {
        return $this->generateTotpCode($secret, $this->timeStep($timestamp ?? time()));
    }

    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = rawurlencode((string) config('app.name'));
        $label = rawurlencode((string) config('app.name').':'.$user->email);

        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    private function verifyTotpCode(User $user, string $code): bool
    {
        $secret = (string) $user->mfa_secret;

        if ($secret === '') {
            return false;
        }

        $normalized = preg_replace('/\s+/', '', $code) ?? '';

        if (! preg_match('/^\d{6}$/', $normalized)) {
            return false;
        }

        $currentStep = $this->timeStep(time());

        foreach ([-1, 0, 1] as $offset) {
            $expected = $this->generateTotpCode($secret, $currentStep + $offset);

            if (hash_equals($expected, $normalized)) {
                return true;
            }
        }

        return false;
    }

    private function verifyAndConsumeRecoveryCode(User $user, string $recoveryCode): bool
    {
        $hashedCodes = $user->mfa_recovery_codes ?? [];
        $normalized = strtoupper(trim($recoveryCode));

        foreach ($hashedCodes as $index => $hashedCode) {
            if (Hash::check($normalized, (string) $hashedCode)) {
                unset($hashedCodes[$index]);

                $user->forceFill([
                    'mfa_recovery_codes' => array_values($hashedCodes),
                ])->save();

                return true;
            }
        }

        return false;
    }

    private function generateSecret(): string
    {
        return $this->base32Encode(random_bytes(20));
    }

    /**
     * @return array<int, string>
     */
    private function generateRecoveryCodes(): array
    {
        $codes = [];

        for ($index = 0; $index < 8; $index++) {
            $codes[] = strtoupper(Str::random(10));
        }

        return $codes;
    }

    /**
     * @param  array<int, string>  $recoveryCodes
     * @return array<int, string>
     */
    private function hashRecoveryCodes(array $recoveryCodes): array
    {
        return array_map(
            static fn (string $code): string => Hash::make($code),
            $recoveryCodes,
        );
    }

    private function generateTotpCode(string $base32Secret, int $timeStep): string
    {
        $key = $this->base32Decode($base32Secret);

        if ($key === '') {
            return '000000';
        }

        $counter = pack('N*', 0).pack('N*', $timeStep);
        $hash = hash_hmac('sha1', $counter, $key, true);
        $offset = ord(substr($hash, -1)) & 0x0F;
        $binary = unpack('N', substr($hash, $offset, 4));
        $value = ((int) ($binary[1] ?? 0)) & 0x7FFFFFFF;

        return str_pad((string) ($value % 1000000), 6, '0', STR_PAD_LEFT);
    }

    private function timeStep(int $timestamp): int
    {
        return intdiv($timestamp, 30);
    }

    private function base32Encode(string $binary): string
    {
        if ($binary === '') {
            return '';
        }

        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $bits = '';

        foreach (str_split($binary) as $character) {
            $bits .= str_pad(decbin(ord($character)), 8, '0', STR_PAD_LEFT);
        }

        $encoded = '';

        foreach (str_split($bits, 5) as $chunk) {
            $encoded .= $alphabet[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))];
        }

        return $encoded;
    }

    private function base32Decode(string $base32): string
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $cleaned = strtoupper(str_replace(['=', ' '], '', $base32));
        $buffer = 0;
        $bitsLeft = 0;
        $decoded = '';

        foreach (str_split($cleaned) as $character) {
            $value = strpos($alphabet, $character);

            if ($value === false) {
                return '';
            }

            $buffer = ($buffer << 5) | $value;
            $bitsLeft += 5;

            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $decoded .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $decoded;
    }
}
