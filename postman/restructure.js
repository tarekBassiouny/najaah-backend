import fs from "fs";

const INPUT = "postman/scribe.postman.json";
const OUTPUT = "postman/xyz-lms.postman.json";

const source = JSON.parse(fs.readFileSync(INPUT, "utf8"));

function folder(name) {
  return { name, item: [] };
}

const tree = {
  adminAuth: folder("🔐 Admin Auth (JWT)"),
  adminCenters: folder("🧑‍💼 Admin – Centers"),
  adminCourses: folder("🧑‍💼 Admin – Courses"),
  adminSections: folder("🧑‍💼 Admin – Sections"),
  adminEnrollment: folder("🧑‍💼 Admin – Enrollment & Controls"),
  adminVideos: folder("🧑‍💼 Admin – Videos"),
  adminInstructors: folder("🧑‍💼 Admin – Instructors"),
  adminPdfs: folder("🧑‍💼 Admin – PDFs"),
  adminAudit: folder("🧑‍💼 Admin – Audit & Settings"),
  webhooks: folder("🔔 Webhooks"),
  mobileAuth: folder("📱 Mobile Auth (JWT)"),
  studentCourses: folder("🎓 Student – Courses"),
  studentSections: folder("🎓 Student – Sections"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📱 Student – Requests"),
  studentVideos: folder("📱 Student – Videos"),
  studentPdfs: folder("📄 Student – PDFs"),
  studentEnrollments: folder("🎓 Student – Enrollments"),
  instructors: folder("👨‍🏫 Instructors"),
  health: folder("🧪 Smoke & Health")
};

function route(item) {
  const raw = item.request?.url?.raw ?? "";

  if (raw.includes("/api/v1/admin/auth")) return tree.adminAuth;
  if (raw.includes("/api/v1/admin/centers")) return tree.adminCenters;
  if (raw.includes("/api/v1/admin/courses") && raw.includes("/sections"))
    return tree.adminSections;
  if (raw.includes("/api/v1/admin/courses")) return tree.adminCourses;
  if (
    raw.includes("/api/v1/admin/enrollments") ||
    raw.includes("/device-change-requests") ||
    raw.includes("/extra-view-requests")
  )
    return tree.adminEnrollment;
  if (raw.includes("/api/v1/admin/pdfs")) return tree.adminPdfs;
  if (
    raw.includes("/api/v1/admin/videos") ||
    raw.includes("/api/v1/admin/video-uploads")
  ) 
    return tree.adminVideos;
  if (
    raw.includes("/api/v1/admin/instructors") ||
    raw.match(/\/api\/v1\/admin\/courses\/.*\/instructors/)
  )
    return tree.adminInstructors;
  if (
    raw.includes("/api/v1/admin/audit-logs") ||
    raw.includes("/api/v1/admin/settings")
  )
    return tree.adminAudit;
  if (raw.includes("/api/webhooks/")) return tree.webhooks;

  if (raw.includes("/api/v1/auth")) return tree.mobileAuth;
  if (raw.includes("/playback")) return tree.studentPlayback;
  if (raw.includes("/api/v1/courses") && raw.includes("/sections"))
    return tree.studentSections;
  if (raw.includes("/api/v1/courses")) return tree.studentCourses;
  if (raw.match(/\/api\/v1\/courses\/.*\/instructors/)) return tree.adminCourses;
  if (
    raw.includes("/api/v1/device-change-requests") ||
    raw.includes("/api/v1/extra-view-requests")
  )
    return tree.studentRequests;
  if (raw.includes("/api/v1/pdfs")) return tree.studentPdfs;
  if (raw.includes("/api/v1/enrollments")) return tree.studentEnrollments;
  if (raw.includes("/api/v1/instructors")) return tree.instructors;
  if (raw.endsWith("/up")) return tree.health;
  if (
    raw.endsWith("/api/v1/videos") ||
    raw.endsWith("/api/v1/video-uploads")
  ) 
    return tree.studentVideos;

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
    tree.adminAuth,
    tree.adminCenters,
    tree.adminCourses,
    tree.adminSections,
    tree.adminEnrollment,
    tree.adminVideos,
    tree.adminInstructors,
    tree.adminPdfs,
    tree.adminAudit,
    tree.webhooks,
    tree.mobileAuth,
    tree.studentCourses,
    tree.studentSections,
    tree.studentPlayback,
    tree.studentRequests,
    tree.studentPdfs,
    tree.studentEnrollments,
    tree.studentVideos,
    tree.instructors,
    tree.health
  ].filter(f => f.item.length > 0)
};

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
