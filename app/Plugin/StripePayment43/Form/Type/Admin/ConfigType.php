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

namespace Plugin\StripePayment43\Form\Type\Admin;

use Plugin\StripePayment43\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('public_key', TextType::class, [
                'required' => true,
            ])
            ->add('restricted_private_key', TextType::class, [
                'required' => true,
            ])
            ->add('environments', ChoiceType::class, [
                'choices' => [
                    'テスト環境' => 1,
                    '本番環境' => 2,
                ],
                'expanded' => true,
                'multiple' => false,
                'label' => 'Environment',
                'required' => false,
            ])
            ->add('separate_capture', CheckboxType::class, [
                'label' => '有効',
                'required' => false,
            ])
            ->add('receive_notification', CheckboxType::class, [
                'label' => '有効',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
