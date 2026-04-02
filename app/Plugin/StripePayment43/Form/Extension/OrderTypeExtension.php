<?php

namespace Plugin\StripePayment43\Form\Extension;

use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class OrderTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // stripe_payment_token フィールドを追加
        $builder->add('stripe_payment_token', HiddenType::class, [
            'required' => false,
        ]);
        // stripe_payment_method_name フィールドを追加
        $builder->add('stripe_payment_method_name', HiddenType::class, [
            'required' => false,
        ]);
        // stripe_payment_card_no_last4 フィールドを追加
        $builder->add('stripe_payment_card_no_last4', HiddenType::class, [
            'required' => false,
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield OrderType::class;
    }
}
