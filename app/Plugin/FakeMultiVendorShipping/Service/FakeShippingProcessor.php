<?php

namespace Plugin\FakeMultiVendorShipping\Service;

use Psr\Log\LoggerInterface;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Entity\Master\OrderItemType;

class FakeShippingProcessor implements ItemHolderPreprocessor
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        // This processor runs with priority 750, 
        // while the original DeliveryFeePreprocessor runs with priority 800.
        // Therefore, the delivery fee item should already be present in the itemHolder.

        $vendors = [];
        $this->logger->info("[FakeMultiVendorShipping] Starting shipping recalculation. Items: " . count($itemHolder->getItems()));

        foreach ($itemHolder->getItems() as $item) {
            if (!$item->isProduct()) continue;

            $productClass = $item->getProductClass();
            if (!$productClass) continue;

            // In EC-CUBE 4, Trait methods added via @EntityExtension are available on Proxy objects.
            $vendor = null;
            $fee = 0;
            
            try {
                if (method_exists($productClass, 'getVendorName')) {
                    $vendor = $productClass->getVendorName();
                }
                if (method_exists($productClass, 'getShippingFee')) {
                    $fee = $productClass->getShippingFee();
                }
            } catch (\Exception $e) {
                $this->logger->error("[FakeMultiVendorShipping] Error reading vendor/fee: " . $e->getMessage());
            }
            
            $this->logger->info("[FakeMultiVendorShipping] Product ID " . $productClass->getId() . " -> Vendor: " . ($vendor ?? 'NULL') . ", Fee: " . $fee);

            if ($vendor) {
                if (!isset($vendors[$vendor])) {
                    $vendors[$vendor] = $fee;
                } else {
                    $vendors[$vendor] = max($vendors[$vendor], $fee);
                }
            } else {
                if (!isset($vendors['default'])) {
                    $vendors['default'] = $fee;
                } else {
                    $vendors['default'] = max($vendors['default'], $fee);
                }
            }
        }

        $shippingTotal = array_sum($vendors);
        $this->logger->info("[FakeMultiVendorShipping] Calculated Total Shipping: " . $shippingTotal);

        // Find the delivery fee items and update their price.
        // DeliveryFeePreprocessor adds items with OrderItemType::DELIVERY_FEE.
        $found = false;
        foreach ($itemHolder->getItems() as $item) {
            if ($item->getOrderItemType() && $item->getOrderItemType()->getId() == OrderItemType::DELIVERY_FEE) {
                $this->logger->info("[FakeMultiVendorShipping] Updating DeliveryFee item price from " . $item->getPrice() . " to " . $shippingTotal);
                $item->setPrice($shippingTotal);
                $found = true;
            }
        }

        if (!$found) {
            $this->logger->warning("[FakeMultiVendorShipping] No DeliveryFee item found to update!");
        }
    }
}
