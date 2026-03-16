# Course Asset Authoring Admin UX Handoff

## Scope

This handoff defines the validated admin workflow for creating course assets inside Course Builder using the existing assignments/quizzes and AI content backend.

It covers:
- workflow and screen structure
- wireframes
- implemented backend alignment
- frontend contract shapes
- UI state machine
- AI prompt architecture guardrails

Related docs:
- `/docs/features/assignments_quizzes.md`
- `/docs/features/assignments_quizzes_api_contract.md`
- `/docs/features/assignments_quizzes_frontend_quick_reference.md`
- `/docs/features/course_asset_authoring_frontend_implementation_checklist.md`
- `/docs/features/course_asset_authoring_frontend_wireframes.md`

---

## Validation Summary

Validated decisions:
- Use one entry point in Course Builder: `Generate Assets`.
- Do not force both AI and manual paths for every asset.
- Keep `summary` and `flashcards` AI-first in the MVP UI.
- Keep `quiz` and `assignment` hybrid: AI generate or manual create.
- Keep review before publish mandatory for all AI-generated assets.
- Keep Course Builder asset generation source-bound to `video` or `pdf`.

Rejected or adjusted ideas from the earlier discussion:
- Do not expose short-answer quiz generation in v1. Backend quiz questions only support single-choice and multiple-choice.
- Do not promise manual creation for all asset types. Backend manual CRUD exists only for quizzes and assignments.
- Do not model batch generation as one backend job today. The current API is one `ai_content_job` per target asset.

---

## Current Backend Reality

What already exists:
- AI sources: `video`, `pdf`, `section`, `course`
- AI targets: `quiz`, `assignment`, `summary`, `flashcards`, `interactive_activity`
- Course Builder contract: `video` and `pdf` only
- Manual admin CRUD:
  - quizzes
  - quiz questions
  - assignments
  - assignment grading
- AI workflow:
  - create one draft job per asset
  - create multiple jobs through one batch request
  - review payload
  - approve
  - publish
- Admin CRUD for generated learning assets:
  - list
  - show
  - update
  - status publish/archive
- Mobile read APIs for published:
  - summaries
  - flashcards
  - interactive activities

Important constraints the UX must respect:
- One AI job still generates one asset type, even when created through a batch.
- `target_id` supports regenerate/update flows.
- If `target_id` is used in Course Builder flows, it must belong to the same source item.
- Publishing to an existing live quiz or assignment now creates a new inactive version path and swaps on publish.
- Publishing AI output currently creates active/live content immediately:
  - quiz => `is_active=true`
  - assignment => `is_active=true`
  - learning asset => `status=published`, `is_active=true`
- Batch polling uses `GET /ai-content/jobs?batch_key=...`. There is no separate batch show endpoint.
- Asset catalog returns a flat `sources[]` list with section snapshots. Frontend groups sources by section.
- Quiz and assignment resources still expose canonical `attachable_type` and `attachable_id`. Source display names are added on AI job resources, not canonical quiz/assignment resources.
- AI permissions are separate from quiz/assignment permissions.

Implication:
- The admin UX can feel unified, but the implementation still publishes into two different canonical storage paths:
  - assessment entities
  - learning assets

---

## Asset Decision Matrix

| Asset | AI | Manual | Current Backend Fit | UX Recommendation |
|--------|----|--------|---------------------|-------------------|
| Summary | Yes | No | Strong | AI-first |
| Flashcards | Yes | No | Strong | AI-first |
| Interactive Activity | Yes | No | Partial | Hide in MVP UI |
| Quiz | Yes | Yes | Strong | Hybrid |
| Assignment | Yes | Yes | Strong | Hybrid |

Why:
- Summary is deterministic and fast to review.
- Flashcards map well to transcript/PDF extraction.
- Interactive activity exists as a generic payload today, not as a rich authored builder.
- Quiz often needs teacher control over wording and distractors.
- Assignment often needs manual tuning for grading criteria and submission rules.

---

## Recommended Workflow

### 1. Entry Points

Expose `Generate Assets` from:
- video row
- PDF row

Expose `Create Manually` only where manual authoring actually exists:
- quiz
- assignment

### 2. Source Selection

