<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\AiEmployee;

class AiEmployeeController extends Controller
{
    public function index()
    {
        $employees = AiEmployee::where('is_active', true)
            ->orderBy('name')
            ->get();

        return response()->json($employees);
    }
}
