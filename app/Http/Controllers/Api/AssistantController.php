<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use App\Models\Media;
use App\Models\BannerSize;
use App\Models\Bill;
use App\Models\Client;
use App\Models\Designation;
use App\Models\FileTransfer;
use App\Models\Social;
use App\Models\User;
use App\Models\VideoSize;
use OpenAI\Laravel\Facades\OpenAI;

class AssistantController extends Controller
{
    public function handle(Request $request)
    {
        $message = strtolower($request->input('message', ''));

        $patterns = [
            '/how\s+many\s+users/' => fn() => 'There are ' . User::count() . ' users.',

            '/latest\s+user|newest\s+user/' => fn() => ($user = User::latest()->first())
                ? "The latest user is {$user->name}."
                : "No users found.",

            '/user\s+names/' => fn() =>
            User::pluck('name')->implode(', ') ?: 'No user names found.',

            '/user\s+designations/' => fn() =>
            Designation::pluck('name')->implode(', ') ?: 'No designations found.',

            '/users\s+with\s+designation\s+(.+)/' => function ($matches) {
                $designation = trim($matches[1]);
                $users = User::whereHas('designation', fn($q) => $q->where('name', 'like', "%$designation%"))
                    ->pluck('name');
                return $users->isNotEmpty()
                    ? "Users with designation '$designation': " . $users->implode(', ')
                    : "No users found with that designation.";
            },
        ];

        // Loop through regex map
        foreach ($patterns as $pattern => $action) {
            if (preg_match($pattern, $message, $matches)) {
                return response()->json([
                    'answer' => is_callable($action) ? $action($matches ?? []) : $action
                ]);
            }
        }

        return response()->json([
            'answer' => 'Sorry, I don’t have this question coded yet.'
        ]);
    }
}
