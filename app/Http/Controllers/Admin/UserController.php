<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));
        $role = $request->get('role');
        $status = $request->get('status');

        $users = User::query()
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('name', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('id', $q);
                });
            })
            ->when($role, fn ($query) => $query->where('role', $role))
            ->when($status !== null && $status !== '', function ($query) use ($status) {
                if ($status === 'active') {
                    $query->where('is_active', true);
                } elseif ($status === 'suspended') {
                    $query->where('is_active', false);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'total' => User::count(),
            'active' => User::where('is_active', true)->count(),
            'suspended' => User::where('is_active', false)->count(),
            'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'members' => User::where('role', 'member')->count(),
        ];

        return view('admin.users.index', compact('users', 'summary', 'q', 'role', 'status'));
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => ['nullable','regex:/^[0-9]{9,10}$/'],
            'address' => 'nullable|string|max:1000',
            'role' => 'required|in:member,staff,admin,super_admin',
            'is_active' => 'nullable|boolean',
            'password' => 'required|string|min:8',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['is_active'] = $request->boolean('is_active');
        User::create($data);
        return redirect()->route('admin.users.index')->with('success', 'เพิ่มผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function show(User $user)
    {
        $user->load(['orders.items.product', 'addresses']);
        return view('admin.users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('admin.users.form', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => ['nullable','regex:/^[0-9]{9,10}$/'],
            'address' => 'nullable|string|max:1000',
            'role' => 'required|in:member,staff,admin,super_admin',
            'is_active' => 'nullable|boolean',
            'password' => 'nullable|string|min:8',
        ]);
        $data['is_active'] = $request->boolean('is_active');
        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return redirect()->route('admin.users.show', $user)->with('success', 'อัปเดตข้อมูลผู้ใช้งานเรียบร้อยแล้ว');
    }

    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('success', 'ไม่สามารถระงับบัญชีของตนเองได้');
        }
        $user->update(['is_active' => !$user->is_active]);
        return back()->with('success', $user->is_active ? 'เปิดใช้งานบัญชีแล้ว' : 'ระงับบัญชีแล้ว');
    }

    public function resetPassword(User $user)
    {
        $user->update(['password' => Hash::make('password')]);
        return back()->with('success', 'รีเซ็ตรหัสผ่านเป็น password แล้ว');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('success', 'ไม่สามารถลบบัญชีของตนเองได้');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'ลบผู้ใช้งานเรียบร้อยแล้ว');
    }
}
