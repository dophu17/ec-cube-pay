<?php

namespace Plugin\AmazonPayMini\Form\Type\Admin;

use Plugin\AmazonPayMini\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('merchant_id', TextType::class, [
                'label' => 'Merchant ID',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('store_id', TextType::class, [
                'label' => 'Store ID',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('client_id', TextType::class, [
                'label' => 'Client ID',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('public_key_id', TextType::class, [
                'label' => 'Public Key ID',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('private_key', TextareaType::class, [
                'label' => 'Private Key',
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
                'attr' => [
                    'rows' => 10,
                ],
            ])
            ->add('sandbox', CheckboxType::class, [
                'label' => 'Sandbox Mode',
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
