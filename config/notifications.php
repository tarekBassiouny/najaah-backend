<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Student Notification Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for student notifications including app download links
    | and message templates for various notification types.
    |
    */

    'student' => [
        /*
        |--------------------------------------------------------------------------
        | App Download Links
        |--------------------------------------------------------------------------
        */
        'app_links' => [
            'ios' => env('APP_LINK_IOS', 'https://apps.apple.com/app/xyz-lms'),
            'android' => env('APP_LINK_ANDROID', 'https://play.google.com/store/apps/details?id=com.xyz.lms'),
        ],

        /*
        |--------------------------------------------------------------------------
        | Message Templates
        |--------------------------------------------------------------------------
        |
        | Available placeholders:
        | - {center_name}: Name of the center
        | - {course_name}: Name of the course (enrollment only)
        | - {ios_link}: iOS app download link
        | - {android_link}: Android app download link
        | - {phone}: Student's phone number
        |
        */
        'templates' => [
            'welcome' => 'Welcome to {center_name}! Download our app: iOS: {ios_link} | Android: {android_link}',
            'enrollment' => 'You have been enrolled in "{course_name}" at {center_name}. Open the app to start learning!',
        ],
    ],
];
