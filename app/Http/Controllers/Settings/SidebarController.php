<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SidebarController extends Controller
{
    /**
     * Render the per-user sidebar customisation page. The page reads
     * the user's saved preferences via the auth.user shared prop, so
     * we don't need to pass anything extra here.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Sidebar', [
            // Mirrors the convention used by Settings/Profile + Password —
            // the page reads $page.props.status to flash "Saved." after
            // a successful update.
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Persist the user's sidebar preferences.
     *
     * Payload shape:
     *   {
     *     "main":   [ {"href": "/dashboard", "visible": true}, ... ],
     *     "footer": [ {"href": "/pulse",     "visible": false}, ... ]
     *   }
     *
     * Validation rules:
     *   - both sections required
     *   - each href must be one of the canonical hrefs from
     *     config/sidebar.php — anything else is rejected so a tampered
     *     payload can't smuggle arbitrary routes into the JSON column
     *   - visible must be boolean
     *   - duplicates within a section are rejected (Eloquent would
     *     happily store them; the merge logic on the client would do
     *     the wrong thing)
     */
    public function update(Request $request): RedirectResponse
    {
        // Items may be moved between sections, so any known href is valid in
        // either section — validate against the union of both lists.
        $allowed = array_values(array_unique(array_merge(
            config('sidebar.main', []),
            config('sidebar.footer', []),
        )));

        $validated = $request->validate([
            'main'             => ['present', 'array'],
            'main.*.href'      => ['required', 'string', Rule::in($allowed)],
            'main.*.visible'   => ['required', 'boolean'],
            'footer'           => ['present', 'array'],
            'footer.*.href'    => ['required', 'string', Rule::in($allowed)],
            'footer.*.visible' => ['required', 'boolean'],
        ]);

        foreach (['main', 'footer'] as $section) {
            $hrefs = collect($validated[$section] ?? [])->pluck('href');
            if ($hrefs->count() !== $hrefs->unique()->count()) {
                return back()->withErrors([
                    $section => "Duplicate items in the {$section} section.",
                ]);
            }
        }

        $user = $request->user();
        $user->nav_preferences = [
            'main'   => $validated['main'],
            'footer' => $validated['footer'],
        ];
        $user->save();

        return back()->with('status', 'sidebar-updated');
    }

    /**
     * Drop the user's preferences back to null — AppSidebar.vue then
     * falls back to the canonical order with everything visible.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = $request->user();
        $user->nav_preferences = null;
        $user->save();

        return back()->with('status', 'sidebar-reset');
    }
}
