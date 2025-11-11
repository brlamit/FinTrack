<?php

namespace App\Mail;

use App\Models\Group;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GroupEngagementReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public Group $group;
    public Group $leaderGroup;
    public float $percentOfLeader;
    public float $leaderTotal;
    public float $groupTotal;

    /**
     * Create a new message instance.
     */
    public function __construct(Group $group, Group $leaderGroup, float $percentOfLeader, float $leaderTotal, float $groupTotal)
    {
        $this->group = $group;
        $this->leaderGroup = $leaderGroup;
        $this->percentOfLeader = $percentOfLeader;
        $this->leaderTotal = $leaderTotal;
        $this->groupTotal = $groupTotal;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Give ' . $this->group->name . ' a quick engagement boost',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.group_engagement_reminder',
            with: [
                'group' => $this->group,
                'leaderGroup' => $this->leaderGroup,
                'percentOfLeader' => $this->percentOfLeader,
                'leaderTotal' => $this->leaderTotal,
                'groupTotal' => $this->groupTotal,
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
