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

function mergeCollectionVariables(existing = [], additions = []) {
  const merged = new Map();

  for (const entry of [...existing, ...additions]) {
    if (!entry?.key) continue;
    merged.set(entry.key, entry);
  }

  return [...merged.values()];
}

const folder = name => ({ name, item: [] });

const tree = {
  manualAuth: folder("🔐 Manual Auth Bootstrap"),
  admin: folder("🧑‍💼 Admin"),
  public: folder("🔔 Public"),
  mobileAuth: folder("📱 Mobile – Auth"),
  mobileProfile: folder("👤 Mobile – Profile"),
  studentCenters: folder("🏫 Student – Centers"),
  studentEducation: folder("📚 Student – Education"),
  studentCourses: folder("🎓 Student – Courses"),
  studentPlayback: folder("🎬 Student – Playback"),
  studentRequests: folder("📨 Student – Requests"),
  studentVideoCodes: folder("🎟️ Student – Video Codes"),
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
  tree.manualAuth,
  tree.admin,
  tree.public,
  tree.mobileAuth,
  tree.mobileProfile,
  tree.studentCenters,
  tree.studentEducation,
  tree.studentCourses,
  tree.studentPlayback,
  tree.studentRequests,
  tree.studentVideoCodes,
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

function buildScriptEvent(listen, lines) {
  return {
    listen,
    script: {
      type: "text/javascript",
      exec: lines,
    },
  };
}

function appendEvent(item, listen, lines) {
  const event = buildScriptEvent(listen, lines);
  const events = Array.isArray(item.event) ? [...item.event] : [];
  const existing = events.find(entry => entry.listen === listen);

  if (existing?.script?.exec) {
    existing.script.exec = [...existing.script.exec, "", ...lines];
  } else {
    events.push(event);
  }

  item.event = events;
}

function buildDefaultCollectionTestEvent() {
  return buildScriptEvent("test", [
    "const responseTimeBudget = Number(pm.collectionVariables.get('max_response_time_ms') || pm.environment.get('max_response_time_ms') || '10000');",
    "pm.test('Status code is not 5xx', function () {",
    "  pm.expect(pm.response.code).to.be.below(500);",
    "});",
    "pm.test('Response time is within budget', function () {",
    "  pm.expect(pm.response.responseTime).to.be.below(responseTimeBudget);",
    "});",
    "if (pm.response.code !== 204) {",
    "  const contentType = String(pm.response.headers.get('Content-Type') || '').toLowerCase();",
    "  pm.test('API returns JSON', function () {",
    "    pm.expect(contentType).to.include('application/json');",
    "  });",
    "  if (contentType.includes('application/json')) {",
    "    let json = null;",
    "    pm.test('Response body is valid JSON', function () {",
    "      json = pm.response.json();",
    "      pm.expect(json).to.be.an('object');",
    "    });",
    "    if (json && Object.prototype.hasOwnProperty.call(json, 'success')) {",
    "      pm.test('success flag is boolean when present', function () {",
    "        pm.expect(json.success).to.be.a('boolean');",
    "      });",
    "    }",
    "    if (json && json.meta && typeof json.meta === 'object') {",
    "      if (Object.prototype.hasOwnProperty.call(json.meta, 'page')) {",
    "        pm.test('Pagination meta includes page', function () {",
    "          pm.expect(json.meta.page).to.be.a('number');",
    "        });",
    "      }",
    "      if (Object.prototype.hasOwnProperty.call(json.meta, 'per_page')) {",
    "        pm.test('Pagination meta includes per_page', function () {",
    "          pm.expect(json.meta.per_page).to.be.a('number');",
    "        });",
    "      }",
    "      if (Object.prototype.hasOwnProperty.call(json.meta, 'total')) {",
    "        pm.test('Pagination meta includes total', function () {",
    "          pm.expect(json.meta.total).to.be.a('number');",
    "        });",
    "      }",
    "    }",
    "    if (json && json.success === false && json.error) {",
    "      pm.test('error payload includes code and message', function () {",
    "        pm.expect(json.error).to.be.an('object');",
    "        pm.expect(json.error.code).to.be.a('string').and.not.empty;",
    "        pm.expect(json.error.message).to.be.a('string').and.not.empty;",
    "      });",
    "    }",
    "  }",
    "}",
  ]);
}

function buildCollectionPrerequestEvent() {
  return buildScriptEvent("prerequest", [
    "const parseBoolean = (value, fallback) => {",
    "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
    "  const normalized = value.trim().toLowerCase();",
    "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
    "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
    "  return fallback;",
    "};",
    "const rawUrl = pm.request.url.toString();",
    "const path = rawUrl.replace(/^\\{\\{[^}]+\\}\\}/, '').split('?')[0];",
    "const isProduction = parseBoolean(pm.environment.get('is_production'), false);",
    "const autoAuthEnabled = !isProduction && parseBoolean(pm.environment.get('auto_auth_enabled'), true);",
    "const adminToken = pm.environment.get('admin_access_token');",
    "const mobileToken = pm.environment.get('mobile_access_token');",
    "const adminPublicPaths = new Set(['/api/v1/admin/auth/login']);",
    "const mobileProtectedPatterns = [",
    "  /^\\/api\\/v1\\/auth\\/me(?:\\/|$)/,",
    "  /^\\/api\\/v1\\/(settings\\/device-change|device-change\\/submit)$/,",
    "  /^\\/api\\/v1\\/courses\\/enrolled(?:\\/|$)/,",
    "  /^\\/api\\/v1\\/video-codes(?:\\/|$)/,",
    "  /^\\/api\\/v1\\/surveys(?:\\/|$)/,",
    "  /^\\/api\\/v1\\/centers\\/[^/]+\\/activity\\/weekly$/,",
    "  /^\\/api\\/v1\\/centers\\/[^/]+\\/courses\\/[^/]+(?:\\/|$)/,",
    "];",
    "const isAdminRoute = path.startsWith('/api/v1/admin/');",
    "const requiresMobileAuth = mobileProtectedPatterns.some(pattern => pattern.test(path));",
    "if (autoAuthEnabled && isAdminRoute && !adminPublicPaths.has(path) && adminToken) {",
    "  pm.request.headers.upsert({ key: 'Authorization', value: `Bearer ${adminToken}` });",
    "} else if (autoAuthEnabled && requiresMobileAuth && mobileToken) {",
    "  pm.request.headers.upsert({ key: 'Authorization', value: `Bearer ${mobileToken}` });",
    "}",
  ]);
}

function attachAuthTokenCapture(item, path) {
  if (path === "/api/v1/auth/send-otp") {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const tokenCaptureEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('token_capture_enabled'), true);",
      "pm.test('OTP request succeeds', () => {",
      "  pm.response.to.have.status(200);",
      "});",
      "const json = pm.response.json();",
      "const otpToken = json?.token?.token ?? json?.token ?? null;",
      "pm.test('OTP tokens returned', () => {",
      "  pm.expect(otpToken).to.be.a('string').and.not.empty;",
      "});",
      "if (tokenCaptureEnabled && typeof otpToken === 'string' && otpToken.length > 0) {",
      "  pm.environment.set('otp_token', otpToken);",
      "}",
    ]);
  }

  if (path === "/api/v1/auth/verify" || path === "/api/v1/auth/refresh") {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const tokenCaptureEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('token_capture_enabled'), true);",
      "pm.test('Mobile auth request succeeds', () => {",
      "  pm.response.to.have.status(200);",
      "});",
      "const json = pm.response.json();",
      "const token = json?.token || null;",
      "pm.test('Mobile access token returned', () => {",
      "  pm.expect(token?.access_token).to.be.a('string').and.not.empty;",
      "});",
      "if (tokenCaptureEnabled && typeof token?.access_token === 'string' && token.access_token.length > 0) {",
      "  pm.environment.set('mobile_access_token', token.access_token);",
      "}",
      "if (tokenCaptureEnabled && typeof token?.refresh_token === 'string' && token.refresh_token.length > 0) {",
      "  pm.environment.set('mobile_refresh_token', token.refresh_token);",
      "}",
    ]);
  }

  if (path === "/api/v1/admin/auth/login" || path === "/api/v1/admin/auth/refresh") {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const tokenCaptureEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('token_capture_enabled'), true);",
      "pm.test('Admin auth request succeeds', () => {",
      "  pm.response.to.have.status(200);",
      "});",
      "const json = pm.response.json();",
      "const adminToken = json?.data?.token || null;",
      "pm.test('Admin access token returned', () => {",
      "  pm.expect(adminToken).to.be.a('string').and.not.empty;",
      "});",
      "if (tokenCaptureEnabled && typeof adminToken === 'string' && adminToken.length > 0) {",
      "  pm.environment.set('admin_access_token', adminToken);",
      "}",
    ]);
  }
}

