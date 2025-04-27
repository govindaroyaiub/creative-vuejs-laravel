<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Designation;
use App\Models\User;
use App\Models\Route;
use Illuminate\Validation\Rule;

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

        $data = $request->validate([
            'permissions' => 'nullable|array',
        ]);

        $user->permissions = $data['permissions'] ?? [];
        $user->save();

        // 🛠 If the updated user is the currently logged in user, refresh session
        if (auth()->id() == $user->id) {
            auth()->setUser($user); // ✅ Important: refresh user session
        }

        return back()->with('success', 'Permissions updated.');
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
            'href' => 'required|string|max:255|unique:routes,href', // 🔥 fix here
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
}
