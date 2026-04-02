<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\EmergencyType;
use App\Models\EmergencyReport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->isAdmin()) {
                abort(403, 'Access denied. Admin privileges required.');
            }
            return $next($request);
        });
    }

    private function canCreateAdmin()
    {
        return auth()->user()->isSuperAdmin();
    }

    // User Management
    public function users(Request $request)
    {
        $query = User::query();

        // Exclude the current logged-in user from the list
        $query->where('id', '!=', auth()->id());

        // Hide admin and super admin users from non-super admins
        if (!auth()->user()->isSuperAdmin()) {
            $query->whereNotIn('type', [2, 3]); // Exclude Admin (2) and Super Admin (3)
        }

        if ($request->filled('type')) {
            $type = $request->type;
            // Prevent non-super admins from filtering by admin or super admin
            if (!auth()->user()->isSuperAdmin() && in_array($type, ['2', '3'])) {
                $type = null; // Ignore the filter
            }
            if ($type !== null) {
                $query->where('type', $type);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(10);

        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        try {
            // Determine allowed user types based on current user's role
            $allowedTypes = [0, 1]; // Regular user and Responder by default
            if ($this->canCreateAdmin()) {
                $allowedTypes[] = 2; // Admin (only super admin can create)
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'password' => 'required|string|min:8|confirmed',
                'type' => ['required', 'integer', Rule::in($allowedTypes)],
            ]);

            // Additional check: prevent non-super admins from creating admins
            if ($validated['type'] == 2 && !$this->canCreateAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to create admin users.',
                        'errors' => ['type' => ['You do not have permission to create admin users.']]
                    ], 403);
                }
                return back()->withErrors(['type' => 'You do not have permission to create admin users.'])->withInput();
            }

            $validated['password'] = Hash::make($validated['password']);
            $validated['email_verified_at'] = now();

            User::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User created successfully.'
                ]);
            }

            return redirect()->route('admin.users')->with('success', 'User created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    public function editUser($id)
    {
        $user = User::findOrFail($id);
        return view('admin.users.edit', compact('user'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // Prevent non-super admins from editing super admins
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to edit super admin users.'
                ], 403);
            }
            return redirect()->route('admin.users')->with('error', 'You do not have permission to edit super admin users.');
        }

        try {
            // Determine allowed user types based on current user's role
            $allowedTypes = [0, 1]; // Regular user and Responder by default
            if ($this->canCreateAdmin()) {
                $allowedTypes[] = 2; // Admin (only super admin can create)
                $allowedTypes[] = 3; // Super Admin (only super admin can create)
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
                'password' => 'nullable|string|min:8|confirmed',
                'type' => ['required', 'integer', Rule::in($allowedTypes)],
            ]);

            // Additional check: prevent non-super admins from changing user to admin
            if ($validated['type'] == 2 && !$this->canCreateAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to change user type to admin.',
                        'errors' => ['type' => ['You do not have permission to change user type to admin.']]
                    ], 403);
                }
                return back()->withErrors(['type' => 'You do not have permission to change user type to admin.'])->withInput();
            }

            // Prevent changing super admin type (only super admins can do this)
            if ($user->isSuperAdmin() && $validated['type'] != 3 && !auth()->user()->isSuperAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to change super admin user type.',
                        'errors' => ['type' => ['You do not have permission to change super admin user type.']]
                    ], 403);
                }
                return back()->withErrors(['type' => 'You do not have permission to change super admin user type.'])->withInput();
            }

            // Prevent non-super admins from creating super admins
            if ($validated['type'] == 3 && !$this->canCreateAdmin()) {
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You do not have permission to change user type to super admin.',
                        'errors' => ['type' => ['You do not have permission to change user type to super admin.']]
                    ], 403);
                }
                return back()->withErrors(['type' => 'You do not have permission to change user type to super admin.'])->withInput();
            }

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $user->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User updated successfully.'
                ]);
            }

            return redirect()->route('admin.users')->with('success', 'User updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot delete your own account.'
                ], 403);
            }
            return redirect()->route('admin.users')->with('error', 'You cannot delete your own account.');
        }

        // Prevent non-super admins from deleting super admins
        if ($user->isSuperAdmin() && !auth()->user()->isSuperAdmin()) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to delete super admin users.'
                ], 403);
            }
            return redirect()->route('admin.users')->with('error', 'You do not have permission to delete super admin users.');
        }

        $user->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User deleted successfully.'
            ]);
        }

        return redirect()->route('admin.users')->with('success', 'User deleted successfully.');
    }

    // Emergency Type Management
    public function emergencyTypes()
    {
        $types = EmergencyType::orderBy('name')->paginate(10);
        return view('admin.emergency-types.index', compact('types'));
    }

    public function createEmergencyType()
    {
        return view('admin.emergency-types.create');
    }

    public function storeEmergencyType(Request $request)
    {
        try {
            $validated = $request->validate([
                'code' => 'required|string|max:50|unique:emergency_types',
                'name' => 'required|string|max:255',
                'icon' => 'nullable|string|max:255',
                'description' => 'nullable|string|max:1000',
                'status' => 'required|in:0,1',
            ]);

            $validated['status'] = (bool) $validated['status'];

            EmergencyType::create($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Emergency type created successfully.'
                ]);
            }

            return redirect()->route('admin.emergency-types')->with('success', 'Emergency type created successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    public function editEmergencyType($id)
    {
        $type = EmergencyType::findOrFail($id);
        return view('admin.emergency-types.edit', compact('type'));
    }

    public function updateEmergencyType(Request $request, $id)
    {
        $type = EmergencyType::findOrFail($id);

        try {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('emergency_types')->ignore($type->id)],
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
            'status' => 'required|in:0,1',
        ]);

        $validated['status'] = (bool) $validated['status'];

        $type->update($validated);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Emergency type updated successfully.'
                ]);
            }

            return redirect()->route('admin.emergency-types')->with('success', 'Emergency type updated successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $e->errors()
                ], 422);
            }
            throw $e;
        }
    }

    public function deleteEmergencyType(Request $request, $id)
    {
        $type = EmergencyType::findOrFail($id);
        
        // Check if type is being used
        $inUse = EmergencyReport::where('type', $type->code)->exists();
        
        if ($inUse) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete emergency type that is in use.'
                ], 403);
            }
            return redirect()->route('admin.emergency-types')->with('error', 'Cannot delete emergency type that is in use.');
        }

        $type->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Emergency type deleted successfully.'
            ]);
        }

        return redirect()->route('admin.emergency-types')->with('success', 'Emergency type deleted successfully.');
    }

    // Reports Management
    public function reports(Request $request)
    {
        $query = EmergencyReport::query();

        if ($request->filled('status')) {
            // Map report status to responder status
            $statusMap = [
                'pending' => function($q) {
                    $q->whereDoesntHave('responders');
                },
                'acknowledged' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'assigned');
                    });
                },
                'dispatched' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'en_route');
                    });
                },
                'in_progress' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'on_scene');
                    });
                },
                'resolved' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'completed');
                    });
                },
                'cancelled' => function($q) {
                    $q->whereHas('responders', function($subQ) {
                        $subQ->where('role', 'primary')->where('status', 'cancelled');
                    });
                },
            ];
            
            if (isset($statusMap[$request->status])) {
                $statusMap[$request->status]($query);
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }

        $reports = $query->with(['user', 'emergencyType', 'responders.responder'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('admin.reports.index', compact('reports'));
    }
}

