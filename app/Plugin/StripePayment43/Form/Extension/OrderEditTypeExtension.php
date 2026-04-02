<?php

namespace Plugin\StripePayment43\Form\Extension;

use Eccube\Form\Type\Admin\OrderType;
use Plugin\StripePayment43\Entity\PaymentStatus;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;

class OrderEditTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('StripePaymentPaymentStatus', EntityType::class, [
                'required' => false,
                'class' => PaymentStatus::class,
                'choice_label' => function (PaymentStatus $PaymentStatus) {
                    return $PaymentStatus->getName();
                },
                'placeholder' => false,
                'query_builder' => function ($er) {
                    return $er->createQueryBuilder('p')
                        ->orderBy('p.id', 'ASC');
                },
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield OrderType::class;
    }
}
