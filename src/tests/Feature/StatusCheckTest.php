<?php
/** 5.ステータス確認機能 */
namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class StatusCheckTest extends TestCase
{
    use RefreshDatabase;

    /**     勤怠レコード付きユーザーを作成するヘルパー     */
    private function createUserWithStatus(string $workStatus): User
    {
        $user = User::factory()->create([
            'email' => $workStatus . '@example.com',
            'password' => bcrypt('password123'),
        ]);

        Attendance::factory()->create([
            'user_id' => $user->id,
            'work_date' => Carbon::today(),
            'work_status' => $workStatus,
        ]);

        return $user;
    }

    public function test_before_work_status_shows_off_work()
    {
        $user = $this->createUserWithStatus('before_work');
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200)
                 ->assertSee('勤務外');
    }

    public function test_working_status_shows_working()
    {
        $user = $this->createUserWithStatus('working');
        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200)
                ->assertSee('勤務中');
    }

    public function test_on_break_status_shows_on_break()
    {
        $user = $this->createUserWithStatus('on_break');

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200)
                 ->assertSee('休憩中');
    }

    public function test_after_work_status_shows_after_work()
    {
        $user = $this->createUserWithStatus('after_work');

        $this->actingAs($user);

        $response = $this->get('/attendance');

        $response->assertStatus(200)
                 ->assertSee('退勤済');
    }
}
