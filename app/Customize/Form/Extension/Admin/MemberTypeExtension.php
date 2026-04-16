<?php

namespace Customize\Form\Extension\Admin;

use Eccube\Form\Type\Admin\MemberType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

class MemberTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('is_vendor', CheckboxType::class, [
                'label' => 'Vendor Flag',
                'required' => false,
                'eccube_form_options' => [
                    'auto_render' => false,
                ],
            ])
            ->add('vendor_name', TextType::class, [
                'label' => 'Vendor Name',
                'required' => false,
                'constraints' => [
                    new Length(['max' => 255]),
                ],
                'eccube_form_options' => [
                    'auto_render' => false,
                ],
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield MemberType::class;
    }
}
