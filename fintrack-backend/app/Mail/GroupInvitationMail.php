<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use App\Models\User;
use App\Models\Group;

class GroupInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public $invitedUser;
    public $group;
    public $invitedByUser;

    /**
     * Create a new message instance.
     */
    public function __construct(User $invitedUser, Group $group, User $invitedByUser)
    {
        $this->invitedUser = $invitedUser;
        $this->group = $group;
        $this->invitedByUser = $invitedByUser;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "{$this->invitedByUser->name} invited you to join \"{$this->group->name}\" on FinTrack!",
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.group_invitation',
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
