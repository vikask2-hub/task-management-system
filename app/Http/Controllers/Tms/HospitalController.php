<?php

namespace App\Http\Controllers\Tms;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTmsHospitalRequest;
use App\Models\TmsBusinessUnit;
use App\Models\TmsHospital;
use App\Models\TmsUser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class HospitalController extends Controller
{
    public function index(Request $request): View
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $businessUnits = $this->businessUnitsFor($actor);
        $hospitals = TmsHospital::with('businessUnit')
            ->whereIn('business_unit_id', $businessUnits->pluck('id'))
            ->when($request->filled('search'), fn ($query) => $query->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->when($request->filled('business_unit_id'), fn ($query) => $query->where('business_unit_id', $request->integer('business_unit_id')))
            ->orderBy('name')
            ->paginate(18)
            ->withQueryString();

        return view('tms.hospitals.index', compact('hospitals', 'businessUnits'));
    }

    public function store(StoreTmsHospitalRequest $request): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($this->businessUnitsFor($actor)->contains('id', $request->integer('business_unit_id')), 403);
        TmsHospital::create($request->validated() + ['created_by_id' => $actor->id]);

        return back()->with('success', 'Hospital added to the directory.');
    }

    public function update(StoreTmsHospitalRequest $request, TmsHospital $hospital): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        $businessUnits = $this->businessUnitsFor($actor);
        abort_unless($businessUnits->contains('id', $hospital->business_unit_id) && $businessUnits->contains('id', $request->integer('business_unit_id')), 403);
        $hospital->update($request->validated());

        return back()->with('success', 'Hospital updated.');
    }

    public function destroy(Request $request, TmsHospital $hospital): RedirectResponse
    {
        /** @var TmsUser $actor */
        $actor = $request->attributes->get('tmsUser');
        abort_unless($actor->isRole('GM') || ($actor->isRole('AM') && $actor->businessUnits->contains('id', $hospital->business_unit_id)), 403);
        $hospital->update(['is_active' => false]);

        return back()->with('success', 'Hospital deactivated.');
    }

    private function businessUnitsFor(TmsUser $actor): Collection
    {
        return $actor->isRole('GM') ? TmsBusinessUnit::where('is_active', true)->orderBy('name')->get() : $actor->businessUnits()->where('is_active', true)->orderBy('name')->get();
    }
}
