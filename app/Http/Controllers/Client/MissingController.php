<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MissingController extends Controller
{
    public function index()
    {
        return view('missing.index');
    }

    public function detail()
    {
        return view('missing.detail');
    }
}
