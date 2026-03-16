# Course Asset Authoring Frontend Wireframes

## Purpose

This document defines the screen structure for Course Builder asset authoring.

Use it for:
- Figma wireframes
- UX review
- frontend implementation alignment

Backend assumptions are already implemented.

---

## Screen 1: Course Builder With Asset Slots

### Layout

```text
--------------------------------------------------------------------------------
Course: Biology 101
Tabs: [Content] [Settings] [Students] [Analytics]
--------------------------------------------------------------------------------

Section: Introduction
--------------------------------------------------------------------------------
Video  Welcome to Biology                                       [Generate Assets]
Subtitle: Video | 05:30

Summary       [Published]         [View] [Edit] [Regenerate]
Quiz          [Missing]           [Generate] [Create Quiz]
Flashcards    [Generating]        [View Progress]
Assignment    [Draft]             [Edit] [Publish]

--------------------------------------------------------------------------------
PDF  Course Outline                                              [Generate Assets]
Subtitle: PDF | 12 pages

Summary       [Review Required]   [Review] [Regenerate]
Quiz          [Approved]          [Review] [Publish] [Regenerate]
Flashcards    [Published]         [View] [Edit] [Regenerate]
Assignment    [Missing]           [Generate] [Create Assignment]
--------------------------------------------------------------------------------
```

### Notes

- Source rows are `video` or `pdf` only.
- Each row shows exactly four slots:
  - summary
  - quiz
  - flashcards
  - assignment
- Slot badge color should map to `slot_state`.
- `Generate Assets` is attached to the source row, not the whole page in MVP.

### Mobile / Narrow Width

```text
----------------------------------------
Video: Welcome to Biology
[Generate Assets]

Summary      Published
[View] [Edit]

Quiz         Missing
[Generate] [Create Quiz]

Flashcards   Generating
[View Progress]

Assignment   Draft
[Edit] [Publish]
----------------------------------------
```

---

## Screen 2: Generate Assets Modal

### Layout

```text
------------------------------------------------------------
Generate Assets
------------------------------------------------------------
Source
Video: Welcome to Biology
Section: Introduction

Assets
[x] Summary
[x] Quiz
[x] Flashcards
[ ] Assignment

Creation Mode
Quiz         (o) AI Generate   ( ) Manual Create
Assignment   (o) AI Generate   ( ) Manual Create

Options

Summary
Length: [Medium v]
Include key points: [x]

Quiz
Question count: [ 10 ]
Difficulty: [Medium v]
Question styles:
[x] Single choice
[ ] Multiple choice
[x] True/False

Flashcards
Card count: [ 15 ]
Focus:
[x] Definitions
[x] Concepts
[ ] Formulas

------------------------------------------------------------
                  [Cancel] [Generate 3 Assets]
------------------------------------------------------------
```

### Notes

- Source is read-only and prefilled from the clicked source row.
- Manual selection removes that asset from the AI batch payload.
- Unsupported options must not appear.

---

## Screen 3: Batch Progress Drawer

### Layout

```text
------------------------------------------------------------
Generation Progress
Source: Video - Welcome to Biology
Batch: 8c7d...cb3e
------------------------------------------------------------

Summary
Status: Processing
[spinner]

Quiz
Status: Completed
[Review]

Flashcards
Status: Failed
Reason: Source text was too short
[Retry]

Assignment
Status: Not requested

------------------------------------------------------------
[Close] [Keep Running in Background]
------------------------------------------------------------
```

### Notes

- This is a drawer, not a full page.
- User can close it and return later from the source row.
- Progress should come from `GET /ai-content/jobs?batch_key=...`.

---

## Screen 4: Review Drawer

### Layout

```text
-----------------------------------------------------------------------
Review Generated Assets
Source: Video - Welcome to Biology
-----------------------------------------------------------------------

Tabs
[Summary] [Quiz] [Flashcards]

Right rail
- Job status
- Source label
- Last updated
- Actions

Main review panel changes by tab.
-----------------------------------------------------------------------
```

### Notes

- Review is per asset.
- Tabs should only show assets included in the batch.
- If one asset fails, it should not block review of others.

---

## Screen 5: Summary Review

### Layout

```text
------------------------------------------------------------
Summary Review
------------------------------------------------------------
Title
[ Welcome Summary                                  ]

Content
[                                                   ]
[  Generated summary text...                        ]
[                                                   ]

Key Points Preview
- Cell structure
- DNA basics
- Scientific notation

------------------------------------------------------------
[Regenerate] [Save Draft] [Approve] [Publish]
------------------------------------------------------------
```

### Notes

