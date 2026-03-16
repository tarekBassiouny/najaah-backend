<?php

declare(strict_types=1);

namespace App\Services\VideoAccess;

use App\Models\VideoCodeBatch;

class VideoCodeGenerator
{
    /**
     * Characters used for code generation (ambiguous characters removed).
     */
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    private const GROUP_LENGTH = 4;

    private const RAW_CODE_LENGTH = self::GROUP_LENGTH * 3;

    private const MAX_TOKEN_SPACE = 1048576; // 32^4

    /**
     * Generate a code for a specific sequence number in a batch.
     *
     * Format: XXXX-XXXX-XXXX
     */
    public function generateCode(VideoCodeBatch $batch, int $sequence): string
    {
        $payload = $this->generateRawPayload($batch, $sequence);
        $checksum = $this->generateChecksum($payload, $batch->secret_key);

        return $this->formatCode($payload.$checksum);
    }

    /**
     * Parse and validate a code string.
     *
     * @return array{batch: VideoCodeBatch, sequence: int}|null
     */
    public function parseAndValidate(string $code): ?array
    {
        $normalized = $this->normalizeCode($code);

        if (strlen($normalized) !== self::RAW_CODE_LENGTH || ! $this->isAlphabetToken($normalized)) {
            return null;
        }

        $batchCode = substr($normalized, 0, self::GROUP_LENGTH);
        $sequenceToken = substr($normalized, self::GROUP_LENGTH, self::GROUP_LENGTH);
        $checksum = substr($normalized, self::GROUP_LENGTH * 2, self::GROUP_LENGTH);

        $sequenceValue = $this->decodeToken($sequenceToken);
        if ($sequenceValue === null) {
            return null;
        }

        $sequence = $sequenceValue + 1;

        /** @var VideoCodeBatch|null $batch */
        $batch = VideoCodeBatch::query()
            ->where('batch_code', $batchCode)
            ->whereNull('deleted_at')
            ->first();

        if ($batch === null) {
            return null;
        }

        // Validate sequence is within range
        if ($sequence < 1 || $sequence > $batch->quantity) {
            return null;
        }

        $expectedRaw = $this->generateRawPayload($batch, $sequence)
            .$this->generateChecksum($this->generateRawPayload($batch, $sequence), $batch->secret_key);

        if (! hash_equals($expectedRaw, $normalized) || substr($expectedRaw, -self::GROUP_LENGTH) !== $checksum) {
            return null;
        }

        return [
            'batch' => $batch,
            'sequence' => $sequence,
        ];
    }

    /**
     * Generate a unique batch code prefix.
     */
    public function generateBatchCode(int $centerId): string
    {
        unset($centerId);

        if (VideoCodeBatch::withTrashed()->count() >= self::MAX_TOKEN_SPACE) {
            throw new \RuntimeException('The short video code space is exhausted.');
        }

        for ($attempt = 0; $attempt < 1000; $attempt++) {
            $batchCode = $this->randomToken(self::GROUP_LENGTH);

            if (! VideoCodeBatch::withTrashed()->where('batch_code', $batchCode)->exists()) {
                return $batchCode;
            }
        }

        throw new \RuntimeException('Unable to generate a unique batch code.');
    }

    /**
     * Generate a random secret key for HMAC signing.
     */
    public function generateSecretKey(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Normalize input by removing separators and uppercasing.
     */
    public function normalizeCode(string $code): string
    {
        $normalized = preg_replace('/[^A-Z0-9]/', '', strtoupper(trim($code)));

        return is_string($normalized) ? $normalized : '';
    }

    /**
     * Format a normalized code for display.
     */
    public function formatCode(string $code): string
    {
        $normalized = $this->normalizeCode($code);

        if (strlen($normalized) !== self::RAW_CODE_LENGTH) {
            return $normalized;
        }

        return implode('-', str_split($normalized, self::GROUP_LENGTH));
    }

    private function generateRawPayload(VideoCodeBatch $batch, int $sequence): string
    {
        $batchCode = strtoupper(trim((string) $batch->batch_code));
        if (strlen($batchCode) !== self::GROUP_LENGTH || ! $this->isAlphabetToken($batchCode)) {
            throw new \RuntimeException('Invalid batch code format.');
        }

        if ($sequence < 1 || $sequence > self::MAX_TOKEN_SPACE) {
            throw new \RuntimeException('Sequence exceeds the supported code space.');
        }

        return $batchCode.$this->encodeValue($sequence - 1, self::GROUP_LENGTH);
    }

    private function generateChecksum(string $payload, string $secretKey): string
    {
        $hmac = hash_hmac('sha256', $payload, $secretKey, true);
        $chunk = substr($hmac, 0, 4);
        $unpacked = unpack('N', $chunk);

        if ($unpacked === false || ! array_key_exists(1, $unpacked)) {
            throw new \RuntimeException('Failed to encode checksum.');
        }

        $value = $unpacked[1] >> 12;

        return $this->encodeValue($value, self::GROUP_LENGTH);
    }

    private function randomToken(int $length): string
    {
        $token = '';
        $maxIndex = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $token .= self::ALPHABET[random_int(0, $maxIndex)];
        }

        return $token;
    }

    private function encodeValue(int $value, int $length): string
    {
        if ($value < 0 || $value >= (32 ** $length)) {
            throw new \RuntimeException('Encoded value is outside the supported token space.');
        }

        $token = array_fill(0, $length, self::ALPHABET[0]);

        for ($index = $length - 1; $index >= 0; $index--) {
            $token[$index] = self::ALPHABET[$value % 32];
            $value = intdiv($value, 32);
        }

        return implode('', $token);
    }

    private function decodeToken(string $token): ?int
    {
        $value = 0;

        foreach (str_split($token) as $character) {
            $position = strpos(self::ALPHABET, $character);

            if ($position === false) {
                return null;
            }

            $value = ($value * 32) + $position;
        }

        return $value;
    }

    private function isAlphabetToken(string $token): bool
    {
        foreach (str_split($token) as $character) {
            if (strpos(self::ALPHABET, $character) === false) {
                return false;
            }
        }

        return true;
    }
}
