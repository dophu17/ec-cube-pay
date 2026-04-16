<?php

namespace Customize\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\CartItem")
 */
trait CartItemTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $vendor_id;

    public function getVendorId(): ?int
    {
        return $this->vendor_id;
    }

    public function setVendorId(?int $vendor_id): self
    {
        $this->vendor_id = $vendor_id;

        return $this;
    }
}