- Editing should write to AI job `reviewed_payload`.
- After publish, later edits should open the learning-asset edit flow.

---

## Screen 6: Flashcards Review

### Layout

```text
------------------------------------------------------------
Flashcards Review
------------------------------------------------------------
Title
[ Biology Flashcards                                ]

Cards
--------------------------------------------------
Card 1
Front: [ Cell ]
Back:  [ The basic structural unit of life. ]

Card 2
Front: [ DNA ]
Back:  [ Molecule carrying genetic information. ]

[+ Add Card]
--------------------------------------------------

[Regenerate] [Save Draft] [Approve] [Publish]
------------------------------------------------------------
```

### Notes

- Cards should support reorder.
- Keep the editor simple. No advanced card modes in v1.

---

## Screen 7: Quiz Review

### Layout

```text
-----------------------------------------------------------------------
Quiz Review
-----------------------------------------------------------------------
Title
[ Biology Basics Quiz                                          ]

Description
[ Short review quiz for the lesson.                            ]

Questions
-----------------------------------------------------------------------
Q1. What is the function of DNA?

(o) Stores genetic information
( ) Produces oxygen
( ) Breaks down food
( ) Pumps blood

Explanation
[ DNA carries hereditary information.                           ]

[Edit Question] [Delete]
-----------------------------------------------------------------------
[+ Add Question]

[Regenerate] [Save Draft] [Approve] [Publish]
-----------------------------------------------------------------------
```

### Notes

- Frontend should treat `true_false` as a specialized single-choice rendering.
- Do not design short-answer UI for this screen.
- On publish, the backend safely swaps versions if a live quiz already exists.

---

## Screen 8: Assignment Review

### Layout

```text
------------------------------------------------------------
Assignment Review
------------------------------------------------------------
Title
[ Write a short reflection on cell theory            ]

Description
[ Explain the main principles in 150-300 words.      ]

Submission Types
[x] File
[x] Text
[ ] Link

Max Points
[ 100 ]

------------------------------------------------------------
[Regenerate] [Save Draft] [Approve] [Publish]
------------------------------------------------------------
```

### Notes

- Keep assignment review lightweight.
- More advanced grading/rubric editing can stay in the existing assignment screen later.

---

## Screen 9: Manual Create Entry

### Quiz Manual Create Banner

```text
------------------------------------------------------------
Create Quiz
Source Context: Video - Welcome to Biology
Section: Introduction
This quiz will be attached to this source item.
------------------------------------------------------------
```

### Assignment Manual Create Banner

```text
------------------------------------------------------------
Create Assignment
Source Context: PDF - Course Outline
Section: Introduction
This assignment will be attached to this source item.
------------------------------------------------------------
```

### Notes

- These are existing create screens with a source-context banner.
- Do not fork new manual authoring pages for Course Builder.

---

## Screen 10: Published Asset Actions

### Slot Action Menu

```text
Summary   [Published]

[View]
[Edit]
[Regenerate]
```

```text
Quiz      [Published]

[View]
[Edit]
[Regenerate]
```

### Notes

- `Edit` on published summary/flashcards uses learning-asset admin endpoints.
- `Edit` on published quiz/assignment goes to canonical quiz/assignment edit screens.
- `Regenerate` opens the AI flow with `target_id` preset.

---

## Visual Direction Notes

Use the UX rules below while designing:
- make source rows feel like authored curriculum objects, not generic cards
- make slot states readable at a glance
- use the same badge vocabulary everywhere
- make `Generate Assets` the primary action on the source row
- keep review and publish visually distinct
- highlight source context in every modal or drawer

Recommended badge mapping:
- `missing`: neutral
- `draft`: slate
- `generating`: blue
- `review_required`: amber
- `approved`: teal
- `published`: green
- `failed`: red

Recommended action emphasis:
- primary: `Generate`, `Review`, `Publish`
- secondary: `Edit`, `View`
- danger/quiet: `Discard`, `Archive`

---

## Responsive Rules

Desktop:
- source rows stay inline
- batch progress uses right drawer
- review uses wide drawer or side panel

Tablet:
- source row stacks actions below title
- review becomes full-width panel

Mobile:
- source row becomes cards
- one slot row per line
- progress and review should open full-screen sheets

---

## Figma Frame List

Create these frames:
1. Course Builder - default
2. Course Builder - mixed slot states
3. Generate Assets Modal
4. Batch Progress Drawer
5. Review Drawer - summary
6. Review Drawer - flashcards
7. Review Drawer - quiz
8. Review Drawer - assignment
9. Manual Quiz Create with source banner
10. Manual Assignment Create with source banner
11. Mobile Course Builder source card
12. Mobile Review Sheet
