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

namespace Plugin\StripePayment43\Form\Extension;

use Doctrine\ORM\EntityRepository;
use Eccube\Form\Type\Admin\SearchOrderType;
use Plugin\StripePayment43\Entity\PaymentStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * 注文手続き画面のFormを拡張し、カード入力フォームを追加する.
 * 支払い方法に応じてエクステンションを作成する.
 */
class SearchPaymentStatusExtention extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('PaymentStatuses', EntityType::class, [
            'class' => PaymentStatus::class,
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('p')
                    ->orderBy('p.id', 'ASC');
            },
            'label' => 'stripe_payment.admin.payment_status.search_condition_payment_status',
            'multiple' => true,
            'expanded' => true,
        ]);
    }

    public function getExtendedType()
    {
        return SearchOrderType::class;
    }

    public static function getExtendedTypes(): iterable
    {
        return [SearchOrderType::class];
    }
}
