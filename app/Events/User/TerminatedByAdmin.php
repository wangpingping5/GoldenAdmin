<?php

namespace App\Events\User;

use App\Models\User;

class TerminatedByAdmin
{
    /**
     * @var User
     */
    protected $terminatedUser;

    public function __construct(User $terminatedUser)
    {
        $this->terminatedUser = $terminatedUser;
    }

    /**
     * @return User
     */
    public function getterminatedUser()
    {
        return $this->terminatedUser;
    }
}
