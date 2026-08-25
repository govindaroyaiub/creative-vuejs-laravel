<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The whole task module, in its final shape.
 *
 * This is a squash. The feature was rewritten twice inside two days, and the
 * five migrations that recorded that history built columns only to drop them
 * again — `tasks.user_id`, `tasks.position`, `tasks.status`, `tasks.priority`,
 * `task_user.position` and three indexes were all created and destroyed before
 * the dust settled. A fresh install paid for every one of them.
 *
 * The filename is deliberately unchanged even though this now creates five
 * tables rather than one. Laravel identifies a migration by that string, so
 * renaming it would make every already-migrated database treat this as pending
 * and try to create tables it already has. Keeping the name means existing
 * databases see nothing new and never run this file; only a fresh database
 * does, and it lands directly on the shape below.
 *
 * The four migrations that were folded in leave orphan rows behind in the
 * `migrations` table of any database that ran them. That is harmless — Laravel
 * only ever compares those names against the files on disk to decide what is
 * pending, and a name with no file is simply ignored.
 *
 * Dropped along with them: the data backfill from `create_task_boards_and_lists`
 * (it returned early when `tasks` was empty, so it never did anything on a
 * fresh install) and the creator-repair pass from
 * `restore_creators_as_task_participants`. Default lists are not seeded here
 * either — `BoardController::createBoard` builds them from
 * `Board::DEFAULT_LISTS` the first time a user opens the board.
 *
 * Tables are created parent-first so every foreign key has its target, and
 * dropped in reverse for the same reason.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('boards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('name');
            // Manual order of a user's boards in the switcher.
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();

            // "My boards, in order" — the only way boards are ever listed.
            $table->index(['user_id', 'position']);
        });

        // Board membership. An owner is a member too, so access is one lookup.
        Schema::create('board_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['board_id', 'user_id']);
            // The unique index above already covers the `board_id` foreign key,
            // but nothing covers `user_id`, and MySQL requires an index on
            // every foreign key column.
            $table->index('user_id');
        });

        Schema::create('task_lists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('board_id')->constrained('boards')->onDelete('cascade');
            $table->string('name');
            // Manual order of columns across the board.
            $table->unsignedInteger('position')->default(0);
            // Protected lists cannot be renamed or deleted by the user.
            $table->boolean('is_protected')->default(false);
            $table->timestamps();

            $table->index(['board_id', 'position']);
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            // Which column the card sits in. Nullable so a card survives its
            // list being deleted rather than cascading away with it.
            $table->foreignId('list_id')->nullable()->constrained('task_lists')->onDelete('cascade');
            // Who created it — the attribution shown on the card.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('due_date')->nullable();
            // Manual order inside the list.
            $table->unsignedInteger('position')->default(0);
            // Set when the card is completed. Completed cards keep their list
            // and position — they are archived off the board, not moved — so
            // restoring one is just clearing this column.
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('due_date');
            // Board render: the cards of a list, in order.
            $table->index(['list_id', 'position']);
            // Archive drawer and its count: completed cards of a list.
            $table->index(['list_id', 'completed_at']);
        });

        // Card participants. A card can sit on several people's boards at once.
        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained('tasks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['task_id', 'user_id']);
            // As in `board_user`: the unique index covers `task_id`, so
            // `user_id` needs its own for the foreign key.
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_user');
        Schema::dropIfExists('tasks');
        Schema::dropIfExists('task_lists');
        Schema::dropIfExists('board_user');
        Schema::dropIfExists('boards');
    }
};
