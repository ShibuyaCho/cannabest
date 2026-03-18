<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift; // Ensure your Shift model exists
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    /**
     * Handle clock in and create a new shift.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Optional: Check if the user already has an active shift.
        $activeShift = Shift::where('cashier_id', $user->id)
            ->whereNull('shift_stop_time')
            ->where('is_complete', false)
            ->first();
        if ($activeShift) {
            return response()->json([
                'error' => 'Already clocked in',
                'shift_start_time' => $activeShift->shift_start_time,
                'shiftId' => $activeShift->id,
            ], 400);
        }

        // Create a new shift record with shift_start_time set to the current time.
        $shift = Shift::create([
            'cashier_id' => $user->id,
            'shift_start_time' => now(),
        ]);

        return response()->json([
            'shift_start_time' => $shift->shift_start_time,
            'shiftId' => $shift->id,
        ]);
    }

    /**
     * Handle clock out by updating the existing shift.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $shiftId
     * @return \Illuminate\Http\JsonResponse
     */
    public function clockOut(Request $request, $shiftId)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Find the shift belonging to the current user.
        $shift = Shift::where('id', $shiftId)
            ->where('cashier_id', $user->id)
            ->first();
        if (!$shift) {
            return response()->json(['error' => 'Shift not found'], 404);
        }

        // Make sure the shift hasn't been clocked out already.
        if ($shift->shift_stop_time !== null) {
            return response()->json(['error' => 'Already clocked out'], 400);
        }

        // Update the shift record with the current time as shift_stop_time.
        $shift->update([
            'shift_stop_time' => now(),
            'is_complete'     => true,
            'status'          => 'closed',
        ]);

        return response()->json([
            'shift_stop_time' => $shift->shift_stop_time,
            'status'          => $shift->status,
        ]);
    }
}
