<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserAdminController extends Controller
{
    public function index(Request $request): Response
    {
        $query = User::withCount('videos');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
        }

        $users = $query->orderByDesc('created_at')->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users', [
            'users' => $users,
            'filters' => $request->only(['search']),
        ]);
    }

    public function updateQuota(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'quota_total' => ['required', 'integer', 'min:0', 'max:1000'],
            'role' => ['required', 'string', 'in:admin,user'],
        ]);

        $user->update($validated);

        return back()->with('success', "Kuota dan peran pengguna {$user->name} berhasil diperbarui.");
    }
}
