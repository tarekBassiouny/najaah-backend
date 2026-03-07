# Center Landing Pages Feature

## Overview

Enable centers to create custom landing pages at `{center_subdomain}.najaah.me/` instead of redirecting to `/login`. System landing page remains at `najaah.me/`.

## Design Decisions

| Decision | Choice | Rationale |
|----------|--------|-----------|
| Approach | Simple fixed sections | Not a complex page builder - predictable structure |
| Serving | API + External SPA | Backend provides API, frontend is separate repo |
| Sections | Full marketing page | Hero, About, Courses, Instructors, Testimonials, Contact, Social |
| Editor Layout | Single page with tabs | Each section as a tab for easy navigation |
| Save Strategy | Per-section saves | Save individual sections independently |
| Preview | Embedded iframe | Preview within admin panel using signed token |
| Styling | Moderate | Colors, fonts, spacing - no full CSS injection |

---

## Database Schema

### Table: `center_landing_pages`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Primary key |
| center_id | FK UNIQUE | One landing page per center |
| is_enabled | BOOLEAN | Whether landing page is active |
| status | TINYINT | 0=draft, 1=published |
| **Meta Section** |||
| meta_title_translations | JSON | SEO title per language |
| meta_description_translations | JSON | SEO description per language |
| **Hero Section** |||
| hero_title_translations | JSON | Main headline |
| hero_subtitle_translations | JSON | Supporting text |
| hero_image_url | VARCHAR | Background/hero image |
| hero_cta_text_translations | JSON | Button text |
| hero_cta_url | VARCHAR | Button link |
| **About Section** |||
| about_title_translations | JSON | Section title |
| about_content_translations | JSON | Rich text content |
| about_image_url | VARCHAR | About section image |
| **Visibility Flags** |||
| show_hero | BOOLEAN | Show hero section |
| show_about | BOOLEAN | Show about section |
| show_courses | BOOLEAN | Show featured courses |
| show_instructors | BOOLEAN | Show instructors |
| show_testimonials | BOOLEAN | Show testimonials |
| show_contact | BOOLEAN | Show contact section |
| **Contact Section** |||
| contact_email | VARCHAR | Contact email |
| contact_phone | VARCHAR | Contact phone |
| contact_address_translations | JSON | Address per language |
| **Social Links** |||
| social_links | JSON | Array: [{platform, url}] |
| **Styling Options** |||
| primary_color | VARCHAR(7) | Primary brand color (#RRGGBB) |
| secondary_color | VARCHAR(7) | Secondary color |
| accent_color | VARCHAR(7) | Accent/highlight color |
| heading_font | VARCHAR | Heading font family |
| body_font | VARCHAR | Body text font family |
| button_style | VARCHAR | rounded, square, pill |
| section_spacing | VARCHAR | compact, normal, spacious |
| **Preview** |||
| preview_token | VARCHAR(64) | Signed preview token |
| preview_token_expires_at | TIMESTAMP | Token expiration |
| **Timestamps** |||
| published_at | TIMESTAMP | When published |
| created_at | TIMESTAMP | Created timestamp |
| updated_at | TIMESTAMP | Updated timestamp |
| deleted_at | TIMESTAMP | Soft delete |

### Table: `center_landing_testimonials`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT PK | Primary key |
| center_landing_page_id | FK | Parent landing page |
| name | VARCHAR | Person's name |
| title_translations | JSON | Role/title (e.g., "Student") |
| content_translations | JSON | Testimonial text |
| avatar_url | VARCHAR | Person's photo |
| rating | TINYINT | 1-5 star rating |
| order_index | INT | Display order |
| is_visible | BOOLEAN | Show/hide toggle |
| created_at | TIMESTAMP | Created timestamp |
| updated_at | TIMESTAMP | Updated timestamp |
| deleted_at | TIMESTAMP | Soft delete |

---

## API Endpoints

### Admin API (Authenticated)

Base: `/api/v1/admin/centers/{center}/landing-page`

#### Core Operations

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/` | Get full landing page data |
| DELETE | `/` | Delete landing page |

#### Section Updates (Per-Tab)

| Method | Endpoint | Description |
|--------|----------|-------------|
| PUT | `/meta` | Update SEO meta (title, description) |
| PUT | `/hero` | Update hero section |
| PUT | `/about` | Update about section |
| PUT | `/styling` | Update colors, fonts, spacing |
| PUT | `/contact` | Update contact info |
| PUT | `/social` | Update social links |
| PUT | `/visibility` | Update section show/hide flags |

#### Actions

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/publish` | Set status to published |
| POST | `/unpublish` | Set status to draft |
| POST | `/enable` | Enable landing page |
| POST | `/disable` | Disable landing page |

#### Preview

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/preview-token` | Generate preview token (15 min expiry) |
| GET | `/preview-url` | Get iframe-ready preview URL |

#### Testimonials

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/testimonials` | List all testimonials |
| POST | `/testimonials` | Create testimonial |
| PUT | `/testimonials/{id}` | Update testimonial |
| DELETE | `/testimonials/{id}` | Delete testimonial |
| POST | `/testimonials/reorder` | Reorder testimonials |

#### Media

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/media` | Upload images |

### Public API (Unauthenticated)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/resolve/landing/{slug}` | Get published landing page |
| GET | `/api/v1/resolve/landing/{slug}?preview={token}` | Preview draft with token |

#### Public Response Includes:
- Landing page content (all enabled sections)
- Center branding (logo, colors from center settings)
- Featured courses (limit 6)
- Featured instructors (limit 6)
- Visible testimonials (ordered)
- Contact info & social links
- Styling options

---

## Frontend Integration Guide

### Admin Panel Structure (Tabs)

```
┌─────────────────────────────────────────────────────────────┐
│  Landing Page Editor                        [Preview] [Save]│
├─────────────────────────────────────────────────────────────┤
│ ┌──────┬───────┬─────────┬─────────┬─────────┬────────────┐ │
│ │ Meta │ Hero  │  About  │ Contact │ Social  │  Styling   │ │
│ └──────┴───────┴─────────┴─────────┴─────────┴────────────┘ │
│                                                             │
│  [Tab Content - Form Fields]                                │
│                                                             │
│  ┌─────────────────────────────────────────────────────────┐│
│  │ Section Visibility Toggles                              ││
│  │ □ Hero  □ About  □ Courses  □ Instructors  □ Contact   ││
│  └─────────────────────────────────────────────────────────┘│
│                                                             │
│  Status: Draft/Published    [Publish] [Enable/Disable]      │
└─────────────────────────────────────────────────────────────┘
```

### Preview Implementation

```javascript
// 1. Generate preview token
const { token, preview_url } = await api.post('/landing-page/preview-token');

// 2. Show iframe with preview
<iframe src={preview_url} />

// Token expires in 15 minutes, regenerate as needed
```

### Save Flow (Per Section)

```javascript
// Save only the current tab's data
await api.put('/landing-page/hero', {
  hero_title_translations: { en: "Welcome", ar: "أهلاً" },
  hero_subtitle_translations: { en: "Learn with us" },
  hero_image_url: "https://...",
  hero_cta_text_translations: { en: "Get Started" },
  hero_cta_url: "/register"
});

// Success: Show toast "Hero section saved"
```

### Styling Options

```javascript
// Available fonts (predefined list)
const fonts = [
  "Inter", "Poppins", "Roboto", "Open Sans",
  "Playfair Display", "Cairo", "Tajawal"
];

// Button styles
const buttonStyles = ["rounded", "square", "pill"];

// Section spacing
const spacingOptions = ["compact", "normal", "spacious"];
```

---

## Migration Flow (Backward Compatibility)

```
┌─────────────────────────┐
│ Center visits subdomain │
└───────────┬─────────────┘
            │
            ▼
┌─────────────────────────┐     ┌─────────────────────┐
│ Has landing page?       │──No─▶│ Redirect to /login  │
└───────────┬─────────────┘     └─────────────────────┘
            │ Yes
            ▼
┌─────────────────────────┐     ┌─────────────────────┐
│ is_enabled = true?      │──No─▶│ Redirect to /login  │
└───────────┬─────────────┘     └─────────────────────┘
            │ Yes
            ▼
┌─────────────────────────┐     ┌─────────────────────┐
│ status = published?     │──No─▶│ Redirect to /login  │
└───────────┬─────────────┘     └─────────────────────┘
            │ Yes
            ▼
┌─────────────────────────┐
│ Show landing page       │
└─────────────────────────┘
```

---

## Permissions

| Permission | Description | Roles |
|------------|-------------|-------|
| `landing_page.manage` | Full CRUD access | center_owner, center_admin |

---

## Files to Create

### Phase 1: Database & Models (8 files)
- `database/migrations/2026_03_07_000001_create_center_landing_pages_table.php`
- `database/migrations/2026_03_07_000002_create_center_landing_testimonials_table.php`
- `app/Models/CenterLandingPage.php`
- `app/Models/CenterLandingTestimonial.php`
- `app/Enums/LandingPageStatus.php`
- `database/factories/CenterLandingPageFactory.php`
- `database/factories/CenterLandingTestimonialFactory.php`
- Modify: `app/Models/Center.php`

### Phase 2: Service Layer (4 files)
- `app/Services/LandingPages/Contracts/LandingPageServiceInterface.php`
- `app/Services/LandingPages/Contracts/SubdomainResolverServiceInterface.php`
- `app/Services/LandingPages/LandingPageService.php`
- `app/Services/LandingPages/SubdomainResolverService.php`
- Modify: `app/Providers/AppServiceProvider.php`

### Phase 3: Admin API (14 files)
- `routes/api/v1/admin/landing-pages.php`
- `app/Http/Controllers/Admin/LandingPages/LandingPageController.php`
- `app/Http/Controllers/Admin/LandingPages/LandingPageSectionController.php`
- `app/Http/Controllers/Admin/LandingPages/LandingPageTestimonialController.php`
- `app/Http/Controllers/Admin/LandingPages/LandingPageMediaController.php`
- `app/Http/Controllers/Admin/LandingPages/LandingPagePreviewController.php`
- `app/Http/Requests/Admin/LandingPages/UpdateMetaSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateHeroSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateAboutSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateStylingSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateContactSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateSocialSectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateVisibilitySectionRequest.php`
- `app/Http/Requests/Admin/LandingPages/StoreLandingTestimonialRequest.php`
- `app/Http/Requests/Admin/LandingPages/UpdateLandingTestimonialRequest.php`
- `app/Http/Requests/Admin/LandingPages/ReorderTestimonialsRequest.php`
- `app/Http/Resources/Admin/LandingPages/LandingPageResource.php`
- `app/Http/Resources/Admin/LandingPages/LandingTestimonialResource.php`
- Modify: `bootstrap/app.php`

### Phase 4: Public API (2 files)
- `app/Http/Controllers/LandingPageResolveController.php`
- `app/Http/Resources/Public/LandingPagePublicResource.php`
- Modify: `routes/api/v1/resolve.php`

### Phase 5: Subdomain Resolution (2 files)
- `app/Http/Middleware/ResolveCenterSubdomain.php`
- `app/Http/Controllers/LandingPageController.php`
- Modify: `routes/web.php`
- Modify: `bootstrap/app.php`
- Modify: `config/app.php`

### Phase 6: Quality (6 files)
- `tests/Feature/Admin/LandingPages/LandingPageControllerTest.php`
- `tests/Feature/Admin/LandingPages/LandingPageSectionControllerTest.php`
- `tests/Feature/Admin/LandingPages/LandingPageTestimonialControllerTest.php`
- `tests/Feature/Public/LandingPageResolveControllerTest.php`
- `tests/Unit/Services/LandingPages/LandingPageServiceTest.php`
- `tests/Unit/Services/LandingPages/SubdomainResolverServiceTest.php`
- Modify: `database/seeders/PermissionSeeder.php`

---

## Summary

| Phase | Description | New Files | Modified |
|-------|-------------|-----------|----------|
| 1 | Database & Models | 7 | 1 |
| 2 | Service Layer | 4 | 1 |
| 3 | Admin API | 18 | 1 |
| 4 | Public API | 2 | 1 |
| 5 | Subdomain Resolution | 2 | 3 |
| 6 | Quality & Tests | 6 | 1 |
| **Total** | | **39** | **8** |
