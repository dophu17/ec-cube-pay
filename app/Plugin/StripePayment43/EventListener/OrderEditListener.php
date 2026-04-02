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

class OrderEditListener implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    public function __construct(EntityManagerInterface $em, ConfigRepository $configRepository, TranslatorInterface $translator)
    {
        $this->configRepository = $configRepository;
        $this->translator = $translator;
        $this->em = $em;
    }

    /**
     * 受注編集に遷移時に起動
     */
    public static function getSubscribedEvents(): array
    {
        return [
            '@admin/Order/edit.twig' => 'onOrderEditEvent',
        ];
    }

    /**
     * TemplateEvent $event
     *
     * @throws ApiErrorException
     * @throws ORMException
     */
    public function onOrderEditEvent(TemplateEvent $event): JsonResponse
    {
        // Stripeの決済ID取得
        $Order = $event->getParameter('Order');
        $stripe_payment_id = ($Order && $Order->getId()) ? $Order->getStripePaymentId() : null;

        // 環境設定を取得
        $config = $this->configRepository->get();
        if (!$config) {
            $event->setParameter('environments', 1);
        } else {
            // 環境設定が存在する場合
            $environments = $config->getEnvironments();
            $event->setParameter('environments', $environments);
        }

        $event->setParameter('stripe_payment_id', $stripe_payment_id);

        return new JsonResponse([], 200);
    }
}
