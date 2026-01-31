<?php

declare(strict_types=1);

use App\Enums\EnrollmentStatus;
use App\Models\Enrollment;

test('statusLabel returns correct labels for all statuses', function (): void {
    $enrollment = new Enrollment;

    $enrollment->status = EnrollmentStatus::Active;
    expect($enrollment->statusLabel())->toBe('ACTIVE');

    $enrollment->status = EnrollmentStatus::Deactivated;
    expect($enrollment->statusLabel())->toBe('DEACTIVATED');

    $enrollment->status = EnrollmentStatus::Cancelled;
    expect($enrollment->statusLabel())->toBe('CANCELLED');

    $enrollment->status = EnrollmentStatus::Pending;
    expect($enrollment->statusLabel())->toBe('PENDING');
});

test('statusLabels returns all status mappings', function (): void {
    $labels = Enrollment::statusLabels();

    expect($labels)->toBe([
        0 => 'ACTIVE',
        1 => 'DEACTIVATED',
        2 => 'CANCELLED',
        3 => 'PENDING',
    ]);
});
