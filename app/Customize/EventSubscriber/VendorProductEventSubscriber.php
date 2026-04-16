<?php

namespace Customize\EventSubscriber;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VendorProductEventSubscriber implements EventSubscriberInterface
{
    /**
     * @param TemplateEvent $event
     */
    public function onAdminProductEdit(TemplateEvent $event)
    {
        $source = $event->getSource();

        $html = '
                                <div class="row">
                                    <div class="col-3">
                                        <span>Vendor</span>
                                    </div>
                                    <div class="col mb-2">
                                        <div>
                                            {{ form_widget(form.Vendor) }}
                                            {{ form_errors(form.Vendor) }}
                                        </div>
                                    </div>
                                </div>';

        // Try replacing the standard entity extension comment
        if (strpos($source, '{# エンティティ拡張の自動出力 #}') !== false) {
            $newSource = str_replace('{# エンティティ拡張の自動出力 #}', $html . "\n" . '{# エンティティ拡張の自動出力 #}', $source);
        } else {
            // Fallback: Insert before product_class popup or at the end of a known section
            $searchFallback = '{{ form_widget(form.class.tax_rate) }}'; // Near line 538 in product.twig
            if (strpos($source, $searchFallback) !== false) {
                 $newSource = str_replace($searchFallback, $searchFallback . "\n" . '</div></div></div>' . $html . '<div><div><div>', $source);
            } else {
                 $newSource = $source . $html;
            }
        }
        
        $event->setSource($newSource);
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin/Product/product.twig' => 'onAdminProductEdit',
            '@admin/Product/product.twig' => 'onAdminProductEdit',
            'admin/product/product.twig' => 'onAdminProductEdit',
            '@admin/product/product.twig' => 'onAdminProductEdit',
        ];
    }
}
