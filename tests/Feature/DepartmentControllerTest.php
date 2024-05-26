<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public array $departmentJsonStructure = [
        'data' => [
            '*' => [
                'id',
                'name',
                'address',
            ]
        ]
    ];

    public function test_get_empty_departments_list(): void
    {
        $response = $this->getJson('/api/departments');

        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructure)
            ->assertJsonCount(0, 'data');
    }

    public function test_get_not_empty_departments_list(): void
    {
        Department::factory()->count(1)->create();
        $response = $this->getJson('/api/departments');

        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructure)
            ->assertJsonCount(1, 'data');
    }

    public function test_get_non_existent_department_by_id()
    {
        $url = '/api/departments/1';

        $response = $this->getJson($url);
        $response->assertNotFound();

        $this->assertThrows(
            fn () => $this->withoutExceptionHandling()->getJson($url),
            ModelNotFoundException::class
        );
    }

    public function test_get_existent_department_by_id()
    {
        Department::factory()->count(1)->create();

        $response = $this->getJson('/api/departments/1');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'address',
                ]
            ]);
    }

    #[DataProvider('invalidDepartmentData')]
    public function test_create_department_with_invalid_data($data, $fields): void
    {
       $response = $this->postJson('/api/departments', $data);
       $response
           ->assertStatus(422)
           ->assertJsonValidationErrors($fields);
    }

    public static function invalidDepartmentData(): array
    {
        return [
            [['name' => '', 'address' => ''], ['name', 'address']],
            [['address' => ''], ['name', 'address']],
            [['name' => ''], ['name', 'address']],
            [[], ['name', 'address']],
            [['name' => 'validName'], ['address']],
            [['address' => 'Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605'], ['name']],
            [['name' => 'validName', 'address' => 'a'], ['address']],
            [['name' => 'n', 'address' => 'Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605'], ['name']],
            [['name' => 'validName', 'address' => 'Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605'], ['address']],
        ];
    }
}
