<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class InstructionsController extends Controller
{
    public function index()
    {
        return view('instructions.index');
    }
}
