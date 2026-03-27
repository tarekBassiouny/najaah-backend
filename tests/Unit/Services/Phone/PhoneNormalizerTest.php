<?php

declare(strict_types=1);

use App\Services\Phone\PhoneNormalizer;

it('normalizes split phone and country code inputs', function (): void {
    $normalizer = new PhoneNormalizer;

    expect($normalizer->normalize('1012345678', '+20'))->toBe('+201012345678')
        ->and($normalizer->normalize('1012345678', '0020'))->toBe('+201012345678');
});

it('normalizes egyptian local and international variants to one canonical value', function (): void {
    $normalizer = new PhoneNormalizer;

    $expected = '+201012345678';

    expect($normalizer->normalize('+201012345678'))->toBe($expected)
        ->and($normalizer->normalize('00201012345678'))->toBe($expected)
        ->and($normalizer->normalize('201012345678'))->toBe($expected)
        ->and($normalizer->normalize('01012345678'))->toBe($expected);
});

it('returns null for unusable input', function (): void {
    $normalizer = new PhoneNormalizer;

    expect($normalizer->normalize(null))->toBeNull()
        ->and($normalizer->normalize(''))->toBeNull()
        ->and($normalizer->normalize('abc'))->toBeNull();
});
