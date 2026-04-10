<?php

namespace Plugin\AmazonPayMini\EventListener;

use Eccube\Event\TemplateEvent;
use Plugin\AmazonPayMini\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutEventListener implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            'cart/index.twig' => 'onRenderCart',
            'shopping/index.twig' => 'onRenderShopping',
        ];
    }

    /**
     * Inject Amazon Pay button into Cart page
     *
     * @param TemplateEvent $event
     */
    public function onRenderCart(TemplateEvent $event)
    {
        $Config = $this->configRepository->get();
        if (!$Config || !$Config->getMerchantId()) {
            return;
        }

        // In a real plugin, we would use a more specific hook or block
        // For this mini version, we'll append it to the end of the page body or a safe area
        $event->addSnippet('@AmazonPayMini/default/AmazonPay/button.twig');
    }

    /**
     * Inject Amazon Pay button into Shopping page
     *
     * @param TemplateEvent $event
     */
    public function onRenderShopping(TemplateEvent $event)
    {
        $Config = $this->configRepository->get();
        if (!$Config || !$Config->getMerchantId()) {
            return;
        }

        $event->addSnippet('@AmazonPayMini/default/AmazonPay/button.twig');
    }
}
