<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTmsBdeRequest;
use App\Models\TmsAuditLog;
use App\Models\TmsUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class BdeController extends Controller
{
    public function store(StoreTmsBdeRequest $request): RedirectResponse
    {
        /** @var TmsUser $manager */
        $manager = $request->attributes->get('tmsUser');
        $data = $request->validated();
        $businessUnitId = (int) $data['business_unit_id'];

        $bde = DB::transaction(function () use ($data, $businessUnitId, $manager): TmsUser {
            $bde = TmsUser::create([
                'name' => $data['name'],
                'email' => str($data['email'])->lower()->toString(),
                'employee_code' => $this->generateEmployeeCode(),
                'phone' => $data['phone'] ?? null,
                'role' => 'BDE',
                'is_active' => true,
                'direct_manager_id' => $manager->id,
                'default_business_unit_id' => $businessUnitId,
                'timezone' => $manager->timezone,
                'avatar_color' => '#0891b2',
                'password' => $data['password'],
            ]);
            $bde->businessUnits()->attach($businessUnitId, ['is_primary' => true]);

            TmsAuditLog::create([
                'actor_user_id' => $manager->id,
                'action' => 'bde.created',
                'entity_type' => 'user',
                'entity_id' => $bde->id,
                'business_unit_id' => $businessUnitId,
                'metadata' => ['email' => $bde->email],
            ]);

            return $bde;
        });

        return redirect()->route('tms.tasks.index')->with('success', "{$bde->name} can now sign in as a BDE.");
    }

    private function generateEmployeeCode(): string
    {
        do {
            $employeeCode = 'BDE-'.str()->upper(str()->random(8));
        } while (TmsUser::where('employee_code', $employeeCode)->exists());

        return $employeeCode;
    }
}
