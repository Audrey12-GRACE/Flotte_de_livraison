<?php

namespace Tests\Feature;

use App\Models\Course;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CourseCancellationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_course_can_be_cancelled_before_pickup(): void
    {
        $course = Course::create([
            'adresse' => 'Be, Lome',
            'montant' => 1000,
        ]);

        $response = $this->patchJson("/api/courses/{$course->id}/annuler");

        $response->assertOk()
            ->assertJsonPath('statut', 'annulee');
        $this->assertDatabaseHas('courses', [
            'id' => $course->id,
            'statut' => 'annulee',
        ]);
    }
}
