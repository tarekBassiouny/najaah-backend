import fs from "fs";

const INPUT = "postman/scribe.postman.json";
const OUTPUT = "postman/najaah.postman.json";

function tryParseJson(path) {
  if (!fs.existsSync(path)) return null;

  const raw = fs.readFileSync(path, "utf8");
  try {
    return JSON.parse(raw);
  } catch {
    return null;
  }
}

function loadSourceCollection() {
  const sources = [
    "storage/app/private/scribe/collection.json",
    "storage/app/scribe/collection.json",
    "public/docs/collection.json",
    INPUT,
  ];

  for (const sourcePath of sources) {
    const parsed = tryParseJson(sourcePath);
    if (parsed) {
      if (sourcePath !== INPUT) {
        fs.writeFileSync(INPUT, JSON.stringify(parsed, null, 2));
      }
      return parsed;
    }
  }

  throw new Error(
    `Unable to parse any source collection. Checked: ${sources.join(", ")}.`
  );
}

const source = loadSourceCollection();

const folder = name => ({ name, item: [] });

const tree = {
  admin: folder("🧑‍💼 Admin"),
  public: folder("🔔 Public"),
  mobileAuth: folder("📱 Mobile – Auth (JWT)"),
  studentCenters: folder("🏫 Student – Centers (unbranded)"),
  studentCourses: folder("🎓 Student – Courses"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📱 Student – Requests"),
  studentPdfs: folder("📄 Student – PDFs"),
  studentEnrollments: folder("🎓 Student – Enrollments"),
  mobileSurveys: folder("📱 Mobile – Surveys"),
  instructors: folder("👨‍🏫 Instructors"),
  health: folder("🧪 Smoke & Health")
};

/* ---------------- HELPERS ---------------- */

const normalizePath = raw =>
  raw
    .replace(/^{{.*?}}/, "")
    .split("?")[0];

const has = (p, v) => p.includes(v);
const ADMIN_PREFIX = "/api/v1/admin";
const CENTER_ADMIN_PREFIX = /^\/api\/v1\/admin\/centers\/[^/]+(\/|$)/;
const moduleMap = new Map();

function ensureModule(name) {
  if (!moduleMap.has(name)) {
    const moduleFolder = folder(name);
    tree.admin.item.push(moduleFolder);
    moduleMap.set(name, moduleFolder);
  }

  return moduleMap.get(name);
}

function ensureScope(moduleName, scope) {
  const moduleFolder = ensureModule(moduleName);
  const scopeName = scope === "center" ? "Center Scoped" : "System Scoped";
  let scopeFolder = moduleFolder.item.find(entry => entry.name === scopeName);

  if (!scopeFolder) {
    scopeFolder = folder(scopeName);
    moduleFolder.item.push(scopeFolder);
  }

  return scopeFolder;
}

function getAdminScope(path) {
  return CENTER_ADMIN_PREFIX.test(path) ? "center" : "system";
}

function getAdminModulePath(path, scope) {
  if (scope === "center") {
    return path.replace(/^\/api\/v1\/admin\/centers\/[^/]+\/?/, "");
  }

  return path.replace(/^\/api\/v1\/admin\/?/, "");
}

function resolveAdminModule(path, scope) {
  const modulePath = getAdminModulePath(path, scope);
  const clean = modulePath.replace(/^\/+/, "");
  const centerRootActions = [
    "status",
    "restore",
    "onboarding",
    "branding"
  ];

  if (!clean) return "Centers";
  if (clean.startsWith("auth")) return "Auth";
  if (clean.startsWith("analytics")) return "Analytics";
  if (clean.startsWith("dashboard")) return "Analytics";
  if (clean.startsWith("agents")) return "Agents";
  if (clean.startsWith("surveys")) return "Surveys";
  if (clean.startsWith("notifications")) return "Notifications";
  if (clean.startsWith("roles")) return "Roles";
  if (clean.startsWith("permissions")) return "Permissions";
  if (clean.startsWith("users")) return "Users";
  if (clean.startsWith("students")) return "Students";
  if (clean.startsWith("settings")) return "Settings";
  if (clean.startsWith("audit-logs")) return "Audit Logs";
  if (clean.startsWith("playback-sessions")) return "Playback Sessions";
  if (clean.startsWith("categories")) return "Categories";
  if (clean.startsWith("pdfs")) return "PDFs";
  if (clean.startsWith("videos")) return "Videos";
  if (clean.startsWith("instructors") || clean.match(/^courses\/[^/]+\/instructors(\/|$)/)) {
    return "Instructors";
  }
  if (
    clean.startsWith("video-access-codes") ||
    clean.startsWith("video-access-requests") ||
    clean.startsWith("video-accesses") ||
    clean.startsWith("bulk-whatsapp-jobs")
  ) {
    return "Video Access";
  }
  if (
    clean.startsWith("enrollments") ||
    clean.startsWith("device-change-requests") ||
    clean.startsWith("extra-view-requests") ||
    clean.startsWith("extra-view-grants")
  ) {
    return "Enrollment & Controls";
  }
  if (
    clean.startsWith("grades") ||
    clean.startsWith("schools") ||
    clean.startsWith("colleges")
  ) {
    return "Education";
  }
  if (clean.match(/^courses\/[^/]+\/sections(\/|$)/)) return "Sections";
  if (clean.startsWith("courses")) return "Courses";
  if (scope === "center" && centerRootActions.some(action => clean.startsWith(action))) {
    return "Centers";
  }
  if (clean.startsWith("centers")) return "Centers";

  return "Other";
}

/* ---------------- ROUTER ---------------- */

function route(item) {
  const raw = item.request?.url?.raw ?? "";
  const path = normalizePath(raw);

  if (path.startsWith(ADMIN_PREFIX)) {
    const scope = getAdminScope(path);
    const module = resolveAdminModule(path, scope);
    return ensureScope(module, scope);
  }

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

  // Surveys
  if (has(path, "/api/v1/surveys")) return tree.mobileSurveys;

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
  info: { ...source.info, name: "Najaah LMS API (v1)" },
  item: Object.values(tree).filter(f => f.item.length > 0)
};

for (const moduleFolder of tree.admin.item) {
  moduleFolder.item.sort((a, b) => {
    const order = { "System Scoped": 0, "Center Scoped": 1 };
    return (order[a.name] ?? 99) - (order[b.name] ?? 99);
  });
}

tree.admin.item.sort((a, b) => a.name.localeCompare(b.name));

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
