<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $spaIndex = public_path('index.html');
    if (file_exists($spaIndex)) {
        return response()->file($spaIndex, [
            'Content-Type' => 'text/html',
        ]);
    }
    return view('welcome');
});

// In production, serve the SPA for any route that doesn't match an API or file.
// The built frontend lives in public/index.html after running `npm run build` in frontend/.
Route::fallback(function () {
    $spaIndex = public_path('index.html');
    if (file_exists($spaIndex)) {
        return response()->file($spaIndex, [
            'Content-Type' => 'text/html',
        ]);
    }

    return response()->json(['message' => 'Not Found'], 404);
});
