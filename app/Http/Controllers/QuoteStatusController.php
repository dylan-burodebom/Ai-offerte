<?php

namespace App\Http\Controllers;

use App\Models\Quote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuoteStatusController extends Controller
{
    public function update(Request $request, Quote $quote): JsonResponse
    {
        abort_if($quote->user_id !== Auth::id(), 403);

        $nieuweStatus = $request->input('status');

        $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Quote::STATUSSEN)],
            'reden'  => [
                in_array($nieuweStatus, ['gewonnen', 'verloren']) ? 'required' : 'nullable',
                'string',
                'max:500',
            ],
        ], [
            'reden.required' => 'Geef een reden op voor deze statuswijziging.',
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
                'user_id'       => Auth::id(),
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
