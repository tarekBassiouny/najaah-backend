# Student & Parent Web Portal — Student Web API Contract

## Status: Draft
## Source Phase: 3 — Student Web Portal
## Audience: Portal frontend
## Related Plan: [student-parent-web-portal.md](../../feature/student-parent-web-portal.md)

---

## Purpose

Define the student-facing web API surface that reuses existing mobile behavior under web auth.

---

## Scope

This contract covers:
- student web routes under `/api/v1/web`
- guest-browsable browsing routes
- authenticated student routes
- web-specific differences from mobile, especially auth and logout

---

## Route Strategy

- Existing mobile controller behavior is reused where platform-agnostic.
- Web auth is separate from mobile auth.
- Device-change routes remain mobile-only unless explicitly added later.

---

## Endpoint Groups

| Group | Examples | Status |
|-------|----------|--------|
| Guest browsing | explore, search, centers, categories, instructors | planned |
| Profile | me, profile details, education update, logout | planned |
| Enrolled courses | enrolled courses, by instructor, weekly activity | planned |
| Playback | request playback, refresh token, progress, close session | planned |
| Assets | course assets, asset detail, progress tracking | planned |
| Quizzes | attempts, results, answer review | planned |
| Assignments | submissions, assignment groups | planned |
| Requests | extra views, video access, enrollment requests | planned |
| Surveys | assigned surveys, survey show, survey submit | planned |

---

## Web-Specific Rules

- Web auth tokens must carry `platform = web`.
- Playback checks must use the web pool when the token platform is web.
- Playback concurrency still spans both mobile and web.
- Logout revokes only the current web token, not mobile tokens.

---

## Open Items

- Confirm exact list of mobile controllers reused without changes.
- Confirm whether any mobile resources leak mobile-only fields in web responses.
- Confirm whether guest browsing on web uses the same middleware composition as mobile or a web-specific variation.

