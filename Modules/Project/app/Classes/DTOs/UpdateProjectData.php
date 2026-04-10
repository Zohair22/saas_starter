<?php

namespace Modules\Project\Classes\DTOs;

use Modules\Project\Http\Requests\UpdateProjectRequest;

class UpdateProjectData
{
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $description,
    ) {}

    public static function fromRequest(UpdateProjectRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            description: $request->validated('description'),
        );
    }
}
