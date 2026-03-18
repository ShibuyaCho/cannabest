<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimeClockController extends Controller
{
      public function clockIn(Request $request)
    {
        return TimeClock::create([
            'user_id' => Auth::id(),
            'clock_in' => now(),
        ]);
    }

    public function clockOut(Request $request)
    {
        $clock = TimeClock::where('user_id', Auth::id())
                          ->whereNull('clock_out')
                          ->latest()
                          ->first();
        if ($clock) {
            $clock->clock_out = now();
            $clock->save();
        }

        return response()->json(['message' => 'Clocked out']);
    }
}
