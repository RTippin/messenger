<?php

namespace RTippin\Messenger;

class Definitions
{
    /**
     * All thread types and meanings
     */
    const Thread = [
        1 => 'PRIVATE',
        2 => 'GROUP'
    ];

    /**
     * All message types and meanings
     */
    const Message = [
        0 => 'MESSAGE',
        1 => 'IMAGE_MESSAGE',
        2 => 'DOCUMENT_MESSAGE',
        88 => 'PARTICIPANT_JOINED_WITH_INVITE',
        90 => 'VIDEO_CALL',
        91 => 'GROUP_AVATAR_CHANGED',
        92 => 'THREAD_ARCHIVED',
        93 => 'GROUP_CREATED',
        94 => 'GROUP_RENAMED',
        95 => 'DEMOTED_ADMIN',
        96 => 'PROMOTED_ADMIN',
        97 => 'PARTICIPANT_LEFT_GROUP',
        98 => 'PARTICIPANT_REMOVED',
        99 => 'PARTICIPANTS_ADDED'
    ];

    /**
     * All call types and meanings
     */
    const Call  = [
        1 => 'VIDEO'
    ];

    /**
     * All friend types and meanings
     */
    const FriendStatus  = [
        0 => 'NOT_FRIEND',
        1 => 'FRIEND',
        2 => 'SENT_FRIEND_REQUEST',
        3 => 'PENDING_FRIEND_REQUEST'
    ];

    /**
     * All friend types and meanings
     */
    const OnlineStatus  = [
        0 => 'OFFLINE',
        1 => 'ONLINE',
        2 => 'AWAY'
    ];

    /**
     * All default group thread avatars
     */
    const DefaultGroupAvatars = [
        '1.png',
        '2.png',
        '3.png',
        '4.png',
        '5.png',
    ];

    /**
     * Default thread attributes
     */
    const DefaultThread = [
        'type' => 1,
        'subject' => null,
        'image' => null,
        'calling' => true,
        'invitations' => false,
        'add_participants' => false,
        'messaging' => true,
        'knocks' => true,
        'lockout' => false
    ];

    /**
     * Default participant
     */
    const DefaultParticipant = [
        'add_participants' => false,
        'manage_invites' => false,
        'admin' => false,
        'deleted_at' => null,
        'pending' => false,
        'start_calls' => false,
        'send_knocks' => false,
        'send_messages' => true
    ];

    /**
     * Default admin participant
     */
    const DefaultAdminParticipant = [
        'add_participants' => true,
        'manage_invites' => true,
        'admin' => true,
        'deleted_at' => null,
        'pending' => false,
        'start_calls' => true,
        'send_knocks' => true,
        'send_messages' => true
    ];

    /**
     * Invite expires at post meanings
     */
    const InviteExpires = [
        0 => 'NEVER',
        1 => 'THIRTY_MINUTES',
        2 => 'ONE_HOUR',
        3 => 'SIX_HOURS',
        4 => 'TWELVE_HOURS',
        5 => 'ONE_DAY',
        6 => 'ONE_WEEK',
        7 => 'TWO_WEEKS',
        8 => 'ONE_MONTH'
    ];
}