function attachWorkflowStateCapture(item, path) {
  if (/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+$/.test(path)) {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const workflowStateEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('workflow_state_enabled'), true);",
      "const json = pm.response.json();",
      "pm.test('Course details payload returned', () => {",
      "  pm.expect(json?.data?.id).to.exist;",
      "  pm.expect(json?.data?.has_access).to.be.a('boolean');",
      "});",
      "if (workflowStateEnabled && json?.success && json?.data?.id) {",
      "  const centerId = pm.request.url.variables.get('center');",
      "  pm.environment.set('current_center_id', String(centerId || ''));",
      "  pm.environment.set('current_course_id', String(json.data.id));",
      "}",
    ]);
  }

  if (/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assets$/.test(path)) {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const workflowStateEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('workflow_state_enabled'), true);",
      "const json = pm.response.json();",
      "pm.test('Course assets list returned', () => {",
      "  pm.expect(json?.data).to.be.an('array');",
      "});",
      "if (workflowStateEnabled && json?.success && Array.isArray(json.data) && json.data.length > 0) {",
      "  const firstAsset = json.data[0];",
      "  if (firstAsset?.id !== undefined) pm.environment.set('current_asset_id', String(firstAsset.id));",
      "  if (typeof firstAsset?.type === 'string' && firstAsset.type.length > 0) pm.environment.set('current_asset_type', firstAsset.type);",
      "}",
    ]);
  }

  if (/^\/api\/v1\/centers\/[^/]+\/assets\/[^/]+\/[^/]+$/.test(path)) {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const workflowStateEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('workflow_state_enabled'), true);",
      "const json = pm.response.json();",
      "pm.test('Course asset detail returned', () => {",
      "  pm.expect(json?.data?.id).to.exist;",
      "});",
      "if (workflowStateEnabled && json?.success && json?.data?.id !== undefined) {",
      "  pm.environment.set('current_asset_id', String(json.data.id));",
      "  if (typeof json?.data?.type === 'string' && json.data.type.length > 0) pm.environment.set('current_asset_type', json.data.type);",
      "}",
    ]);
  }

  if (/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/[^/]+\/attempts$/.test(path)) {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const workflowStateEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('workflow_state_enabled'), true);",
      "const json = pm.response.json();",
      "pm.test('Quiz attempt payload returned', () => {",
      "  pm.expect(json?.data?.id).to.exist;",
      "  pm.expect(json?.data?.quiz_id).to.exist;",
      "});",
      "if (workflowStateEnabled && json?.success && json?.data?.id !== undefined) {",
      "  pm.environment.set('current_attempt_id', String(json.data.id));",
      "  pm.environment.set('current_quiz_id', String(json.data.quiz_id));",
      "}",
    ]);
  }

  if (/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/submission$/.test(path)) {
    appendEvent(item, "test", [
      "const parseBoolean = (value, fallback) => {",
      "  if (typeof value !== 'string' || value.trim() === '') return fallback;",
      "  const normalized = value.trim().toLowerCase();",
      "  if (['1', 'true', 'yes', 'on'].includes(normalized)) return true;",
      "  if (['0', 'false', 'no', 'off'].includes(normalized)) return false;",
      "  return fallback;",
      "};",
      "const workflowStateEnabled = !parseBoolean(pm.environment.get('is_production'), false) && parseBoolean(pm.environment.get('workflow_state_enabled'), true);",
      "const json = pm.response.json();",
      "pm.test('Assignment submission payload returned', () => {",
      "  pm.expect(json?.data?.id).to.exist;",
      "});",
      "if (workflowStateEnabled && json?.success && json?.data?.id !== undefined) {",
      "  pm.environment.set('current_submission_id', String(json.data.id));",
      "  const assignmentId = pm.request.url.variables.get('assignment');",
      "  if (assignmentId !== undefined && assignmentId !== null) pm.environment.set('current_assignment_id', String(assignmentId));",
      "}",
    ]);
  }
}

