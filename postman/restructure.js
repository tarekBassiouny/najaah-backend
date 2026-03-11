import fs from "fs";

const INPUT = "postman/scribe.postman.json";
const OUTPUT = "postman/najaah.postman.json";
const API_PREFIX = "/api/v1";
const ADMIN_PREFIX = `${API_PREFIX}/admin`;
const CENTER_ADMIN_PREFIX = /^\/api\/v1\/admin\/centers\/[^/]+(\/|$)/;

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
  mobileAuth: folder("📱 Mobile – Auth"),
  mobileProfile: folder("👤 Mobile – Profile"),
  studentCenters: folder("🏫 Student – Centers"),
  studentEducation: folder("📚 Student – Education"),
  studentCourses: folder("🎓 Student – Courses"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📨 Student – Requests"),
  studentPdfs: folder("📄 Student – PDFs"),
  studentQuizzes: folder("🧠 Student – Quizzes"),
  studentAssignments: folder("📝 Student – Assignments"),
  studentLearningAssets: folder("🧩 Student – Learning Assets"),
  mobileSurveys: folder("🗳️ Student – Surveys"),
  instructors: folder("👨‍🏫 Instructors"),
  health: folder("🧪 Smoke & Health"),
  uncategorized: folder("🧩 Uncategorized"),
};

const orderedFolders = [
  tree.admin,
  tree.public,
  tree.mobileAuth,
  tree.mobileProfile,
  tree.studentCenters,
  tree.studentEducation,
  tree.studentCourses,
  tree.studentPlayback,
  tree.studentRequests,
  tree.studentPdfs,
  tree.studentQuizzes,
  tree.studentAssignments,
  tree.studentLearningAssets,
  tree.mobileSurveys,
  tree.instructors,
  tree.health,
  tree.uncategorized,
];

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

function flatten(items) {
  return items.flatMap(item => (item.item ? flatten(item.item) : item));
}

function normalizePath(raw = "") {
  return raw.replace(/^{{.*?}}/, "").split("?")[0];
}

function normalizeMethod(item) {
  return String(item.request?.method ?? "GET").toUpperCase();
}

function cloneItem(item) {
  return JSON.parse(JSON.stringify(item));
}

function sanitizeName(name = "") {
  return String(name).replace(/\.$/, "").replace(/\s+/g, " ").trim();
}

function humanizeSegment(segment = "") {
  return segment
    .replace(/^:/, "")
    .replace(/_/g, " ")
    .replace(/-/g, " ")
    .replace(/\bapi\b/gi, "API")
    .replace(/\bpdfs\b/gi, "PDFs")
    .replace(/\bpdf\b/gi, "PDF")
    .replace(/\botp\b/gi, "OTP")
    .replace(/\bid\b/gi, "ID")
    .replace(/\bai\b/gi, "AI")
    .replace(/\bjwt\b/gi, "JWT")
    .replace(/\burl\b/gi, "URL")
    .replace(/\bwhatsapp\b/gi, "WhatsApp")
    .replace(/\b([a-z])/g, char => char.toUpperCase());
}

function singularize(word = "") {
  const irregular = new Map([
    ["quizzes", "quiz"],
    ["courses", "course"],
    ["categories", "category"],
    ["colleges", "college"],
    ["settings", "setting"],
    ["summaries", "summary"],
  ]);

  if (irregular.has(word)) return irregular.get(word);
  if (word.endsWith("ies")) return `${word.slice(0, -3)}y`;
  if (word.endsWith("ses")) return word.slice(0, -2);
  if (word.endsWith("s") && !word.endsWith("ss")) return word.slice(0, -1);
  return word;
}

function extractSegments(path) {
  return normalizePath(path).split("/").filter(Boolean);
}

function isPlaceholder(segment = "") {
  return segment.startsWith(":") || /^\{.+\}$/.test(segment);
}

function isInformativeName(name) {
  const weakNames = new Set([
    "send",
    "verify",
    "refresh",
    "profile",
    "logout",
    "store",
    "explore",
    "show",
    "index",
    "signed url",
    "status",
    "summaries",
    "summary",
    "flashcards",
    "redeem",
  ]);

  if (!name) return false;
  if (/^(GET|POST|PUT|PATCH|DELETE)\s+/i.test(name)) return false;
  if (weakNames.has(name.toLowerCase())) return false;

  return name.length >= 10;
}

