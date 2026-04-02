<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\MailTemplate;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\StripePayment43\Entity\PaymentStatus;
use Plugin\StripePayment43\Service\Method\StripePayment;
use Psr\Container\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    private $pages = [
        [
            'name' => 'カード情報変更',
            'url' => 'stripe_payment_mypage_card_info',
            'filename' => 'StripePayment43/Resource/template/card_info',
        ],
    ];

    public function update(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $this->migration($entityManager->getConnection(), $meta['code']);
    }

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createStripePayment($container);
        $this->createPaymentStatuses($container);
        $this->createPages($container);
        $this->addMailTemplate($container);
    }

    private function addMailTemplate(ContainerInterface $container)
    {
        // Mailテンプレートの追加
        $entityManager = $container->get('doctrine')->getManager();
        $mailTemplateRepository = $entityManager->getRepository(MailTemplate::class);

        // 既存の「決済失敗通知」テンプレートをチェック
        $paymentFailureTemplate = $mailTemplateRepository->findOneBy(['name' => '決済失敗通知']);
        if (!$paymentFailureTemplate) {
            $paymentFailureTemplate = new MailTemplate();
            $paymentFailureTemplate->setName('決済失敗通知');
            $paymentFailureTemplate->setFileName('StripePayment43/Resource/template/Mail/payment_failure.twig');
            $paymentFailureTemplate->setMailSubject('【決済失敗通知】受注ID: orderId');

            $entityManager->persist($paymentFailureTemplate);
        }

        // 既存の「Stripe返金通知」テンプレートをチェック
        $refundTemplate = $mailTemplateRepository->findOneBy(['name' => 'Stripe返金通知']);
        if (!$refundTemplate) {
            $refundTemplate = new MailTemplate();
            $refundTemplate->setName('Stripe返金通知');
            $refundTemplate->setFileName('StripePayment43/Resource/template/Mail/refund.twig');
            $refundTemplate->setMailSubject('【Stripe返金通知】受注ID: orderId');

            $entityManager->persist($refundTemplate);
        }

        // 既存の「Stripe一部キャプチャ通知」テンプレートをチェック
        $refundTemplate = $mailTemplateRepository->findOneBy(['name' => 'Stripe一部キャプチャ通知']);
        if (!$refundTemplate) {
            $refundTemplate = new MailTemplate();
            $refundTemplate->setName('Stripe一部キャプチャ通知');
            $refundTemplate->setFileName('StripePayment43/Resource/template/Mail/partial_capture.twig');
            $refundTemplate->setMailSubject('【Stripe一部キャプチャ通知】受注ID: orderId');

            $entityManager->persist($refundTemplate);
        }

        $entityManager->flush();
    }

    private function createStripePayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $paymentRepository = $entityManager->getRepository(Payment::class);

        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;

        $Payment = $paymentRepository->findOneBy(['method_class' => StripePayment::class]);
        if ($Payment) {
            return;
        }

        $Payment = new Payment();
        $Payment->setCharge(0);
        $Payment->setSortNo($sortNo);
        $Payment->setVisible(true);
        $Payment->setMethod('Stripe決済'); // todo nameでいいんじゃないか
        $Payment->setMethodClass(StripePayment::class);

        $entityManager->persist($Payment);
        $entityManager->flush($Payment);
    }

    private function createMasterData(ContainerInterface $container, array $statuses, $class)
    {
        $entityManager = $container->get('doctrine')->getManager();
        $i = 0;
        foreach ($statuses as $id => $name) {
            $PaymentStatus = $entityManager->find($class, $id);
            if (!$PaymentStatus) {
                $PaymentStatus = new $class();
            }
            $PaymentStatus->setId($id);
            $PaymentStatus->setName($name);
            $PaymentStatus->setSortNo($i++);
            $entityManager->persist($PaymentStatus);
            $entityManager->flush($PaymentStatus);
        }
    }

    private function createPaymentStatuses(ContainerInterface $container)
    {
        $statuses = [
            PaymentStatus::OUTSTANDING => '未決済',
            PaymentStatus::PROVISIONAL_SALES => '仮売上',
            PaymentStatus::ACTUAL_SALES => '実売上',
            PaymentStatus::CANCEL => 'キャンセル',
            PaymentStatus::FAILURE => '決済失敗',
            PaymentStatus::PARTIAL_REFUND => '一部返金',
            PaymentStatus::FULL_REFUND => '全額返金',
            PaymentStatus::CHARGEBACK => 'チャージバック',
        ];
        $this->createMasterData($container, $statuses, PaymentStatus::class);
    }

    private function createPages(ContainerInterface $container)
    {
        $em = $container->get('doctrine.orm.entity_manager');

        foreach ($this->pages as $pageInfo) {
            $Page = $em->getRepository(Page::class)->findOneBy(['url' => $pageInfo['url']]);
            if (null === $Page) {
                $this->createPage($em, $pageInfo['name'], $pageInfo['url'], $pageInfo['filename']);
            }
        }
    }

    private function createPage(EntityManagerInterface $em, $name, $url, $filename)
    {
        $Page = new Page();
        $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
        $Page->setName($name);
        $Page->setUrl($url);
        $Page->setFileName($filename);

        $em->persist($Page);
        $em->flush($Page);
        $Layout = $em->find(Layout::class, Layout::DEFAULT_LAYOUT_UNDERLAYER_PAGE);
        $PageLayout = new PageLayout();
        $PageLayout->setPage($Page)
            ->setPageId($Page->getId())
            ->setLayout($Layout)
            ->setLayoutId($Layout->getId())
            ->setSortNo(0);
        $em->persist($PageLayout);
        $em->flush($PageLayout);
    }

    public function uninstall(array $meta, ContainerInterface $container): void
    {
        $em   = $container->get('doctrine.orm.entity_manager');
        $conn = $em->getConnection();

        $pluginCode = $meta['code'];

        $tableName = self::MIGRATION_TABLE_PREFIX . strtolower($pluginCode);

        $schema = $conn->createSchemaManager();
        $candidates = array_unique([$tableName]);

        foreach ($candidates as $table) {
            if ($table && $schema->tablesExist([$table])) {
                $quoted = $conn->quoteIdentifier($table);
                $conn->executeStatement("DROP TABLE {$quoted}");
            }
        }
    }
}
