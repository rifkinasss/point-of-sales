<?php

namespace App\Http\Controllers;

use App\Services\BrowserTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BrowserTimezoneController extends Controller
{
    public function __invoke(Request $request, BrowserTimezone $browserTimezone): JsonResponse
    {
        $timezone = $request->string('timezone')->toString();
        $updated = $browserTimezone->current() !== $timezone;

        if ($browserTimezone->isValid($timezone)) {
            $browserTimezone->set($timezone);
        } else {
            $updated = false;
        }

        return response()->json(['updated' => $updated]);
    }
}
