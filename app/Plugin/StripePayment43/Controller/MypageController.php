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

namespace Plugin\StripePayment43\Controller;

use Eccube\Controller\AbstractController;
use Plugin\StripePayment43\Repository\ConfigRepository;
use Plugin\StripePayment43\Service\StripeService;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Stripe\StripeClient;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

class MypageController extends AbstractController
{
    /**
     * @var CsrfTokenManagerInterface
     */
    protected CsrfTokenManagerInterface $csrfTokenManager;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    /**
     * @var StripeService
     */
    protected StripeService $stripeService;

    /**
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param LoggerInterface $logger
     * @param ConfigRepository $configRepository
     * @param StripeService $stripeService
     * */
    public function __construct(
        CsrfTokenManagerInterface $csrfTokenManager,
        LoggerInterface $logger,
        ConfigRepository $configRepository,
        StripeService $stripeService,
    ) {
        $this->csrfTokenManager = $csrfTokenManager;
        $this->logger = $logger;
        $this->configRepository = $configRepository;
        $this->stripeService = $stripeService;
    }

    /**
     * Stripe クライアントを初期化し、設定を確認
     */
    protected function initializeStripe(): StripeClient
    {
        $config = $this->configRepository->get();
        $restricted_private_key = $config->getRestrictedPrivateKey();

        if (empty($restricted_private_key)) {
            throw new \RuntimeException('Stripeのキーが設定されていません。');
        }

        return new StripeClient($restricted_private_key);
    }

    /**
     * Stripe Customer ID を取得または作成
     */
    protected function getStripeCustomerId(StripeClient $stripe): string
    {
        // Stripeの顧客IDがあるかチェック
        $Customer = $this->getUser();

        // ない場合は作成
        if ($Customer->getStripeCustomerId() == '') {
            try {
                $objStripeCustomer = $stripe->customers->create([
                    'name' => $Customer->getName01().$Customer->getName02(),
                    'email' => $Customer->getEmail(),
                    'metadata' => [
                        'EC-CUBE Customer ID' => $Customer->getId(),
                    ],
                ]);
            } catch (\Exception $e) {
                $this->logger->error('Stripe顧客の作成中にエラーが発生しました: '.$e->getMessage(), ['exception' => $e]);
                throw new \Exception('Stripe顧客の作成中にエラーが発生しました。');
            }

            // Stripe顧客IDを保存
            $Customer->setStripeCustomerId($objStripeCustomer->id);
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            return $objStripeCustomer->id;
        }

        return $Customer->getStripeCustomerId();
    }

    /**
     * 共通エラーレスポンスを返す
     */
    protected function handleException(\Exception $e, string $message, int $statusCode = 500): JsonResponse
    {
        $this->logger->error($message.': '.$e->getMessage(), ['exception' => $e]);

        return new JsonResponse(['error' => true, 'message' => $message], $statusCode);
    }

    /**
     * @Route("/mypage/stripe_payment_card_info", name="stripe_payment_mypage_card_info", methods={"GET"})
     *
     * @Template("@StripePayment43/card_info.twig")
     */
    public function index(Request $request)
    {
        if (!$this->stripeService->isAvailable) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => 'Stripe決済は現在準備できておりません。',
                    ],
                ], 500);
        }

        $stripe = $this->initializeStripe();
        $stripeCustomerId = $this->getStripeCustomerId($stripe);

        // フォーム作成
        try {
            $form = $this->formFactory->createBuilder()
                ->add('_token', HiddenType::class, [
                    'data' => $this->csrfTokenManager->getToken('stripe_payment')->getValue(),
                ])
                ->getForm();
            $form->handleRequest($request);

            // 登録済みカード取得（linkと紐づているカードは除外する）
            $paymentMethods = $stripe->paymentMethods->all([
                'customer' => $stripeCustomerId,
                'type' => 'card',
            ]);
            $filteredCards = array_filter($paymentMethods->data, fn ($paymentMethod) => !isset($paymentMethod->card->wallet) || $paymentMethod->card->wallet->type !== 'link');

            return [
                'form' => $form->createView(),
                'stripe_public_key' => $this->configRepository->get()->getPublicKey(),
                'existingCards' => $filteredCards,
            ];
        } catch (\Exception $e) {
            $this->logger->error('カード情報の取得中にエラーが発生しました。: '.$e->getMessage(), ['exception' => $e]);
            throw new \Exception('カード情報の取得中にエラーが発生しました。');
        }
    }

    /**
     * @Route("/mypage/stripe_payment_get_client_secret", name="stripe_get_client_secret", methods={"GET"})
     */
    public function getClientSecret(): JsonResponse
    {
        try {
            $stripe = $this->initializeStripe();
            $stripeCustomerId = $this->getStripeCustomerId($stripe);

            $setupIntent = $stripe->setupIntents->create([
                'customer' => $stripeCustomerId,
                'payment_method_types' => ['card'],
            ]);

            return new JsonResponse(['client_secret' => $setupIntent->client_secret]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'SetupIntentの作成中にエラーが発生しました。');
        }
    }

    /**
     * @Route("/mypage/stripe_payment_card_delete/{paymentMethodId}", name="stripe_payment_mypage_card_delete", methods={"POST"})
     */
    public function deleteCard(Request $request, string $paymentMethodId): JsonResponse
    {
        try {
            $stripe = $this->initializeStripe();
            $stripe->paymentMethods->detach($paymentMethodId);

            return new JsonResponse(['message' => 'カードが正常に削除されました。']);
        } catch (\Exception $e) {
            return $this->handleException($e, 'カード削除中にエラーが発生しました。');
        }
    }
}
