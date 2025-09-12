<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Tetris;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class TetrisController extends Controller
{
    public function index()
    {
        $topScores = Tetris::with('user')
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        return Inertia::render('Tetris/Index', [
            'topScores' => $topScores,
        ]);
        return Inertia::render('Tetris/Index');
    }

    public function submitScore(Request $request)
    {
        $request->validate([
            'score' => 'required|integer|min:0',
        ]);

        Tetris::create([
            'user_id' => auth()->id(),
            'score' => $request->score,
        ]);

        $topScores = Tetris::with('user')
            ->orderByDesc('score')
            ->limit(10)
            ->get();

        return response()->json(['topScores' => $topScores]);
    }
}
