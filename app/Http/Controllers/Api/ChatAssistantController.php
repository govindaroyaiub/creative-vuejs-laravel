<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use Gemini\Laravel\Facades\Gemini;
use Carbon\Carbon;
use App\Models\Bill;
use App\Models\Client;
use App\Models\ColorPalette;
use App\Models\Designation;
use App\Models\FileTransfer;
use App\Models\Media;
use App\Models\Preview;
use App\Models\Route as AppRoute; // Renamed to avoid conflict with Illuminate\Support\Facades\Route
use App\Models\Social;
use App\Models\SubBill;
use App\Models\SubBanner;
use App\Models\SubGif;
use App\Models\SubSocial;
use App\Models\SubVersion;
use App\Models\SubVideo;
use App\Models\User;
use App\Models\Version;
use App\Models\VideoSize;

class ChatAssistantController extends Controller
{
    public function sendMessage(Request $request)
    {
        $userQuery = $request->input('query');

        // 1. Gather ALL potentially relevant data
        // The more data you fetch here, the broader the questions Gemini can answer.
        // Be mindful of performance and token limits.
        $contextualData = $this->gatherComprehensiveContext($userQuery); // Pass query to allow for some targeted fetching

        // 2. Prepare a more flexible prompt for Gemini
        $prompt = $this->buildDynamicGeminiPrompt($userQuery, $contextualData);

        try {
            $response = Gemini::generativeModel(model: 'gemini-1.5-flash')
                              ->generateContent($prompt);

            return response()->json(['reply' => $response->text()]);

        } catch (\Exception $e) {
            \Log::error('Gemini API Error: ' . $e->getMessage());
            return response()->json(['reply' => 'Sorry, I am unable to process your request at the moment. Please try again later.'], 500);
        }
    }

