<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Registration;

class ApiController extends Controller
{
    /**
     * Display the index page
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping()
    {
        return response()->json(['message' => 'pong']);
    }

    /**
     * Return a requested registration if it exists and the user is allowed to see it
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function registrationsShow(Request $request)
    {
        // check if user is not authenticated
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // get registration
        $registration = Registration::find($request->registration);
        if (!$registration) {
            return response()->json(['message' => 'Registration not found'], 404);
        }

        // check if user is allowed to see the registration
        if ($registration->user_id != $request->user()->id) {
            return response()->json(['message' => 'Not allowed'], 403);
        }

        return response()->json($registration);
    }
}
