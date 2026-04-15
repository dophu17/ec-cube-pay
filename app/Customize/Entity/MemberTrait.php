<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Member")
 */
trait MemberTrait
{
    /**
     * @ORM\Column(type="boolean", options={"default":false})
     */
    private $is_vendor = false;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $vendor_name;

    public function isVendor(): bool
    {
        return (bool) $this->is_vendor;
    }

    public function setIsVendor(bool $is_vendor): self
    {
        $this->is_vendor = $is_vendor;
        return $this;
    }

    public function getVendorName(): ?string
    {
        return $this->vendor_name;
    }

    public function setVendorName(?string $vendor_name): self
    {
        $this->vendor_name = $vendor_name;
        return $this;
    }
}
