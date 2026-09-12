<?php

namespace Tests\Feature;

use App\Models\TmsAuditLog;
use App\Models\TmsBusinessUnit;
use App\Models\TmsHospital;
use App\Models\TmsTask;
use App\Models\TmsTaskCategory;
use App\Models\TmsTaskHistory;
use App\Models\TmsUser;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TmsWorkflowTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_the_tms_login(): void
    {
        $this->get(route('tms.tasks.index'))->assertRedirect(route('tms.login'));
    }

    public function test_demo_login_starts_the_selected_role_session(): void
    {
        Config::set('tms.demo_mode', true);
        $unit = TmsBusinessUnit::factory()->create();
        $manager = TmsUser::factory()->generalManager()->for($unit, 'defaultBusinessUnit')->create(['email' => 'gm@tms.demo']);

        $this->post(route('tms.demo', 'GM'))
            ->assertRedirect(route('tms.tasks.index'))
            ->assertSessionHas('tms_user_id', $manager->id);
    }

    public function test_manager_navigation_contains_only_tasks_verification_reports_and_logout(): void
    {
        [, $manager] = $this->createTeam();

        $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.tasks.index'))
            ->assertOk()
            ->assertSee('>Tasks<', false)
            ->assertSee('>Verification<', false)
            ->assertSee('>Report<', false)
            ->assertSee('>Logout<', false)
            ->assertSee('Add BDE')
            ->assertDontSee('>Hospitals<', false)
            ->assertDontSee('>Recurring<', false)
            ->assertDontSee('>Team<', false)
            ->assertDontSee('>Master data<', false)
            ->assertDontSee('>Audit log<', false)
            ->assertDontSee('>Notifications<', false);
    }

    public function test_bde_navigation_contains_only_tasks_and_logout(): void
    {
        [, , $executive] = $this->createTeam();

        $this->withSession(['tms_user_id' => $executive->id])
            ->get(route('tms.tasks.index'))
            ->assertOk()
            ->assertSee('>Tasks<', false)
            ->assertSee('>Logout<', false)
            ->assertDontSee('>Verification<', false)
            ->assertDontSee('>Report<', false)
            ->assertDontSee('Add BDE');
    }

    public function test_general_manager_has_the_same_simple_workspace_as_an_assistant_manager(): void
    {
        $unit = TmsBusinessUnit::factory()->create();
        $generalManager = TmsUser::factory()->generalManager()->for($unit, 'defaultBusinessUnit')->create();

        $this->withSession(['tms_user_id' => $generalManager->id])
            ->get(route('tms.tasks.index'))
            ->assertOk()
            ->assertSee('>Tasks<', false)
            ->assertSee('>Verification<', false)
            ->assertSee('>Report<', false)
            ->assertSee('>Logout<', false)
            ->assertDontSee('Add BDE');
    }

    public function test_assistant_manager_can_create_a_bde_in_an_assigned_business_unit(): void
    {
        [$unit, $manager] = $this->createTeam();

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.bdes.store'), $this->bdePayload($unit))
            ->assertRedirect(route('tms.tasks.index'))
            ->assertSessionHas('success', 'Priya Nair can now sign in as a BDE.');

        $bde = TmsUser::where('email', 'priya.nair@example.test')->firstOrFail();

        $this->assertSame('Priya Nair', $bde->name);
        $this->assertSame('BDE', $bde->role);
        $this->assertTrue($bde->is_active);
        $this->assertSame($manager->id, $bde->direct_manager_id);
        $this->assertSame($unit->id, $bde->default_business_unit_id);
        $this->assertStringStartsWith('BDE-', $bde->employee_code);
        $this->assertTrue(Hash::check('SecurePass123!', $bde->password));
        $this->assertDatabaseHas('tms_business_unit_user', [
            'tms_user_id' => $bde->id,
            'tms_business_unit_id' => $unit->id,
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('tms_audit_logs', [
            'actor_user_id' => $manager->id,
            'entity_type' => 'user',
            'entity_id' => $bde->id,
            'action' => 'bde.created',
        ]);
    }

    public function test_newly_created_bde_can_sign_in_to_the_tasks_only_workspace(): void
    {
        [$unit, $manager] = $this->createTeam();

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.bdes.store'), $this->bdePayload($unit));

        $bde = TmsUser::where('email', 'priya.nair@example.test')->firstOrFail();

        $this->post(route('tms.login.store'), [
            'email' => 'PRIYA.NAIR@EXAMPLE.TEST',
            'password' => 'SecurePass123!',
        ])
            ->assertRedirect(route('tms.tasks.index'))
            ->assertSessionHas('tms_user_id', $bde->id);

        $this->get(route('tms.tasks.index'))
            ->assertOk()
            ->assertSee('My tasks')
            ->assertDontSee('>Verification<', false)
            ->assertDontSee('>Report<', false)
            ->assertDontSee('Add BDE');
    }

    public function test_assistant_manager_cannot_create_a_bde_in_an_unassigned_business_unit(): void
    {
        [, $manager] = $this->createTeam();
        $unassignedUnit = TmsBusinessUnit::factory()->create();

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.bdes.store'), $this->bdePayload($unassignedUnit))
            ->assertSessionHasErrors([
                'business_unit_id' => 'Choose one of your assigned business units.',
            ]);

        $this->assertDatabaseMissing('tms_users', ['email' => 'priya.nair@example.test']);
    }

    public function test_general_manager_and_bde_cannot_create_bdes(): void
    {
        [$unit, , $executive] = $this->createTeam();
        $generalManager = TmsUser::factory()->generalManager()->create([
            'default_business_unit_id' => $unit->id,
        ]);

        $this->withSession(['tms_user_id' => $generalManager->id])
            ->post(route('tms.bdes.store'), $this->bdePayload($unit))
            ->assertForbidden();

        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.bdes.store'), $this->bdePayload($unit))
            ->assertForbidden();

        $this->assertDatabaseMissing('tms_users', ['email' => 'priya.nair@example.test']);
    }

    public function test_bde_creation_requires_a_unique_email_and_confirmed_password(): void
    {
        [$unit, $manager] = $this->createTeam();
        TmsUser::factory()->create(['email' => 'priya.nair@example.test']);

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.bdes.store'), array_replace($this->bdePayload($unit), [
                'email' => ' PRIYA.NAIR@EXAMPLE.TEST ',
                'password_confirmation' => 'DifferentPass123!',
            ]))
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_verification_lists_only_submitted_tasks(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $submitted = $this->createTask($unit, $manager, $executive);
        $submitted->update(['status' => 'SUBMITTED']);
        $todo = $this->createTask($unit, $manager, $executive);

        $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.verification'))
            ->assertOk()
            ->assertSee($submitted->title)
            ->assertDontSee($todo->title)
            ->assertSee('Ready for review');
    }

    public function test_report_and_date_scoped_csv_export_work_for_managers(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $included = $this->createTask($unit, $manager, $executive);
        $included->update(['planned_at' => '2026-09-11 10:00:00']);
        $excluded = $this->createTask($unit, $manager, $executive);
        $excluded->update(['planned_at' => '2026-08-01 10:00:00']);

        $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.reports'))
            ->assertOk()
            ->assertSee('Status distribution')
            ->assertSee('Export CSV');

        $response = $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.reports.export', ['from' => '2026-09-01', 'to' => '2026-09-30']))
            ->assertOk()
            ->assertDownload();

        $this->assertStringContainsString($included->title, $response->streamedContent());
        $this->assertStringNotContainsString($excluded->title, $response->streamedContent());
    }

    public function test_report_rejects_an_inverted_date_range(): void
    {
        [, $manager] = $this->createTeam();

        $this->withSession(['tms_user_id' => $manager->id])
            ->from(route('tms.reports'))
            ->get(route('tms.reports', ['from' => '2026-09-30', 'to' => '2026-09-01']))
            ->assertRedirect(route('tms.reports'))
            ->assertSessionHasErrors('to');
    }

    public function test_csv_export_neutralizes_spreadsheet_formulas(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);
        $task->update(['title' => '=HYPERLINK("https://example.test")']);

        $response = $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.reports.export'))
            ->assertDownload();

        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
    }

    public function test_bde_cannot_access_verification_or_reports(): void
    {
        [, , $executive] = $this->createTeam();

        $this->withSession(['tms_user_id' => $executive->id])->get(route('tms.verification'))->assertForbidden();
        $this->withSession(['tms_user_id' => $executive->id])->get(route('tms.reports'))->assertForbidden();
    }

    public function test_removed_modules_are_not_routable(): void
    {
        [, $manager] = $this->createTeam();

        foreach (['dashboard', 'hospitals', 'recurring', 'team', 'master-data', 'audit', 'notifications'] as $module) {
            $this->withSession(['tms_user_id' => $manager->id])->get('/tms/'.$module)->assertNotFound();
        }
    }

    public function test_logout_ends_the_tms_session_for_every_role(): void
    {
        [, , $executive] = $this->createTeam();

        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.logout'))
            ->assertRedirect(route('tms.login'))
            ->assertSessionMissing('tms_user_id');
    }

    public function test_assistant_manager_creates_a_task_for_a_direct_report(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $category = TmsTaskCategory::factory()->create();

        $response = $this->withSession(['tms_user_id' => $manager->id])->post(route('tms.tasks.store'), [
            'title' => 'Meet hospital procurement lead',
            'category_id' => $category->id,
            'priority' => 'HIGH',
            'business_unit_id' => $unit->id,
            'assignee_id' => $executive->id,
            'planned_at' => '2026-09-12 10:00:00',
            'due_at' => '2026-09-12 17:00:00',
            'verification_required' => true,
        ]);

        $response->assertRedirect(route('tms.tasks.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('tms_tasks', ['title' => 'Meet hospital procurement lead', 'assignee_id' => $executive->id, 'status' => 'TODO']);
        $task = TmsTask::where('title', 'Meet hospital procurement lead')->firstOrFail();
        $this->assertDatabaseHas('tms_task_histories', ['task_id' => $task->id, 'to_status' => 'TODO']);
        $this->assertDatabaseHas('tms_audit_logs', ['actor_user_id' => $manager->id, 'entity_id' => $task->id, 'action' => 'task.created']);
    }

    public function test_task_moves_from_start_to_submission_and_manager_verification(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);

        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.tasks.transition', [$task, 'START']))
            ->assertRedirect();
        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.tasks.transition', [$task, 'SUBMIT']), ['outcome' => 'Decision-maker agreed to a product review.'])
            ->assertRedirect();
        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.transition', [$task, 'VERIFY']))
            ->assertRedirect();

        $this->assertDatabaseHas('tms_tasks', ['id' => $task->id, 'status' => 'COMPLETED', 'outcome' => 'Decision-maker agreed to a product review.']);
        $this->assertSame(3, TmsTaskHistory::where('task_id', $task->id)->count());
        $this->assertSame(3, TmsAuditLog::where('entity_id', $task->id)->count());
    }

    public function test_task_without_verification_completes_directly_with_accurate_copy(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);
        $task->update(['status' => 'IN_PROGRESS', 'verification_required' => false]);

        $this->withSession(['tms_user_id' => $executive->id])
            ->get(route('tms.tasks.show', $task))
            ->assertOk()
            ->assertSee('Complete task')
            ->assertSee('marking this task complete')
            ->assertDontSee('sending this task to your manager');

        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.tasks.transition', [$task, 'SUBMIT']), ['outcome' => 'Completed during the visit.'])
            ->assertRedirect();

        $this->assertDatabaseHas('tms_tasks', ['id' => $task->id, 'status' => 'COMPLETED']);
    }

    public function test_manager_can_cancel_a_submitted_task(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);
        $task->update(['status' => 'SUBMITTED', 'outcome' => 'Submitted result']);

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.transition', [$task, 'CANCEL']))
            ->assertRedirect();

        $this->assertDatabaseHas('tms_tasks', ['id' => $task->id, 'status' => 'CANCELLED']);
    }

    public function test_rework_requires_manager_instructions(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);
        $task->update(['status' => 'SUBMITTED', 'outcome' => 'Submitted result']);

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.transition', [$task, 'REWORK']))
            ->assertSessionHasErrors('reason');

        $this->assertDatabaseHas('tms_tasks', ['id' => $task->id, 'status' => 'SUBMITTED']);
    }

    public function test_bde_cannot_create_tasks(): void
    {
        [, , $executive] = $this->createTeam();

        $this->withSession(['tms_user_id' => $executive->id])
            ->get(route('tms.tasks.create'))
            ->assertForbidden();
    }

    public function test_assistant_manager_can_render_the_task_creation_form(): void
    {
        [, $manager, $executive] = $this->createTeam();
        TmsTaskCategory::factory()->create(['name' => 'Hospital Visit']);

        $this->withSession(['tms_user_id' => $manager->id])
            ->get(route('tms.tasks.create'))
            ->assertOk()
            ->assertSee('Create a task')
            ->assertSee($executive->name)
            ->assertSee('Hospital Visit')
            ->assertSee('Require manager verification')
            ->assertDontSee('Bulk assignees')
            ->assertDontSee('Evidence')
            ->assertDontSee('Check-in');
    }

    public function test_manager_can_update_a_todo_task(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $category = TmsTaskCategory::factory()->create();
        $task = $this->createTask($unit, $manager, $executive);

        $this->withSession(['tms_user_id' => $manager->id])
            ->patch(route('tms.tasks.update', $task), [
                'title' => 'Updated hospital follow-up',
                'category_id' => $category->id,
                'priority' => 'HIGH',
                'business_unit_id' => $unit->id,
                'assignee_id' => $executive->id,
                'planned_at' => '2026-09-12 10:00:00',
                'due_at' => '2026-09-12 17:00:00',
                'verification_required' => true,
            ])
            ->assertRedirect(route('tms.tasks.show', $task));

        $this->assertDatabaseHas('tms_tasks', ['id' => $task->id, 'title' => 'Updated hospital follow-up', 'priority' => 'HIGH']);
    }

    public function test_task_comments_are_saved_and_escaped(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $task = $this->createTask($unit, $manager, $executive);
        $comment = '<script>alert("cto")</script> Review completed.';

        $this->withSession(['tms_user_id' => $executive->id])
            ->post(route('tms.tasks.comments.store', $task), ['body' => $comment])
            ->assertRedirect();

        $this->assertDatabaseHas('tms_task_comments', ['task_id' => $task->id, 'author_id' => $executive->id, 'body' => $comment]);
        $this->withSession(['tms_user_id' => $executive->id])
            ->get(route('tms.tasks.show', $task))
            ->assertSee($comment)
            ->assertDontSee($comment, false);
    }

    public function test_task_dates_must_be_in_chronological_order(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $category = TmsTaskCategory::factory()->create();

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.store'), [
                'title' => 'Invalid schedule',
                'category_id' => $category->id,
                'priority' => 'MEDIUM',
                'business_unit_id' => $unit->id,
                'assignee_id' => $executive->id,
                'planned_at' => '2026-09-12 17:00:00',
                'due_at' => '2026-09-12 10:00:00',
            ])
            ->assertSessionHasErrors('due_at');

        $this->assertDatabaseMissing('tms_tasks', ['title' => 'Invalid schedule']);
    }

    public function test_bde_receives_404_for_another_executives_task(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $otherExecutive = TmsUser::factory()->reportingTo($manager)->create();
        $task = $this->createTask($unit, $manager, $otherExecutive);

        $this->withSession(['tms_user_id' => $executive->id])
            ->get(route('tms.tasks.show', $task))
            ->assertNotFound();
    }

    public function test_manager_cannot_assign_task_to_report_in_another_business_unit(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $otherUnit = TmsBusinessUnit::factory()->create();
        $category = TmsTaskCategory::factory()->create();

        $response = $this->withSession(['tms_user_id' => $manager->id])->post(route('tms.tasks.store'), [
            'title' => 'Cross-unit task',
            'category_id' => $category->id,
            'priority' => 'MEDIUM',
            'business_unit_id' => $otherUnit->id,
            'assignee_id' => $executive->id,
            'planned_at' => '2026-09-12 10:00:00',
            'due_at' => '2026-09-12 17:00:00',
        ]);

        $response->assertSessionHasErrors(['business_unit_id']);
        $this->assertDatabaseMissing('tms_tasks', ['title' => 'Cross-unit task']);
        $this->assertSame($unit->id, $executive->default_business_unit_id);
    }

    public function test_task_uses_a_hospital_snapshot_and_rejects_cross_unit_hospitals(): void
    {
        [$unit, $manager, $executive] = $this->createTeam();
        $category = TmsTaskCategory::factory()->create();
        $hospital = TmsHospital::factory()->create([
            'name' => 'City Heart Hospital',
            'address' => '12 Health Avenue',
            'business_unit_id' => $unit->id,
            'created_by_id' => $manager->id,
        ]);
        $payload = [
            'title' => 'Meet hospital owner',
            'category_id' => $category->id,
            'priority' => 'HIGH',
            'business_unit_id' => $unit->id,
            'hospital_id' => $hospital->id,
            'assignee_id' => $executive->id,
            'planned_at' => '2026-09-12 10:00:00',
            'due_at' => '2026-09-12 17:00:00',
        ];

        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.store'), $payload)
            ->assertRedirect(route('tms.tasks.index'));

        $this->assertDatabaseHas('tms_tasks', ['hospital_id' => $hospital->id, 'hospital_name_snapshot' => 'City Heart Hospital', 'address_snapshot' => '12 Health Avenue']);

        $otherUnit = TmsBusinessUnit::factory()->create();
        $otherHospital = TmsHospital::factory()->create(['business_unit_id' => $otherUnit->id, 'created_by_id' => $manager->id]);
        $this->withSession(['tms_user_id' => $manager->id])
            ->post(route('tms.tasks.store'), array_replace($payload, ['title' => 'Wrong hospital', 'hospital_id' => $otherHospital->id]))
            ->assertSessionHasErrors(['hospital_id']);
        $this->assertDatabaseMissing('tms_tasks', ['title' => 'Wrong hospital']);
    }

    /**
     * @return array{TmsBusinessUnit, TmsUser, TmsUser}
     */
    private function createTeam(): array
    {
        $unit = TmsBusinessUnit::factory()->create();
        $manager = TmsUser::factory()->assistantManager()->create(['default_business_unit_id' => $unit->id]);
        $manager->businessUnits()->attach($unit, ['is_primary' => true]);
        $executive = TmsUser::factory()->reportingTo($manager)->create();
        $executive->businessUnits()->attach($unit, ['is_primary' => true]);

        return [$unit, $manager, $executive];
    }

    private function createTask(TmsBusinessUnit $unit, TmsUser $manager, TmsUser $executive): TmsTask
    {
        return TmsTask::factory()->create([
            'business_unit_id' => $unit->id,
            'assignee_id' => $executive->id,
            'created_by_id' => $manager->id,
        ]);
    }

    /**
     * @return array<string, bool|int|string>
     */
    private function bdePayload(TmsBusinessUnit $unit): array
    {
        return [
            'name' => ' Priya Nair ',
            'email' => 'Priya.Nair@Example.Test',
            'phone' => ' +91 98765 43210 ',
            'business_unit_id' => $unit->id,
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
            'role' => 'GM',
            'direct_manager_id' => 999999,
            'is_active' => false,
        ];
    }
}
