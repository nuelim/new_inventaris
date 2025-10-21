<?php

namespace Database\Factories;

use App\Models\AssetType;
use Illuminate\Database\Eloquent\Factories\Factory;

class AssetTypeFactory extends Factory
{
    protected $model = AssetType::class;

    public function definition()
    {
        $categories = ['IT', 'Kendaraan', 'Bangunan', 'Mesin', 'Lainnya'];
        
        return [
            'name' => fake()->words(2, true),
            'code' => 'AT-' . fake()->unique()->numerify('#####'),
            'description' => fake()->sentence(),
            'category' => fake()->randomElement($categories),
            'depreciation_years' => fake()->numberBetween(1, 10),
            'requires_calibration' => fake()->boolean(30),
            'requires_maintenance' => fake()->boolean(90),
            'is_active' => true,
        ];
    }

    public function it(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'IT',
            'requires_calibration' => true,
            'depreciation_years' => 3,
        ]);
    }

    public function vehicle(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Kendaraan',
            'requires_maintenance' => true,
            'depreciation_years' => 5,
        ]);
    }

    public function building(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Bangunan',
            'depreciation_years' => 20,
        ]);
    }

    public function machine(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Mesin',
            'requires_calibration' => fake()->boolean(50),
            'requires_maintenance' => true,
            'depreciation_years' => 7,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}