The source is fixed by the row that opened the modal.

```text
Generate Assets
------------------------------------------------
Video: "Introduction to Thermodynamics"
Section: "Module 1"
```

Rules:
- Pre-fill source when opened from a course builder node.
- Do not allow changing source inside the modal in MVP.
- Show source title, source type, and parent section name when available.

### 3. Asset Selection

```text
Assets to Create
------------------------------------------------
[x] Summary
[x] Quiz
[x] Flashcards
[ ] Assignment
```

Rules:
- Multi-select is valid.
- AI-first assets default to selected when source contains usable text/transcript.
- If source text is missing, disable AI-only assets with a clear reason.

### 4. Mode Selection

Only hybrid assets need a mode selector.

```text
Creation Mode
------------------------------------------------
Quiz        (AI Generate / Manual Create)
Assignment  (AI Generate / Manual Create)
Summary     AI Generate
Flashcards  AI Generate
```

Rules:
- Do not show a fake manual option for assets that are not manually supported.
- Manual mode should jump directly into the existing quiz or assignment editor with source context prefilled.

### 5. Generation Options

Show target-specific options only for selected AI assets.

```text
Quiz Options
------------------------------------------------
Question count: 10
Difficulty: Medium
Question style:
[x] Single choice
[ ] Multiple choice
[x] True/False

Assignment Options
------------------------------------------------
Assignment style: Practice
Submission types:
[x] File
[x] Text
[ ] Link

Flashcards Options
------------------------------------------------
Card count: 15
Focus:
[x] Definitions
[x] Key concepts
[ ] Formulas
```

Validation notes:
- `True/False` should map to single-choice with two options, not a new backend question type.
- Do not offer `short answer` under quiz options.
- Backend now validates target-specific `generation_config` for the Course Builder batch endpoint.

### 6. Generation Execution

For AI assets, current backend behavior is:
- submit one batch request per source item
- create one AI job per selected asset
- group them with one `batch_key`
- poll with `GET /ai-content/jobs?batch_key=...`
- aggregate results into one review board

### 7. Review Board

```text
Generated Assets Review
------------------------------------------------
[Summary] [Quiz] [Flashcards] [Assignment]

Summary
------------------------------------------------
Preview text...
[Edit Draft] [Regenerate] [Approve]

Quiz
------------------------------------------------
10 questions generated
[Edit Questions] [Regenerate] [Approve]

Flashcards
------------------------------------------------
15 cards generated
[Edit Draft] [Regenerate] [Approve]
```

Rules:
- Review stays per asset, not all-or-nothing.
- `Regenerate` creates a new job or updates an existing target carefully.
- `Approve` means the payload is accepted.
- `Publish` should be explicit and separate from `Approve`.

### 8. Publish

Use two explicit actions:
- `Approve Draft`
- `Publish Asset`

This is important because the current backend publish step makes content live immediately.

If product wants a safer editorial workflow, backend needs an additive change:
- publish as draft/inactive first
- activate separately

---

## Screen Map

### A. Course Builder With Asset Status

```text
Course: Biology 101
--------------------------------------------------------------
[Content] [Settings] [Students] [Analytics]

Section 1: Introduction
  Video: Welcome
  Assets: Summary  Quiz  Flashcards  Assignment
  Status: Published  Draft  Missing  Missing
  [Generate Assets] [Create Quiz] [Create Assignment]

  PDF: Lesson Notes
  Assets: Summary  Flashcards
  Status: Missing  Missing
  [Generate Assets]
```

What this screen needs from backend:
- source lists for sections, videos, PDFs
- existing asset presence by source
- readable source-linked labels, not only raw IDs

### B. Generate Assets Drawer

```text
Generate Learning Assets
------------------------------------------------
Source: Video / Welcome

Assets
[x] Summary
[x] Quiz
[x] Flashcards
[ ] Assignment

Mode
Quiz: AI Generate

Options
Question count: 10
Difficulty: Medium

[Cancel] [Generate 3 Assets]
```

### C. Review and Publish Drawer

```text
Review Generated Assets
------------------------------------------------
3 assets ready
1 asset failed

Summary        Ready       [Review]
Quiz           Ready       [Review]
Flashcards     Failed      [Retry]
Assignment     Skipped

[Approve Ready Assets] [Publish Approved Assets]
```

