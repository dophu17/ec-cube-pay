<?php

namespace Customize\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Eccube\Entity\Member;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var Member
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumn(name="vendor_id", referencedColumnName="id", nullable=true)
     */
    private $Vendor;

    public function getVendor(): ?Member
    {
        return $this->Vendor;
    }

    public function setVendor(?Member $Vendor): self
    {
        $this->Vendor = $Vendor;
        return $this;
    }
}
