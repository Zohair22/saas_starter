<?php

namespace Modules\Tenant\Classes\DTOs;

use Modules\Tenant\Http\Requests\StoreTenantRequest;

class CreateTenantData
{
    public function __construct(
        public readonly string $name,
        public readonly string $slug,
        public readonly int $ownerId,
    ) {}

    public static function fromRequest(StoreTenantRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            slug: $request->validated('slug'),
            ownerId: $request->user()->id,
        );
    }
}
