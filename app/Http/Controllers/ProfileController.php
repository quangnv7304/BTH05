<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Redirect;
use App\Models\User;
use App\Http\Requests\ProfileUpdateRequest;

class ProfileController extends Controller
{
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('profile.show', compact('user'));
    }

    public function edit()
{
    $user = Auth::user(); // Lấy thông tin người dùng đang đăng nhập
    $name = $user->name;
    return view('profile.edit', compact('name', 'user'));
}

    

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'bio' => 'nullable|string',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->bio = $request->bio;

        if ($request->hasFile('avatar')) {
            // Xóa ảnh cũ nếu có
            if ($user->avatar) {
                Storage::delete($user->avatar);
            }

            // Lưu ảnh mới
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }
        dd($user); // Kiểm tra giá trị của $user trước khi lưu
        $user->save();

        return redirect()->route('profile.edit')->with('success', 'Profile updated successfully!');
    }

    public function updateProfile(ProfileUpdateRequest $request): RedirectResponse
    {
        // Cập nhật các trường thông tin từ dữ liệu được xác thực
        $request->user()->fill($request->validated());

        // Nếu email thay đổi, đặt email_verified_at thành null
        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        // Xử lý cập nhật avatar
        if ($request->hasFile('avatar')) {
            // Xóa avatar cũ nếu tồn tại
            if ($request->user()->avatar) {
                Storage::delete($request->user()->avatar);
            }

            // Lưu avatar mới
            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $request->user()->avatar = $avatarPath;
        }

        // Lưu các thay đổi vào cơ sở dữ liệu
        $request->user()->save();

        // Chuyển hướng về trang chỉnh sửa hồ sơ với thông báo thành công
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }
}
