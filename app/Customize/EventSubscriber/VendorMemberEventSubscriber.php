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

        // Try replacing the standard entity extension comment
        if (strpos($source, '{# エンティティ拡張の自動出力 #}') !== false) {
            $newSource = str_replace('{# エンティティ拡張の自動出力 #}', $html . "\n" . '{# エンティティ拡張の自動出力 #}', $source);
        } else {
            // Fallback: Insert after two_factor_auth_enabled
            $searchFallback = '{{ form_widget(form.two_factor_auth_enabled) }}';
            if (strpos($source, $searchFallback) !== false) {
                // Find the end of the div row
                $newSource = str_replace($searchFallback, $searchFallback . "\n" . '</div></div>' . $html . '<div><div>', $source);
                // This is getting messy. Let\'s just append at the end of the file if all else fails.
            } else {
                 $newSource = $source . $html;
            }
        }
        
        $event->setSource($newSource);
    }

    public static function getSubscribedEvents()
    {
        return [
            'admin/Setting/System/member_edit.twig' => 'onAdminMemberEdit',
            '@admin/Setting/System/member_edit.twig' => 'onAdminMemberEdit',
            'admin/setting/system/member_edit.twig' => 'onAdminMemberEdit',
            '@admin/setting/system/member_edit.twig' => 'onAdminMemberEdit',
        ];
    }
}
