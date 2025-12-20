import fs from "fs";

const INPUT = "postman/scribe.postman.json";
const OUTPUT = "postman/xyz-lms.postman.json";

const source = JSON.parse(fs.readFileSync(INPUT, "utf8"));

function folder(name) {
  return { name, item: [] };
}

const tree = {
  /* -------- ADMIN -------- */

  adminAuth: folder("🔐 Admin – Auth (JWT)"),
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

  /* -------- WEBHOOKS -------- */

  webhooks: folder("🔔 Webhooks"),

  /* -------- MOBILE / STUDENT -------- */

  mobileAuth: folder("📱 Mobile – Auth (JWT)"),
  studentCourses: folder("🎓 Student – Courses"),
  studentSections: folder("🎓 Student – Sections"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📱 Student – Requests"),
  studentVideos: folder("📱 Student – Videos"),
  studentPdfs: folder("📄 Student – PDFs"),
  studentEnrollments: folder("🎓 Student – Enrollments"),
  instructors: folder("👨‍🏫 Instructors"),

  /* -------- HEALTH -------- */

  health: folder("🧪 Smoke & Health")
};

function route(item) {
  const raw = item.request?.url?.raw ?? "";

  /* ================= ADMIN ================= */

  // ---- Auth
  if (raw.includes("/api/v1/admin/auth"))
    return tree.adminAuth;

  // ---- Centers
  if (raw.includes("/api/v1/admin/centers"))
    return tree.adminCenters;

  // ---- Sections (must be before courses)
  if (
    raw.includes("/api/v1/admin/courses") &&
    raw.includes("/sections")
  )
    return tree.adminSections;

  // ---- Courses
  if (raw.includes("/api/v1/admin/courses"))
    return tree.adminCourses;

  // ---- Enrollment & Requests
  if (
    raw.includes("/api/v1/admin/enrollments") ||
    raw.includes("/api/v1/admin/device-change-requests") ||
    raw.includes("/api/v1/admin/extra-view-requests")
  )
    return tree.adminEnrollment;

  // ---- PDFs
  if (raw.includes("/api/v1/admin/pdfs"))
    return tree.adminPdfs;

  // ---- Videos
  if (
    raw.includes("/api/v1/admin/videos") ||
    raw.includes("/api/v1/admin/video-uploads") ||
    raw.includes("/api/v1/admin/video-upload-sessions")
  )
    return tree.adminVideos;

  // ---- Instructors
  if (
    raw.includes("/api/v1/admin/instructors") ||
    raw.match(/\/api\/v1\/admin\/courses\/.*\/instructors/)
  )
    return tree.adminInstructors;

  // ---- Roles / Permissions / Users  ✅ NEW
  if (raw.includes("/api/v1/admin/roles"))
    return tree.adminRoles;

  if (raw.includes("/api/v1/admin/permissions"))
    return tree.adminPermissions;

  if (raw.includes("/api/v1/admin/users"))
    return tree.adminUsers;

  if (raw.includes("/api/v1/admin/students"))
    return tree.adminStudents;

  // ---- Settings  ✅ NEW
  if (raw.includes("/api/v1/admin/settings"))
    return tree.adminSettings;

  // ---- Audit Logs (ONLY audit logs)
  if (raw.includes("/api/v1/admin/audit-logs"))
    return tree.adminAudit;

  /* ================= WEBHOOKS ================= */

  if (raw.includes("/api/webhooks/"))
    return tree.webhooks;

  /* ================= MOBILE AUTH ================= */

  if (raw.includes("/api/v1/auth"))
    return tree.mobileAuth;

  /* ================= STUDENT ================= */

  if (raw.includes("/api/v1/playback"))
    return tree.studentPlayback;

  if (
    raw.includes("/api/v1/courses") &&
    raw.includes("/sections")
  )
    return tree.studentSections;

  if (raw.includes("/api/v1/courses"))
    return tree.studentCourses;

  if (
    raw.includes("/api/v1/device-change-requests") ||
    raw.includes("/api/v1/extra-view-requests")
  )
    return tree.studentRequests;

  if (raw.includes("/api/v1/pdfs"))
    return tree.studentPdfs;

  if (raw.includes("/api/v1/enrollments"))
    return tree.studentEnrollments;

  if (
    raw.endsWith("/api/v1/videos") ||
    raw.includes("/api/v1/video-uploads")
  )
    return tree.studentVideos;

  if (raw.includes("/api/v1/instructors"))
    return tree.instructors;

  /* ================= HEALTH ================= */

  if (raw.endsWith("/up"))
    return tree.health;

  return null;
}

function flatten(items) {
  const out = [];
  for (const i of items) {
    if (i.item) out.push(...flatten(i.item));
    else out.push(i);
  }
  return out;
}

const allRequests = flatten(source.item);

for (const req of allRequests) {
  const target = route(req);
  if (target) target.item.push(req);
}

const finalCollection = {
  info: {
    ...source.info,
    name: "XYZ LMS API (v1)"
  },
  item: [
    // ---- ADMIN
    tree.adminAuth,
    tree.adminCenters,
    tree.adminCourses,
    tree.adminSections,
    tree.adminEnrollment,
    tree.adminVideos,
    tree.adminInstructors,
    tree.adminPdfs,

    // ✅ NEW – explicit admin management domains
    tree.adminRoles,
    tree.adminPermissions,
    tree.adminUsers,
    tree.adminStudents,
    tree.adminSettings,
    tree.adminAudit,

    // ---- SYSTEM
    tree.webhooks,

    // ---- MOBILE / STUDENT
    tree.mobileAuth,
    tree.studentCourses,
    tree.studentSections,
    tree.studentPlayback,
    tree.studentRequests,
    tree.studentPdfs,
    tree.studentEnrollments,
    tree.studentVideos,

    // ---- SHARED / MISC
    tree.instructors,
    tree.health
  ].filter(f => f.item.length > 0)
};

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
