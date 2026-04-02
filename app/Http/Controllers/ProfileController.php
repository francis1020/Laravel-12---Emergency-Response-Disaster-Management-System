<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        $user->load(['responderDetail', 'userDetail']);
        
        return view('profile.edit', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        if ($request->filled('password')) {
            $validated['password'] = Hash::make($request->validate([
                'password' => ['required', Password::defaults(), 'confirmed'],
            ])['password']);
        }

        $user->update($validated);

        // Update responder details if user is a responder
        if ($user->isResponder()) {
            $responderValidated = $request->validate([
                'department' => 'nullable|string|max:255',
                'station_address' => 'nullable|string|max:255',
                'office_location_latitude' => 'nullable|numeric|between:-90,90',
                'office_location_longitude' => 'nullable|numeric|between:-180,180',
                'vehicle_type' => 'nullable|string|max:255',
                'license_number' => 'nullable|string|max:255',
                'status' => 'nullable|string|in:available,busy,offline,unavailable',
                'emergency_types' => 'nullable|array',
                'emergency_types.*' => 'exists:emergency_types,code',
                'auto_assign' => 'nullable|boolean',
            ]);

            $responderDetail = $user->responderDetail;
            if (!$responderDetail) {
                $responderDetail = \App\Models\ResponderDetail::create([
                    'user_id' => $user->id,
                ]);
            }

            // Handle auto_assign checkbox (if not present in request, set to false)
            $responderValidated['auto_assign'] = $request->has('auto_assign') ? (bool) $request->input('auto_assign') : false;

            $responderDetail->update($responderValidated);
        }

        // Update user details if user is a regular user
        if ($user->isUser()) {
            $userValidated = $request->validate([
                'contact_number' => 'nullable|string|max:11',
                'address' => 'nullable|string|max:255',
                'birthdate' => 'nullable|date',
                'gender' => 'nullable|string|in:male,female,other',
            ]);

            $userDetail = $user->userDetail;
            if (!$userDetail) {
                $userDetail = \App\Models\UserDetail::create([
                    'user_id' => $user->id,
                ]);
            }

            $userDetail->update($userValidated);
        }

        return redirect()->route('profile.edit')->with('status', 'profile-updated');
    }

    public function validatePassword(Request $request)
    {
        try {
            $request->validate([
                'password' => ['required', 'current_password'],
            ]);

            return response()->json([
                'valid' => true,
                'message' => 'Password is correct'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'valid' => false,
                'errors' => $e->errors(),
                'message' => 'The password you entered is incorrect.'
            ], 422);
        }
    }

    public function updateResponderSettings(Request $request)
    {
        $user = Auth::user();
        
        if (!$user->isResponder()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only responders can update these settings.'
            ], 403);
        }

        $validated = $request->validate([
            'status' => 'nullable|string|in:available,busy,offline,unavailable',
            'auto_assign' => 'nullable|boolean',
        ]);

        $responderDetail = $user->responderDetail;
        if (!$responderDetail) {
            $responderDetail = \App\Models\ResponderDetail::create([
                'user_id' => $user->id,
            ]);
        }

        if (isset($validated['status'])) {
            $responderDetail->status = $validated['status'];
        }
        
        if (isset($validated['auto_assign'])) {
            $responderDetail->auto_assign = (bool) $validated['auto_assign'];
        }

        $responderDetail->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Settings updated successfully',
            'responder_detail' => $responderDetail
        ]);
    }

    public function destroy(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}

