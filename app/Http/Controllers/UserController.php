<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();
        
        // Search
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('username', 'like', '%' . $request->search . '%');
            });
        }
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }
        
        $users = $query->latest()->paginate(10)->withQueryString();
        
        // Stats
        $stats = [
            'total' => User::count(),
            'super_admin' => User::where('role', 'super_admin')->count(),
            'admin' => User::where('role', 'admin')->count(),
            'user' => User::where('role', 'user')->count(),
        ];
        
        return Inertia::render('Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'stats' => $stats,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|min:8|confirmed',
            'role' => 'required|in:user,admin,super_admin',
        ]);
        
        // ✅ Hanya super admin yang bisa buat super admin baru
        if ($validated['role'] === 'super_admin' && auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'Hanya Super Admin yang bisa membuat Super Admin baru');
        }
        
        User::create([
            'name' => $validated['name'],
            'username' => $validated['username'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
        ]);
        
        return back()->with('success', 'User berhasil ditambahkan');
    }

    public function update(Request $request, $id)  // ✅ UBAH: terima $id sebagai parameter
    {
        $user = User::findOrFail($id);  // ✅ UBAH: cari user manual
        
        // ✅ Super admin bisa edit semua termasuk diri sendiri
        // Admin biasa tidak bisa edit super admin
        if (auth()->user()->role !== 'super_admin' && $user->role === 'super_admin') {
            return back()->with('error', 'Anda tidak memiliki akses untuk mengedit Super Admin');
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|max:255|unique:users,username,' . $user->id,
            'password' => 'nullable|min:8|confirmed',
            'role' => 'required|in:user,admin,super_admin',
        ]);
        
        // ✅ Hanya super admin yang bisa ubah role ke super admin
        if ($validated['role'] === 'super_admin' && auth()->user()->role !== 'super_admin') {
            return back()->with('error', 'Hanya Super Admin yang bisa mengubah role menjadi Super Admin');
        }
        
        $user->name = $validated['name'];
        $user->username = $validated['username'];
        $user->role = $validated['role'];
        
        if ($request->filled('password')) {
            $user->password = bcrypt($validated['password']);
        }
        
        $user->save();
        
        return back()->with('success', 'User berhasil diupdate');
    }

    public function destroy($id)  // ✅ UBAH: terima $id sebagai parameter
    {
        $user = User::findOrFail($id);  // ✅ UBAH: cari user manual
        
        // ✅ Super admin bisa hapus semua termasuk diri sendiri
        // Admin biasa tidak bisa hapus super admin
        if (auth()->user()->role !== 'super_admin' && $user->role === 'super_admin') {
            return back()->with('error', 'Anda tidak memiliki akses untuk menghapus Super Admin');
        }
        
        // ✅ Warning jika super admin hapus diri sendiri
        if (auth()->id() === $user->id && $user->role === 'super_admin') {
            // Cek apakah masih ada super admin lain
            $otherSuperAdmins = User::where('role', 'super_admin')
                ->where('id', '!=', $user->id)
                ->count();
            
            if ($otherSuperAdmins === 0) {
                return back()->with('error', 'Tidak bisa menghapus Super Admin terakhir!');
            }
        }
        
        $user->delete();
        
        // ✅ Logout jika hapus diri sendiri
        if (auth()->id() === $user->id) {
            auth()->logout();
            return redirect()->route('login')->with('success', 'Akun Anda telah dihapus');
        }
        
        return back()->with('success', 'User berhasil dihapus');
    }
}