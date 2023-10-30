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
        // get all users and get execute avatarUrl
        $users = User::with('course', 'roles')->get()->map(function ($user) {
            $user->avatarUrl = $user->avatarUrl();

            return $user;
        });

        return response()->json([
            'success' => true,
            'users' => $users,
        ]);
    }
}
