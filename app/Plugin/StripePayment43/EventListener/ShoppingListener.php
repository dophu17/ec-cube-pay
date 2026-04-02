<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43\EventListener;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Eccube\Event\TemplateEvent;
use Eccube\Exception\ShoppingException;
use Plugin\StripePayment43\Repository\ConfigRepository;
use Plugin\StripePayment43\Service\StripeService;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ShoppingListener implements EventSubscriberInterface
{
    /**
     * @var EntityManager
     */
    protected EntityManager $em;

    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    /**
     * @var StripeService
     */
    protected StripeService $stripeService;

    public function __construct(EntityManager $em, ConfigRepository $configRepository, StripeService $stripeService)
    {
        $this->configRepository = $configRepository;
        $this->em = $em;
        $this->stripeService = $stripeService;
    }

    /**
     * cartからshoppingに遷移時に起動
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopping/index.twig' => 'onShoppingEvent',
        ];
    }

    /**
     * TemplateEvent $event
     *
     * @throws ApiErrorException
     * @throws ORMException
     * @throws ShoppingException
     */
    public function onShoppingEvent(TemplateEvent $event): void
    {
        // Stripeの顧客IDがあるかチェック
        $Order = $event->getParameter('Order');
        $Customer = $Order->getCustomer();

        // ゲストの場合、Stripe顧客の作成しない
        if ($Customer !== null) {
            // Stripe顧客IDがない場合は作成する
            if ($Customer->getStripeCustomerId() == '') {
                try {
                    $objStripeCustomer = $this->stripeService->createStripeCustomer($Customer);
                } catch (\Exception $e) {
                    error_log('Create stripe customer is failed: '.$e->getMessage());
                    $event->setParameter('customerSessionErrorMsg', 'Create stripe customer is failed: '.$e->getMessage());

                    return;
                }

                // Stripe顧客IDを保存
                $Customer->setStripeCustomerId($objStripeCustomer->id);
                $this->em->persist($Customer);
                $this->em->flush();
            }

            // customerSessionを作成
            $sessionData = [
                'components' => [
                    'payment_element' => [
                        'enabled' => true,
                        'features' => [
                            'payment_method_redisplay' => 'enabled',
                            'payment_method_remove' => 'enabled',
                            'payment_method_save' => 'enabled',
                            'payment_method_save_usage' => 'on_session',
                        ],
                    ],
                ],
                'customer' => $Customer->getStripeCustomerId(),
            ];

            try {
                // CustomerSessionを作成する
                $customerSession = $this->stripeService->createCustomerSession($sessionData);
                $event->setParameter('customerSession', $customerSession->client_secret);
            } catch (\Exception $e) {
                error_log('Create stripe customer session is failed: '.$e->getMessage());
                $event->setParameter('customerSessionErrorMsg', 'Create stripe customer session is failed:'.$e->getMessage());

                return;
            }
        } else {
            $event->setParameter('customerSession', null);
        }
        $event->setParameter('customerSessionErrorMsg', null);
    }
}