    /**
     * Gathers a broad set of relevant data from your application.
     * The AI will then use this data to answer diverse questions.
     * @param string $userQuery The user's query to potentially guide data fetching.
     */
    private function gatherComprehensiveContext(string $userQuery): array
    {
        $data = [];
        $today = Carbon::today();
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $oneMonthAgo = Carbon::now()->subMonths(1);

        // --- General Overview Data ---
        $data['total_clients_count'] = Client::count();
        $data['total_users_count'] = User::count();
        $data['total_previews_count'] = Preview::count();
        $data['total_bills_count'] = Bill::count();

        // --- Recent Updates (for various models) ---
        // Recent Previews created/updated
        $data['recent_previews'] = Preview::with('client', 'uploader', 'colorPalette')
                                        ->where('updated_at', '>=', $sevenDaysAgo)
                                        ->orderBy('updated_at', 'desc')
                                        ->limit(5)
                                        ->get()
                                        ->map(function($preview) {
                                            return [
                                                'id' => $preview->id,
                                                'name' => $preview->name,
                                                'client_name' => $preview->client->name ?? 'N/A',
                                                'uploader_name' => $preview->uploader->name ?? 'N/A',
                                                'updated_at' => $preview->updated_at->format('Y-m-d H:i'),
                                            ];
                                        })->toArray();

        // Recent File Transfers
        $data['recent_file_transfers'] = FileTransfer::with('user')
                                                ->where('created_at', '>=', $sevenDaysAgo)
                                                ->orderBy('created_at', 'desc')
                                                ->limit(5)
                                                ->get()
                                                ->map(function($ft) {
                                                    return [
                                                        'name' => $ft->name,
                                                        'client' => $ft->client, // assuming 'client' is a string field
                                                        'uploaded_by' => $ft->user->name ?? 'N/A',
                                                        'uploaded_at' => $ft->created_at->format('Y-m-d H:i'),
                                                    ];
                                                })->toArray();

        // Recent Media uploads
        $data['recent_media_uploads'] = Media::with('uploader')
                                                ->where('created_at', '>=', $sevenDaysAgo)
                                                ->orderBy('created_at', 'desc')
                                                ->limit(5)
                                                ->get()
                                                ->map(function($media) {
                                                    return [
                                                        'name' => $media->name,
                                                        'uploaded_by' => $media->uploader->name ?? 'N/A',
                                                        'uploaded_at' => $media->created_at->format('Y-m-d H:i'),
                                                    ];
                                                })->toArray();


        // --- Specific Entity Lookups (triggered by keywords in query for efficiency) ---
        // If the user asks about a specific client:
        if (preg_match('/client (\w+)/i', $userQuery, $matches) || preg_match('/client name is (\w+)/i', $userQuery, $matches)) {
            $clientName = $matches[1];
            $client = Client::where('name', 'like', "%{$clientName}%")
                            ->with('colorPalette', 'users')
                            ->first();
            if ($client) {
                $data['specific_client_info'] = [
                    'id' => $client->id,
                    'name' => $client->name,
                    'website' => $client->website,
                    'preview_url' => $client->preview_url,
                    'color_palette_name' => $client->colorPalette->name ?? 'N/A',
                    'associated_users' => $client->users->pluck('name')->toArray(),
                    'created_at' => $client->created_at->format('Y-m-d'),
                ];
                // Also fetch their previews
                $data['client_previews'] = Preview::where('client_id', $client->id)
                                                    ->with('versions')
                                                    ->limit(3)
                                                    ->get()
                                                    ->map(function($preview) {
                                                        return [
                                                            'name' => $preview->name,
                                                            'versions_count' => $preview->versions->count(),
                                                            'updated_at' => $preview->updated_at->format('Y-m-d'),
                                                        ];
                                                    })->toArray();
            }
        }

        // If the user asks about a specific user/team member:
        if (preg_match('/user (\w+)/i', $userQuery, $matches) || preg_match('/team member (\w+)/i', $userQuery, $matches) || preg_match('/employee (\w+)/i', $userQuery, $matches)) {
            $userName = $matches[1];
            $user = User::where('name', 'like', "%{$userName}%")
                        ->with('designation', 'client')
                        ->first();
            if ($user) {
                $data['specific_user_info'] = [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'designation' => $user->designation->name ?? 'N/A',
                    'role' => $user->role,
                    'client_name' => $user->client->name ?? 'N/A',
                    'joined_at' => $user->created_at->format('Y-m-d'),
                ];
                // You could also fetch recent media uploaded by this user, file transfers, etc.
            }
        }

        // If the user asks about a specific preview:
        if (preg_match('/preview (\w+)/i', $userQuery, $matches)) {
             $previewName = $matches[1];
             $preview = Preview::where('name', 'like', "%{$previewName}%")
                               ->with('client', 'uploader', 'colorPalette', 'versions.subVersions')
                               ->first();
             if ($preview) {
                 $data['specific_preview_details'] = [
                     'id' => $preview->id,
                     'name' => $preview->name,
                     'client_name' => $preview->client->name ?? 'N/A',
                     'uploader_name' => $preview->uploader->name ?? 'N/A',
                     'color_palette_name' => $preview->colorPalette->name ?? 'N/A',
                     'requires_login' => $preview->requires_login ? 'Yes' : 'No',
                     'created_at' => $preview->created_at->format('Y-m-d'),
                     'versions' => $preview->versions->map(function($version) {
                         return [
                             'name' => $version->name,
                             'type' => $version->type,
                             'is_active' => $version->is_active ? 'Yes' : 'No',
                             'sub_versions_count' => $version->subVersions->count(),
                         ];
                     })->toArray()
                 ];
             }
         }


        // --- Monthly Summaries ---
        // Bills created last month
        $data['bills_last_month'] = Bill::where('created_at', '>=', $oneMonthAgo)
                                        ->selectRaw('COUNT(*) as count, SUM(total_amount) as total_sum')
                                        ->first()
                                        ->toArray();

        // New clients last month
        $data['new_clients_last_month'] = Client::where('created_at', '>=', $oneMonthAgo)
                                                ->count();

        return $data;
    }

