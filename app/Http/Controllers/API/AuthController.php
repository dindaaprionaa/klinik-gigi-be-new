<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Pasien;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    //  REGISTER
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|in:admin,dokter,pasien,kasir',
            'no_identitas' => 'required_if:role,pasien|string|unique:pasiens,no_identitas',
            'tanggal_lahir' => 'required_if:role,pasien|date',
            'alamat' => 'nullable|string',
            'jenis_kelamin' => 'nullable|in:Laki-laki,Perempuan',
            'no_telepon' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role ?? 'pasien',
        ]);

        // Jika role adalah pasien, buat data di tabel pasiens
        if ($user->role === 'pasien') {
            Pasien::create([
                'no_pendaftaran' => 'P' . str_pad($user->id, 4, '0', STR_PAD_LEFT),
                'nama' => $request->name,
                'no_identitas' => $request->no_identitas,
                'tanggal_lahir' => $request->tanggal_lahir,
                'alamat' => $request->alamat,
                'jenis_kelamin' => $request->jenis_kelamin,
                'no_telepon' => $request->no_telepon,
                'email' => $request->email,
            ]);
        }

        if ($request->hasSession()) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();
        }

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'User registered successfully',
            'user' => $user,
            'token' => $token,
        ], 201);
    }

    // LOGIN (FIX TANPA SESSION)
    public function login(Request $request)
{
    $validator = Validator::make($request->all(), [
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'errors' => $validator->errors()
        ], 422);
    }

    $user = User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'message' => 'Email atau password salah',
        ], 401);
    }

    // hapus token lama
    $user->tokens()->delete();

    // buat token baru
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'message' => 'Login berhasil',
        'user' => array_merge($user->toArray(), [
            'photo_url' => $this->photoUrlForUser($user->id),
        ]),
        'token' => $token,
    ]);
}

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logout successful',
        ], 200);
    }


    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => array_merge($user->toArray(), [
                'photo_url' => $this->photoUrlForUser($user->id),
            ]),
        ], 200);
    }


    public function updateProfile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,' . $request->user()->id,
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $user->update($request->only(['name', 'email']));

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => array_merge($user->fresh()->toArray(), [
                'photo_url' => $this->photoUrlForUser($user->id),
            ]),
        ], 200);
    }

    public function updatePhoto(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'photo' => 'required|image|mimes:jpg,jpeg,png,webp',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $path = $this->storePhotoForUser($request, $user->id);

        return response()->json([
            'message' => 'Foto profile updated successfully',
            'photo_url' => asset(str_replace(public_path() . DIRECTORY_SEPARATOR, '', $path)),
            'user' => array_merge($user->toArray(), [
                'photo_url' => $this->photoUrlForUser($user->id),
            ]),
        ], 200);
    }


    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'user' => array_merge($user->toArray(), [
                'photo_url' => $this->photoUrlForUser($user->id),
            ]),
        ], 200);
    }

    public function changePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'Current password does not match'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->password)
        ]);

        return response()->json([
            'message' => 'Password changed successfully'
        ], 200);
    }

    private function photoUrlForUser(int $userId): ?string
    {
        $dir = public_path('assets/profile');
        if (! File::isDirectory($dir)) {
            return null;
        }

        $matches = File::glob($dir . DIRECTORY_SEPARATOR . 'user-' . $userId . '.*');
        if (! $matches) {
            return null;
        }

        return asset('assets/profile/' . basename($matches[0]));
    }

    private function storePhotoForUser(Request $request, int $userId): string
    {
        $dir = public_path('assets/profile');
        if (! File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        foreach (File::glob($dir . DIRECTORY_SEPARATOR . 'user-' . $userId . '.*') ?: [] as $oldFile) {
            File::delete($oldFile);
        }

        $extension = $request->file('photo')->getClientOriginalExtension();
        $filename = 'user-' . $userId . '.' . strtolower($extension);

        $request->file('photo')->move($dir, $filename);

        return $dir . DIRECTORY_SEPARATOR . $filename;
    }
}