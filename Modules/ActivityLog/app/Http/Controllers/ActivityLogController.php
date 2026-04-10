<?php

namespace Modules\ActivityLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\ActivityLog\Models\ActivityLog;
use Modules\ActivityLog\Transformers\ActivityLogResource;

class ActivityLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', ActivityLog::class);

        $logs = ActivityLog::query()
            ->with('actor:id,name,email')
            ->latest()
            ->paginate(50);

        return ActivityLogResource::collection($logs);
    }
}
