<?php

namespace Modules\Membership\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Membership\Models\Invitation;

class TenantInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    public function __construct(
        public readonly Invitation $invitation,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $tenantName = data_get($this->invitation, 'tenant.name', 'your workspace');
        $inviterName = data_get($this->invitation, 'inviter.name', 'A team admin');
        $acceptUrl = url('/api/v1/invitations/'.$this->invitation->token);

        return (new MailMessage)
            ->subject('You are invited to join '.$tenantName)
            ->view('membership::emails.invitation', [
                'tenantName' => $tenantName,
                'inviterName' => $inviterName,
                'role' => data_get($this->invitation->role, 'value', $this->invitation->role),
                'acceptUrl' => $acceptUrl,
                'expiresAt' => $this->invitation->expires_at,
            ]);
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900, 1800];
    }
}
