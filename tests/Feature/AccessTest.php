<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->makeSchool();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/')->assertRedirect('/login');
        $this->get('/classrooms/1/gradebook')->assertRedirect('/login');
    }

    public function test_users_can_sign_in_and_out(): void
    {
        $this->post('/login', ['email' => 'tina@test.local', 'password' => 'password'])->assertRedirect('/');
        $this->assertAuthenticatedAs($this->teacherUser);

        $this->post('/logout')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_wrong_password_is_rejected(): void
    {
        $this->from('/login')->post('/login', ['email' => 'tina@test.local', 'password' => 'nope'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_every_main_page_renders_for_an_admin(): void
    {
        $this->addAssignment();
        $c = $this->classroom->id;
        $s = $this->classroom->students()->first()->id;

        foreach (['/', '/classrooms', "/classrooms/$c", "/classrooms/$c?tab=roster", "/classrooms/$c?tab=assignments",
                  "/classrooms/$c/gradebook", "/classrooms/$c/attendance", "/classrooms/$c/edit", '/classrooms/create',
                  '/students', "/students/$s", "/students/$s/report-card", '/students/create', '/teachers',
                  "/teachers/{$this->teacher->id}", '/teachers/create', '/account'] as $url) {
            $this->actingAs($this->admin)->get($url)->assertOk();
        }
    }

    public function test_teachers_only_manage_their_own_classes(): void
    {
        $this->actingAs($this->teacherUser);

        $this->get("/classrooms/{$this->classroom->id}/gradebook")->assertOk();
        $this->get("/classrooms/{$this->otherClassroom->id}/gradebook")->assertForbidden();
        $this->get("/classrooms/{$this->otherClassroom->id}/attendance")->assertForbidden();

        $this->get('/classrooms')->assertOk()->assertSee('Algebra')->assertDontSee('Painting');
    }

    public function test_teachers_cannot_manage_people_or_create_classes(): void
    {
        $this->actingAs($this->teacherUser);

        $this->get('/students/create')->assertForbidden();
        $this->get('/teachers/create')->assertForbidden();
        $this->get('/classrooms/create')->assertForbidden();
        $this->delete("/classrooms/{$this->classroom->id}")->assertForbidden();
    }

    public function test_old_links_still_work(): void
    {
        $this->actingAs($this->admin)->get('/DisplayTeachers')->assertRedirect('/teachers');
        $this->actingAs($this->admin)->get('/InsertStudent')->assertRedirect('/students/create');
    }
}
