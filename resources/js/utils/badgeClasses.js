export const roleBadgeClass = (role) => {
    if (role === 'owner') {
        return 'bg-slate-900 text-white';
    }

    if (role === 'admin') {
        return 'bg-amber-100 text-amber-800';
    }

    return 'bg-slate-100 text-slate-700';
};

export const statusBadgeClass = (status) => {
    if (status === 'done') {
        return 'bg-emerald-100 text-emerald-800';
    }

    if (status === 'in_progress') {
        return 'bg-sky-100 text-sky-800';
    }

    return 'bg-slate-100 text-slate-700';
};

export const priorityBadgeClass = (priority) => {
    if (priority === 'high') {
        return 'bg-rose-100 text-rose-800';
    }

    if (priority === 'medium') {
        return 'bg-amber-100 text-amber-800';
    }

    return 'bg-slate-100 text-slate-700';
};
