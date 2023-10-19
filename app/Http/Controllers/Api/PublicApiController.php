<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PublicApiController extends Controller
{
    /**
     * Returns all users.
     */
    public function users(): JsonResponse
    {
        // get all users
        $users = User::with('course', 'roles')->get();

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }
}
