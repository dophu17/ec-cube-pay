<?php

namespace Plugin\StripePayment43\Repository;

use Doctrine\Persistence\ManagerRegistry as RegistryInterface;
use Eccube\Entity\Order;
use Eccube\Repository\AbstractRepository;

class OrderRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * StripeのオーダーIDからOrderを取得する
     *
     * @param string $stripeOrderId
     */
    public function getOrderWithStripePayment($stripePaymentId = 1)
    {
        return $this->createQueryBuilder('o')
            ->where('o.stripe_payment_id = :StripePaymentId')
            ->setParameter('StripePaymentId', $stripePaymentId)
            ->getQuery()
            ->getSingleResult();
    }
}
