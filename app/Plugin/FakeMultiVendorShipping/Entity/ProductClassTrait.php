<?php

namespace Plugin\FakeMultiVendorShipping\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\ProductClass")
 */
trait ProductClassTrait
{
    /**
     * @ORM\Column(type="string", nullable=true)
     */
    private $vendor_name;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $shipping_fee;

    public function getVendorName()
    {
        return $this->vendor_name;
    }

    public function setVendorName($vendor_name)
    {
        $this->vendor_name = $vendor_name;
    }

    public function getShippingFee()
    {
        return $this->shipping_fee ?? 0;
    }

    public function setShippingFee($shipping_fee)
    {
        $this->shipping_fee = $shipping_fee;
    }
}
