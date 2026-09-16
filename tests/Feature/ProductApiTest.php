<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_product_view_permission_can_list_products(): void
    {
        $user = $this->createUserWithPermissions(['products.view']);

        $this->actingAs($user);

        Product::factory()->create();

        $response = $this->getJson('/api/products');

        $response->assertOk()
            ->assertJsonStructure([
                'products',
                'stats',
            ]);
    }

    public function test_user_without_product_view_permission_cannot_list_products(): void
    {
        $user = $this->createUserWithPermissions([]);

        $this->actingAs($user);

        $response = $this->getJson('/api/products');

        $response->assertForbidden();
    }

    public function test_user_with_product_create_permission_can_create_product(): void
    {
        $user = $this->createUserWithPermissions(['products.create']);

        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();

        $this->actingAs($user);

        $response = $this->postJson('/api/products', [
            'name' => 'Test Product',
            'sku' => 'TEST-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'description' => 'Test product description',
            'price' => 1500,
            'quantity' => 10,
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('products', [
            'sku' => 'TEST-001',
            'name' => 'Test Product',
        ]);
    }

    public function test_product_creation_requires_required_fields(): void
    {
        $user = $this->createUserWithPermissions(['products.create']);

        $this->actingAs($user);

        $response = $this->postJson('/api/products', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'name',
                'sku',
                'price',
                'quantity',
            ]);
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $role = Role::factory()->create([
            'slug' => 'test-role-' . uniqid(),
            'name' => 'Test Role',
            'is_active' => true,
        ]);

        $permissionIds = collect($permissions)->map(function (string $permission) {
            return Permission::factory()->create([
                'name' => str_replace('.', ' ', $permission),
                'slug' => $permission,
                'group' => 'test',
                'is_active' => true,
            ])->id;
        });

        $role->permissions()->sync($permissionIds);

        return User::factory()->create([
            'role_id' => $role->id,
        ]);
    }
}
