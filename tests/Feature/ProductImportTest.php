<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_preview_and_import_products_from_csv(): void
    {
        $user = $this->productUser();
        $this->actingAs($user);

        $csv = implode("\n", [
            'Product Name,Product Group SKU,Variant Name,Attribute 1,Option 1,Attribute 2,Option 2,Parent Category,Category,Subcategory,Brand,SKU,Barcode,Customer Unit,Supplier Unit,Customer Units in One Supplier Unit,Purchase Price,Selling Price,Regular Price,Opening Supplier Quantity,Additional Customer-Unit Stock,Low Stock Alert,Status',
            'Basmati Rice,,,,,,,Groceries,Rice,Basmati Rice,Store Brand,RICE-001,890001,KG,Bag,50,12000,280,300,3,10,10,Active',
            'Cotton T-Shirt,TSHIRT,Black / Small,Color,Black,Size,Small,Clothing,Shirts,,Demo Fashion,TSHIRT-BLK-S,890101,Piece,Carton,24,12000,750,900,1,0,5,Active',
            'Cotton T-Shirt,TSHIRT,Blue / Large,Color,Blue,Size,Large,Clothing,Shirts,,Demo Fashion,TSHIRT-BLU-L,890102,Piece,Carton,24,12000,800,950,1,6,5,Active',
        ]);

        $preview = $this->post(route('products.import.preview'), [
            'csv_file' => UploadedFile::fake()->createWithContent('products.csv', $csv),
        ])->assertOk()
            ->assertSee('Map Product Columns')
            ->assertSee('Basmati Rice');

        $token = array_key_first(session('product_import'));
        $mapping = [
            'name' => '0',
            'product_group' => '1',
            'variant_name' => '2',
            'attribute_1_name' => '3',
            'attribute_1_value' => '4',
            'attribute_2_name' => '5',
            'attribute_2_value' => '6',
            'parent_category' => '7',
            'category' => '8',
            'subcategory' => '9',
            'brand' => '10',
            'sku' => '11',
            'barcode' => '12',
            'customer_unit' => '13',
            'supplier_unit' => '14',
            'units_per_supplier' => '15',
            'purchase_price' => '16',
            'selling_price' => '17',
            'regular_price' => '18',
            'opening_stock' => '19',
            'additional_customer_stock' => '20',
            'low_stock_alert' => '21',
            'status' => '22',
        ];

        $response = $this->post(route('products.import.store'), [
            'token' => $token,
            'mapping' => $mapping,
            'create_missing' => 1,
        ])->assertOk()
            ->assertSee('Products Imported')
            ->assertSee('2');

        $rice = Product::where('sku', 'RICE-001')->firstOrFail();
        $this->assertSame(160, $rice->stock_quantity);
        $parent = \App\Models\Category::where('name', 'Groceries')->firstOrFail();
        $category = \App\Models\Category::where('name', 'Rice')->firstOrFail();
        $subcategory = \App\Models\Category::where('name', 'Basmati Rice')->firstOrFail();
        $this->assertSame($parent->id, $category->parent_id);
        $this->assertSame($category->id, $subcategory->parent_id);
        $this->assertSame($subcategory->id, $rice->category_id);
        $this->assertDatabaseHas('brands', ['name' => 'Store Brand']);
        $this->assertDatabaseHas('units', ['name' => 'Bag']);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $rice->id,
            'purchase_unit_factor' => 50,
            'compare_at_price' => 300,
            'stock_quantity' => 160,
        ]);

        $shirt = Product::where('name', 'Cotton T-Shirt')->firstOrFail();
        $this->assertTrue($shirt->has_variants);
        $this->assertCount(2, $shirt->variants);
        $this->assertDatabaseHas('product_attributes', ['name' => 'Color']);
        $this->assertDatabaseHas('product_attribute_values', ['value' => 'Black']);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $shirt->id,
            'sku' => 'TSHIRT-BLK-S',
            'name' => 'Black / Small',
            'stock_quantity' => 24,
        ]);
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $shirt->id,
            'sku' => 'TSHIRT-BLU-L',
            'name' => 'Blue / Large',
            'stock_quantity' => 30,
        ]);
    }

    public function test_duplicate_sku_is_rejected_without_blocking_valid_rows(): void
    {
        $user = $this->productUser();
        $this->actingAs($user);

        Product::create([
            'category_id' => \App\Models\Category::create(['name' => 'Existing'])->id,
            'name' => 'Existing Product',
            'sku' => 'DUP-001',
            'purchase_price' => 10,
            'selling_price' => 15,
            'stock_quantity' => 1,
            'low_stock_alert' => 1,
        ]);

        $token = (string) Str::uuid();
        session(["product_import.{$token}" => [
            'headers' => ['Name', 'Category', 'SKU', 'Unit', 'Cost', 'Price'],
            'rows' => [
                ['Duplicate', 'General', 'DUP-001', 'Piece', '10', '15'],
                ['Valid', 'General', 'VALID-001', 'Piece', '20', '30'],
            ],
        ]]);

        $this->post(route('products.import.store'), [
            'token' => $token,
            'mapping' => [
                'name' => '0',
                'category' => '1',
                'sku' => '2',
                'customer_unit' => '3',
                'purchase_price' => '4',
                'selling_price' => '5',
            ],
            'create_missing' => 1,
        ])->assertOk()
            ->assertSee('Rows Rejected')
            ->assertSee('SKU DUP-001 already exists')
            ->assertSee('Download Error Report');

        $this->assertDatabaseHas('products', ['sku' => 'VALID-001']);
        $this->assertDatabaseCount('products', 2);

        $errorToken = array_key_first(session('product_import_errors'));
        $this->get(route('products.import.errors', $errorToken))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('SKU DUP-001 already exists');
    }

    public function test_product_import_template_downloads_as_csv(): void
    {
        $this->actingAs($this->productUser());

        $this->get(route('products.import.template'))
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8')
            ->assertSee('Product Name')
            ->assertSee('Parent Category')
            ->assertSee('Product Group SKU')
            ->assertSee('Variant Name')
            ->assertSee('Attribute 1')
            ->assertSee('Customer Units in One Supplier Unit')
            ->assertSee('Additional Customer-Unit Stock');
    }

    private function productUser(): User
    {
        $tenant = Tenant::create([
            'name' => 'Import Store',
            'slug' => uniqid('import-store-'),
        ]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        $permission = Permission::firstOrCreate(['name' => 'products.create']);
        $role = Role::firstOrCreate(['name' => 'owner']);
        $role->givePermissionTo($permission);
        $user->assignRole($role);

        return $user;
    }
}
