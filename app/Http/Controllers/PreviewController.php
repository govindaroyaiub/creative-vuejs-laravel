<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;

class PreviewController extends Controller
{
    public function index()
    {
        return Inertia::render('Previews/Index');
    }
}
