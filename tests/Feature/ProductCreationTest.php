<?php

namespace Tests\Feature;

use App\Filament\Resources\ProductResource\Pages\CreateProduct;
use App\Models\Color;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_creating_a_product_also_creates_its_models(): void
    {
        $this->actingAs(User::factory()->create());

        $color = Color::create([
            'name' => 'Blue',
            'hex_code' => '#0000FF',
            'is_active' => true,
        ]);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'title' => 'Test Product',
                'description' => 'A test product',
                'category_id' => null,
                'is_active' => true,
                'colorImages' => [
                    ['color_id' => $color->id, 'image' => null],
                ],
                'models' => [
                    [
                        'name' => 'Model A',
                        'price' => '120.50',
                        'price_after_discount' => '100.00',
                        'attributes' => ['lens' => 'blue'],
                        'is_active' => true,
                    ],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('title', 'Test Product')->firstOrFail();

        $this->assertDatabaseHas('product_models', [
            'product_id' => $product->id,
            'name' => 'Model A',
        ]);

        $model = $product->models()->firstOrFail();
        $this->assertSame('120.50', (string) $model->price);
        $this->assertSame('100.00', (string) $model->price_after_discount);
        $this->assertSame(['lens' => 'blue'], $model->attributes);

        $this->assertTrue($product->colors()->where('colors.id', $color->id)->exists());
    }
}