### D. Manual Quiz Branch

```text
Create Quiz
------------------------------------------------
Attached to:
Video / Welcome

Quiz Details
Title
Passing score
Attempts

[Save Quiz]
```

After save:
- redirect into existing quiz question editor

### E. Manual Assignment Branch

```text
Create Assignment
------------------------------------------------
Attached to:
Video / Welcome

Assignment Details
Title
Instructions
Submission types
Max points

[Save Assignment]
```

---

## Backend Mapping

| UX Need | Current Support | Notes |
|---------|-----------------|-------|
| Generate summary from source | Yes | batch create -> AI jobs |
| Generate flashcards from source | Yes | batch create -> AI jobs |
| Generate quiz from source | Yes | batch create -> AI jobs -> quiz publish |
| Generate assignment from source | Yes | batch create -> AI jobs -> assignment publish |
| Manual quiz create | Yes | existing quiz CRUD + question CRUD |
| Manual assignment create | Yes | existing assignment CRUD |
| Manual summary create | No | edit after AI only in v1 |
| Manual flashcards create | No | edit after AI only in v1 |
| Batch generate multiple assets in one click | Yes | one request creates multiple `ai_content_jobs` with shared `batch_key` |
| Review generated payload before publish | Yes | `reviewed_payload` |
| Regenerate existing summary/flashcards | Yes | via `target_id` |
| Regenerate existing assignment | Yes | safe publish swap |
| Regenerate existing quiz | Yes | safe publish swap, no append-to-live behavior |
| Asset list for admin by source | Yes | new course `asset-catalog` endpoint |
| Admin learning asset management | Yes | list/show/update/status |
| Source labels in AI resources | Yes | `source_label`, `target_label` |

---

## Implemented Frontend Contract

### 1. Asset Catalog

Use:
- `GET /api/v1/admin/centers/{center}/courses/{course}/asset-catalog`

Query:
- `section_id`
- `source_type=video|pdf`
- `source_id`

Response notes:
- returns `course`
- returns flat `sources[]`
- each source includes:
  - `type`
  - `id`
  - `title`
  - `order_index`
  - `section`
  - `assets[]`
- each asset slot includes:
  - `asset_type`
  - `slot_state`
  - `canonical`
  - `latest_job`

Frontend rule:
- group `sources[]` by `source.section.id`
- do not expect backend-nested sections in this endpoint

### 2. Batch Create

Use:
- `POST /api/v1/admin/centers/{center}/ai-content/batches`

Rules:
- one request per source item
- source is `video` or `pdf`
- one asset of each `target_type` per batch
- `target_id` is optional and used for regenerate/update flows

### 3. Batch Polling

Use:
- `GET /api/v1/admin/centers/{center}/ai-content/jobs?batch_key={uuid}`

Rules:
- there is no dedicated batch show endpoint
- frontend should poll this list endpoint until all relevant jobs are terminal or reviewable

### 4. Learning Asset Admin

Use:
- `GET /api/v1/admin/centers/{center}/courses/{course}/learning-assets`
- `GET /api/v1/admin/centers/{center}/learning-assets/{asset}`
- `PUT /api/v1/admin/centers/{center}/learning-assets/{asset}`
- `PATCH /api/v1/admin/centers/{center}/learning-assets/{asset}/status`

Rules:
- use for generated `summary` and `flashcards`
- no delete endpoint in v1
- archive through status instead of delete

---

## Permission Model Notes

Current permissions:
- `quiz.manage`
- `assignment.manage`
- `ai_content.generate`
- `ai_content.review_publish`
- `learning_asset.manage`

UX implication:
- a unified asset builder touches multiple permission domains

Recommended frontend handling:
- hide manual quiz path without `quiz.manage`
- hide manual assignment path without `assignment.manage`
- hide AI generate actions without `ai_content.generate`
- hide review/publish actions without `ai_content.review_publish`

Recommended backend review:
- confirm whether AI publish into quiz/assignment should also require `quiz.manage` or `assignment.manage`

---

## Frontend Types

Recommended TypeScript shapes:

