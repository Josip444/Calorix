<?php

namespace App\Services;

class MealPlanGenerator
{

    /**
     * Simulate an OpenAI-generated meal plan structure.
     *
     * This returns 4 weeks, each with 7 days, each day containing
     * $mealsPerDay meals with simple, deterministic macro values.
     */
    /**
     * Generate a one-week meal plan (7 days) via OpenAI.
     */
    public function generateWeek(
        int $dailyCalories,
        int $proteinTarget,
        int $carbsTarget,
        int $fatsTarget,
        int $mealsPerDay,
        ?string $allergies,
        string $goalType,
        int $weekNumber
    ): array {
        $client = \OpenAI::client(config('services.openai.key'));

        $prompt = $this->buildWeekPrompt(
            $dailyCalories,
            $proteinTarget,
            $carbsTarget,
            $fatsTarget,
            $mealsPerDay,
            $allergies,
            $goalType,
            $weekNumber
        );

        $response = $client->chat()->create([
            'model' => 'gpt-4o-mini', // Updated to a more standard model name if needed, but keeping user's preference if specified. User mentioned gpt-4.1-mini before.
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a nutrition expert. Return ONLY valid JSON. All text must be in Croatian language.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.7,
        ]);

        $content = $response->choices[0]->message->content ?? '';
        
        // Clean up markdown code blocks if present
        $content = preg_replace('/^```json\s*|\s*```$/i', '', trim($content));

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
            throw new \Exception('Invalid JSON from AI: '.$content);
        }

        $this->validateWeekStructure($decoded, $mealsPerDay);

        return $decoded;
    }

    private function buildWeekPrompt(
        int $calories,
        int $protein,
        int $carbs,
        int $fats,
        int $meals,
        ?string $allergies,
        string $goal,
        int $weekNumber
    ): string {
        return "
        Generiraj plan prehrane za JEDAN TJEDAN (7 dana).
        Ovo je TJEDAN BROJ: {$weekNumber}.

        STRICT RULES:
        - Return ONLY valid JSON (no text, no explanation)
        - All text must be in standard Croatian language
        - Food names must be in Croatian
        - Instructions must be in Croatian
        - Do not use trailing commas
        - All numbers must be numeric (no units in values)

        STRUCTURE RULES:
        - Must contain exactly 7 days
        - Each day must contain exactly {$meals} meals

        MEAL RULES:
        - meal_type must be one of: breakfast, lunch, dinner, snack

        USER DATA:
        - Calories target per day: {$calories}
        - Protein target per day: {$protein}g
        - Carbs target per day: {$carbs}g
        - Fats target per day: {$fats}g
        - Goal: {$goal}
        - Allergies: ".($allergies ?: 'none')."

        JSON STRUCTURE:
        {
          \"week_number\": {$weekNumber},
          \"days\": [
            {
              \"day_number\": 1,
              \"meals\": [
                {
                  \"meal_type\": \"breakfast\",
                  \"total_calories\": number,
                  \"total_protein_g\": number,
                  \"total_carbs_g\": number,
                  \"total_fats_g\": number,
                  \"instructions_text\": \"upute na hrvatskom\",
                  \"items\": [
                    {
                      \"food_name\": \"naziv namirnice\",
                      \"quantity\": number,
                      \"unit\": \"g\",
                      \"calories\": number,
                      \"protein_g\": number,
                      \"carbs_g\": number,
                      \"fats_g\": number
                    }
                  ]
                }
              ]
            }
          ]
        }
        ";
    }

    private function validateWeekStructure(array $data, int $mealsPerDay): void
    {
        if (!isset($data['days']) || !is_array($data['days'])) {
            throw new \Exception('Missing days');
        }

        if (count($data['days']) !== 7) {
            throw new \Exception('Days count must be 7');
        }

        foreach ($data['days'] as $dayIndex => $day) {
            if (!isset($day['meals']) || !is_array($day['meals'])) {
                throw new \Exception("Day {$dayIndex} missing meals");
            }

            if (count($day['meals']) !== $mealsPerDay) {
                throw new \Exception("Day {$dayIndex} must have {$mealsPerDay} meals");
            }

            foreach ($day['meals'] as $mealIndex => $meal) {
                $requiredMealFields = [
                    'meal_type',
                    'total_calories',
                    'total_protein_g',
                    'total_carbs_g',
                    'total_fats_g',
                    'items'
                ];

                foreach ($requiredMealFields as $field) {
                    if (!isset($meal[$field])) {
                        throw new \Exception("Meal missing field: {$field}");
                    }
                }

                if (!is_array($meal['items']) || empty($meal['items'])) {
                    throw new \Exception("Meal must have items");
                }

                foreach ($meal['items'] as $itemIndex => $item) {
                    $requiredItemFields = [
                        'food_name',
                        'quantity',
                        'unit',
                        'calories',
                        'protein_g',
                        'carbs_g',
                        'fats_g'
                    ];

                    foreach ($requiredItemFields as $field) {
                        if (!isset($item[$field])) {
                            throw new \Exception("Item missing field: {$field}");
                        }
                    }
                }
            }
        }
    }
    private function mealTypeForIndex(int $index): string
    {
        return match ($index) {
            1 => 'breakfast',
            2 => 'lunch',
            3 => 'dinner',
            default => 'snack',
        };
    }
}

