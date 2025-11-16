<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminAvatarRemoveTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_remove_avatar()
    {
        putenv('AVATAR_DISK=public');
        Storage::fake('public');

        $user = User::factory()->create(['role' => 'admin']);

        $file = UploadedFile::fake()->create('old.jpg', 100, 'image/jpeg');
        $path = $file->storeAs('avatars', 'old.jpg', 'public');

        $user->avatar = $path;
        $user->avatar_disk = 'public';
        $user->save();

        $this->actingAs($user);

        $response = $this->post(route('admin.avatar.remove'));
        $response->assertRedirect(route('admin.profile'));

        // file should be deleted
        Storage::disk('public')->assertMissing($path);

        $user->refresh();
        $this->assertNull($user->avatar);
        $this->assertNull($user->avatar_disk);
    }
}
