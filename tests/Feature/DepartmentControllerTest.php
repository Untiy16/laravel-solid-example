<?php

namespace Tests\Feature;

use App\Enums\ReportType;
use App\Models\Department;
use App\Repositories\DepartmentRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DepartmentControllerTest extends TestCase
{
    use RefreshDatabase;

    public array $departmentJsonStructureMultiple = [
        'data' => [
            '*' => [
                'id',
                'name',
                'address',
            ]
        ]
    ];

    public array $departmentJsonStructureSingle = [
        'data' => [
            'id',
            'name',
            'address',
        ]
    ];

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
    public static function reportTypes(): array
    {
        return array_map(fn($i) => [$i], array_column(ReportType::cases(), 'value'));
    }

    public static function validDepartmentData(): array
    {
        return [
            [['name' => 'validName', 'address' => 'Suite 246 81515 Osinski Manor, East Luke, TX 59690-4605']],
        ];
    }

    public function test_get_empty_departments_list(): void
    {
        $response = $this->getJson('/api/departments');
        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructureMultiple)
            ->assertJsonCount(0, 'data');
    }

    public function test_get_empty_departments_list_using_mock(): void
    {
        $this->mock(DepartmentRepository::class)
            ->shouldReceive('findAll')
            ->once()
            ->andReturn(Department::hydrate([
                ['id' => 1, 'name' => 'name1', 'address' => 'address1'],
                ['id' => 2, 'name' => 'name2', 'address' => 'address2'],
            ]));

        $response = $this->getJson('/api/departments');
        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructureMultiple)
            ->assertJsonCount(2, 'data');
    }

    public function test_get_not_empty_departments_list(): void
    {
        Department::factory()->count(5)->create();

        $response = $this->getJson('/api/departments');
        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructureMultiple)
            ->assertJsonCount(5, 'data');
    }

    public function test_get_non_existent_department_by_id()
    {
        $url = '/api/departments/1';

        $response = $this->getJson($url);
        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_get_existent_department_by_id()
    {
        $department = Department::factory()->create();

        $response = $this->getJson("/api/departments/$department->id");
        $response
            ->assertStatus(200)
            ->assertJsonStructure($this->departmentJsonStructureSingle);
    }


    #[DataProvider('invalidDepartmentData')]
    public function test_create_department_with_invalid_data($data, $fields): void
    {
       $response = $this->postJson('/api/departments', $data);
       $response
           ->assertStatus(422)
           ->assertJsonValidationErrors($fields);
    }
    #[DataProvider('validDepartmentData')]
    public function test_create_department_with_valid_data($data): void
    {
       $response = $this->postJson('/api/departments', $data);

       $response
           ->assertCreated()
           ->assertJsonStructure($this->departmentJsonStructureSingle);
    }
    #[DataProvider('validDepartmentData')]
    public function test_update_non_existent_department($data)
    {
        $url = '/api/departments/1';

        $response = $this->putJson($url, $data);

        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    #[DataProvider('validDepartmentData')]
    public function test_update_existent_department($data)
    {
        $department = Department::factory()->create();

        $response = $this->putJson("/api/departments/$department->id", $data);
        $response->assertNoContent();
    }

    #[DataProvider('invalidDepartmentData')]
    public function test_update_existent_department_with_invalid_data($data, $fields)
    {
        $department = Department::factory()->create();
        $url = "/api/departments/$department->id";

        $response = $this->putJson($url, $data);
        $response
           ->assertStatus(422)
           ->assertJsonValidationErrors($fields);
    }

    public function test_delete_non_existent_department()
    {
        $response = $this->deleteJson('/api/departments/1');
        $response
            ->assertNotFound()
            ->assertJsonStructure(['message']);

//        $this->assertThrows(
//            fn () => $this->withoutExceptionHandling()->deleteJson($url),
//            ModelNotFoundException::class
//        );
    }

    public function test_delete_existent_department()
    {
        $department = Department::factory()->create();
        $url = "/api/departments/$department->id";

        $deleteResponse = $this->deleteJson($url);
        $deleteResponse
            ->assertNoContent();

        $getResponse = $this->getJson($url);
        $getResponse->assertNotFound();
    }
    public function test_departments_report_with_wrong_report_type()
    {
        $this->postJson('/api/departments/report/1/invalidType')
            ->assertBadRequest()
            ->assertJson(['message' => 'Report Type not found']);
    }

    #[DataProvider('reportTypes')]
    public function test_departments_report_with_valid_report_types($data)
    {
        $department = Department::factory()->create();

        $response = $this->postJson("/api/departments/report/$department->id/$data");
        $response
            ->assertCreated()
            ->assertJsonStructure([
                'department' => [
                    'id',
                    'name',
                ],
                'rows',
                'summary'
            ]);
    }
}
