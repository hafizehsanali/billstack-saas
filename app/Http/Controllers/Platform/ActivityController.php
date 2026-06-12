<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Request $request): View
    {
        $action = $request->string('action')->toString();

        return view('platform.activities.index', [
            'activities' => PlatformActivityLog::with(['actor', 'tenant'])
                ->when($action, fn ($query) => $query->where('action', $action))
                ->latest()
                ->paginate(25)
                ->withQueryString(),
            'actions' => PlatformActivityLog::query()
                ->distinct()
                ->orderBy('action')
                ->pluck('action'),
            'selectedAction' => $action,
        ]);
    }
}
