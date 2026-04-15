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

        $search = '{# エンティティ拡張の自動出力 #}';

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

        $newSource = str_replace($search, $html . "\n" . $search, $source);
        $event->setSource($newSource);
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin/Product/product.twig' => 'onAdminProductEdit',
        ];
    }
}
