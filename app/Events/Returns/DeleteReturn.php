<?php

namespace App\Events\Returns;

use App\Models\Returns;

class DeleteReturn
{
    /**
     * @var Returns
     */
    protected $DeleteReturn;

    public function __construct(Returns $DeleteReturn)
    {
        $this->DeleteReturn = $DeleteReturn;
    }

    /**
     * @return Returns
     */
    public function getDeleteReturn()
    {
        return $this->DeleteReturn;
    }
}
