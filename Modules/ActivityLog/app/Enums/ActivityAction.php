<?php

namespace Modules\ActivityLog\Enums;

enum ActivityAction: string
{
    case ProjectCreated = 'project.created';
    case ProjectUpdated = 'project.updated';
    case ProjectDeleted = 'project.deleted';
    case TaskCreated = 'task.created';
    case TaskUpdated = 'task.updated';
    case TaskCompleted = 'task.completed';
}
