<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\AIContent\Concerns;

use Illuminate\Validation\Validator;

trait ValidatesAIContentGenerationConfig
{
    /**
     * @param  array<string,mixed>  $config
     */
    protected function validateGenerationConfig(Validator $validator, string $prefix, string $targetType, mixed $config): void
    {
        if (! is_array($config)) {
            return;
        }

        switch ($targetType) {
            case 'summary':
                if (isset($config['length']) && ! in_array($config['length'], ['short', 'medium', 'long'], true)) {
                    $validator->errors()->add($prefix.'.length', 'Length must be short, medium, or long.');
                }

                if (isset($config['include_key_points']) && ! is_bool($config['include_key_points'])) {
                    $validator->errors()->add($prefix.'.include_key_points', 'include_key_points must be boolean.');
                }

                break;

            case 'quiz':
                if (isset($config['question_count']) && (! is_int($config['question_count']) || $config['question_count'] < 1 || $config['question_count'] > 20)) {
                    $validator->errors()->add($prefix.'.question_count', 'question_count must be an integer between 1 and 20.');
                }

                if (isset($config['difficulty']) && ! in_array($config['difficulty'], ['easy', 'medium', 'hard'], true)) {
                    $validator->errors()->add($prefix.'.difficulty', 'difficulty must be easy, medium, or hard.');
                }

                if (isset($config['question_styles'])) {
                    if (! is_array($config['question_styles']) || $config['question_styles'] === []) {
                        $validator->errors()->add($prefix.'.question_styles', 'question_styles must be a non-empty array.');
                        break;
                    }

                    foreach ($config['question_styles'] as $style) {
                        if (! in_array($style, ['single_choice', 'multiple_choice', 'true_false'], true)) {
                            $validator->errors()->add($prefix.'.question_styles', 'question_styles contains an unsupported value.');
                            break;
                        }
                    }
                }

                break;

            case 'flashcards':
                if (isset($config['card_count']) && (! is_int($config['card_count']) || $config['card_count'] < 1 || $config['card_count'] > 50)) {
                    $validator->errors()->add($prefix.'.card_count', 'card_count must be an integer between 1 and 50.');
                }

                if (isset($config['focus'])) {
                    if (! is_array($config['focus'])) {
                        $validator->errors()->add($prefix.'.focus', 'focus must be an array.');
                        break;
                    }

                    foreach ($config['focus'] as $focus) {
                        if (! in_array($focus, ['definitions', 'concepts', 'formulas'], true)) {
                            $validator->errors()->add($prefix.'.focus', 'focus contains an unsupported value.');
                            break;
                        }
                    }
                }

                break;

            case 'assignment':
                if (isset($config['assignment_style']) && ! in_array($config['assignment_style'], ['practice', 'essay', 'project'], true)) {
                    $validator->errors()->add($prefix.'.assignment_style', 'assignment_style must be practice, essay, or project.');
                }

                if (isset($config['submission_types'])) {
                    if (! is_array($config['submission_types']) || $config['submission_types'] === []) {
                        $validator->errors()->add($prefix.'.submission_types', 'submission_types must be a non-empty array.');
                        break;
                    }

                    foreach ($config['submission_types'] as $type) {
                        if (! is_int($type) || ! in_array($type, [0, 1, 2], true)) {
                            $validator->errors()->add($prefix.'.submission_types', 'submission_types contains an unsupported value.');
                            break;
                        }
                    }
                }

                if (isset($config['max_points']) && (! is_numeric($config['max_points']) || (float) $config['max_points'] <= 0)) {
                    $validator->errors()->add($prefix.'.max_points', 'max_points must be a positive number.');
                }

                break;
        }
    }
}
