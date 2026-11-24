<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $staff = $request->user()->staff()->with('assignedTurfs:id,name')->latest()->get();
        $turfs = $request->user()->turfs()->orderBy('name')->get(['id', 'name', 'area']);

        return view('panel.staff', compact('staff', 'turfs'));
    }

    public function store(Request $request)
    {
        AuthController::normalizeContact($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            ...User::contactRules(),
            'password' => ['required', Password::min(6)],
        ] + $this->turfRules($request), $this->turfMessages() + User::contactMessages());

        $staff = User::create(collect($data)->except('turfs')->all() + ['role' => User::ROLE_STAFF, 'owner_id' => $request->user()->id]);
        $staff->assignedTurfs()->sync($data['turfs']);

        return back()->with('success', "Staff account created for {$staff->name}. They can log in with their phone and password and will only see the turfs you picked.");
    }

    /** Edit a staff member's details (name, mobile, email, optional new password) and turfs. */
    public function update(Request $request, User $user)
    {
        abort_unless($user->isStaff() && $user->owner_id === $request->user()->id, 403);
        AuthController::normalizeContact($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            ...User::contactRules($user->id),
            'password' => ['nullable', Password::min(6)],
        ] + $this->turfRules($request), $this->turfMessages() + User::contactMessages());

        $user->fill(collect($data)->only(['name', 'phone', 'email'])->all());
        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model cast
        }
        $user->save();
        $user->assignedTurfs()->sync($data['turfs']);

        return back()->with('success', "{$user->name}'s details were updated".(! empty($data['password']) ? ' — share the new password with them.' : '.'));
    }

    /** Change which turfs a staff member works at. */
    public function turfs(Request $request, User $user)
    {
        abort_unless($user->isStaff() && $user->owner_id === $request->user()->id, 403);
        $data = $request->validate($this->turfRules($request), $this->turfMessages());
        $user->assignedTurfs()->sync($data['turfs']);

        return back()->with('success', "{$user->name} now works at: ".$user->assignedTurfs()->pluck('name')->join(', ').'.');
    }

    /** Staff must work at one or more of this owner's own turfs. */
    private function turfRules(Request $request): array
    {
        return [
            'turfs' => ['required', 'array', 'min:1'],
            'turfs.*' => ['integer', \Illuminate\Validation\Rule::exists('turfs', 'id')->where('owner_id', $request->user()->id)],
        ];
    }

    private function turfMessages(): array
    {
        return ['turfs.required' => 'Pick at least one turf this staff member works at.', 'turfs.min' => 'Pick at least one turf this staff member works at.'];
    }

    public function toggle(Request $request, User $user)
    {
        abort_unless($user->isStaff() && $user->owner_id === $request->user()->id, 403);
        $user->update(['is_active' => ! $user->is_active]);

        return back()->with('success', $user->is_active ? 'Staff enabled.' : 'Staff disabled.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->isStaff() && $user->owner_id === $request->user()->id, 403);
        $user->delete();

        return back()->with('success', 'Staff removed.');
    }
}
