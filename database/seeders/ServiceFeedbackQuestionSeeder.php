<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\ServiceFeedbackQuestion;
use Illuminate\Database\Seeder;

class ServiceFeedbackQuestionSeeder extends Seeder
{
    /** @var list<string> */
    private const DEFAULT_QUESTIONS = [
        'How engaged was the child during this session?',
        'Was progress observed toward therapy goals?',
        'How was the child\'s cooperation and behaviour?',
    ];

    public function run(): void
    {
        $services = Service::query()->get();

        foreach ($services as $service) {
            if ($service->feedbackQuestions()->exists()) {
                continue;
            }

            foreach (self::DEFAULT_QUESTIONS as $index => $text) {
                ServiceFeedbackQuestion::query()->create([
                    'service_id'    => $service->id,
                    'question_text' => $text,
                    'sort_order'    => $index,
                    'is_active'     => true,
                ]);
            }
        }
    }
}
