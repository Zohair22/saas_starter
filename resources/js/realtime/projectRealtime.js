const projectChannelName = (tenantId, projectId) => `tenant.${tenantId}.project.${projectId}`;

export const joinProjectRealtime = ({
    tenantId,
    projectId,
    onHere,
    onJoining,
    onLeaving,
    onTaskCreated,
    onTaskUpdated,
    onTaskCompleted,
}) => {
    const channel = window.Echo.join(projectChannelName(tenantId, projectId));

    if (onHere) {
        channel.here(onHere);
    }

    if (onJoining) {
        channel.joining(onJoining);
    }

    if (onLeaving) {
        channel.leaving(onLeaving);
    }

    if (onTaskCreated) {
        channel.listen('.task.created', (event) => onTaskCreated(event));
    }

    if (onTaskUpdated) {
        channel.listen('.task.updated', (event) => onTaskUpdated(event));
    }

    if (onTaskCompleted) {
        channel.listen('.task.completed', (event) => onTaskCompleted(event));
    }

    return channel;
};

export const leaveProjectRealtime = (tenantId, projectId) => {
    window.Echo.leave(projectChannelName(tenantId, projectId));
};
