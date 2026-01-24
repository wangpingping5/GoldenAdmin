<?php

namespace App\Support\Enum;

class UserStatus
{
    const UNCONFIRMED = 'Unconfirmed';
    const ACTIVE = 'Active';
    const BANNED = 'Banned';
    const DELETED = 'Deleted';
    const REJECTED = 'Rejected';
    const JOIN = 'Join';

    public static function lists()
    {
        return [
            self::ACTIVE => trans(self::ACTIVE),
            self::BANNED => trans(self::BANNED),
            self::DELETED => trans(self::DELETED),
            self::REJECTED => trans(self::REJECTED),
            self::JOIN => trans(self::JOIN),
            self::UNCONFIRMED => trans(self::UNCONFIRMED)
        ];
    }

    public static function bgclass()
    {
        return
        [
            self::ACTIVE => 'bg-primary',
            self::BANNED => 'bg-danger',
            self::DELETED => 'bg-warning',
            self::REJECTED => 'bg-primary',
            self::JOIN => 'bg-info',
            self::UNCONFIRMED => 'bg-default'
        ];
    }


}
