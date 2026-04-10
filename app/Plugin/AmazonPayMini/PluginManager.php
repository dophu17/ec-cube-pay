<?php

namespace Plugin\AmazonPayMini;

use Eccube\Plugin\AbstractPluginManager;
use Plugin\AmazonPayMini\Service\Method\AmazonPay;
use Psr\Container\ContainerInterface;
use Eccube\Entity\Payment;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createAmazonPayPayment($container);
    }

    private function createAmazonPayPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy(['method_class' => AmazonPay::class]);
        if ($Payment) {
            return;
        }

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('Amazon Pay');
        $Payment->setMethodClass(AmazonPay::class);

        $entityManager->persist($Payment);
        $entityManager->flush();
    }

    public function uninstall(array $meta, ContainerInterface $container): void
    {
        // Persistence logic can be added here if needed
    }
}