```ts
type AssetSlotType = 'summary' | 'quiz' | 'flashcards' | 'assignment';

type AssetSlotState =
  | 'missing'
  | 'draft'
  | 'generating'
  | 'review_required'
  | 'approved'
  | 'published'
  | 'failed';

type AssetCanonicalRef =
  | {
      id: number;
      kind: 'quiz' | 'assignment';
      title: string | null;
      is_active: boolean;
      updated_at: string;
    }
  | {
      id: number;
      kind: 'learning_asset';
      title: string | null;
      status: number;
      status_label: string;
      is_active: boolean;
      updated_at: string;
    };

type AssetJobRef = {
  id: number;
  batch_key: string | null;
  status: number;
  status_label: string;
  error_message: string | null;
};

type CourseAssetSlot = {
  asset_type: AssetSlotType;
  slot_state: AssetSlotState;
  canonical: AssetCanonicalRef | null;
  latest_job: AssetJobRef | null;
};

type CourseAssetSource = {
  type: 'video' | 'pdf';
  id: number;
  title: string | null;
  order_index: number;
  section: {
    id: number;
    title: string | null;
    order_index: number;
  } | null;
  assets: CourseAssetSlot[];
};

type AssetCatalogResponse = {
  course: {
    id: number;
    title: string | null;
  };
  sources: CourseAssetSource[];
};

type CreateAssetBatchRequest = {
  course_id: number;
  source_type: 'video' | 'pdf';
  source_id: number;
  assets: Array<{
    target_type: AssetSlotType;
    target_id?: number | null;
    ai_provider?: string;
    ai_model?: string;
    generation_config?: Record<string, unknown>;
  }>;
};

type CreateAssetBatchResponse = {
  batch_key: string;
  jobs: AIContentJobResource[];
};

type LearningAssetAdminResource = {
  id: number;
  center_id: number;
  course_id: number;
  attachable_type: 'video' | 'pdf' | 'section' | 'course' | null;
  attachable_id: number | null;
  attachable_label: string | null;
  asset_type: 'summary' | 'flashcards' | 'interactive_activity';
  status: number;
  status_label: string;
  title: string | null;
  title_translations: Record<string, string> | null;
  content: string | null;
  content_translations: Record<string, string> | null;
  payload: Record<string, unknown> | null;
  is_active: boolean;
  published_at: string | null;
  created_at: string;
  updated_at: string;
};
```

Generation config shapes:

```ts
type SummaryConfig = {
  length?: 'short' | 'medium' | 'long';
  include_key_points?: boolean;
};

type QuizConfig = {
  question_count?: number;
  difficulty?: 'easy' | 'medium' | 'hard';
  question_styles?: Array<'single_choice' | 'multiple_choice' | 'true_false'>;
};

type FlashcardsConfig = {
  card_count?: number;
  focus?: Array<'definitions' | 'concepts' | 'formulas'>;
};

type AssignmentConfig = {
  assignment_style?: 'practice' | 'essay' | 'project';
  submission_types?: number[];
  max_points?: number;
};
```

---

## UI State Machine

### Generate Assets Modal

States:
- `closed`
- `selecting_assets`
- `submitting`
- `submit_failed`
- `submitted`

Transitions:
- `closed -> selecting_assets` on open from source row
- `selecting_assets -> submitting` on submit
- `submitting -> submit_failed` on 4xx/5xx
- `submitting -> submitted` on `202`

UI rules:
- source is preselected from the clicked row
- manual mode is shown only for `quiz` and `assignment`
- summary and flashcards are AI-only in v1

### Batch Polling Drawer

States:
- `idle`
- `polling`
- `partially_failed`
- `ready_for_review`
- `all_terminal`

Transitions:
- start polling once batch create returns `batch_key`
- stay `polling` while any job is `pending` or `processing`
- go `partially_failed` if at least one job is `failed` and another is still actionable
- go `ready_for_review` when at least one job is `completed` or `approved`
- go `all_terminal` when every job is `failed`, `discarded`, or already `published`

Frontend rule:
- poll `GET /ai-content/jobs?batch_key=...`
- do not invent a separate batch status endpoint in UI

### Per-Asset Slot Actions

