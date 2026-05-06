<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\App;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<App>
 */
final class AppFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name'            => fake()->company(),
            'api_key'         => App::generateApiKey(),
            'allowed_domains' => [fake()->domainName()],
            'callback_url'    => null,
            'active'          => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(['active' => false]);
    }
}
