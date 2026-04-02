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

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class StripePaymentEvent implements EventSubscriberInterface
{
    /**
     * リッスンしたいサブスクライバのイベント名の配列を返します。
     * 配列のキーはイベント名、値は以下のどれかをしてします。
     * - 呼び出すメソッド名
     * - 呼び出すメソッド名と優先度の配列
     * - 呼び出すメソッド名と優先度の配列の配列
     * 優先度を省略した場合は0
     *
     * 例：
     * - array('eventName' => 'methodName')
     * - array('eventName' => array('methodName', $priority))
     * - array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'onShoppingIndexTwig',
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            '@admin/Order/index.twig' => 'onAdminOrderTwig',
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            '@admin/Customer/edit.twig' => 'onAdminCustomerEditTwig',
            'Mypage/navi.twig' => 'onMypageNaviTwig',
            'Mypage/history.twig' => 'onMypageHistoryTwig',
        ];
    }

    public function onShoppingIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/stripe_payment.twig');
    }

    public function onShoppingConfirmTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/stripe_payment_confirm.twig');
    }

    public function onMypageHistoryTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/stripe_mypage_history.twig');
    }

    public function onAdminOrderTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/admin/order_index.twig');
    }

    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/admin/order_edit.twig');
    }

    public function onAdminCustomerEditTwig(TemplateEvent $event)
    {
        $event->addSnippet('@StripePayment43/admin/customer_edit.twig');
    }

    public function onMypageNaviTwig(TemplateEvent $event)
    {
        $source = $event->getSource();
        $source .= file_get_contents(__DIR__.'/Resource/template/navi.twig');
        $event->setSource($source);
    }
}
