<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * Stripe顧客ID
     *
     * @var string|null
     *
     * @ORM\Column(type="string", nullable=true)
     */
    public ?string $stripe_customer_id = null;

    public function setStripeCustomerId($stripe_customer_id): void
    {
        $this->stripe_customer_id = $stripe_customer_id;
    }

    public function getStripeCustomerId(): ?string
    {
        return $this->stripe_customer_id;
    }
}
