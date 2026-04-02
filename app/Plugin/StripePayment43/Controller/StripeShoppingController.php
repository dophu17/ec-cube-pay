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

use Eccube\Controller\AbstractShoppingController;
use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\StripePayment43\Repository\ConfigRepository;
use Plugin\StripePayment43\Service\StripeService;
// use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * PaymentIntentを作成する
 */
class StripeShoppingController extends AbstractShoppingController
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var CartService
     */
    protected CartService $cartService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var OrderHelper
     */
    protected OrderHelper $orderHelper;

    /**
     * @var OrderRepository
     */
    protected OrderRepository $orderRepository;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $serviceContainer;

    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    /**
     * @var StripeService
     */
    protected StripeService $stripeService;

    protected RateLimiterFactory $shoppingCheckoutIpLimiter;

    protected RateLimiterFactory $shoppingCheckoutCustomerLimiter;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @param LoggerInterface $logger
     * @param CartService $cartService
     * @param MailService $mailService
     * @param OrderHelper $orderHelper
     * @param OrderRepository $orderRepository
     * @param ContainerInterface $serviceContainer
     * @param ConfigRepository $configRepository
     * @param StripeService $stripeService
     * @param PurchaseFlow $shoppingPurchaseFlow
     * */
    public function __construct(
        LoggerInterface $logger,
        CartService $cartService,
        MailService $mailService,
        OrderHelper $orderHelper,
        OrderRepository $orderRepository,
        ContainerInterface $serviceContainer,
        ConfigRepository $configRepository,
        StripeService $stripeService,
        RateLimiterFactory $shoppingCheckoutIpLimiter,
        RateLimiterFactory $shoppingCheckoutCustomerLimiter,
        PurchaseFlow $shoppingPurchaseFlow,
    ) {
        $this->logger = $logger;
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderHelper = $orderHelper;
        $this->orderRepository = $orderRepository;
        $this->configRepository = $configRepository;
        $this->serviceContainer = $serviceContainer;
        $this->stripeService = $stripeService;
        $this->shoppingCheckoutIpLimiter = $shoppingCheckoutIpLimiter;
        $this->shoppingCheckoutCustomerLimiter = $shoppingCheckoutCustomerLimiter;
        $this->purchaseFlow = $shoppingPurchaseFlow;
    }

    /**
     * @Route("/get_stripe_public_key", name="get_stripe_public_key")
     *
     * @return JsonResponse
     */
    public function getStripePublicKey(): JsonResponse
    {
        try {
            $pk = $this->stripeService->getStripePublicKey();

            return new JsonResponse([
                'public_key' => $pk,
            ], 200);
        } catch (\Exception $e) {
            return new JsonResponse(
                [
                    'error' => [
                        'message' => '公開鍵の取得中にエラーが発生しました。',
                        'details' => $e->getMessage(),
                    ],
                ], 500);
        }
    }

    /**
     * @Route("/submit_stripe_shopping", name="submit_stripe_shopping", methods={"POST"})
     *
     * @return JsonResponse
     */
    public function submitStripeShopping(Request $request)
    {
        // src/Eccube/Controller/ShoppingController.php checkout　より移植
        // ログイン状態のチェック.
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('shopping_login');
        }

        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文処理] 購入処理中の受注が存在しません.', [$preOrderId]);

            return $this->redirectToRoute('shopping_error');
        }

        // フォームの生成.
        $form = $this->createForm(OrderType::class, $Order, [
            // 確認画面から注文処理へ遷移する場合は, Orderエンティティで値を引き回すためフォーム項目の定義をスキップする.
            'skip_add_form' => true,
        ]);

        $form->handleRequest($request);

        log_info('[注文処理] 注文処理を開始します.', [$Order->getId()]);

        try {
            /*
             * 集計処理
             */
            log_info('[注文処理] 集計処理を開始します.', [$Order->getId()]);
            $response = $this->executePurchaseFlow($Order);
            $this->entityManager->flush();

            if ($response) {
                return $response;
            }

            log_info('[注文完了] IPベースのスロットリングを実行します.');
            $ipLimiter = $this->shoppingCheckoutIpLimiter->create($request->getClientIp());
            if (!$ipLimiter->consume()->isAccepted()) {
                log_info('[注文完了] 試行回数制限を超過しました(IPベース)');
                throw new TooManyRequestsHttpException();
            }

            $Customer = $this->getUser();
            if ($Customer instanceof Customer) {
                log_info('[注文完了] 会員ベースのスロットリングを実行します.');
                $customerLimiter = $this->shoppingCheckoutCustomerLimiter->create($Customer->getId());
                if (!$customerLimiter->consume()->isAccepted()) {
                    log_info('[注文完了] 試行回数制限を超過しました(会員ベース)');
                    throw new TooManyRequestsHttpException();
                }
            }

            log_info('[注文処理] PaymentMethodを取得します.', [$Order->getPayment()->getMethodClass()]);
            $paymentMethod = $this->createPaymentMethod($Order, $form);

            /*
             * 決済実行(前処理)
             */
            log_info('[注文処理] PaymentMethod::applyを実行します.');
            if ($response = $this->executeApply($paymentMethod)) {
                return $response;
            }

            /*
             * 決済実行
             *
             * PaymentMethod::checkoutでは決済処理が行われ, 正常に処理出来た場合はPurchaseFlow::commitがコールされます.
             */
            log_info('[注文処理] PaymentMethod::checkoutを実行します.');
            if ($response = $this->executeCheckout($paymentMethod)) {
                return $response;
            }

            $this->entityManager->flush();

            log_info('[注文処理] 注文処理が完了しました.', [$Order->getId()]);
        } catch (ShoppingException $e) {
            log_error('[注文処理] 購入エラーが発生しました.', [$e->getMessage()]);

            $this->entityManager->rollback();

            $this->addError($e->getMessage());

            return $this->redirectToRoute('shopping_error');
        } catch (\Exception $e) {
            log_error('[注文処理] 予期しないエラーが発生しました.', [$e->getMessage()]);

            // $this->entityManager->rollback(); FIXME ユニットテストで There is no active transaction エラーになってしまう

            $this->addError('front.shopping.system_error');

            return $this->redirectToRoute('shopping_error');
        }
        // src/Eccube/Controller/ShoppingController.php checkout　より移植

        // CustomerId取得
        $stripe_payment_token = $Order->getStripePaymentToken();

        // error
        if (is_null($stripe_payment_token)) {
            // トークンがない場合はエラーを返す
            return new JsonResponse(
                [
                    'error' => ['message' => 'confirmation token is not define'],
                ], 500);
        }

        // 絶対URLを生成
        $returnUrl = $this->generateUrl('payment_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);

        $intentData = [
            'amount' => intval($Order->getPaymentTotal()),
            'currency' => 'jpy',
            'confirmation_token' => $stripe_payment_token, // トークンを明確に指定
            'confirm' => true, // confirmation_tokenがある場合は、追加する必要がある
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never', // リダイレクトを無効化
            ], 'metadata' => [
                'EC-CUBE Order ID' => $Order->getOrderNo(),
            ],
            'return_url' => $returnUrl,
            'payment_method_options' => [
                'wechat_pay' => [
                    'client' => 'web',
                ],
            ],
        ];

        try {
            if ($Customer != null) {
                $intentData['customer'] = $Customer->getStripeCustomerId();
            }

            // 決済種別の取得
            $options = [];
            $confirmationToken = $this->stripeService->retrieveConfirmationTokens($stripe_payment_token, $options);
            $payment_type = $confirmationToken['payment_method_preview']['type'];

            // config
            $config = $this->configRepository->get();
            if ($payment_type == 'card') {
                $intentData['payment_method_options'] = [
                    'card' => [
                        'request_three_d_secure' => 'any', // 3Dセキュアを可能であれば要求
                    ],
                ];
            }

            // カードまたはリンク支払いの場合
            if (in_array($payment_type, ['card', 'link'], true)) {
                // capture_method が manual の場合にのみ追加
                if ($config->getSeparateCapture()) {
                    $intentData['payment_method_options'][$payment_type]['capture_method'] = 'manual';
                }
            }

            // payment intentsの作成
            $paymentIntent = $this->stripeService->createPaymentIntents($intentData);
        } catch (\Exception $e) {
            // エラーメッセージの整形
            return new JsonResponse([
                'error' => [
                    'message' => '支払いの作成中にエラーが発生しました。',
                    'details' => $e->getMessage(), // 必要なら開発者向けの詳細情報を含める
                ],
            ], 500);
        }

        // dtb_orderにstripe決済IDを保存する
        $Order->setStripePaymentId($paymentIntent->id);
        $this->entityManager->persist($Order);
        $this->entityManager->flush();

        // 受注IDをセッションにセット
        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());

        return new JsonResponse([
            'payment_intent' => $paymentIntent,
        ], 200);
    }

    /**
     * @Route("/payment/callback", name="payment_callback", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function handlePaymentCallback(Request $request)
    {
        log_debug('handlePaymentCallback $request:', [$request]);

        $redirectStatus = $request->query->get('redirect_status');
        $paymentIntentId = $request->query->get('payment_intent');

        log_debug('redirect_status:', [$redirectStatus]);
        log_debug('paymentIntentId:', [$paymentIntentId]);

        if ($redirectStatus === 'succeeded') {
            log_info('[注文処理] Stripeリダイレクト結果が正常', [$paymentIntentId]);
            $this->processPaymentSuccess();

            return $this->redirectToRoute('shopping_complete');
        } else {
            log_info('[注文処理] Stripeリダイレクト結果がエラー', [$paymentIntentId]);

            return $this->redirectToRoute('shopping_error');
        }
    }

    // src/Eccube/Controller/ShoppingController.php checkout　より移植
    /**
     * PaymentMethodをコンテナから取得する.
     *
     * @param Order $Order
     * @param FormInterface $form
     *
     * @return PaymentMethodInterface
     */
    private function createPaymentMethod(Order $Order, FormInterface $form)
    {
        $PaymentMethod = $this->serviceContainer->get($Order->getPayment()->getMethodClass());
        $PaymentMethod->setOrder($Order);
        $PaymentMethod->setFormType($form);

        return $PaymentMethod;
    }

    /**
     * PaymentMethod::applyを実行する.
     *
     * @param PaymentMethodInterface $paymentMethod
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response
     */
    protected function executeApply(PaymentMethodInterface $paymentMethod)
    {
        $dispatcher = $paymentMethod->apply(); // 決済処理中.

        // リンク式決済のように他のサイトへ遷移する場合などは, dispatcherに処理を移譲する.
        if ($dispatcher instanceof PaymentDispatcher) {
            $response = $dispatcher->getResponse();
            $this->entityManager->flush();

            // dispatcherがresponseを保持している場合はresponseを返す
            if ($response instanceof Response && ($response->isRedirection() || $response->isSuccessful())) {
                log_info('[注文処理] PaymentMethod::applyが指定したレスポンスを表示します.');

                return $response;
            }

            // forwardすることも可能.
            if ($dispatcher->isForward()) {
                log_info('[注文処理] PaymentMethod::applyによりForwardします.',
                    [$dispatcher->getRoute(), $dispatcher->getPathParameters(), $dispatcher->getQueryParameters()]);

                return $this->forwardToRoute($dispatcher->getRoute(), $dispatcher->getPathParameters(),
                    $dispatcher->getQueryParameters());
            } else {
                log_info('[注文処理] PaymentMethod::applyによりリダイレクトします.',
                    [$dispatcher->getRoute(), $dispatcher->getPathParameters(), $dispatcher->getQueryParameters()]);

                return $this->redirectToRoute($dispatcher->getRoute(),
                    array_merge($dispatcher->getPathParameters(), $dispatcher->getQueryParameters()));
            }
        }
    }

    /**
     * PaymentMethod::checkoutを実行する.
     *
     * @param PaymentMethodInterface $paymentMethod
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|Response|null
     */
    protected function executeCheckout(PaymentMethodInterface $paymentMethod)
    {
        $PaymentResult = $paymentMethod->checkout();
        $response = $PaymentResult->getResponse();
        // PaymentResultがresponseを保持している場合はresponseを返す
        if ($response instanceof Response && ($response->isRedirection() || $response->isSuccessful())) {
            $this->entityManager->flush();
            log_info('[注文処理] PaymentMethod::checkoutが指定したレスポンスを表示します.');

            return $response;
        }

        // エラー時はロールバックして購入エラーとする.
        if (!$PaymentResult->isSuccess()) {
            $this->entityManager->rollback();
            foreach ($PaymentResult->getErrors() as $error) {
                $this->addError($error);
            }

            log_info('[注文処理] PaymentMethod::checkoutのエラーのため, 購入エラー画面へ遷移します.', [$PaymentResult->getErrors()]);

            return $this->redirectToRoute('shopping_error');
        }

        return null;
    }
    // src/Eccube/Controller/ShoppingController.php checkout　より移植

    /**
     * @Route("/payment/success", name="payment_success", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function paymentSuccess()
    {
        $this->processPaymentSuccess();

        return new JsonResponse([], 200);
    }

    protected function processPaymentSuccess()
    {
        // 受注を取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);
        $Order = $this->orderRepository->find($orderId);

        // カート削除
        log_info('[注文処理] カートをクリアします.', [$Order->getId()]);
        $this->cartService->clear();

        // メール送信
        log_info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();

        log_info('[注文処理] 注文処理が完了しました. 購入完了画面へ遷移します.', [$Order->getId()]);
    }

    /**
     * @Route("/payment/error", name="payment_error", methods={"GET"})
     *
     * @return JsonResponse
     */
    public function paymentError()
    {
        // 受注を取得
        $orderId = $this->session->get(OrderHelper::SESSION_ORDER_ID);
        $Order = $this->orderRepository->find($orderId);

        $this->purchaseFlow->rollback($Order, new PurchaseContext());

        return new JsonResponse([], 200);
    }
}
