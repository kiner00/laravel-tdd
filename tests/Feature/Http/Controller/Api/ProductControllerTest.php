<?php

namespace Tests\Feature\Http\Controller\Api;

use Faker\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
// use SebastianBergmann\FileIterator\Factory;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function non_authenticated_users_cannot_access_the_following_endpoints_for_the_product_api()
    {
        $index = $this->json('GET', '/api/products');
        $index->assertStatus(401);

        $store = $this->json('POST', '/api/products');
        $store->assertStatus(401);

        $show = $this->json('GET', '/api/products/-1');
        $show->assertStatus(401);

        $update = $this->json('PUT', '/api/products/-1');
        $update->assertStatus(401);

        $destroy = $this->json('DELETE', '/api/products/-1');
        $destroy->assertStatus(401);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_return_a_collection_of_paginated_products()
    {
        $product1 = $this->create('Product');
        $product2 = $this->create('Product');
        $product3 = $this->create('Product');

        $response = $this->actingAs($this->create('User', [], false), 'api')->json('GET', '/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id','name','slug','price','created_at']
                ],

                'links' => ['first', 'last', 'prev', 'next'],
                'meta'  => [
                    'current_page', 'last_page', 'from', 'to', 'path', 'per_page', 'total'
                ]
            ]);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_create_a_product()
    {
        //Given
        $faker = Factory::create();

        //When
        $response = $this->actingAs($this->create('User', [], false), 'api')->json('POST', '/api/products', [
            'name' => $name = $faker->company,
            'slug'  => str_slug($name),
            'price' => $price = random_int(10, 100)
        ]);
            // post request create product

        //Then
            // product exists
        $response->assertJsonStructure([
            'id','name','slug','price','created_at'
        ])
        ->assertJson([
            'name'  => $name,
            'slug'  => str_slug($name),
            'price' => $price
        ])
        ->assertStatus(201);

        $this->assertDatabaseHas('products', [
            'name'  => $name,
            'slug'  => str_slug($name),
            'price' => $price
        ]);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_returwill_fail_with_a_404_if_product_is_not_found()
    {
        $response = $this->actingAs($this->create('User', [], false), 'api')->json('GET', 'api/products/-1');

        $response->assertStatus(404);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_return_a_product()
    {
        //Model name
        $product = $this->create('Product');

        $response = $this->actingAs($this->create('User', [], false), 'api')->json('GET', "api/products/$product->id");

        $response->assertStatus(200)
            ->assertExactJson([
                'id'    =>  $product->id,
                'name'  =>  $product->name,
                'slug'  =>  $product->slug,
                'price'  =>  $product->price,
                'created_at'  =>  (string)$product->created_at,
            ]);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function will_fail_with_a_404_if_product_we_want_to_update_not_found()
    {
        $response = $this->actingAs($this->create('User', [], false), 'api')->json('PUT', 'api/products/-1');

        $response->assertStatus(404);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_update_a_product()
    {
        $product = $this->create('Product');

        $response = $this->actingAs($this->create('User', [], false), 'api')->json('PUT', "api/products/$product->id", [
            'name'  => $product->name.'_updated',
            'slug'  => str_slug($product->slug.'_updated'),
            'price' => $product->price+10
        ]);

        $response->assertStatus(200)
            ->assertExactJson([
                'id'    => $product->id,
                'name'  =>  $product->name.'_updated',
                'slug'  => str_slug($product->slug.'_updated'),
                'price' => $product->price+10,
                'created_at'    => (string)$product->created_at
            ]);

        $this->assertDatabaseHas('products', [
            'id'    => $product->id,
            'name'  =>  $product->name.'_updated',
            'slug'  => str_slug($product->slug.'_updated'),
            'price' => $product->price+10,
            'created_at'    => (string)$product->created_at,
            'updated_at'    => (string)$product->updated_at,
        ]);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function will_fail_with_a_404_if_product_we_want_to_delete_not_found()
    {
        $response = $this->actingAs($this->create('User', [], false), 'api')->json('DELETE', 'api/products/-1');

        $response->assertStatus(404);
    }

    /**
     * GIVEN, WHEN THEN pattern
     *
     * @test
     */
    public function can_delete_a_product()
    {
        $product = $this->create('Product');

        $response = $this->actingAs($this->create('User', [], false), 'api')->json('DELETE', "api/products/$product->id");

        $response->assertStatus(204)
            ->assertSee(null);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
