<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class PersonalEngagementReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;
    public ?Carbon $latestActivityAt;
    public Carbon $inactiveSince;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, ?Carbon $latestActivityAt, Carbon $inactiveSince)
    {
        $this->user = $user;
        $this->latestActivityAt = $latestActivityAt;
        $this->inactiveSince = $inactiveSince;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'A quick nudge to keep your FinTrack budget on track',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.personal_engagement_reminder',
            with: [
                'user' => $this->user,
                'latestActivityAt' => $this->latestActivityAt,
                'inactiveSince' => $this->inactiveSince,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
