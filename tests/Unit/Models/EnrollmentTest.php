<?php

declare(strict_types=1);

use App\Models\Enrollment;

test('statusLabel returns correct labels for all statuses', function (): void {
    $enrollment = new Enrollment;

    $enrollment->status = Enrollment::STATUS_ACTIVE;
    expect($enrollment->statusLabel())->toBe('ACTIVE');

    $enrollment->status = Enrollment::STATUS_DEACTIVATED;
    expect($enrollment->statusLabel())->toBe('DEACTIVATED');

    $enrollment->status = Enrollment::STATUS_CANCELLED;
    expect($enrollment->statusLabel())->toBe('CANCELLED');

    $enrollment->status = Enrollment::STATUS_PENDING;
    expect($enrollment->statusLabel())->toBe('PENDING');
});

test('statusLabel returns UNKNOWN for invalid status', function (): void {
    $enrollment = new Enrollment;
    $enrollment->status = 999;

    expect($enrollment->statusLabel())->toBe('UNKNOWN');
});

test('statusLabels returns all status mappings', function (): void {
    $labels = Enrollment::statusLabels();

    expect($labels)->toBe([
        Enrollment::STATUS_ACTIVE => 'ACTIVE',
        Enrollment::STATUS_DEACTIVATED => 'DEACTIVATED',
        Enrollment::STATUS_CANCELLED => 'CANCELLED',
        Enrollment::STATUS_PENDING => 'PENDING',
    ]);
});
