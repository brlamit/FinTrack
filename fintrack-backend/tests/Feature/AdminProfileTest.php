<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_upload_avatar_and_previous_file_is_deleted()
    {
        // ensure avatar disk env is set to public for tests
        putenv('AVATAR_DISK=public');
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'admin']);

        // create an existing avatar file and set it on the user
        $existing = UploadedFile::fake()->create('old.jpg', 100, 'image/jpeg');
        $existingPath = $existing->storeAs('avatars', 'old.jpg', 'public');
        $user->avatar = $existingPath;
        $user->avatar_disk = 'public';
        $user->save();

        $this->actingAs($user);

        $newFile = UploadedFile::fake()->create('new.jpg', 120, 'image/jpeg');

        $response = $this->post(route('admin.avatar.update'), [
            'avatar' => $newFile,
        ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        // old file should be deleted
        Storage::disk('public')->assertMissing($existingPath);

        // new file should exist
        $stored = $user->refresh()->getRawOriginal('avatar');
        Storage::disk('public')->assertExists($stored);
    }

    public function test_admin_can_update_profile()
    {
        $user = User::factory()->create(['role' => 'admin']);
        $this->actingAs($user);

        $payload = [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'phone' => '1234567890',
        ];

        $response = $this->put(route('admin.profile.update'), $payload);
        $response->assertRedirect(route('admin.profile'));

        $user->refresh();
        $this->assertEquals('New Name', $user->name);
        $this->assertEquals('newemail@example.com', $user->email);
        $this->assertEquals('1234567890', $user->phone);
    }
}
