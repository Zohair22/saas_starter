<?php

namespace Modules\Membership\Providers;

use Illuminate\Support\Facades\Gate;
use Modules\Membership\Interfaces\Contracts\InvitationRepositoryInterface;
use Modules\Membership\Interfaces\Contracts\InvitationServiceInterface;
use Modules\Membership\Interfaces\Contracts\MembershipRepositoryInterface;
use Modules\Membership\Interfaces\Contracts\MembershipServiceInterface;
use Modules\Membership\Models\Invitation;
use Modules\Membership\Models\Membership;
use Modules\Membership\Policies\InvitationPolicy;
use Modules\Membership\Policies\MembershipPolicy;
use Modules\Membership\Repositories\InvitationRepository;
use Modules\Membership\Repositories\MembershipRepository;
use Modules\Membership\Services\InvitationService;
use Modules\Membership\Services\MembershipService;
use Nwidart\Modules\Support\ModuleServiceProvider;

class MembershipServiceProvider extends ModuleServiceProvider
{
    protected string $name = 'Membership';

    protected string $nameLower = 'membership';

    protected array $providers = [
        EventServiceProvider::class,
        RouteServiceProvider::class,
    ];

    public function register(): void
    {
        parent::register();

        $this->app->bind(MembershipRepositoryInterface::class, MembershipRepository::class);
        $this->app->bind(MembershipServiceInterface::class, MembershipService::class);
        $this->app->bind(InvitationRepositoryInterface::class, InvitationRepository::class);
        $this->app->bind(InvitationServiceInterface::class, InvitationService::class);
    }

    public function boot(): void
    {
        parent::boot();

        Gate::policy(Membership::class, MembershipPolicy::class);
        Gate::policy(Invitation::class, InvitationPolicy::class);
    }
}
