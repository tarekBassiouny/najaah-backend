# Center Landing Pages - Frontend API Documentation

## Overview

This feature enables centers to create custom landing pages displayed at `{center_subdomain}.najaah.me/`. The system provides a comprehensive admin API for managing landing page content and a public API for fetching published pages.

## Architecture

- **Admin Panel**: Uses tabbed interface for editing sections
- **Public Frontend (SPA)**: Fetches landing page data via API and renders it
- **Preview Mode**: Signed tokens allow previewing unpublished changes

---

## Public API Endpoints

### Get Landing Page by Center Slug

Retrieves a published landing page for public display.

```
GET /api/v1/resolve/landing-page/{slug}
```

**Parameters:**
- `slug` (path): Center slug (e.g., `my-center`)
- `preview_token` (query, optional): Preview token for viewing draft pages

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Landing page retrieved successfully",
  "data": {
    "center": {
      "id": 1,
      "slug": "my-center",
      "name": "My Center",
      "logo_url": "https://..."
    },
    "meta": {
      "title": "Welcome to My Center",
      "description": "SEO description...",
      "keywords": "education, learning"
    },
    "hero": {
      "title": "Welcome",
      "subtitle": "Learn with us",
      "background_url": "https://...",
      "cta_text": "Get Started",
      "cta_url": "/register"
    },
    "about": {
      "title": "About Us",
      "content": "We are a leading center...",
      "image_url": "https://..."
    },
    "contact": {
      "email": "contact@example.com",
      "phone": "+20 123 456 7890",
      "address": "123 Education St"
    },
    "social": {
      "facebook": "https://facebook.com/...",
      "twitter": null,
      "instagram": "https://instagram.com/...",
      "youtube": null,
      "linkedin": null,
      "tiktok": null
    },
    "styling": {
      "primary_color": "#3B82F6",
      "secondary_color": "#1E40AF",
      "font_family": "Inter"
    },
    "sections": {
      "show_hero": true,
      "show_about": true,
      "show_courses": true,
      "show_testimonials": true,
      "show_contact": true
    },
    "testimonials": [
      {
        "id": 1,
        "author_name": "John Doe",
        "author_title": "Student",
        "author_image_url": "https://...",
        "content": "Great experience!",
        "rating": 5
      }
    ]
  },
  "meta": {
    "is_preview": false
  }
}
```

**Notes:**
- `hero`, `about`, `contact` will be `null` if their respective `show_*` flag is `false`
- Only `is_active: true` testimonials are included
- Testimonials are sorted by `sort_order`

**Errors:**
- `404 NOT_FOUND`: Center not found, landing page not found, or unpublished without valid preview token

---

## Admin API Endpoints

All admin endpoints require:
- Authentication: `Authorization: Bearer {jwt_token}`
- API Key: `X-Api-Key: {center_api_key}`
- Permission: `landing_page.manage`

### Base URL Pattern
```
/api/v1/admin/centers/{center_id}/landing-page/...
```

### Get/Create Landing Page

Returns existing landing page or creates a new draft.

```
GET /api/v1/admin/centers/{center_id}/landing-page
```

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Landing page retrieved successfully",
  "data": {
    "id": 1,
    "center_id": 1,
    "status": 0,
    "status_label": "Draft",
    "is_published": false,
    "meta": {
      "title": null,
      "description": null,
      "keywords": null
    },
    "hero": {
      "title": null,
      "title_translations": null,
      "subtitle": null,
      "subtitle_translations": null,
      "background_url": null,
      "cta_text": null,
      "cta_url": null
    },
    "about": { ... },
    "contact": { ... },
    "social": { ... },
    "styling": { ... },
    "visibility": {
      "show_hero": true,
      "show_about": true,
      "show_courses": true,
      "show_testimonials": true,
      "show_contact": true
    },
    "testimonials": [],
    "created_at": "2026-03-08T10:00:00.000Z",
    "updated_at": "2026-03-08T10:00:00.000Z"
  }
}
```

**Status Values:**
- `0` = Draft
- `1` = Published

---

### Update Sections

Each section is updated independently via PATCH:

#### Meta Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/meta
```
```json
{
  "meta_title": "Page Title",
  "meta_description": "SEO Description",
  "meta_keywords": "keyword1, keyword2"
}
```

#### Hero Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/hero
```
```json
{
  "hero_title": { "en": "Welcome", "ar": "مرحبا" },
  "hero_subtitle": { "en": "Learn with us", "ar": "تعلم معنا" },
  "hero_background_url": "https://...",
  "hero_cta_text": "Get Started",
  "hero_cta_url": "/register"
}
```

#### About Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/about
```
```json
{
  "about_title": { "en": "About Us", "ar": "معلومات عنا" },
  "about_content": { "en": "Long content...", "ar": "المحتوى..." },
  "about_image_url": "https://..."
}
```

#### Contact Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/contact
```
```json
{
  "contact_email": "contact@example.com",
  "contact_phone": "+20 123 456 7890",
  "contact_address": "123 Education Street"
}
```

