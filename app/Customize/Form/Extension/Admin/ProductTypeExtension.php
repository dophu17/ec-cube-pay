<?php

namespace Customize\Form\Extension\Admin;

use Eccube\Entity\Member;
use Eccube\Form\Type\Admin\ProductType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityRepository;

class ProductTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('Vendor', EntityType::class, [
            'class' => Member::class,
            'label' => 'Vendor',
            'required' => false,
            'choice_label' => function (Member $member) {
                return $member->getVendorName() ?: $member->getName();
            },
            'placeholder' => 'No Vendor (Independent)',
            'query_builder' => function (EntityRepository $er) {
                return $er->createQueryBuilder('m')
                    ->where('m.is_vendor = :is_vendor')
                    ->setParameter('is_vendor', true)
                    ->orderBy('m.sort_no', 'ASC');
            },
            'eccube_form_options' => [
                'auto_render' => false,
            ],
        ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield ProductType::class;
    }
}
