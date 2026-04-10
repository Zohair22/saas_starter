<?php

namespace Modules\AuditLog\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Modules\AuditLog\Models\AuditLog;
use Modules\AuditLog\Transformers\AuditLogResource;

class AuditLogController extends Controller
{
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = AuditLog::query()
            ->with('actor:id,name,email')
            ->latest('id')
            ->paginate(50);

        return AuditLogResource::collection($logs);
    }
}
