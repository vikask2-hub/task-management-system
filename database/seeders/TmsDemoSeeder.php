<?php

namespace Database\Seeders;

use App\Models\TmsAuditLog;
use App\Models\TmsBusinessUnit;
use App\Models\TmsHospital;
use App\Models\TmsNotification;
use App\Models\TmsRecurringTemplate;
use App\Models\TmsTask;
use App\Models\TmsTaskCategory;
use App\Models\TmsTaskComment;
use App\Models\TmsTaskHistory;
use App\Models\TmsUser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TmsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $password = config('tms.demo_password') ?: Str::password(24);
        $passwordHash = Hash::make($password);

        $businessUnitNames = ['Delhi NCR', 'Mumbai', 'Bengaluru', 'Chennai', 'Hyderabad', 'Kolkata', 'Pune', 'Ahmedabad', 'Jaipur', 'Surat', 'Lucknow', 'Chandigarh', 'Kochi', 'Indore', 'Bhubaneswar'];
        $businessUnits = collect($businessUnitNames)->map(function (string $name, int $index): TmsBusinessUnit {
            return TmsBusinessUnit::updateOrCreate(
                ['code' => 'BU-'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT)],
                ['name' => $name, 'city' => $name, 'region' => $index < 5 ? 'North & West' : ($index < 10 ? 'South & Central' : 'East & Emerging'), 'is_active' => true],
            );
        });

        $categoryNames = ['Hospital Visit', 'Hospital Owner / Decision-Maker Meeting', 'AP Activity', 'AR Activity', 'Follow-up', 'Lead Generation', 'Collection Support', 'Relationship Management', 'Documentation', 'Internal Coordination', 'Other'];
        $icons = ['building-2', 'handshake', 'receipt-indian-rupee', 'landmark', 'refresh-cw', 'user-round-plus', 'badge-indian-rupee', 'heart-handshake', 'files', 'users-round', 'shapes'];
        $categories = collect($categoryNames)->map(function (string $name, int $index) use ($icons): TmsTaskCategory {
            return TmsTaskCategory::updateOrCreate(
                ['code' => Str::upper(Str::slug($name, '_'))],
                ['name' => $name, 'description' => 'Standard '.$name.' execution activity.', 'icon_key' => $icons[$index], 'is_active' => true, 'sort_order' => $index + 1],
            );
        });

        $generalManagers = collect([
            ['name' => 'Ananya Iyer', 'email' => 'gm@tms.demo'],
            ['name' => 'Vikram Malhotra', 'email' => 'gm.west@tms.demo'],
        ])->map(function (array $data, int $index) use ($businessUnits, $passwordHash): TmsUser {
            $user = TmsUser::updateOrCreate(['email' => $data['email']], $data + ['employee_code' => 'GM-'.str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT), 'role' => 'GM', 'is_active' => true, 'default_business_unit_id' => $businessUnits->first()->id, 'avatar_color' => '#2563eb', 'password' => $passwordHash]);
            $user->businessUnits()->sync($businessUnits->mapWithKeys(fn (TmsBusinessUnit $unit, int $position): array => [$unit->id => ['is_primary' => $position === 0]])->all());

            return $user;
        });

        $assistantManagers = collect(range(1, 10))->map(function (int $number) use ($businessUnits, $passwordHash): TmsUser {
            $unit = $businessUnits[($number - 1) % $businessUnits->count()];
            $user = TmsUser::updateOrCreate(['email' => $number === 1 ? 'am@tms.demo' : "am{$number}@tms.demo"], ['name' => $number === 1 ? 'Rohan Mehta' : $this->personName($number), 'employee_code' => 'AM-'.str_pad((string) $number, 3, '0', STR_PAD_LEFT), 'role' => 'AM', 'is_active' => true, 'default_business_unit_id' => $unit->id, 'avatar_color' => '#7c3aed', 'password' => $passwordHash]);
            $user->businessUnits()->sync([$unit->id => ['is_primary' => true]]);

            return $user;
        });

        $executives = collect(range(1, 50))->map(function (int $number) use ($assistantManagers, $businessUnits, $passwordHash): TmsUser {
            $manager = $assistantManagers[($number - 1) % $assistantManagers->count()];
            $unit = $businessUnits->firstWhere('id', $manager->default_business_unit_id);
            $user = TmsUser::updateOrCreate(['email' => $number === 1 ? 'bde@tms.demo' : "bde{$number}@tms.demo"], ['name' => $number === 1 ? 'Kavya Singh' : $this->personName($number + 20), 'employee_code' => 'BDE-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT), 'phone' => '+91 9'.str_pad((string) (100000000 + $number), 9, '0', STR_PAD_LEFT), 'role' => 'BDE', 'is_active' => true, 'direct_manager_id' => $manager->id, 'default_business_unit_id' => $unit->id, 'avatar_color' => '#0891b2', 'password' => $passwordHash]);
            $user->businessUnits()->sync([$unit->id => ['is_primary' => true]]);

            return $user;
        });

        if (TmsHospital::count() < 100) {
            foreach (range(1, 100) as $number) {
                $unit = $businessUnits[($number - 1) % $businessUnits->count()];
                TmsHospital::create(['name' => $unit->city.' Medical Centre '.str_pad((string) $number, 3, '0', STR_PAD_LEFT), 'business_unit_id' => $unit->id, 'city' => $unit->city, 'address' => $number.', Healthcare Avenue, '.$unit->city, 'pincode' => (string) (100000 + (($number * 701) % 800000)), 'account_type' => $number % 3 === 0 ? 'Multi-speciality' : 'Hospital', 'primary_contact_name' => $this->personName($number + 80), 'primary_contact_designation' => $number % 2 === 0 ? 'Medical Director' : 'Procurement Head', 'phone' => '+91 8'.str_pad((string) (200000000 + $number), 9, '0', STR_PAD_LEFT), 'email' => 'hospital'.str_pad((string) $number, 3, '0', STR_PAD_LEFT).'@example.test', 'is_active' => true, 'created_by_id' => $generalManagers->first()->id]);
            }
        }

        $hospitals = TmsHospital::orderBy('id')->get();
        if (TmsTask::count() === 0) {
            $statuses = ['TODO', 'IN_PROGRESS', 'SUBMITTED', 'COMPLETED', 'BLOCKED', 'COMPLETED', 'COMPLETED'];
            $priorities = ['LOW', 'MEDIUM', 'MEDIUM', 'HIGH', 'URGENT'];
            foreach (range(1, 520) as $number) {
                $assignee = $executives[($number - 1) % $executives->count()];
                $unit = $businessUnits->firstWhere('id', $assignee->default_business_unit_id);
                $category = $categories[($number - 1) % $categories->count()];
                $hospital = $hospitals[($number - 1) % $hospitals->count()];
                $plannedAt = $number <= 50
                    ? now()->startOfDay()->setTime(9 + ($number % 5), 0)
                    : now()->subDays(20)->addDays($number % 35)->setTime(9 + ($number % 5), 0);
                $dueAt = $plannedAt->copy()->setTime(17, 30);
                $status = $statuses[$number % count($statuses)];
                $task = TmsTask::create(['task_number' => 'TMS-'.$plannedAt->format('Ymd').'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT), 'title' => $category->name.' — '.$hospital->name, 'description' => 'Complete the planned activity, capture the outcome and record the next action.', 'category_id' => $category->id, 'priority' => $priorities[$number % count($priorities)], 'status' => $status, 'business_unit_id' => $unit->id, 'hospital_id' => $hospital->id, 'hospital_name_snapshot' => $hospital->name, 'address_snapshot' => $hospital->address, 'assignee_id' => $assignee->id, 'assignee_role_snapshot' => 'BDE', 'created_by_id' => $assignee->direct_manager_id, 'planned_at' => $plannedAt, 'due_at' => $dueAt, 'started_at' => $status !== 'TODO' ? $plannedAt->copy()->addMinutes(20) : null, 'submitted_at' => in_array($status, ['SUBMITTED', 'COMPLETED'], true) ? $dueAt->copy()->subHour() : null, 'completed_at' => $status === 'COMPLETED' ? $dueAt->copy()->subMinutes(30) : null, 'verification_required' => $number % 4 !== 0, 'expected_outcome' => 'Document decision-maker response and agree a next step.', 'outcome' => in_array($status, ['SUBMITTED', 'COMPLETED'], true) ? 'Meeting completed and next follow-up agreed.' : null, 'completion_notes' => in_array($status, ['SUBMITTED', 'COMPLETED'], true) ? 'Shared the product brief and captured requirements.' : null, 'blocker_reason' => $status === 'BLOCKED' ? 'Decision-maker unavailable; manager support requested.' : null, 'source' => $number % 11 === 0 ? 'RECURRING' : 'MANUAL']);
                TmsTaskHistory::create(['task_id' => $task->id, 'from_status' => null, 'to_status' => 'TODO', 'changed_by_id' => $task->created_by_id, 'reason' => 'Task created']);
                if ($status !== 'TODO') {
                    TmsTaskHistory::create(['task_id' => $task->id, 'from_status' => 'TODO', 'to_status' => $status, 'changed_by_id' => $assignee->id, 'reason' => 'Demo workflow activity']);
                }
                if ($number <= 40) {
                    TmsTaskComment::create(['task_id' => $task->id, 'author_id' => $assignee->id, 'body' => 'Field update captured during the hospital visit.']);
                }
            }
        }

        foreach ($executives->take(12) as $executive) {
            TmsNotification::firstOrCreate(['user_id' => $executive->id, 'type' => 'NEW_ASSIGNMENT', 'title' => 'Today’s plan is ready'], ['body' => 'Review your priority tasks and start the first visit.', 'entity_type' => 'task', 'entity_id' => TmsTask::where('assignee_id', $executive->id)->value('id')]);
        }

        TmsRecurringTemplate::firstOrCreate(['name' => 'Daily hospital relationship round'], ['title' => 'Complete priority hospital visit', 'description' => 'Meet the assigned hospital stakeholder and capture next action.', 'category_id' => $categories->first()->id, 'priority' => 'HIGH', 'business_unit_id' => $businessUnits->first()->id, 'created_by_id' => $assistantManagers->first()->id, 'assignee_id' => $executives->first()->id, 'verification_required' => true, 'schedule_type' => 'WEEKDAYS', 'start_date' => now()->startOfMonth(), 'preferred_due_time' => '17:30:00', 'is_active' => true]);
        TmsAuditLog::firstOrCreate(['action' => 'demo.seeded', 'entity_type' => 'system'], ['actor_user_id' => $generalManagers->first()->id, 'metadata' => ['users' => 62, 'tasks' => TmsTask::count()]]);
    }

    private function personName(int $number): string
    {
        $firstNames = ['Aarav', 'Aditi', 'Arjun', 'Diya', 'Ishaan', 'Kiran', 'Meera', 'Neel', 'Priya', 'Rahul', 'Riya', 'Sahil', 'Sneha', 'Varun', 'Zoya'];
        $lastNames = ['Sharma', 'Patel', 'Reddy', 'Gupta', 'Nair', 'Kapoor', 'Joshi', 'Mehta', 'Iyer', 'Singh', 'Das'];

        return $firstNames[$number % count($firstNames)].' '.$lastNames[intdiv($number, count($firstNames)) % count($lastNames)];
    }
}