`missing`
- show `Generate`
- for `quiz` and `assignment`, also show `Create Manually`

`generating`
- show status only
- allow reopen of polling drawer

`review_required`
- show `Review`
- show `Approve` only for users with `ai_content.review_publish`
- show `Regenerate`

`approved`
- show `Publish`
- show `Review`
- show `Regenerate`

`published`
- show `View`
- show `Edit`
- show `Regenerate`

`draft`
- show `Edit`
- show `Publish` only where the canonical endpoint supports it

`failed`
- show `Retry Generate`
- show error message from `latest_job.error_message`

### Review Screen

Per asset, not global.

Quiz review:
- edit quiz title/description
- edit generated questions
- approve
- publish

Assignment review:
- edit title/description
- edit submission types / max points
- approve
- publish

Summary review:
- edit title/content
- approve through AI job
- publish through AI job
- later edits go through learning-asset admin endpoint

Flashcards review:
- edit cards payload
- approve through AI job
- publish through AI job
- later edits go through learning-asset admin endpoint

### Refresh Rules

After:
- publish
- learning asset status change
- manual quiz create
- manual assignment create

Frontend should refetch:
- `asset-catalog`

That keeps Course Builder as the source of truth for slot state.

---

## Prompt Architecture

The current service already uses JSON-only prompts. The next step is to make prompts target-specific and config-aware.

### Shared Rules

All prompts should:
- ground content in transcript/PDF text only
- return JSON only
- avoid unsupported structures
- prefer concise, reviewable output
- avoid hallucinated facts outside the source

### Summary Prompt

Recommended shape:

```json
{
  "title": "string",
  "content": "string",
  "key_points": ["string"]
}
```

Use config:
- `length`: short, medium, long
- `include_key_points`: boolean

### Quiz Prompt

Recommended shape:

```json
{
  "quiz": {
    "title": "string",
    "description": "string"
  },
  "questions": [
    {
      "question": "string",
      "question_type": 0,
      "options": [
        {"text": "string", "is_correct": true},
        {"text": "string", "is_correct": false}
      ],
      "explanation": "string",
      "points": 1
    }
  ]
}
```

Rules:
- allow only backend-supported question types
- map true/false to `question_type=0`
- never emit short-answer questions

Use config:
- `question_count`
- `difficulty`
- `allow_multiple_choice`
- `allow_true_false`

### Assignment Prompt

Recommended shape:

```json
{
  "assignment": {
    "title": "string",
    "description": "string",
    "submission_types": [0, 1],
    "max_points": 100,
    "passing_score": 60,
    "evaluation_criteria": ["string"]
  }
}
```

Note:
- `evaluation_criteria` is useful in UI and review payload, even if canonical persistence needs expansion later

Use config:
- `assignment_style`
- `submission_types`
- `max_points`

### Flashcards Prompt

Recommended shape:

```json
{
  "title": "string",
  "cards": [
    {
      "front": "string",
      "back": "string"
    }
  ]
}
```

Use config:
- `card_count`
- `focus`: definitions, concepts, formulas

### Interactive Activity Prompt

Recommended shape:

```json
{
  "title": "string",
  "instructions": "string",
  "activity_kind": "guided_steps",
  "steps": [
    {
      "title": "string",
      "description": "string",
      "estimated_seconds": 60
    }
  ]
}
```

Note:
- current backend/mobile support is generic payload rendering, not a full authored interactive engine

---

## Delivery Status

Implemented now:
- batch create with shared `batch_key`
- batch polling through existing jobs list endpoint
- course asset catalog endpoint
- admin learning asset list/show/update/status endpoints
- safe live-version swap on AI publish for quiz, assignment, and learning asset
- source labels on AI job resources

Still later if needed:
- section/course entry points in Course Builder UI
- interactive activity UI in admin
- publish-as-inactive editorial mode
- richer prompt config usage beyond validation

---

## Final Product Direction

The right v1 is not "manual and AI for everything."

The right v1 is:
- one unified asset creation workflow
- AI-first for high-speed assets
- manual fallback only where teachers actually need control
- review before publish
- clear distinction between what exists now and what needs additive backend work

That gives admins a fast course-asset workflow without designing UI that the current backend cannot safely support.
