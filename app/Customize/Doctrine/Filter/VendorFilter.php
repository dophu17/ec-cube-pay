<?php

namespace Customize\Doctrine\Filter;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Eccube\Entity\Order;
use Eccube\Entity\Product;

class VendorFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        if ($targetEntity->reflClass->name === Product::class) {
            return sprintf('%s.vendor_id = %s', $targetTableAlias, $this->getParameter('vendor_id'));
        }

        if ($targetEntity->reflClass->name === Order::class) {
            return sprintf('EXISTS (SELECT 1 FROM dtb_order_item WHERE order_id = %s.id AND vendor_id = %s)', $targetTableAlias, $this->getParameter('vendor_id'));
        }

        return '';
    }
}
