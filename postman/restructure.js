import fs from "fs";

const INPUT = "postman/scribe.postman.json";
const OUTPUT = "postman/xyz-lms.postman.json";

const source = JSON.parse(fs.readFileSync(INPUT, "utf8"));

const folder = name => ({ name, item: [] });

const tree = {
  /* -------- ADMIN -------- */
  adminAuth: folder("🔐 Admin – Auth (JWT)"),
  adminAgents: folder("🧑‍💼 Admin – Agents"),
  adminAnalytics: folder("🧑‍💼 Admin – Analytics"),
  adminCategories: folder("🧑‍💼 Admin – Categories"),
  adminCenters: folder("🧑‍💼 Admin – Centers"),
  adminCourses: folder("🧑‍💼 Admin – Courses"),
  adminSections: folder("🧑‍💼 Admin – Sections"),
  adminEnrollment: folder("🧑‍💼 Admin – Enrollment & Controls"),
  adminVideos: folder("🧑‍💼 Admin – Videos"),
  adminInstructors: folder("🧑‍💼 Admin – Instructors"),
  adminPdfs: folder("🧑‍💼 Admin – PDFs"),
  adminRoles: folder("🧑‍💼 Admin – Roles"),
  adminPermissions: folder("🧑‍💼 Admin – Permissions"),
  adminUsers: folder("🧑‍💼 Admin – Users"),
  adminStudents: folder("🧑‍💼 Admin – Students"),
  adminSettings: folder("🧑‍💼 Admin – Settings"),
  adminAudit: folder("🧑‍💼 Admin – Audit Logs"),

  /* -------- PUBLIC -------- */
  public: folder("🔔 Public"),

  /* -------- STUDENT / MOBILE -------- */
  mobileAuth: folder("📱 Mobile – Auth (JWT)"),
  studentCenters: folder("🏫 Student – Centers (unbranded)"),
  studentCourses: folder("🎓 Student – Courses"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📱 Student – Requests"),
  studentPdfs: folder("📄 Student – PDFs"),
  studentEnrollments: folder("🎓 Student – Enrollments"),
  instructors: folder("👨‍🏫 Instructors"),

  /* -------- HEALTH -------- */
  health: folder("🧪 Smoke & Health")
};

/* ---------------- HELPERS ---------------- */

const normalizePath = raw =>
  raw
    .replace(/^{{.*?}}/, "")
    .split("?")[0];

const has = (p, v) => p.includes(v);

/* ---------------- ROUTER ---------------- */

function route(item) {
  const raw = item.request?.url?.raw ?? "";
  const path = normalizePath(raw);

  /* ========= ADMIN ========= */

  if (has(path, "/api/v1/admin/auth")) return tree.adminAuth;
  // Categories & PDFs under centers must be checked BEFORE adminCenters
  if (path.match(/^\/api\/v1\/admin\/centers\/[^/]+\/categories/)) return tree.adminCategories;
  if (path.match(/^\/api\/v1\/admin\/centers\/[^/]+\/pdfs/)) return tree.adminPdfs;
  if (path.match(/^\/api\/v1\/admin\/centers\/[^/]+\/videos/)) return tree.adminVideos;
  if (has(path, "/api/v1/admin/centers")) return tree.adminCenters;
  if (has(path, "/api/v1/admin/courses") && has(path, "/sections")) return tree.adminSections;
  if (has(path, "/api/v1/admin/courses")) return tree.adminCourses;
  if (
    has(path, "/api/v1/admin/enrollments") ||
    has(path, "/api/v1/admin/device-change-requests") ||
    has(path, "/api/v1/admin/extra-view-requests")
  ) return tree.adminEnrollment;
  if (has(path, "/api/v1/admin/analytics")) return tree.adminAnalytics;
  if (
    has(path, "/api/v1/admin/instructors") ||
    path.match(/^\/api\/v1\/admin\/courses\/[^/]+\/instructors/)
  ) return tree.adminInstructors;
  if (has(path, "/api/v1/admin/agents")) return tree.adminAgents;
  if (has(path, "/api/v1/admin/roles")) return tree.adminRoles;
  if (has(path, "/api/v1/admin/permissions")) return tree.adminPermissions;
  if (has(path, "/api/v1/admin/users")) return tree.adminUsers;
  if (has(path, "/api/v1/admin/students")) return tree.adminStudents;
  if (has(path, "/api/v1/admin/settings")) return tree.adminSettings;
  if (has(path, "/api/v1/admin/audit-logs")) return tree.adminAudit;

  /* ========= PUBLIC ========= */

  if (has(path, "/api/v1/resolve")) return tree.public;
  if (path === "/webhooks/bunny") return tree.public;

  /* ========= MOBILE AUTH ========= */

  if (has(path, "/api/v1/auth")) return tree.mobileAuth;

  /* ========= STUDENT ========= */

  // Playback
  if (
    path.match(/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/(request_playback|refresh_token|playback_progress|close_session)$/)
  ) return tree.studentPlayback;

  // Extra view
  if (path.endsWith("/extra-view")) return tree.studentRequests;

  // Device change
  if (path === "/api/v1/settings/device-change") return tree.studentRequests;
  if (path === "/api/v1/device-change/submit") return tree.studentRequests;

  // Enrollment request
  if (path.endsWith("/enroll-request")) return tree.studentEnrollments;

  // Enrolled courses
  if (path === "/api/v1/courses/enrolled") return tree.studentCourses;
  if (path === "/api/v1/courses/enrolled/by-instructor") return tree.studentCourses;

  // Explore
  if (path === "/api/v1/courses/explore") return tree.studentCourses;

  // PDFs
  if (path.match(/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/pdfs\/[^/]+\/signed-url$/))
    return tree.studentPdfs;

  // Explore
  if (path === "/api/v1/courses/explore") return tree.studentCourses;
  
  // Course detail (must be BEFORE centers)
  if (path.match(/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+$/))
    return tree.studentCourses;

  // Search / categories
  if (path === "/api/v1/search" || path === "/api/v1/categories")
    return tree.studentCourses;

  // Auth profile/logout
  if (
    path === "/api/v1/auth/me" ||
    path === "/api/v1/auth/logout"
  ) return tree.mobileAuth;

  // Centers (unbranded)
  if (
    path === "/api/v1/centers" ||
    path.match(/^\/api\/v1\/centers\/[^/]+$/)
  ) return tree.studentCenters;

  // Instructors
  if (path === "/api/v1/instructors") return tree.instructors;

  /* ========= HEALTH ========= */

  if (path.endsWith("/up")) return tree.health;

  return null;
}

/* ---------------- BUILD ---------------- */

function flatten(items) {
  return items.flatMap(i => i.item ? flatten(i.item) : i);
}

for (const req of flatten(source.item)) {
  const target = route(req);
  if (target) target.item.push(req);
}

const finalCollection = {
  info: { ...source.info, name: "XYZ LMS API (v1)" },
  item: Object.values(tree).filter(f => f.item.length > 0)
};

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
