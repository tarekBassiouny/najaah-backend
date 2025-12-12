#!/usr/bin/env bash
set -euo pipefail

BLUE="\033[1;34m"
GREEN="\033[1;32m"
YELLOW="\033[1;33m"
RED="\033[1;31m"
NC="\033[0m"

info()    { echo -e "${BLUE}▶ $1${NC}"; }
success() { echo -e "${GREEN}✔ $1${NC}"; }
warn()    { echo -e "${YELLOW}⚠ $1${NC}"; }
error()   { echo -e "${RED}❌ $1${NC}"; }

TASK_ID="${1:-}"
ISSUE_ID="${2:-}"
TASK_TITLE="${3:-}"
MODE="${4:-}"

if [[ -z "$TASK_ID" || -z "$ISSUE_ID" || -z "$TASK_TITLE" ]]; then
  error "Usage: run-task TASK-02 70 \"Playback authorization engine\" [--dry-run]"
  exit 1
fi

info "Fetching GitHub issue #$ISSUE_ID..."

ISSUE_JSON=$(gh issue view "$ISSUE_ID" --json body 2>/dev/null || true)
TASK_BODY=$(echo "$ISSUE_JSON" | jq -r .body)

if [[ -z "$TASK_BODY" || "$TASK_BODY" == "null" ]]; then
  error "Failed to fetch issue body"
  exit 1
fi

success "Fetched issue #$ISSUE_ID"

TASK_ID_LOWER=$(echo "$TASK_ID" | tr '[:upper:]' '[:lower:]')
TASK_SLUG=$(echo "$TASK_TITLE" | tr '[:upper:]' '[:lower:]' | tr ' ' '-')
TASK_BRANCH="task-${TASK_ID_LOWER#task-}-${TASK_SLUG}"

info "Building Codex task branch: feature/$TASK_BRANCH"
info "Building Codex prompt..."

###############################################
# SAFE HEREDOC — Variables expand normally
###############################################
PROMPT=$(cat <<EOF
You are working in a Git repository with a \`dev\` branch.

TASK CONTEXT
============
Internal Task ID: $TASK_ID
GitHub Issue Number: #$ISSUE_ID
Task title: [LMS] $TASK_TITLE

$TASK_BODY

GIT WORKFLOW (MANDATORY)
=======================
1. Checkout \`dev\`
2. Pull latest changes from \`origin/dev\`
3. Create a new branch:
   feature/$TASK_BRANCH
4. Implement ONLY this task
5. Commit changes with message:
   feat($TASK_ID_LOWER): $TASK_SLUG (#$ISSUE_ID)
6. Push branch to origin
7. Open a Pull Request targeting \`dev\` with:
   - Title:
     [LMS][$TASK_ID][#$ISSUE_ID] $TASK_TITLE
   - Description:
     - Closes #$ISSUE_ID
     - Task: $TASK_ID
     - GitHub Issue: #$ISSUE_ID
     - Summary of changes
     - What was NOT changed
     - Tests added/updated
     - Link to issue #$ISSUE_ID

STOP after creating the PR.

STRICT SAFETY RULES
===================
- Implement ONLY what is defined in this task
- Do NOT touch other tasks
- Do NOT refactor unrelated code
- Do NOT change APIs unless explicitly required
- Do NOT modify database schema unless specified
- If unsure → STOP and ask for clarification

ALLOWED ACTIONS
===============
- Create new files required for this task
- Modify existing files ONLY when required
- Add tests related ONLY to this task

FORBIDDEN ACTIONS
=================
- No speculative improvements
- No TODO placeholders
- No cross-task integration
- No follow-up tasks

OUTPUT REQUIREMENTS
===================
- All changes committed to the feature branch
- PR created and ready for review
- Stop execution immediately after PR creation
EOF
)

echo -e "${BLUE}DEBUG:${NC} Prompt length = ${#PROMPT}"

###############################################
# DRY RUN MODE
###############################################
if [[ "$MODE" == "--dry-run" ]]; then
  echo -e "${YELLOW}🧪 DRY RUN — Codex will NOT be executed${NC}"
  echo "--------------------------------------------------------"
  echo "$PROMPT"
  echo "--------------------------------------------------------"
  success "Prompt printed successfully"
  exit 0
fi

###############################################
# RUN CODEX FOR REAL
###############################################
info "Launching Codex..."
echo "--------------------------------------------------------"
echo "$PROMPT"
echo "--------------------------------------------------------"

codex run <<< "$PROMPT"
success "Codex run completed"
