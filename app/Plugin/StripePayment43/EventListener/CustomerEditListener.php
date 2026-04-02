<?php

namespace Plugin\StripePayment43\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Exception\ORMException;
use Eccube\Event\TemplateEvent;
use Plugin\StripePayment43\Repository\ConfigRepository;
use Stripe\Exception\ApiErrorException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\Translation\TranslatorInterface;

class CustomerEditListener implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    public function __construct(EntityManagerInterface $em, ConfigRepository $configRepository, TranslatorInterface $translator)
    {
        $this->configRepository = $configRepository;
        $this->translator = $translator;
        $this->em = $em;
    }

    /**
     * 会員編集に遷移時に起動
     */
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Customer/edit.twig' => 'onCustmoerEditEvent',
        ];
    }

    /**
     * TemplateEvent $event
     *
     * @throws ApiErrorException
     * @throws ORMException
     */
    public function onCustmoerEditEvent(TemplateEvent $event): JsonResponse
    {
        // Stripeの顧客ID取得
        $Customer = $event->getParameter('Customer');
        $stripe_customer_id = $Customer->getStripeCustomerId();

        // 環境設定を取得
        $config = $this->configRepository->get();
        if (!$config) {
            $event->setParameter('environments', 1);
        } else {
            // 環境設定が存在する場合
            $environments = $config->getEnvironments();
            $event->setParameter('environments', $environments);
        }

        $event->setParameter('stripe_customer_id', $stripe_customer_id);

        return new JsonResponse([], 200);
    }
}
