# Project Overview

## Product Shape
Najaah LMS is a multi-tenant learning platform connecting centers with students through video-first courses.

## Tech Stack
- Laravel 11 / PHP 8.4
- MySQL 8 via Laravel Sail
- Pest for tests
- Scribe for API docs
- Bunny Stream for video delivery
- Bunny CDN for storage-backed downloads

## Core Tenancy Model
- Branded centers have isolated student identities.
- Unbranded centers share student identity across unbranded centers.
- Center scoping must be enforced in queries, authorization, and API surface.

## Actor Model
- Super admin: global system access
- Center owner/admin: center-scoped management
- Content manager: content-focused access
- Student: mobile content consumption

## Auth Split
- Students: phone plus OTP, device registration, JWT access and refresh tokens
- Admins: email/password with Sanctum SPA sessions

## Core Domain Areas
- Courses, sections, videos, and PDFs
- Enrollments and request workflows
- Playback sessions and view limits
- Device binding and device-change requests
- Center, course, video, and student-level settings
