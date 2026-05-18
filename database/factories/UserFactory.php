<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        $gender = fake()->randomElement(['male', 'female', 'other']);
        $dob = fake()->dateTimeBetween('-18 years', '-5 years');

        return [
            'full_name'        => fake()->name(),
            'father_name'      => fake()->name('male'),
            'email'            => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password'         => static::$password ??= Hash::make('password'),
            'role_id'          => Role::query()->where('name', 'child')->value('id') ?? 1,
            'age'              => (int) now()->diffInYears($dob),
            'gender'           => $gender,
            'date_of_birth'    => $dob->format('Y-m-d'),
            'address'          => fake()->streetAddress() . ', ' . fake()->city(),
            'phone_number'     => '03' . fake()->numerify('#########'),
            'whatsapp_number'  => '03' . fake()->numerify('#########'),
            'parent_notes'     => null,
            'status'           => 'active',
            'remember_token'   => Str::random(10),
        ];
    }

    public function child(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::query()->where('name', 'child')->value('id'),
            'status'  => 'active',
        ]);
    }

    public function therapist(): static
    {
        return $this->state(fn () => [
            'role_id'      => Role::query()->where('name', 'therapist')->value('id'),
            'status'       => 'active',
            'father_name'  => null,
            'parent_notes' => null,
            'date_of_birth' => fake()->dateTimeBetween('-50 years', '-25 years')->format('Y-m-d'),
            'age'          => fake()->numberBetween(25, 55),
        ]);
    }

    public function admin(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::query()->where('name', 'admin')->value('id'),
            'status'  => 'active',
        ]);
    }

    public function finance(): static
    {
        return $this->state(fn () => [
            'role_id' => Role::query()->where('name', 'finance')->value('id'),
            'status'  => 'active',
        ]);
    }

    public function approvedChild(): static
    {
        return $this->child()->state(fn () => [
            'status'      => 'active',
            'approved_at' => now(),
        ]);
    }
}
