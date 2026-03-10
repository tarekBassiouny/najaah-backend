/**
 * Assignments & Quizzes API Types
 *
 * Source contract:
 * /docs/features/assignments_quizzes_api_contract.md
 */

export type LocaleCode = 'en' | 'ar';

export type AttemptScorePolicy = 0 | 1 | 2;
export type QuestionType = 0 | 1;
export type QuizAttemptStatus = 0 | 1 | 2 | 3;
export type SubmissionType = 0 | 1 | 2;
export type SubmissionStatus = 0 | 1 | 2 | 3;

export type AttachableType = 'video' | 'pdf' | 'section' | 'course' | null;

export type IsoDateString = string;

export interface ApiErrorShape {
  code: string;
  message: string;
  details?: Record<string, string[]> | null;
}

export interface ApiSuccessResponse<TData, TMeta = unknown> {
  success: true;
  message?: string;
  data: TData;
  meta?: TMeta;
}

export interface ApiErrorResponse {
  success: false;
  message: string;
  code?: string;
  errors?: Record<string, string[]> | null;
  data?: null;
  error?: ApiErrorShape;
}

export type ApiResponse<TData, TMeta = unknown> =
  | ApiSuccessResponse<TData, TMeta>
  | ApiErrorResponse;

export interface PaginationMeta {
  page: number;
  per_page: number;
  total: number;
  last_page?: number;
}

export interface UserLite {
  id: number;
  name: string;
  email?: string;
  phone?: string;
}

export interface QuizAnswerDto {
  id: number;
  answer: string;
  answer_translations?: Record<LocaleCode, string>;
  is_correct?: boolean;
  order_index?: number;
}

export interface QuizQuestionDto {
  id: number;
  quiz_id?: number;
  question: string;
  question_translations?: Record<LocaleCode, string>;
  question_type: QuestionType;
  question_type_label?: string;
  allows_multiple_answers?: boolean;
  explanation?: string | null;
  explanation_translations?: Partial<Record<LocaleCode, string>> | null;
  points: number;
  order_index?: number;
  is_active?: boolean;
  ai_generated?: boolean;
  ai_source_type?: string | null;
  ai_source_id?: number | null;
  answers?: QuizAnswerDto[];
}

export interface QuizAdminListItem {
  id: number;
  course_id: number;
  title: string;
  title_translations: Record<LocaleCode, string>;
  description: string;
  attachable_type: AttachableType;
  attachable_id: number | null;
  passing_score: number;
  max_attempts: number;
  attempt_score_policy: AttemptScorePolicy;
  attempt_score_policy_label: string;
  time_limit_minutes: number | null;
  shuffle_questions: boolean;
  shuffle_answers: boolean;
  show_correct_answers: boolean;
  show_score_immediately: boolean;
  is_required: boolean;
  is_active: boolean;
  available_from: IsoDateString | null;
  available_until: IsoDateString | null;
  order_index: number;
  questions_count: number;
  attempts_count: number;
  created_at: IsoDateString;
  updated_at: IsoDateString;
}

export interface QuizAdminDetail extends QuizAdminListItem {
  center_id: number;
  description_translations: Partial<Record<LocaleCode, string>> | null;
  has_unlimited_attempts: boolean;
  has_time_limit: boolean;
  is_available: boolean;
  total_points: number;
  questions?: QuizQuestionDto[];
  creator?: UserLite;
}

export interface QuizAttemptAdminListItem {
  id: number;
  quiz_id: number;
  user_id: number;
  user?: UserLite;
  attempt_number: number;
  status: QuizAttemptStatus;
  status_label: string;
  started_at: IsoDateString | null;
  submitted_at: IsoDateString | null;
  time_spent_seconds: number;
  score: number | null;
  points_earned: number;
  points_possible: number;
  passed: boolean | null;
  created_at: IsoDateString;
}

