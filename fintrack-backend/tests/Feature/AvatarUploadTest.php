<?php
namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class AvatarUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_upload_avatar_and_it_is_stored()
    {
        // Arrange: fake the public disk and create a user
        Storage::fake('public');
        $user = User::factory()->create();

        // Act: act as the user and post an avatar file
        $file = UploadedFile::fake()->image('avatar.jpg', 100, 100);

        $response = $this->actingAs($user)
            ->post(route('user.avatar.update'), [
                'avatar' => $file,
            ]);

        // Assert: the user record updated and file stored on the public disk
        $user->refresh();
        $this->assertNotNull($user->avatar, 'avatar path should be set on the user');
        Storage::disk('public')->assertExists($user->avatar);

        // Response should redirect back for a non-AJAX request
        $response->assertRedirect();
    }
}
