<?php

namespace Customize\EventSubscriber;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class VendorMemberEventSubscriber implements EventSubscriberInterface
{
    /**
     * @param TemplateEvent $event
     */
    public function onAdminMemberEdit(TemplateEvent $event)
    {
        $source = $event->getSource();

        $search = '{# エンティティ拡張の自動出力 #}';

        $html = '
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <span>Vendor Flag</span>
                                    </div>
                                    <div class="col">
                                        {{ form_widget(form.is_vendor) }}
                                        {{ form_errors(form.is_vendor) }}
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-3">
                                        <span>Vendor Name</span>
                                    </div>
                                    <div class="col">
                                        {{ form_widget(form.vendor_name) }}
                                        {{ form_errors(form.vendor_name) }}
                                    </div>
                                </div>';

        $newSource = str_replace($search, $html . "\n" . $search, $source);
        $event->setSource($newSource);
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin/Setting/System/member_edit.twig' => 'onAdminMemberEdit',
        ];
    }
}
