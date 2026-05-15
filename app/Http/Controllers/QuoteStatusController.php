<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteStatusController extends Controller
{
    public function update(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->user_id !== auth()->id(), 403);

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Quote::STATUSSEN)],
            'reden'  => ['nullable', 'string', 'max:500'],
        ]);

        $nieuweStatus = $request->status;

        if (! $quote->kanNaarStatus($nieuweStatus)) {
            return response()->json([
                'message' => "Overgang van '{$quote->status}' naar '{$nieuweStatus}' is niet toegestaan.",
            ], 422);
        }

        DB::transaction(function () use ($quote, $nieuweStatus, $request) {
            $oudeStatus = $quote->status;

            $updateData = ['status' => $nieuweStatus];

            if ($nieuweStatus === 'verzonden' && ! $quote->verzonden_op) {
                $updateData['verzonden_op'] = now();
            }

            $quote->update($updateData);

            $quote->statusHistory()->create([
                'user_id'       => auth()->id(),
                'oude_status'   => $oudeStatus,
                'nieuwe_status' => $nieuweStatus,
                'reden'         => $request->reden,
                'datum'         => now(),
            ]);
        });

        $quote->load('statusHistory.user');

        return response()->json([
            'status'         => $quote->status,
            'verzonden_op'   => $quote->verzonden_op?->toISOString(),
            'status_history' => $quote->statusHistory,
        ]);
    }
}
