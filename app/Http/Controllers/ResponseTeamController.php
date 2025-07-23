<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ResponseTeam;

class ResponseTeamController extends Controller
{
    public function index()
    {
        return response()->json(ResponseTeam::with('members')->get());
    }
}
