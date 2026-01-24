<?php

declare(strict_types=1);

namespace App\Http\Requests\Mobile\Concerns;

use App\Models\PlaybackSession;

trait ValidatesSessionOwnership
{
    public function authorize(): bool
    {
        $session = PlaybackSession::find($this->integer('session_id'));

        return $session !== null && $session->user_id === $this->user()?->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:playback_sessions,id'],
            'watch_duration' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function bodyParameters(): array
    {
        return [
            'session_id' => [
                'description' => 'Playback session identifier.',
                'example' => 123,
            ],
            'watch_duration' => [
                'description' => 'Total seconds watched.',
                'example' => 1234,
            ],
        ];
    }
}
