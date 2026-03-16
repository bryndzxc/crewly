<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserTutorialController extends Controller
{
    public function markCompleted(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false], 401);
        }

        $user->forceFill([
            'tutorial_completed_at' => now(),
        ])->save();

        return response()->json([
            'success' => true,
            'tutorial_completed_at' => $user->tutorial_completed_at,
        ]);
    }
}
