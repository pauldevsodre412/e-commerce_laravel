<?php

/*
 * This file is part of the Antvel Shop package.
 *
 * (c) Gustavo Ocanto <gustavoocanto@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */


namespace Tests\Feature\Products;

use Tests\TestCase;
use Antvel\User\Models\User;
use Antvel\Product\Models\ProductFeatures;
use Illuminate\Foundation\Testing\DatabaseMigrations;

class ProductsFeaturesTest extends TestCase
{
    use DatabaseMigrations;

    public function setUp()
    {
        parent::setUp();

        $this->admin = factory('Antvel\User\Models\User')->states('admin')->create();
    }

    protected function validData($attributes = [])
    {
        return array_merge($attributes, [
            'name' => 'New name',
            'help_message' => 'New message',
            'status' => true,
            'validation_rules' => [
                'required' => true
            ]
        ]);
    }

    /** @test */
    function an_unauthorized_user_is_not_allowed_to_manage_products_features()
    {
        $user = factory(User::class)->states('customer')->create();

        $this->actingAs($user)
            ->get(route('features.index'))
            ->assertStatus(401);
    }

    /** @test */
    function an_authorized_user_is_allowed_to_manage_products_features()
    {
        factory(ProductFeatures::class)->create(['name' => 'foo']);

        $this->actingAs($this->admin)->get(route('features.index'))->assertSuccessful()->assertSeeText('Foo');
    }

    /** @test */
    function an_authorized_user_can_create_new_features()
    {
        $this->actingAs($this->admin)->get(route('features.create'))->assertSuccessful();
    }

    /** @test */
    function an_unauthorized_user_can_store_new_features()
    {
        $this->post(route('features.store'), $this->validData())
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertCount(0, ProductFeatures::get());
    }

    /** @test */
    function an_authorized_user_can_store_new_features()
    {
        $this->actingAs($this->admin)->post(route('features.store'), $this->validData())->assertStatus(302);

        tap(ProductFeatures::get()->first(), function ($feature) {
            $this->assertEquals('New name', $feature->name);
            $this->assertEquals('New message', $feature->help_message);
            $this->assertEquals('required', $feature->validation_rules);
        });
    }

    /** @test */
    function an_authorized_user_can_edit_new_features()
    {
        $feature = factory(ProductFeatures::class)->create(['name' => 'foo', 'help_message' => 'bar']);

        $this->actingAs($this->admin)
            ->get(route('features.edit', $feature))
            ->assertSuccessful()
            ->assertSee($feature->name)
            ->assertSee($feature->help_message);
    }

    /** @test */
    function an_unauthorized_user_cannot_edit_new_features()
    {
        $feature = factory(ProductFeatures::class)->create(['name' => 'foo', 'help_message' => 'bar']);

        $this->get(route('features.edit', $feature))
            ->assertStatus(302)
            ->assertRedirect(route('login'));

        $this->assertSame('foo', $feature->name);
        $this->assertSame('bar', $feature->help_message);
    }

    /** @test */
    function an_authorized_user_can_update_a_given_features()
    {
        $feature = factory(ProductFeatures::class)->create([
            'name' => 'Old name',
            'help_message' => 'Old message',
            'status' => true,
            'validation_rules' => 'required'
        ]);

        $data = [
            'name' => 'Updated name',
            'help_message' => 'Updated message',
            'status' => true,
            'validation_rules' => [
                'required' => false
            ]
        ];

        $this->actingAs($this->admin)->patch(route('features.update', $feature), $data)
            ->assertStatus(302)
            ->assertRedirect(route('features.edit', $feature));

        tap($feature->fresh(), function ($feature) {
            $this->assertEquals('Updated name', $feature->name);
            $this->assertEquals('Updated message', $feature->help_message);
            $this->assertTrue((bool) $feature->status);
            $this->assertEquals('', $feature->validation_rules);
        });
    }
}