#### Social Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/social
```
```json
{
  "social_facebook": "https://facebook.com/...",
  "social_twitter": "https://twitter.com/...",
  "social_instagram": "https://instagram.com/...",
  "social_youtube": "https://youtube.com/...",
  "social_linkedin": "https://linkedin.com/...",
  "social_tiktok": "https://tiktok.com/..."
}
```

#### Styling Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/styling
```
```json
{
  "primary_color": "#3B82F6",
  "secondary_color": "#1E40AF",
  "font_family": "Inter"
}
```
**Note:** Colors must be valid hex format (`#RGB`, `#RRGGBB`, or `#RRGGBBAA`)

#### Visibility Section
```
PATCH /api/v1/admin/centers/{center_id}/landing-page/sections/visibility
```
```json
{
  "show_hero": true,
  "show_about": true,
  "show_courses": true,
  "show_testimonials": true,
  "show_contact": false
}
```

---

### Publish/Unpublish

```
POST /api/v1/admin/centers/{center_id}/landing-page/publish
POST /api/v1/admin/centers/{center_id}/landing-page/unpublish
```

---

### Testimonials CRUD

#### List Testimonials
```
GET /api/v1/admin/centers/{center_id}/landing-page/testimonials
```

#### Create Testimonial
```
POST /api/v1/admin/centers/{center_id}/landing-page/testimonials
```
```json
{
  "author_name": "John Doe",
  "author_title": "Student",
  "author_image_url": "https://...",
  "content": { "en": "Great experience!", "ar": "تجربة رائعة!" },
  "rating": 5,
  "is_active": true
}
```

#### Update Testimonial
```
PUT /api/v1/admin/centers/{center_id}/landing-page/testimonials/{testimonial_id}
```

#### Delete Testimonial
```
DELETE /api/v1/admin/centers/{center_id}/landing-page/testimonials/{testimonial_id}
```

#### Reorder Testimonials
```
POST /api/v1/admin/centers/{center_id}/landing-page/testimonials/reorder
```
```json
{
  "testimonial_ids": [3, 1, 2]
}
```

---

### Media Upload

#### Upload Hero Background
```
POST /api/v1/admin/centers/{center_id}/landing-page/media/hero-background
Content-Type: multipart/form-data

image: <file> (max 5MB)
```

#### Upload About Image
```
POST /api/v1/admin/centers/{center_id}/landing-page/media/about-image
Content-Type: multipart/form-data

image: <file> (max 5MB)
```

#### Upload Testimonial Author Image
```
POST /api/v1/admin/centers/{center_id}/landing-page/media/testimonial-image
Content-Type: multipart/form-data

image: <file> (max 2MB)
```

**Response:**
```json
{
  "success": true,
  "message": "Image uploaded successfully",
  "data": {
    "url": "https://storage.example.com/centers/1/landing-page/hero/hero_123456789.jpg"
  }
}
```

---

### Preview Token

Generate a temporary token to preview unpublished changes.

```
POST /api/v1/admin/centers/{center_id}/landing-page/preview-token
```

**Response:**
```json
{
  "success": true,
  "message": "Preview token generated successfully",
  "data": {
    "token": "abc123...64chars...",
    "preview_url": "https://my-center.najaah.me/?preview_token=abc123...",
    "expires_in_minutes": 30
  }
}
```

---

## Translation Fields

Fields with `_translations` suffix expect/return an object:
```json
{
  "en": "English content",
  "ar": "المحتوى العربي"
}
```

In public API responses, these are resolved to a single value based on the request's `Accept-Language` header or `?lang=` parameter.

---

## Frontend Implementation Notes

### Admin Panel Structure
```
Landing Page Editor
├── Tab: Meta (SEO)
├── Tab: Hero Section
├── Tab: About Section
├── Tab: Contact
├── Tab: Social Links
├── Tab: Styling (Colors, Fonts)
├── Tab: Visibility (Toggle sections)
└── Tab: Testimonials (CRUD list)
```

### Public Landing Page Structure
```
Landing Page (SPA)
├── Hero Section (if show_hero)
├── About Section (if show_about)
├── Courses Section (if show_courses) - fetch from existing course API
├── Testimonials Section (if show_testimonials)
└── Contact Section (if show_contact)
```

### Preview Flow
1. Admin clicks "Preview" button
2. Frontend calls `POST .../preview-token`
3. Opens `preview_url` in new tab/iframe
4. Public SPA detects `preview_token` query param
5. Fetches landing page with token → shows draft version

### Courses Section
The `show_courses` flag indicates whether to display a courses section. The actual course data should be fetched from the existing courses API:
```
GET /api/v1/mobile/centers/{center_id}/courses
```

---

## Error Responses

All errors follow the standard format:
```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Human readable message"
  }
}
```

Common error codes:
- `NOT_FOUND`: Resource not found (404)
- `VALIDATION_ERROR`: Invalid request data (422)
- `UNAUTHORIZED`: Missing/invalid auth (401)
- `FORBIDDEN`: Insufficient permissions (403)
