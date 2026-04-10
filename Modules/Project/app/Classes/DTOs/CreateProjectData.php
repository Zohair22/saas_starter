<?php

namespace Modules\Project\Classes\DTOs;

use Modules\Project\Http\Requests\StoreProjectRequest;

class CreateProjectData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly int $createdBy,
        public readonly string $name,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(StoreProjectRequest $request): self
    {
        return new self(
            tenantId: (int) data_get($request->attributes->get('tenant'), 'id'),
            createdBy: (int) $request->user()->id,
            name: $request->validated('name'),
            description: $request->validated('description'),
        );
    }
}