function createBootstrapClone(item, path) {
  const bootstrapNames = new Map([
    ["/api/v1/admin/auth/login", "Admin Login"],
    ["/api/v1/admin/auth/refresh", "Admin Refresh"],
    ["/api/v1/auth/send-otp", "Mobile Send OTP"],
    ["/api/v1/auth/verify", "Mobile Verify OTP"],
    ["/api/v1/auth/refresh", "Mobile Refresh Token"],
  ]);

  const bootstrapName = bootstrapNames.get(path);
  if (!bootstrapName) {
    return null;
  }

  const cloned = cloneItem(item);
  cloned.name = bootstrapName;

  return cloned;
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
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assets$/, "GET", "List Course Assets"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/[^/]+\/[^/]+$/, "GET", "Show Course Asset"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/[^/]+\/[^/]+\/progress$/, "POST", "Track Learning Asset Progress"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/request_playback$/, "POST", "Request Playback"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/refresh_token$/, "POST", "Refresh Playback Token"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/playback_progress$/, "POST", "Update Playback Progress"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/close_session$/, "POST", "Close Playback Session"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/extra-view$/, "POST", "Create Extra View Request"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/access-request$/, "POST", "Create Video Access Request"],
    [/^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/videos\/[^/]+\/access-status$/, "GET", "Get Video Access Status"],
    [/^\/api\/v1\/video-access-codes\/redeem$/, "POST", "Redeem Video Access Code"],
    [/^\/api\/v1\/video-codes\/redeem$/, "POST", "Redeem Batch Video Code"],
    [/^\/api\/v1\/video-codes\/validate$/, "POST", "Validate Batch Video Code"],
    [/^\/api\/v1\/video-codes\/my-redemptions$/, "GET", "List My Redeemed Video Codes"],
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
    [/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/[^/]+\/attempts$/, "POST", "Start Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/attempts\/[^/]+$/, "GET", "Show Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/attempts\/[^/]+\/answers$/, "POST", "Save Quiz Answer"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/attempts\/[^/]+\/submit$/, "POST", "Submit Quiz Attempt"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/attempts\/[^/]+\/results$/, "GET", "Show Quiz Results"],
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
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/submission$/, "POST", "Create Assignment Submission Draft"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/submissions\/[^/]+\/files$/, "POST", "Upload Submission File"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/submissions\/[^/]+\/files\/[^/]+$/, "DELETE", "Delete Submission File"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/submissions\/[^/]+\/submit$/, "POST", "Submit Assignment Submission"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/groups$/, "GET", "List Assignment Groups"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/groups$/, "POST", "Create Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/groups\/[^/]+$/, "GET", "Show Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/groups\/[^/]+\/join$/, "POST", "Join Assignment Group"],
    [/^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/groups\/[^/]+\/leave$/, "POST", "Leave Assignment Group"],
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
    path === "/api/v1/video-codes/redeem" ||
    path === "/api/v1/video-codes/validate" ||
    path === "/api/v1/video-codes/my-redemptions"
  ) {
    return tree.studentVideoCodes;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/pdfs\/[^/]+\/signed-url$/.test(path)
  ) {
    return tree.studentPdfs;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/quizzes$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/quizzes\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/quiz-attempts\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/[^/]+\/attempts$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/quiz\/attempts\/[^/]+/.test(path)
  ) {
    return tree.studentQuizzes;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assignments$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assignments\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/submissions\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assignment-groups\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/submission$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/submissions\/[^/]+/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/[^/]+\/groups$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/assignment\/groups\/[^/]+/.test(path)
  ) {
    return tree.studentAssignments;
  }

  if (
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/(summaries|flashcards|interactive-activities)$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/(summaries|flashcards|interactive-activities)\/[^/]+$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/courses\/[^/]+\/assets$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/[^/]+\/[^/]+$/.test(path) ||
    /^\/api\/v1\/centers\/[^/]+\/assets\/[^/]+\/[^/]+\/progress$/.test(path)
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
  const path = normalizePath(structured.request?.url?.raw ?? "");
  structured.name = buildDisplayName(structured);
  attachAuthTokenCapture(structured, path);
  attachWorkflowStateCapture(structured, path);
  target.item.push(structured);

  const bootstrapClone = createBootstrapClone(structured, path);
  if (bootstrapClone) {
    tree.manualAuth.item.push(bootstrapClone);
  }
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
  event: [
    buildCollectionPrerequestEvent(),
    ...(Array.isArray(source.event) ? source.event : []),
    buildDefaultCollectionTestEvent(),
  ],
  variable: mergeCollectionVariables(Array.isArray(source.variable) ? source.variable : [], [
    {
      key: "max_response_time_ms",
      value: "10000",
      type: "string",
    },
  ]),
  item: orderedFolders.filter(entry => entry.item.length > 0),
};

fs.writeFileSync(OUTPUT, JSON.stringify(finalCollection, null, 2));
console.log("✅ Postman collection structured:", OUTPUT);
console.log(
  `📦 Source requests: ${sourceItems.length}, final requests: ${flatten(finalCollection.item).length}`
);
