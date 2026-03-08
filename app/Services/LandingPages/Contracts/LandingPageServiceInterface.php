<?php

declare(strict_types=1);

namespace App\Services\LandingPages\Contracts;

use App\Models\Center;
use App\Models\CenterLandingPage;
use App\Models\CenterLandingTestimonial;
use App\Models\User;

interface LandingPageServiceInterface
{
    public function getOrCreateForCenter(Center $center): CenterLandingPage;

    public function getForCenter(Center $center): ?CenterLandingPage;

    public function getPublishedForCenter(Center $center): ?CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateMetaSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateHeroSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateAboutSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateContactSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateSocialSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateStylingSection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function updateVisibilitySection(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingPage;

    public function publish(CenterLandingPage $landingPage, ?User $actor = null): CenterLandingPage;

    public function unpublish(CenterLandingPage $landingPage, ?User $actor = null): CenterLandingPage;

    /**
     * @param array<string, mixed> $data
     */
    public function createTestimonial(CenterLandingPage $landingPage, array $data, ?User $actor = null): CenterLandingTestimonial;

    /**
     * @param array<string, mixed> $data
     */
    public function updateTestimonial(CenterLandingTestimonial $testimonial, array $data, ?User $actor = null): CenterLandingTestimonial;

    public function deleteTestimonial(CenterLandingTestimonial $testimonial, ?User $actor = null): void;

    /**
     * @param array<int> $orderedIds
     */
    public function reorderTestimonials(CenterLandingPage $landingPage, array $orderedIds, ?User $actor = null): void;

    public function generatePreviewToken(CenterLandingPage $landingPage): string;

    public function validatePreviewToken(CenterLandingPage $landingPage, string $token): bool;
}
