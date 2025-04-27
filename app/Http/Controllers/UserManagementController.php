<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Designation;
use App\Models\User;
use App\Models\Route;
use Illuminate\Validation\Rule;
use App\Services\ResendMailService;

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
            ->select(
                'users.id',
                'users.name',
                'users.email',
                'users.role',
                'users.permissions',
                'users.created_at',
                'designations.name as designation_name'
            )
            ->orderBy('users.name', 'ASC')
            ->get();

        $routes = \App\Models\Route::orderBy('title')->get(); // 🛠 If you have Route model

        return Inertia::render('UserManagements/Users/Index', [
            'users' => $users,
            'routes' => $routes,
        ]);
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
        ]);
    
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => 'user',
            'password' => bcrypt('password'),
            'permissions' => ['/welcome-to-planetnine/register'],
        ]);
    
        // ✅ Send Welcome Email
        ResendMailService::send(
            $user->email,
            $user->name,
            'Welcome to Planet Nine!',
            view('emails.welcome', compact('user'))->render()
        );
    
        // ✅ Return JSON
        return response()->json([
            'success' => true,
            'newUser' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'designation_name' => null,
                'permissions' => $user->permissions,
            ],
        ]);
    }

    public function userDelete($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
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

    public function register()
    {
        $designations = Designation::orderBy('name')->get();
        return Inertia::render('UserManagements/Register/Index', [
            'designations' => $designations,
        ]);
    }

    public function registerPost(Request $request)
    {
        // 🛠 Validate only Designation and Password fields
        $data = $request->validate([
            'designation' => 'required|exists:designations,id',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        // 🛠 Create User
        $user = User::create([
            'designation' => $data['designation'],
            'password' => Hash::make($data['password']),
            'role' => 'user', // or default role
            'name' => 'Unnamed', // Default, or you can add another field later
            'email' => uniqid() . '@example.com', // Dummy email (because unique is required if email is in db)
        ]);

        // Optionally log in the user automatically
        auth()->login($user);

        return redirect()->route('dashboard');
    }
}
