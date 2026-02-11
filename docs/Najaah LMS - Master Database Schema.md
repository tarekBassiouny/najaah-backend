# Najaah LMS – Master Database Schema
Version: 1.1  
Status: Production-Ready

---

# 1. Centers & Roles

## centers
- id (BIGINT, PK)
- slug (STRING)
- type (TINYINT: 0=unbranded, 1=branded)
- is_demo (BOOLEAN)
- name_translations (JSON)
- description_translations (JSON, nullable)
- logo_url (STRING, nullable)
- primary_color (STRING, nullable)
- default_view_limit (INT)
- allow_extra_view_requests (BOOLEAN)
- pdf_download_permission (BOOLEAN)
- device_limit (INT)
- created_at
- updated_at
- deleted_at

---

## roles
- id
- name (STRING)
- name_translations (JSON)
- created_at
- updated_at
- deleted_at

### Seeded Roles:
- SUPER_ADMIN
- CENTER_OWNER
- CENTER_ADMIN
- CONTENT_MANAGER
- STUDENT

---

## users
- id
- center_id (nullable for admins, required for students)
- name
- email (nullable)
- phone (nullable)
- country_code
- username (nullable)
- password (nullable)
- avatar_url (nullable)
- last_login_at (nullable)
- status (TINYINT: 0 inactive, 1 active, 2 banned)
- is_student (BOOLEAN)
- created_at
- updated_at
- deleted_at

---

## role_user
- id
- user_id
- role_id
- created_at
- updated_at
- deleted_at

---

## user_centers
- id
- user_id
- center_id
- created_at
- updated_at
- deleted_at

---

# 2. Student Identity & Authentication

## user_devices
- id
- user_id
- device_id
- model
- os_version
- status (TINYINT: 0 active, 1 revoked, 2 pending)
- approved_at (nullable)
- last_used_at (nullable)
- created_at
- updated_at
- deleted_at

---

## otp_requests
- id
- user_id (nullable)
- phone
- country_code
- otp_code
- otp_token
- provider (sms/whatsapp/email/voice)
- expires_at
- consumed_at (nullable)
- created_at
- updated_at
- deleted_at

---

## jwt_tokens
- id
- user_id
- access_token (hashed)
- refresh_token (hashed)
- expires_at
- refresh_expires_at
- revoked_at (nullable)
- device_id (nullable)
- created_at
- updated_at
- deleted_at

---

# 3. Courses & Content Structure

## courses
- id
- center_id
- category_id (nullable)
- title_translations (JSON)
- description_translations (JSON)
- instructor_translations (JSON, nullable)
- college_translations (JSON, nullable)
- grade_year (STRING, nullable)
- difficulty_level (TINYINT, nullable)
- language (STRING, nullable)
- course_code (STRING, nullable)
- tags (JSON, multilingual, nullable)
- status (TINYINT: 0 draft, 1 uploading, 2 ready, 3 published, 4 archived)
- is_published (BOOLEAN)
- thumbnail_url (nullable)
- duration_minutes (nullable)
- is_featured (BOOLEAN)
- is_demo (BOOLEAN)
- created_by (user_id)
- cloned_from_id (nullable)
- publish_at (nullable)
- created_at
- updated_at
- deleted_at

---

## sections
- id
- course_id
- title_translations (JSON)
- description_translations (JSON, nullable)
- order_index
- visible (BOOLEAN)
- is_demo (BOOLEAN)
- created_at
- updated_at
- deleted_at

---

# 4. Videos

## videos
- id
- title_translations (JSON)
- description_translations (JSON, nullable)
- source_type (TINYINT: 0=url, 1=native)
- source_provider (STRING: bunny/youtube/zoom/vimeo/custom)
- source_id (nullable)
- source_url (nullable)
- duration_seconds (nullable)
- lifecycle_status (TINYINT)
- tags (JSON, nullable)
- created_by
- upload_session_id (BIGINT, nullable)
- original_filename (STRING, nullable)
- encoding_status (TINYINT: 0 pending, 1 uploading, 2 processing, 3 ready)
- is_demo (BOOLEAN)
- thumbnail_url (STRING, nullable)
- thumbnail_urls (JSON, nullable)
- created_at
- updated_at
- deleted_at

---

## course_videos
- id
- course_id
- video_id
- section_id (nullable)
- order_index
- visible (BOOLEAN)
- view_limit_override (nullable)
- created_at
- updated_at
- deleted_at

---

# 4.1 Video Upload Sessions (NEW)

## video_upload_sessions
- id
- center_id
- uploaded_by (user_id)
- bunny_upload_id (STRING)
- upload_status (TINYINT: 0 pending, 1 uploading, 2 uploaded, 3 processing, 4 ready, 5 failed)
- progress_percent (INT)
- error_message (TEXT, nullable)
- created_at
- updated_at
- deleted_at

---

# 5. PDFs

## pdfs
- id
- title_translations (JSON)
- description_translations (JSON, nullable)
- source_type (TINYINT: 0=url, 1=native)
- source_provider
- source_id (nullable)
- source_url (nullable)
- file_size_kb (nullable)
- file_extension
- is_demo (BOOLEAN)
- created_by
- created_at
- updated_at
- deleted_at

---

## course_pdfs
- id
- course_id
- pdf_id
- section_id (nullable)
- video_id (nullable)
- order_index
- visible (BOOLEAN)
- download_permission_override (nullable)
- created_at
- updated_at
- deleted_at

---

# 6. Enrollment Module

## enrollments
- id
- user_id
- course_id
- center_id
- status (ACTIVE / DEACTIVATED / CANCELLED)
- enrolled_at
- expires_at (nullable)
- created_at
- updated_at
- deleted_at

---

# 7. Settings Hierarchy  
Override priority: **Student > Video > Course > Center**

## center_settings
- id
- center_id
- settings (JSON)
- created_at
- updated_at
- deleted_at

## course_settings
- id
- course_id
- settings (JSON)
- created_at
- updated_at
- deleted_at

## video_settings
- id
- video_id
- settings (JSON)
- created_at
- updated_at
- deleted_at

## student_settings
- id
- user_id
- settings (JSON)
- created_at
- updated_at
- deleted_at

---

# 8. Playback & Analytics

## playback_sessions
- id
- user_id
- video_id
- device_id
- started_at
- ended_at (nullable)
- progress_percent
- is_full_play (BOOLEAN)
- created_at
- updated_at
- deleted_at

---

# 9. Audit Logging

## audit_logs
- id
- user_id (nullable)
- action
- entity_type
- entity_id
- metadata (JSON)
- created_at
- updated_at
- deleted_at

---

# 10. Optional Tables

## categories
- id
- title_translations (JSON)
- description_translations (JSON, nullable)
- parent_id (nullable)
- order_index
- created_at
- updated_at
- deleted_at

---

# 11. Instructors (NEW)

## instructors
- id (BIGINT, PK)
- center_id (nullable) 
  # nullable = global instructors allowed (optional)
- name_translations (JSON)
- bio_translations (JSON, nullable)
- title_translations (JSON, nullable)          # e.g., Professor, Doctor, Trainer
- avatar_url (STRING, nullable)
- email (STRING, nullable)
- phone (STRING, nullable)
- social_links (JSON, nullable)                # { "facebook": "", "instagram": "", ... }
- created_by (user_id)
- created_at
- updated_at
- deleted_at

## course_instructors
# Pivot table for multi-instructor courses (M:N)
- id
- course_id
- instructor_id
- role (STRING, nullable)                      # lead, assistant, guest (optional)
- created_at
- updated_at
- deleted_at

---

# End of Document
