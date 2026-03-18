<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organization;
use App\Models\Branch;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class SuperAdminController extends Controller
{
    public function dashboard()
    {
        $organizations = Organization::with(['users'])->get();
        return view('superadmin.dashboard', compact('organizations'));
    }

    public function createOrganization(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:retail,wholesale,producer,processor,laboratory,admin',
                 'email' => 'nullable|email',
                'phone' => 'nullable|string|max:20',
                'license_number' => 'nullable|string|unique:organizations,license_number',
                'business_name' => 'required|string|max:255',
                'physical_address' => 'nullable|string',
                'password' => 'required|string|min:8',
            ]);

            DB::beginTransaction();

            $organization = Organization::create([
                'name' => $validatedData['name'],
                'type' => $validatedData['type'],
                'email' => $validatedData['email'],
                'phone' => $validatedData['phone'] ?? null,
                'license_number' => $validatedData['license_number'] ?? null,
                'business_name' => $validatedData['business_name'],
                'physical_address' => $validatedData['physical_address'] ?? null,
            ]);  

            $user = $organization->users()->create([
                'name' => $validatedData['name'] . ' Admin',
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => 2, // Assuming 2 is the role_id for organization admin
                'apiKey' => Str::random(60),
            ]);

            DB::commit();

            return response()->json([
                'organization' => $organization,
                'admin_email' => $validatedData['email'],
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating organization: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the organization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createUser(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:8',
                'role_id' => 'required|exists:roles,id',
            ]);

            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role_id' => $validatedData['role_id'],
                'apiKey' => Str::random(60),
            ]);

            return response()->json($user, 201);
        } catch (\Exception $e) {
            Log::error('Error creating user: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while creating the user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function linkUserToOrganization(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'user_id' => 'required|exists:users,id',
                'organization_id' => 'required|exists:organizations,id',
            ]);

            $user = User::find($validatedData['user_id']);
            $user->organizations()->attach($validatedData['organization_id']);

            return response()->json(['message' => 'User linked to organization successfully']);
        } catch (\Exception $e) {
            Log::error('Error linking user to organization: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while linking the user to the organization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function createBranch(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'organization_id' => 'required|exists:organizations,id',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $branch = Branch::create([
            'name' => $validatedData['name'],
            'organization_id' => $validatedData['organization_id'],
        ]);

        User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role_id' => 3, // Assuming 3 is the role_id for branch admin
            'organization_id' => $validatedData['organization_id'],
            'branch_id' => $branch->id,
        ]);

        return redirect()->route('superadmin.dashboard')->with('success', 'Branch created successfully');
    }

    public function getUsers()
    {
        $users = User::with(['role', 'organization', 'organizations'])->get();
        
        $users = $users->map(function ($user) {
            $userData = $user->toArray();
            $userData['primary_organization'] = $user->organization ? $user->organization->name : 'Not Assigned';
            $userData['linked_organizations'] = $user->organizations->pluck('name');
            return $userData;
        });

        return response()->json($users);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $user->update($request->all());
        return response()->json($user);
    }

    public function getOrganizations()
    {
        $organizations = Organization::with('users')->get();
        return response()->json($organizations);
    }

    public function updateOrganization(Request $request, $id)
    {
        $organization = Organization::findOrFail($id);
        $organization->update($request->all());
        return response()->json(['message' => 'Organization updated successfully']);
    }

    public function showLoginForm()
    {
        return view('superadmin.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            if ($user->role_id === 1) { // Assuming 1 is the role_id for superadmin
                return redirect()->intended('superadmin/dashboard');
            }
            Auth::logout();
        }

        return back()->withErrors(['email' => 'Invalid credentials or insufficient permissions.']);
    }

    public function addOrganization(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $organization = Organization::create([
            'name' => $request->name,
        ]);

        return response()->json($organization);
    }

    public function storeOrganization(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:retail,wholesale',
        ]);

        DB::beginTransaction();

        try {
            // Create organization
            $organization = Organization::create($validatedData);

            // Create default branch
            $branch = Branch::create([
                'name' => $organization->name,
                'organization_id' => $organization->id,
            ]);

            // Create admin user
            $adminEmail = 'admin@example' . $organization->id . '.com';
            $superAdmin = User::where('role_id', 1)->first(); // Assuming 1 is super admin role

            User::create([
                'name' => 'Admin',
                'email' => $adminEmail,
                'password' => $superAdmin ? $superAdmin->password : Hash::make('default_password'),
                'role_id' => 2, // Assuming 2 is organization admin role
                'organization_id' => $organization->id,
                'branch_id' => $branch->id,
            ]);

            DB::commit();

            return response()->json([
                'organization' => $organization,
                'branch' => $branch,
                'admin_email' => $adminEmail
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteOrganization($id)
    {
        try {
            $organization = Organization::findOrFail($id);
            $organization->delete();
            return response()->json(['message' => 'Organization deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting organization: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the organization',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteUser($id)
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return response()->json(['message' => 'User deleted successfully']);
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while deleting the user',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getCustomerSubscriptions()
    {
        $customers = Customer::with(['organizations' => function($query) {
            $query->select('organizations.id', 'organizations.name', 'organizations.type');
        }])
        ->get(['id', 'name', 'email']);

        return response()->json($customers);
    }

    public function getOrganizationUsers($id)
    {
        try {
            $users = User::where('organization_id', $id)
                         ->orWhereHas('organizations', function($query) use ($id) {
                             $query->where('organizations.id', $id);
                         })
                         ->with('role')
                         ->get();

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error getting organization users: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching organization users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getUserOrganizations($id)
    {
        try {
            $user = User::findOrFail($id);
            $organizations = $user->organizations;
            return response()->json($organizations);
        } catch (\Exception $e) {
            Log::error('Error getting user organizations: ' . $e->getMessage());
            return response()->json([
                'message' => 'An error occurred while fetching user organizations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function getOrganization($id)
{
    try {
        $organization = Organization::findOrFail($id);
        return response()->json($organization);
    } catch (\Exception $e) {
        Log::error('Error fetching organization: ' . $e->getMessage());
        return response()->json([
            'message' => 'An error occurred while fetching the organization',
            'error' => $e->getMessage(),
        ], 500);
    }
}

}