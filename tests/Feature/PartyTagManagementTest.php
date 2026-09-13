<?php

namespace Tests\Feature;

use App\Models\PartyTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PartyTagManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_party_tag(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        $response = $this->actingAs($admin)->post(route('party-tags.store'), [
            'name' => 'عميل ذهبي',
            'applies_to' => 'customer',
        ]);

        $response->assertRedirect(route('party-tags.index'));

        $this->assertDatabaseHas('party_tags', [
            'name' => 'عميل ذهبي',
            'applies_to' => 'customer',
        ]);

        $tag = PartyTag::query()->where('name', 'عميل ذهبي')->firstOrFail();
        $this->assertNotSame('', $tag->slug);
    }

    public function test_repeated_tag_name_and_applies_to_gets_an_incremented_slug_instead_of_colliding(): void
    {
        $this->seed();

        $admin = User::query()->where('email', 'admin@tallalin.local')->firstOrFail();

        PartyTag::query()->create([
            'name' => 'مورد رئيسي',
            'slug' => 'mord-ryysy',
            'applies_to' => 'supplier',
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->post(route('party-tags.store'), [
            'name' => 'مورد رئيسي',
            'applies_to' => 'supplier',
        ]);

        $response->assertRedirect(route('party-tags.index'));
        $response->assertSessionDoesntHaveErrors();

        $this->assertSame(
            2,
            PartyTag::query()->where('name', 'مورد رئيسي')->where('applies_to', 'supplier')->count()
        );

        $newTag = PartyTag::query()
            ->where('name', 'مورد رئيسي')
            ->where('applies_to', 'supplier')
            ->where('slug', '!=', 'mord-ryysy')
            ->first();

        $this->assertNotNull($newTag, 'Expected a second tag with an incremented slug.');
        $this->assertSame('mord-ryysy-2', $newTag->slug);
    }
}
