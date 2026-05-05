<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user sidebar customisation.
     *
     * Stored shape (cast to array on the User model):
     *   {
     *     "main":   [ {"href": "/dashboard", "visible": true}, ... ],
     *     "footer": [ {"href": "/pulse", "visible": false}, ... ]
     *   }
     *
     * `null` (the default) means "use the canonical AppSidebar order
     * with everything visible". Any item the user hasn't explicitly
     * positioned (e.g. a new entry shipped after they saved their
     * preferences) is appended to the end as visible — see the merge
     * logic in AppSidebar.vue.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('nav_preferences')->nullable()->after('permissions');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('nav_preferences');
        });
    }
};