export interface AssignmentAdminListItem {
  id: number;
  course_id: number;
  title: string;
  title_translations: Record<LocaleCode, string>;
  description: string;
  attachable_type: AttachableType;
  attachable_id: number | null;
  submission_types: SubmissionType[];
  max_points: number;
  passing_score: number;
  is_group_assignment: boolean;
  max_group_size: number | null;
  is_required: boolean;
  is_active: boolean;
  due_date: IsoDateString | null;
  is_past_due: boolean;
  late_submission_allowed: boolean;
  available_from: IsoDateString | null;
  available_until: IsoDateString | null;
  order_index: number;
  submissions_count: number;
  created_at: IsoDateString;
  updated_at: IsoDateString;
}

export interface AssignmentSubmissionFileDto {
  id: number;
  file_name: string;
  file_size_kb: number;
  file_size_mb?: number;
  file_type: string;
  file_extension?: string;
  created_at?: IsoDateString;
}

export interface AssignmentSubmissionAdminItem {
  id: number;
  assignment_id: number;
  user_id: number;
  user?: UserLite;
  group_id: number | null;
  submission_type: SubmissionType;
  submission_type_label: string;
  status: SubmissionStatus;
  status_label: string;
  submitted_at: IsoDateString | null;
  is_late: boolean;
  days_late: number;
  score: number | null;
  score_after_penalty: number | null;
  passed: boolean | null;
  files_count?: number;
  graded_at: IsoDateString | null;
  grader?: UserLite | null;
  created_at: IsoDateString;
}

export interface AssignmentSubmissionAdminDetail extends AssignmentSubmissionAdminItem {
  assignment?: {
    id: number;
    title: string;
    max_points: number;
    passing_score: number;
  };
  text_content: string | null;
  link_url: string | null;
  feedback: string | null;
  files?: AssignmentSubmissionFileDto[];
}

export interface QuizMobileListItem {
  id: number;
  title: string;
  description: string;
  attachable_type: AttachableType;
  attachable_id: number | null;
  passing_score: number;
  max_attempts: number;
  time_limit_minutes: number | null;
  is_required: boolean;
  is_available: boolean;
  remaining_attempts: number | null;
  best_score: number | null;
  can_attempt: boolean;
  questions_count: number;
}

export interface QuizMobileInfo extends QuizMobileListItem {
  shuffle_questions: boolean;
  shuffle_answers: boolean;
  show_correct_answers: boolean;
  show_score_immediately: boolean;
  available_from: IsoDateString | null;
  available_until: IsoDateString | null;
  total_questions: number;
  total_points: number;
}

export interface QuizAttemptQuestionMobile {
  id: number;
  question: string;
  question_type: QuestionType;
  question_type_label: string;
  points: number;
  is_answered: boolean;
  selected_answer_ids: number[];
  answers: Array<{
    id: number;
    answer: string;
  }>;
}

export interface QuizAttemptDetailMobile {
  id: number;
  quiz_id: number;
  quiz_title: string;
  attempt_number: number;
  status: QuizAttemptStatus;
  status_label: string;
  started_at: IsoDateString | null;
  time_limit_minutes: number | null;
  remaining_time_seconds: number | null;
  total_questions: number;
  answered_questions: number;
  questions: QuizAttemptQuestionMobile[];
}

export interface QuizAttemptHistoryItemMobile {
  id: number;
  attempt_number: number;
  status: QuizAttemptStatus;
  status_label: string;
  score: number | null;
  passed: boolean | null;
  started_at: IsoDateString | null;
  submitted_at: IsoDateString | null;
  time_spent_seconds: number;
  answered_questions: number;
  total_questions: number;
  can_resume: boolean;
}

export interface QuizAttemptHistoryStatsMobile {
  lowest_score: number | null;
  average_score: number | null;
  highest_score: number | null;
  opened_count: number;
  completed_count: number;
  failed_count: number;
}

export interface QuizAttemptHistoryMobile {
  attempts: QuizAttemptHistoryItemMobile[];
  stats: QuizAttemptHistoryStatsMobile;
  best_score: number | null;
  remaining_attempts: number | null;
}

