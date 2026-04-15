<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @ORM\Column(type="integer", nullable=true, options={"unsigned":true})
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