    /**
     * Builds the complete prompt for the Gemini model, incorporating user query and contextual data.
     * This is where "prompt engineering" happens to instruct the AI.
     */
    private function buildDynamicGeminiPrompt(string $userQuery, array $contextualData): string
    {
        $prompt = "You are an intelligent AI assistant for our internal project and client management system. Your goal is to answer questions about clients, users, previews, bills, file transfers, and general system updates.\n";
        $prompt .= "Use the provided context to answer questions accurately and concisely. If the information is not available in the context, state that you don't have the information. Do not make up answers.\n\n";
        $prompt .= "The current date and time is: " . Carbon::now()->format('Y-m-d H:i:s') . " (Dhaka, Bangladesh time zone).\n\n";


        // --- General Overview ---
        $prompt .= "### System Overview:\n";
        $prompt .= "- Total clients: {$contextualData['total_clients_count']}\n";
        $prompt .= "- Total users: {$contextualData['total_users_count']}\n";
        $prompt .= "- Total previews created: {$contextualData['total_previews_count']}\n";
        $prompt .= "- Total bills recorded: {$contextualData['total_bills_count']}\n\n";

        // --- Recent Activity ---
        if (!empty($contextualData['recent_previews'])) {
            $prompt .= "### Recent Previews (Last 7 Days):\n";
            foreach ($contextualData['recent_previews'] as $preview) {
                $prompt .= "- Preview ID: {$preview['id']}, Name: '{$preview['name']}' (Client: {$preview['client_name']}), Uploader: {$preview['uploader_name']}, Last Updated: {$preview['updated_at']}\n";
            }
            $prompt .= "\n";
        }
        if (!empty($contextualData['recent_file_transfers'])) {
            $prompt .= "### Recent File Transfers (Last 7 Days):\n";
            foreach ($contextualData['recent_file_transfers'] as $ft) {
                $prompt .= "- File: '{$ft['name']}' (Client: {$ft['client']}), Uploaded by: {$ft['uploaded_by']}, Uploaded At: {$ft['uploaded_at']}\n";
            }
            $prompt .= "\n";
        }
        if (!empty($contextualData['recent_media_uploads'])) {
            $prompt .= "### Recent Media Uploads (Last 7 Days):\n";
            foreach ($contextualData['recent_media_uploads'] as $media) {
                $prompt .= "- Media: '{$media['name']}', Uploaded by: {$media['uploaded_by']}, Uploaded At: {$media['uploaded_at']}\n";
            }
            $prompt .= "\n";
        }

        // --- Monthly Summaries ---
        if (isset($contextualData['bills_last_month'])) {
            $prompt .= "### Last Month's Financial Summary:\n";
            $prompt .= "- Bills created: {$contextualData['bills_last_month']['count']}\n";
            $prompt .= "- Total amount billed: {$contextualData['bills_last_month']['total_sum']}\n\n";
        }
        if (isset($contextualData['new_clients_last_month'])) {
            $prompt .= "### New Clients Last Month:\n";
            $prompt .= "- Number of new clients: {$contextualData['new_clients_last_month']}\n\n";
        }

        // --- Specific Entity Details (if found based on query) ---
        if (isset($contextualData['specific_client_info'])) {
            $client = $contextualData['specific_client_info'];
            $prompt .= "### Details for Client '{$client['name']}':\n";
            $prompt .= "- ID: {$client['id']}\n";
            $prompt .= "- Website: {$client['website']}\n";
            $prompt .= "- Preview URL: {$client['preview_url']}\n";
            $prompt .= "- Color Palette: {$client['color_palette_name']}\n";
            $prompt .= "- Associated Users: " . implode(', ', $client['associated_users']) . "\n";
            $prompt .= "- Created At: {$client['created_at']}\n\n";
            if (!empty($contextualData['client_previews'])) {
                $prompt .= "#### Recent Previews for this Client:\n";
                foreach ($contextualData['client_previews'] as $cp) {
                    $prompt .= "- Preview Name: '{$cp['name']}', Versions: {$cp['versions_count']}, Last Updated: {$cp['updated_at']}\n";
                }
                $prompt .= "\n";
            }
        }

        if (isset($contextualData['specific_user_info'])) {
            $user = $contextualData['specific_user_info'];
            $prompt .= "### Details for User '{$user['name']}':\n";
            $prompt .= "- ID: {$user['id']}\n";
            $prompt .= "- Email: {$user['email']}\n";
            $prompt .= "- Designation: {$user['designation']}\n";
            $prompt .= "- Role: {$user['role']}\n";
            $prompt .= "- Associated Client: {$user['client_name']}\n";
            $prompt .= "- Joined At: {$user['joined_at']}\n\n";
        }

        if (isset($contextualData['specific_preview_details'])) {
            $preview = $contextualData['specific_preview_details'];
            $prompt .= "### Details for Preview '{$preview['name']}':\n";
            $prompt .= "- ID: {$preview['id']}\n";
            $prompt .= "- Client: {$preview['client_name']}\n";
            $prompt .= "- Uploader: {$preview['uploader_name']}\n";
            $prompt .= "- Color Palette: {$preview['color_palette_name']}\n";
            $prompt .= "- Requires Login: {$preview['requires_login']}\n";
            $prompt .= "- Created At: {$preview['created_at']}\n";
            if (!empty($preview['versions'])) {
                $prompt .= "#### Versions:\n";
                foreach ($preview['versions'] as $version) {
                    $prompt .= "- Name: '{$version['name']}', Type: '{$version['type']}', Active: {$version['is_active']}, Sub-versions: {$version['sub_versions_count']}\n";
                }
            }
            $prompt .= "\n";
        }

        // --- User Query ---
        $prompt .= "User's specific query: {$userQuery}\n\n";
        $prompt .= "Based on the information above, please answer the user's query. Be helpful, concise, and professional. If the information is not directly available in the provided context, state that clearly and offer to look into it if it's a capability you could potentially add (e.g., 'I can look up more detailed information if you provide a specific client ID.').";

        return $prompt;
    }
}