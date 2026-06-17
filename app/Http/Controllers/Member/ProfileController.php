<?php
namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\ShippingAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Support\ValidatesThaiAddress;

class ProfileController extends Controller
{
    use ValidatesThaiAddress;
    public function show()
    {
        $user = auth()->user()->load('addresses');
        $defaultAddress = $user->addresses->where('is_default', true)->first();
        return view('member.profile', compact('user', 'defaultAddress'));
    }

    public function update(Request $request)
    {
        // ป้องกัน Browser auto-fill ช่องรหัสผ่านใหม่แล้วทำให้ Edit profile บันทึกไม่ได้
        if ($request->filled('password') && ! $request->filled('password_confirmation')) {
            $request->merge(['password' => null]);
        }
        $user = auth()->user();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $user->id,
            'phone' => ['nullable','regex:/^[0-9]{9,10}$/'],
            'address' => 'nullable|string|max:1000',
            'password' => 'nullable|min:8|confirmed',
        ]);

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
        ]);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }
        $user->save();

        return back()->with('success', 'บันทึกข้อมูลบัญชีเรียบร้อยแล้ว');
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => ['required','regex:/^[0-9]{9,10}$/'],
            'address' => 'required|string|max:1000',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'subdistrict' => 'required|string|max:100',
            'postal_code' => ['required','regex:/^[0-9]{5}$/'],
            'is_default' => 'nullable|boolean',
        ]);

        $data = $this->validateThaiAddressOrFail($data, true);

        if ($request->boolean('is_default')) {
            ShippingAddress::where('user_id', auth()->id())->update(['is_default' => false]);
        }

        auth()->user()->addresses()->create([
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'province' => $data['province'],
            'district' => $data['district'],
            'subdistrict' => $data['subdistrict'] ?? null,
            'postal_code' => $data['postal_code'],
            'is_default' => $request->boolean('is_default') || auth()->user()->addresses()->count() === 0,
        ]);

        return back()->with('success', 'เพิ่มที่อยู่จัดส่งเรียบร้อยแล้ว');
    }

    public function updateAddress(Request $request, ShippingAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        $data = $request->validate([
            'recipient_name' => 'required|string|max:255',
            'phone' => ['required','regex:/^[0-9]{9,10}$/'],
            'address' => 'required|string|max:1000',
            'province' => 'required|string|max:100',
            'district' => 'required|string|max:100',
            'subdistrict' => 'required|string|max:100',
            'postal_code' => ['required','regex:/^[0-9]{5}$/'],
            'is_default' => 'nullable|boolean',
        ]);

        $data = $this->validateThaiAddressOrFail($data, true);

        if ($request->boolean('is_default')) {
            ShippingAddress::where('user_id', auth()->id())->where('id', '!=', $address->id)->update(['is_default' => false]);
        }

        $address->update([
            'recipient_name' => $data['recipient_name'],
            'phone' => $data['phone'],
            'address' => $data['address'],
            'province' => $data['province'],
            'district' => $data['district'],
            'subdistrict' => $data['subdistrict'] ?? null,
            'postal_code' => $data['postal_code'],
            'is_default' => $request->boolean('is_default'),
        ]);

        return back()->with('success', 'บันทึกที่อยู่จัดส่งเรียบร้อยแล้ว');
    }

    public function destroyAddress(ShippingAddress $address)
    {
        abort_unless($address->user_id === auth()->id(), 403);
        $address->delete();
        return back()->with('success', 'ลบที่อยู่จัดส่งเรียบร้อยแล้ว');
    }
}
