<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class FileController extends Controller
{
    public function serve(string $path): Response
    {
        if (str_contains($path, '..') || ! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        return response(
            Storage::disk('public')->get($path),
            200,
            ['Content-Type' => Storage::disk('public')->mimeType($path)]
        );
    }
}
