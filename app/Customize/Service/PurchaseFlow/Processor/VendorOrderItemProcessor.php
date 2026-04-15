<?php

namespace Customize\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

class VendorOrderItemProcessor implements ItemHolderPreprocessor
{
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($item->isProduct()) {
                $Product = $item->getProduct();
                if ($Product && $Product->getVendor()) {
                    $item->setVendorId($Product->getVendor()->getId());
                }
            }
        }
    }
}
