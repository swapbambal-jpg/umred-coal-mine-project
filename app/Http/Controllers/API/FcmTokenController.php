<?php


namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class FcmTokenController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string'
        ]);

        FcmToken::updateOrCreate(
            ['token' => $request->fcm_token],
            ['user_id' => auth()->id()]
        );

        return response()->json(['message' => 'Token saved']);
    }
}
