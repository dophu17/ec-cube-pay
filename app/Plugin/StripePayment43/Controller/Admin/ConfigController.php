<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43\Controller\Admin;

use Eccube\Controller\AbstractController;
use Plugin\StripePayment43\Form\Type\Admin\ConfigType;
use Plugin\StripePayment43\Repository\ConfigRepository;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\AuthenticationException;
use Stripe\StripeClient;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ConfigController extends AbstractController
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ConfigRepository $configRepository, LoggerInterface $logger, UrlGeneratorInterface $router)
    {
        $this->configRepository = $configRepository;
        $this->logger = $logger;
        $this->router = $router;
    }

    /**
     * @Route("/%eccube_admin_route%/stripe_payment/config", name="stripe_payment43_admin_config")
     *
     * @Template("@StripePayment43/admin/config.twig")
     */
    public function index(Request $request)
    {
        $Config = $this->configRepository->get();
        $form = $this->createForm(ConfigType::class, $Config);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isSuccess = true;
            $Config = $form->getData();
            $restrictedPrivateKey = $Config->getRestrictedPrivateKey() ?? null;

            // 制限付きキーが入力されている場合
            if (!empty($restrictedPrivateKey)) {
                try{
                    $stripe = new StripeClient($restrictedPrivateKey);
                    $receiveNotification = $Config->getReceiveNotification();
                    $webhookEndpointId = $Config->getWebhookEndpointId();

                    // WebHookの削除が必要な場合
                    if ($receiveNotification === false && !empty($webhookEndpointId)) {
                        // Webhookの削除
                        $stripe->webhookEndpoints->delete($webhookEndpointId, []);
                        $Config->setWebhookEndpointId(null);

                    }else{
                        // WebHookの登録が必要な場合
                        if ($this->requireWebhookCreate($receiveNotification, $webhookEndpointId, $stripe)) {
                                $webhookEndpoint = $stripe->webhookEndpoints->create([
                                    'enabled_events' => ['payment_intent.succeeded', 'payment_intent.amount_capturable_updated', 'payment_intent.payment_failed', 'charge.refunded', 'charge.dispute.created', 'charge.refund.updated', 'payment_intent.canceled'],
                                    'url' => $this->getWebhookURL(),
                                ]);

                                $Config->setWebhookEndpointId($webhookEndpoint->id);
                        }
                    }

                } catch (AuthenticationException $e) {
                    $this->logger->error('制限付きキー誤り', ['exception' => $e]);
                    $this->addError('stripe_payment.admin.save.create_webhook.failed', 'admin');
                    $isSuccess = false;
                }
            }

            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            if($isSuccess){
                $this->addSuccess('stripe_payment.admin.save.success', 'admin');
            }

            return $this->redirectToRoute('stripe_payment43_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * webhookの登録が必要か判定する
     *
     * @return bool 必要:true 以外:false
     */
    private function requireWebhookCreate($receiveNotification, $webhookEndpointId, $stripe): bool
    {
        return $receiveNotification && !$this->isWebhookEndpointExists($webhookEndpointId, $stripe, $this->getWebhookURL());
    }

    /**
     * Stripe側にwebhookのEndpointが登録されているか判定する
     *
     * @return bool 登録されている:true 以外:false
     */
    private function isWebhookEndpointExists($webhookEndpointId, $stripe, string $url): bool
    {
        // webhookEndpointIdが空の場合
        if (empty($webhookEndpointId)) {
            $this->logger->info('webhookEndpointIdが空');

            return false;
        }

        try {
            // Webhook Endpointの取得を試みる
            $webhookEndpoint = $stripe->webhookEndpoints->retrieve($webhookEndpointId, []);

            // Webhook Endpointが存在し、URLが一致する場合
            if ($webhookEndpoint->url === $url) {
                $this->logger->info('Webhook Endpointが存在し、URLが一致する');

                return true;
            }

            // Webhook Endpointが存在するがURLが一致しない場合
            $this->logger->info('Webhook Endpointが存在するがURLが一致しない');

            return false;
        } catch (InvalidRequestException $e) {
            // Webhook Endpointが存在しない場合
            return false;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * webhookのURLを取得
     *
     * @return string URL
     */
    private function getWebhookURL(): string
    {
        // ルート名を使用して URL を生成
        return $this->router->generate('stripe_payment_webhook', [], UrlGeneratorInterface::ABSOLUTE_URL);
    }
}
