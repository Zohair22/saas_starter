import { useEffect, useRef, useState } from 'react';
import { joinProjectRealtime, leaveProjectRealtime } from '../realtime/projectRealtime';

/**
 * Lifecycle-safe hook that joins a project presence channel and forwards
 * task broadcast events to the provided callback handlers.
 *
 * The channel is joined once when both `tenantId` and `projectId` are truthy.
 * Callbacks are stored in refs, so updating them never re-triggers a reconnect.
 * The channel is left automatically on unmount or when either ID changes.
 *
 * @param {{
 *   tenantId: string|number|null,
 *   projectId: string|number|null,
 *   onTaskCreated?: (event: object) => void,
 *   onTaskUpdated?: (event: object) => void,
 *   onTaskCompleted?: (event: object) => void,
 *   onHere?: (members: object[]) => void,
 *   onJoining?: (member: object) => void,
 *   onLeaving?: (member: object) => void,
 * }} options
 *
 * @returns {{ isConnected: boolean }}
 */
export default function useProjectRealtime({
    tenantId,
    projectId,
    onTaskCreated,
    onTaskUpdated,
    onTaskCompleted,
    onHere,
    onJoining,
    onLeaving,
} = {}) {
    const [isConnected, setIsConnected] = useState(false);

    // Store all callbacks in refs so updates never cause a channel reconnect.
    const onTaskCreatedRef = useRef(onTaskCreated);
    const onTaskUpdatedRef = useRef(onTaskUpdated);
    const onTaskCompletedRef = useRef(onTaskCompleted);
    const onHereRef = useRef(onHere);
    const onJoiningRef = useRef(onJoining);
    const onLeavingRef = useRef(onLeaving);

    useEffect(() => { onTaskCreatedRef.current = onTaskCreated; }, [onTaskCreated]);
    useEffect(() => { onTaskUpdatedRef.current = onTaskUpdated; }, [onTaskUpdated]);
    useEffect(() => { onTaskCompletedRef.current = onTaskCompleted; }, [onTaskCompleted]);
    useEffect(() => { onHereRef.current = onHere; }, [onHere]);
    useEffect(() => { onJoiningRef.current = onJoining; }, [onJoining]);
    useEffect(() => { onLeavingRef.current = onLeaving; }, [onLeaving]);

    useEffect(() => {
        if (!tenantId || !projectId) {
            return;
        }

        joinProjectRealtime({
            tenantId,
            projectId,
            onHere: (members) => onHereRef.current?.(members),
            onJoining: (member) => onJoiningRef.current?.(member),
            onLeaving: (member) => onLeavingRef.current?.(member),
            onTaskCreated: (event) => onTaskCreatedRef.current?.(event),
            onTaskUpdated: (event) => onTaskUpdatedRef.current?.(event),
            onTaskCompleted: (event) => onTaskCompletedRef.current?.(event),
        });

        setIsConnected(true);

        return () => {
            leaveProjectRealtime(tenantId, projectId);
            setIsConnected(false);
        };
    }, [tenantId, projectId]);

    return { isConnected };
}