function explicitDisplayName(method, path) {
  const rules = [
    [/^\/api\/v1\/auth\/send-otp$/, "POST", "Send OTP"],
    [/^\/api\/v1\/auth\/verify$/, "POST", "Verify OTP"],
    [/^\/api\/v1\/auth\/refresh$/, "POST", "Refresh Mobile Token"],
    [/^\/api\/v1\/auth\/me$/, "GET", "Get My Profile"],
    [/^\/api\/v1\/auth\/me$/, "POST", "Update My Profile"],
    [/^\/api\/v1\/auth\/me\/profile$/, "GET", "Get My Full Profile"],
    [/^\/api\/v1\/auth\/me\/education$/, "PATCH", "Update My Education"],
    [/^\/api\/v1\/auth\/logout$/, "POST", "Logout"],
    [/^\/api\/v1\/admin\/auth\/me$/, "GET", "Get Current Admin Profile"],
    [/^\/api\/v1\/admin\/auth\/me$/, "PATCH", "Update Current Admin Profile"],
    [/^\/api\/v1\/settings\/device-change$/, "POST", "Create Device Change Request"],
    [/^\/api\/v1\/device-change\/submit$/, "POST", "Submit Device Change with OTP"],
    [/^\/api\/v1\/courses\/explore$/, "GET", "Explore Courses"],
    [/^\/api\/v1\/courses\/enrolled$/, "GET", "List Enrolled Courses"],
    [/^\/api\/v1\/courses\/enrolled\/by-instructor$/, "GET", "List Enrolled Courses by Instructor"],
    [/^\/api\/v1\/search$/, "GET", "Search Courses"],
    [/^\/api\/v1\/categories$/, "GET", "List Categories"],
    [/^\/api\/v1\/instructors$/, "GET", "List Instructors"],
    [/^\/api\/v1\/centers$/, "GET", "List Centers"],
    [/^\/api\/v1\/centers\/[^/]+$/, "GET", "Show Center"],
    [/^\/api\/v1\/centers\/[^/]+\/categories$/, "GET", "List Center Categories"],
    [/^\/api\/v1\/centers\/[^/]+\/education$/, "GET", "Get Education Configuration"],
    [/^\/api\/v1\/centers\/[^/]+\/grades$/, "GET", "List Grades"],
    [/^\/api\/v1\/centers\/[^/]+\/schools$/, "GET", "List Schools"],
    [/^\/api\/v1\/centers\/[^/]+\/colleges$/, "GET", "List Colleges"],
    [/^\/api\/v1\/centers\/[^/]+\/activity\/weekly$/, "GET", "Get Weekly Activity"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+$/, "GET", "Show Course Details"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/pdfs\/[^/]+\/signed-url$/, "GET", "Get PDF Signed URL"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/request_playback$/, "POST", "Request Playback"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/refresh_token$/, "POST", "Refresh Playback Token"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/playback_progress$/, "POST", "Update Playback Progress"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/close_session$/, "POST", "Close Playback Session"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/extra-view$/, "POST", "Create Extra View Request"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/access-request$/, "POST", "Create Video Access Request"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/access-status$/, "GET", "Get Video Access Status"],
    [/^\/api\/v1\/video-access-codes\/redeem$/, "POST", "Redeem Video Access Code"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/enroll-request$/, "POST", "Create Enrollment Request"],
    [/^\/api\/v1\/surveys\/assigned$/, "GET", "List Assigned Surveys"],
    [/^\/api\/v1\/surveys\/[^/]+$/, "GET", "Show Survey"],
    [/^\/api\/v1\/surveys\/[^/]+\/submit$/, "POST", "Submit Survey"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/quizzes$/, "GET", "List Quizzes"],
    [/^\/api\/v1\/centers\/[^/]+\/quizzes\/[^/]+$/, "GET", "Show Quiz"],
    [/^\/api\/v1\/centers\/[^/]+\/quizzes\/[^/]+\/my-attempts$/, "GET", "List My Quiz Attempts"],
    [/^\/api\/v1\/centers\/[^/]+\/quizzes\/[^/]+\/start$/, "POST", "Start Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+$/, "GET", "Show Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+\/answer$/, "POST", "Save Quiz Answer"],
    [/^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+\/submit$/, "POST", "Submit Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+\/results$/, "GET", "Show Quiz Results"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assignments$/, "GET", "List Assignments"],
    [/^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+$/, "GET", "Show Assignment"],
    [/^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+\/my-submission$/, "GET", "Get My Assignment Submission"],
    [/^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+\/submissions$/, "POST", "Create Assignment Submission"],
    [/^\/api\/v1\/centers\/[^/]+\/submissions\/[^/]+\/files$/, "POST", "Upload Submission File"],
    [/^\/api\/v1\/centers\/[^/]+\/submissions\/[^/]+\/files\/[^/]+$/, "DELETE", "Delete Submission File"],
    [/^\/api\/v1\/centers\/[^/]+\/submissions\/[^/]+\/submit$/, "POST", "Submit Assignment Submission"],
    [/^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+\/groups$/, "GET", "List Assignment Groups"],
    [/^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+\/groups$/, "POST", "Create Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assignment-groups\/[^/]+$/, "GET", "Show Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assignment-groups\/[^/]+\/join$/, "POST", "Join Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assignment-groups\/[^/]+\/leave$/, "POST", "Leave Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/summaries$/, "GET", "List Course Summaries"],
    [/^\/api\/v1\/centers\/[^/]+\/summaries\/[^/]+$/, "GET", "Show Summary"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/flashcards$/, "GET", "List Flashcard Sets"],
    [/^\/api\/v1\/centers\/[^/]+\/flashcards\/[^/]+$/, "GET", "Show Flashcard Set"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/interactive-activities$/, "GET", "List Interactive Activities"],
    [/^\/api\/v1\/centers\/[^/]+\/interactive-activities\/[^/]+$/, "GET", "Show Interactive Activity"],
    [/^\/api\/v1\/resolve\/centers\/[^/]+$/, "GET", "Resolve Center by Slug"],
    [/^\/webhooks\/bunny$/, "POST", "Receive Bunny Webhook"],
    [/^\/webhooks\/evolution$/, "POST", "Receive Evolution Webhook"],
  ];

  for (const [pattern, expectedMethod, name] of rules) {
    if (pattern.test(path) && method === expectedMethod) {
      return name;
    }
  }

  return null;
}

function genericDisplayName(method, path) {
  const segments = extractSegments(path);
  const working = [...segments];

  if (working[0] === "api" && working[1] === "v1") {
    working.splice(0, 2);
  }

  if (working[0] === "admin") {
    working.shift();
  }

  const specialActions = new Map([
    ["bulk-status", "Bulk Update"],
    ["bulk-delete", "Bulk Delete"],
    ["bulk-restore", "Bulk Restore"],
    ["bulk-featured", "Bulk Update Featured"],
    ["bulk-tier", "Bulk Update Tier"],
    ["bulk-close", "Bulk Close"],
    ["bulk-publish", "Bulk Publish"],
    ["bulk-unpublish", "Bulk Unpublish"],
    ["bulk-approve", "Bulk Approve"],
    ["bulk-reject", "Bulk Reject"],
    ["bulk-pre-approve", "Bulk Pre-Approve"],
    ["bulk-revoke", "Bulk Revoke"],
    ["bulk-send-whatsapp", "Bulk Send via WhatsApp"],
    ["bulk-attach", "Bulk Attach"],
    ["bulk-detach", "Bulk Detach"],
    ["bulk-assign-centers", "Bulk Assign Centers"],
    ["assign-center", "Assign Center"],
    ["roles", "Sync Roles"],
    ["permissions", "Sync Permissions"],
    ["status", "Update Status"],
    ["restore", "Restore"],
    ["retry", "Retry"],
    ["preview", "Preview"],
    ["duplicate", "Duplicate"],
    ["clone", "Clone"],
    ["publish", "Publish"],
    ["unpublish", "Unpublish"],
    ["approve", "Approve"],
    ["reject", "Reject"],
    ["pre-approve", "Pre-Approve"],
    ["reorder", "Reorder"],
    ["read", "Mark As Read"],
    ["read-all", "Mark All As Read"],
    ["close", "Close"],
    ["assign", "Assign"],
    ["analytics", "View Analytics"],
    ["attempts", "List Attempts"],
    ["statistics", "View Statistics"],
    ["grade", "Grade"],
    ["return", "Return For Revision"],
    ["download", "Download"],
    ["lookup", "Lookup"],
    ["count", "Get Count"],
    ["options", "Get Options"],
    ["review", "Review"],
    ["resume", "Resume"],
    ["pause", "Pause"],
    ["retry-failed", "Retry Failed"],
    ["preview-token", "Generate Preview Token"],
    ["hero-background", "Upload Hero Background"],
    ["about-image", "Upload About Image"],
    ["testimonial-image", "Upload Testimonial Image"],
    ["logo", "Upload Logo"],
    ["change-password", "Change Password"],
    ["forgot", "Forgot Password"],
    ["reset", "Reset Password"],
  ]);

  const actionSegment = working[working.length - 1];
  const previousSegment = working[working.length - 2];
  const lastIsPlaceholder = isPlaceholder(actionSegment);

  let resourceSegment = null;
  let actionLabel = null;

  if (!lastIsPlaceholder && specialActions.has(actionSegment)) {
    actionLabel = specialActions.get(actionSegment);
    resourceSegment = previousSegment;
  } else if (
    !lastIsPlaceholder &&
    !["centers", "courses", "videos", "pdfs", "sections"].includes(actionSegment) &&
    previousSegment &&
    isPlaceholder(previousSegment)
  ) {
    actionLabel = humanizeSegment(actionSegment);
    resourceSegment = working[working.length - 3];
  }

  if (resourceSegment === null) {
    for (let index = working.length - 1; index >= 0; index -= 1) {
      if (!isPlaceholder(working[index])) {
        resourceSegment = working[index];
        break;
      }
    }
  }

  if (!resourceSegment) {
    return `${humanizeSegment(method.toLowerCase())} Endpoint`;
  }

  const resourcePlural = humanizeSegment(resourceSegment);
  const resourceSingular = humanizeSegment(singularize(resourceSegment));
  const endsWithPlaceholder = isPlaceholder(working[working.length - 1]);
  const baseAction =
    actionLabel ??
    (method === "GET"
      ? endsWithPlaceholder
        ? "Show"
        : "List"
      : method === "POST"
        ? endsWithPlaceholder
          ? "Create"
          : "Create"
        : method === "PUT" || method === "PATCH"
          ? "Update"
          : method === "DELETE"
            ? "Delete"
            : humanizeSegment(method.toLowerCase()));

  const resourceLabel =
    baseAction === "List" || baseAction.startsWith("Bulk ")
      ? resourcePlural
      : resourceSingular;

  if (baseAction === "Get Count") {
    return `Get ${resourceSingular} Count`.trim();
  }

  if (baseAction === "Get Options") {
    return `Get ${resourceSingular} Options`.trim();
  }

  return `${baseAction} ${resourceLabel}`.trim();
}

function buildDisplayName(item) {
  const originalName = sanitizeName(item.name);

  if (isInformativeName(originalName)) {
    return originalName;
  }

  const path = normalizePath(item.request?.url?.raw ?? "");
  const method = normalizeMethod(item);

  return (
    explicitDisplayName(method, path) ??
    genericDisplayName(method, path) ??
    originalName
  );
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

  if (!clean) return "Centers";
  if (clean.startsWith("auth")) return "Auth";
  if (clean.startsWith("analytics") || clean.startsWith("dashboard")) return "Analytics";
  if (clean.startsWith("agents")) return "Agents";
  if (clean.startsWith("ai/providers") || clean.startsWith("ai/options")) return "AI Providers";
  if (clean.startsWith("ai-content")) return "AI Content";
  if (clean.startsWith("surveys")) return "Surveys";
  if (clean.startsWith("notifications")) return "Notifications";
  if (clean.startsWith("roles") || clean.startsWith("permissions")) return "Roles & Permissions";
  if (clean.startsWith("users")) return "Admin Users";
  if (clean.startsWith("students")) return "Students";
  if (clean.startsWith("settings")) return "Settings";
  if (clean.startsWith("audit-logs")) return "Audit Logs";
  if (clean.startsWith("playback-sessions")) return "Playback Sessions";
  if (clean.startsWith("categories")) return "Categories";
  if (clean.startsWith("pdfs")) return "PDFs";
  if (clean.startsWith("videos")) return "Videos";
  if (clean.startsWith("landing-page")) return "Landing Pages";
  if (clean.startsWith("assignments") || clean.startsWith("submissions") || clean.includes("/assignments")) {
    return "Assignments";
  }
  if (clean.startsWith("quizzes") || clean.startsWith("quiz-") || clean.includes("/quizzes")) {
    return "Quizzes";
  }
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
  if (clean.startsWith("enrollments")) return "Enrollments";
  if (clean.startsWith("device-change-requests")) return "Device Change Requests";
  if (clean.startsWith("extra-view-requests")) return "Extra View Requests";
  if (
    clean.startsWith("grades") ||
    clean.startsWith("schools") ||
    clean.startsWith("colleges")
  ) {
    return "Education";
  }
  if (clean.startsWith("courses")) return "Courses";
  if (clean.startsWith("centers")) return "Centers";

  return "Other";
}

function route(item) {
  const path = normalizePath(item.request?.url?.raw ?? "");

  if (!path) return tree.uncategorized;

  if (path.startsWith(ADMIN_PREFIX)) {
    const scope = getAdminScope(path);
    const module = resolveAdminModule(path, scope);
    return ensureScope(module, scope);
  }

  if (path.startsWith("/api/v1/resolve") || path.startsWith("/webhooks/")) {
    return tree.public;
  }

  if (path.endsWith("/up")) {
    return tree.health;
  }

  if (path.startsWith("/api/v1/auth/")) {
    if (path === "/api/v1/auth/me" || path === "/api/v1/auth/me/profile" || path === "/api/v1/auth/me/education") {
      return tree.mobileProfile;
    }

    return tree.mobileAuth;
  }

  if (path === "/api/v1/settings/device-change" || path === "/api/v1/device-change/submit") {
    return tree.studentRequests;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/(education|grades|schools|colleges)$/.test(path)
  ) {
    return tree.studentEducation;
  }

  if (
    path === "/api/v1/centers" ||
    /^\/api\/v1\/centers\/[^/]+$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/categories$/.test(path)
  ) {
    return tree.studentCenters;
  }

  if (path === "/api/v1/instructors") {
    return tree.instructors;
  }

  if (
    path === "/api/v1/courses/explore" ||
    path === "/api/v1/courses/enrolled" ||
    path === "/api/v1/courses/enrolled/by-instructor" ||
    path === "/api/v1/search" ||
    path === "/api/v1/categories" ||
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/activity\/weekly$/.test(path)
  ) {
    return tree.studentCourses;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/(request_playback|refresh_token|playback_progress|close_session)$/.test(path)
  ) {
    return tree.studentPlayback;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/(extra-view|access-request|access-status)$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/enroll-request$/.test(path) ||
    path === "/api/v1/video-access-codes/redeem"
  ) {
    return tree.studentRequests;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/pdfs\/[^/]+\/signed-url$/.test(path)
  ) {
    return tree.studentPdfs;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/quizzes$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/quizzes\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+/.test(path)
  ) {
    return tree.studentQuizzes;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assignments$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/submissions\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assignment-groups\/[^/]+/.test(path)
  ) {
    return tree.studentAssignments;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/(summaries|flashcards|interactive-activities)$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/(summaries|flashcards|interactive-activities)\/[^/]+$/.test(path)
  ) {
    return tree.studentLearningAssets;
  }

  if (path.startsWith("/api/v1/surveys")) {
    return tree.mobileSurveys;
  }

  if (path.startsWith(API_PREFIX)) {
    return tree.uncategorized;
  }

  return tree.uncategorized;
}

const sourceItems = flatten(source.item ?? []);

for (const req of sourceItems) {
  const target = route(req);
  const structured = cloneItem(req);
  structured.name = buildDisplayName(structured);
  target.item.push(structured);
}

for (const moduleFolder of tree.admin.item) {
  moduleFolder.item.sort((a, b) => {
    const order = { "System Scoped": 0, "Center Scoped": 1 };
    return (order[a.name] ?? 99) - (order[b.name] ?? 99);
  });
}

tree.admin.item.sort((a, b) => a.name.localeCompare(b.name));

const finalCollection = {
  info: { ...source.info, name: "Najaah LMS API (v1)" },
  item: orderedFolders.filter(entry => entry.item.length > 0),
};

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
console.log(
  `📦 Endpoints copied: ${sourceItems.length} -> ${flatten(finalCollection.item).length}`
);
