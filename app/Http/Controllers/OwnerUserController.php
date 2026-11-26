<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class OwnerUserController extends Controller
{
    public function index()
    {
        return view('owner.users.index', [
            'users' => User::orderBy('role')->orderBy('name')->paginate(20),
        ]);
    }

    public function create()
    {
        return view('owner.users.form', ['user' => new User(['role' => 'cashier', 'is_active' => true])]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', 'unique:users,username'],
            'role' => ['required', Rule::in(['owner', 'cashier'])],
            'password' => ['required', 'string', 'min:8'],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        $user = User::create($data);
        $this->audit($request->user(), 'user.created', $user, null);

        return redirect()->route('owner.users')->with('status', 'Akun berhasil dibuat.');
    }

    public function edit(User $user)
    {
        return view('owner.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('users', 'username')->ignore($user->id)],
            'role' => ['required', Rule::in(['owner', 'cashier'])],
            'password' => ['nullable', 'string', 'min:8'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withErrors(['is_active' => 'Akun owner yang sedang digunakan tidak dapat dinonaktifkan.'])->withInput();
        }
        if ($user->is($request->user()) && $data['role'] !== 'owner') {
            return back()->withErrors(['role' => 'Peran owner yang sedang digunakan tidak dapat diubah.'])->withInput();
        }

        $before = $user->only(['name', 'username', 'role', 'is_active']);
        $passwordChanged = filled($data['password']);
        if ($passwordChanged) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        DB::transaction(function () use ($request, $user, $data, $before, $passwordChanged) {
            $user->update($data);
            $this->audit($request->user(), 'user.updated', $user, $before);
            if ($passwordChanged || ! $user->is_active || $before['role'] !== $user->role) {
                $user->tokens()->delete();
            }
        });

        return redirect()->route('owner.users')->with('status', 'Perubahan akun berhasil disimpan.');
    }

    private function audit(User $actor, string $action, User $user, ?array $before): void
    {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'entity_type' => 'user',
            'entity_id' => $user->id,
            'before_data' => $before,
            'after_data' => $user->only(['name', 'username', 'role', 'is_active']),
        ]);
    }
}
