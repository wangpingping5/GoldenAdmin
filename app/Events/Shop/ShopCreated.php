<?php

namespace App\Events\Shop;

use App\Models\Shop;

class ShopCreated
{
    /**
     * @var User
     */
    protected $createdShop;

    public function __construct(Shop $createdShop)
    {
        $this->createdShop = $createdShop;
    }

    /**
     * @return User
     */
    public function getCreatedShop()
    {
        return $this->createdShop;
    }
}
