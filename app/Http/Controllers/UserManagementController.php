<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Designation;
use App\Models\User;
use App\Models\Route;
use App\Models\Client;
use Illuminate\Validation\Rule;
use App\Services\ResendMailService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\DB;

class UserManagementController extends Controller
{
    public function designationIndex()
    {
        $designations = Designation::orderBy('name', 'ASC')->get();

        return Inertia::render('UserManagements/Designations/Index', [
            'designations' => $designations,
        ]);
    }

    public function designationStore(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:designations,name',
        ]);

        Designation::create($data);

        return redirect()->route('user-managements')->with('success', 'Designation created successfully.');
    }

    public function designationUpdate($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->update(request()->validate([
            'name' => 'required|string|max:255',
        ]));

        return redirect()->back()->with('success', 'Designation updated successfully.');
    }

    public function designationDestroy($id)
    {
        $designation = Designation::findOrFail($id);
        $designation->delete();
    }

    public function userIndex()
    {
        $users = User::leftJoin('designations', 'users.designation', '=', 'designations.id')
            ->leftJoin('clients', 'users.client_id', '=', 'clients.id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'users.permissions',
                'users.client_id',
                'users.created_at',
                'designations.name as designation_name',
                'clients.name as client_name'
            )
            ->orderBy('users.name', 'ASC')
            ->get();

        $routes = Route::orderBy('title')->get();
        $clients = Client::orderBy('name')->get();

        return Inertia::render('UserManagements/Users/Index', [
            'users' => $users,
            'routes' => $routes,
            'clients' => $clients,
        ]);
    }

    public function updateClient(Request $request, User $user)
    {
        $request->validate([
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $user->client_id = $request->client_id;
        $user->save();

        return response()->json(['message' => 'Client updated successfully.']);
    }

    public function userPermissionsUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $user->update([
            'permissions' => $validated['permissions'] ?? [],
        ]);

        // 🛠 Refresh session if the updated user is the current logged-in user
        if (auth()->id() === $user->id) {
            auth()->login($user->fresh()); // ✅ refresh user object properly
        }

        return back()->with('success', 'Permissions updated successfully.');
    }

    public function userCreate(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|in:super_admin,admin,user',
            'permissions' => 'nullable|array',
            'send_mail' => 'nullable|boolean',
            'client_id' => 'nullable|exists:clients,id',
        ]);

        $permissions = $data['permissions'] ?? [];

        if (!in_array('/welcome-to-planetnine/register', $permissions)) {
            $permissions[] = '/welcome-to-planetnine/register';
        }

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'client_id' => $data['client_id'] ?? null,
            'password' => bcrypt('password'), // temporary
            'designation' => $data['send_mail'] ? null : 7, // if no mail, assign client designation
            'permissions' => $permissions,
        ]);

        if ($data['send_mail']) {
            Mail::send('emails.welcome', ['user' => $user], function ($message) use ($user) {
                $message->to($user->email, $user->name)
                    ->subject('Welcome to Planet Nine!');
            });
        }

        return response()->json([
            'success' => true,
            'newUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'designation_name' => $user->designationRelation?->name ?? null,
                'permissions' => $user->permissions,
                'client_id' => $user->client_id,
            ],
        ]);
    }

    public function userPasswordUpdate(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // ✅ Generate new password
        $temporaryPassword = Str::random(10);

        $user->update([
            'password' => bcrypt($temporaryPassword),
        ]);

        // ✅ Add '/change-password' permission
        $permissions = $user->permissions ?? [];
        if (!in_array('/change-password', $permissions)) {
            $permissions[] = '/change-password';
            $user->permissions = $permissions;
            $user->save();
        }

        // 🛠️ NEW - If the affected user is currently logged in, refresh session
        if (auth()->check() && auth()->id() == $user->id) {
            auth()->setUser($user->fresh()); // ✅ Important: refresh logged-in user session
        }

        // ✅ Send Email
        ResendMailService::send(
            $user->email,
            $user->name,
            'Password Reset at Planet Nine!',
            view('emails.changepassword', [
                'user' => $user,
                'temporaryPassword' => $temporaryPassword,
            ])->render()
        );

        return back()->with('success', 'Password reset successfully and email sent.');
    }

    public function userDelete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
    }

    public function updateRole(Request $request, $id)
    {
        $request->validate([
            'role' => 'required|in:super_admin,admin,user',
        ]);

        $user = User::findOrFail($id);
        $user->role = $request->role;
        $user->save();

        return response()->json(['success' => true]);
    }

    public function routesIndex()
    {
        $routes = Route::orderBy('title', 'ASC')->get();
        return Inertia::render('UserManagements/Routes/Index', [
            'routes' => $routes
        ]);
    }

    public function routesStore(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255|unique:routes,title',
            'href' => 'required|string|max:255|unique:routes,href',
        ]);

        Route::create($data);

        return redirect()->route('user-managements-routes')->with('success', 'Route created successfully.');
    }

    public function routesUpdate($id)
    {
        $route = Route::findOrFail($id);
        $route->update(request()->validate([
            'title' => 'required|string|max:255',
            'href' => [
                'required',
                'string',
                'max:255',
                Rule::unique('routes')->ignore($route->id),
            ],
        ]));
    }

    public function routesDestroy($id)
    {
        $route = Route::findOrFail($id);
        $route->delete();
    }

    public function register(Request $request)
    {
        $userId = $request->query('user');

        if (!$userId) {
            abort(403, 'Invalid registration link.');
        }

        $user = User::find($userId);

        if (!$user) {
            abort(404, 'User not found.');
        }

        if (!in_array('/welcome-to-planetnine/register', $user->permissions ?? [])) {
            abort(403, 'Unauthorized access.');
        }

        // ✅ Now everything is safe
        $designations = Designation::orderBy('name')->get();

        return Inertia::render('UserManagements/Register/Index', [
            'designations' => $designations,
        ]);
    }

    public function registerPost(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'designation' => 'required|exists:designations,id',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::findOrFail($data['user_id']);

        // ✅ Update user information
        $user->update([
            'designation' => $data['designation'],
            'password' => bcrypt($data['password']),
            'email_verified_at' => now(),
        ]);

        // ✅ Refresh user from database to get latest permissions
        $user->refresh();

        // ✅ Remove '/welcome-to-planetnine/register' permission if it exists
        $user->permissions = collect($user->permissions ?? [])
            ->reject(fn($permission) => $permission === '/welcome-to-planetnine/register')
            ->values()
            ->all();
        $user->save();

        // ✅ Logout immediately after registration
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Registration completed successfully. Please login.');
    }

    public function changePassword(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            abort(403, 'Unauthorized access.');
        }

        if (!in_array('/change-password', $user->permissions ?? [])) {
            abort(403, 'Unauthorized access.');
        }

        return Inertia::render('UserManagements/ChangePassword/Index');
    }

    public function changePasswordPost(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::findOrFail($data['user_id']);

        $user->update([
            'password' => bcrypt($data['password']),
            'email_verified_at' => now(),
        ]);

        // Remove '/change-password' permission
        $user->permissions = array_filter($user->permissions ?? [], fn($perm) => $perm !== '/change-password');
        $user->save();

        // ✅ Just to be safe: logout if session active
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Password changed successfully. Please login.');
    }
}