export interface QuizResultMobile {
  id: number;
  quiz_id: number;
  quiz_title: string;
  attempt_number: number;
  status: QuizAttemptStatus;
  status_label: string;
  started_at: IsoDateString | null;
  submitted_at: IsoDateString | null;
  time_spent_seconds: number;
  score: number | null;
  points_earned: number | null;
  points_possible: number;
  passed: boolean | null;
  passing_score: number;
  questions?: Array<{
    id: number;
    question: string;
    points: number;
    points_earned: number;
    is_correct: boolean;
    selected_answer_ids: number[];
    correct_answer_ids: number[];
    explanation: string;
    answers: Array<{
      id: number;
      answer: string;
      is_correct: boolean;
    }>;
  }>;
}

export interface AssignmentMobileListItem {
  id: number;
  title: string;
  description: string;
  attachable_type: AttachableType;
  attachable_id: number | null;
  submission_types: SubmissionType[];
  is_group_assignment: boolean;
  max_points: number;
  passing_score: number;
  is_required: boolean;
  due_date: IsoDateString | null;
  is_past_due: boolean;
  late_submission_allowed: boolean;
  is_available: boolean;
  can_submit: boolean;
  submission_status: SubmissionStatus | null;
  submission_status_label: string | null;
  score: number | null;
  passed: boolean | null;
}

export interface AssignmentSubmissionMobile {
  id: number;
  assignment_id: number;
  submission_type: SubmissionType;
  submission_type_label: string;
  text_content: string | null;
  link_url: string | null;
  status: SubmissionStatus;
  status_label: string;
  submitted_at: IsoDateString | null;
  is_late: boolean;
  days_late: number;
  score: number | null;
  score_after_penalty: number | null;
  passed: boolean | null;
  feedback: string | null;
  graded_at: IsoDateString | null;
  files?: AssignmentSubmissionFileDto[];
  group?: {
    id: number;
    name: string;
    members_count: number;
  } | null;
  created_at: IsoDateString;
  updated_at: IsoDateString;
}

export interface AssignmentMobileDetail extends AssignmentMobileListItem {
  allowed_file_types: string[] | null;
  max_file_size_mb: number;
  max_files: number;
  max_group_size: number | null;
  is_late: boolean;
  late_penalty_percent: number;
  available_from: IsoDateString | null;
  available_until: IsoDateString | null;
  my_submission: AssignmentSubmissionMobile | null;
}

export interface AssignmentGroupMobile {
  id: number;
  assignment_id: number;
  name: string;
  created_by: number;
  creator?: { id: number; name: string };
  members_count: number;
  members?: Array<{
    user_id: number;
    name: string;
    role: string;
    joined_at: IsoDateString | null;
  }>;
  has_submission?: boolean;
  created_at: IsoDateString;
}

export interface AssignmentGroupsIndexMobile {
  groups: AssignmentGroupMobile[];
  my_group_id: number | null;
  max_group_size: number | null;
}

export interface WeeklyActivitySeriesItem {
  date: string;
  watch_duration_seconds: number;
  quiz_attempts_count: number;
  assignment_submissions_count: number;
}

export interface WeeklyActivityMobile {
  range: {
    days: number;
    timezone: string;
    start_date: string;
    end_date: string;
  };
  series: WeeklyActivitySeriesItem[];
  totals: {
    watch_duration_seconds: number;
    quiz_attempts_count: number;
    assignment_submissions_count: number;
  };
}

export interface MobileCourseVideoLessonStats {
  id: number;
  title: string;
  duration_seconds: number;
  thumbnail: string;
  requires_redemption: boolean;
  has_redeemed: boolean;
  is_locked: boolean;
  view_limit: number | null;
  remaining_views: number | null;
  full_plays: number;
  watch_duration_seconds: number;
  access_status: string | null;
  pending_request_id: number | null;
}
