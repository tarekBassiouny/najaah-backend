<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LandingPages\LandingPageController;
use App\Http\Controllers\Admin\LandingPages\LandingPageMediaController;
use App\Http\Controllers\Admin\LandingPages\LandingPagePreviewController;
use App\Http\Controllers\Admin\LandingPages\LandingPageSectionController;
use App\Http\Controllers\Admin\LandingPages\LandingPageTestimonialController;
use Illuminate\Support\Facades\Route;

Route::middleware(['require.permission:landing_page.manage', 'scope.center', 'ensure.branded.center'])->group(function (): void {
    // Landing page main routes
    Route::get('/centers/{center}/landing-page', [LandingPageController::class, 'show'])
        ->whereNumber('center');
    Route::post('/centers/{center}/landing-page/publish', [LandingPageController::class, 'publish'])
        ->whereNumber('center');
    Route::post('/centers/{center}/landing-page/unpublish', [LandingPageController::class, 'unpublish'])
        ->whereNumber('center');

    // Section update routes
    Route::patch('/centers/{center}/landing-page/sections/meta', [LandingPageSectionController::class, 'updateMeta'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/hero', [LandingPageSectionController::class, 'updateHero'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/about', [LandingPageSectionController::class, 'updateAbout'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/contact', [LandingPageSectionController::class, 'updateContact'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/social', [LandingPageSectionController::class, 'updateSocial'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/styling', [LandingPageSectionController::class, 'updateStyling'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/sections/visibility', [LandingPageSectionController::class, 'updateVisibility'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/layout/order', [LandingPageSectionController::class, 'updateLayoutOrder'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/layout/variants', [LandingPageSectionController::class, 'updateLayoutVariants'])
        ->whereNumber('center');
    Route::patch('/centers/{center}/landing-page/layout/styles', [LandingPageSectionController::class, 'updateLayoutStyles'])
        ->whereNumber('center');

    // Testimonial routes
    Route::get('/centers/{center}/landing-page/testimonials', [LandingPageTestimonialController::class, 'index'])
        ->whereNumber('center');
    Route::post('/centers/{center}/landing-page/testimonials', [LandingPageTestimonialController::class, 'store'])
        ->whereNumber('center');
    Route::get('/centers/{center}/landing-page/testimonials/{testimonial}', [LandingPageTestimonialController::class, 'show'])
        ->whereNumber(['center', 'testimonial']);
    Route::put('/centers/{center}/landing-page/testimonials/{testimonial}', [LandingPageTestimonialController::class, 'update'])
        ->whereNumber(['center', 'testimonial']);
    Route::delete('/centers/{center}/landing-page/testimonials/{testimonial}', [LandingPageTestimonialController::class, 'destroy'])
        ->whereNumber(['center', 'testimonial']);
    Route::post('/centers/{center}/landing-page/testimonials/reorder', [LandingPageTestimonialController::class, 'reorder'])
        ->whereNumber('center');

    // Media upload routes
    Route::post('/centers/{center}/landing-page/media/hero-background', [LandingPageMediaController::class, 'uploadHeroBackground'])
        ->whereNumber('center');
    Route::post('/centers/{center}/landing-page/media/about-image', [LandingPageMediaController::class, 'uploadAboutImage'])
        ->whereNumber('center');
    Route::post('/centers/{center}/landing-page/media/testimonial-image', [LandingPageMediaController::class, 'uploadTestimonialImage'])
        ->whereNumber('center');

    // Preview route
    Route::post('/centers/{center}/landing-page/preview-token', [LandingPagePreviewController::class, 'generateToken'])
        ->whereNumber('center');
});
