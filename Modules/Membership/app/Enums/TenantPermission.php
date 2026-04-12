<?php

namespace Modules\Membership\Enums;

enum TenantPermission: string
{
    case ViewMemberships = 'view_memberships';
    case ManageMemberships = 'manage_memberships';
    case ManageInvitations = 'manage_invitations';
    case ManageProjects = 'manage_projects';
    case ManageBilling = 'manage_billing';
    case ManageTenantSettings = 'manage_tenant_settings';
}
