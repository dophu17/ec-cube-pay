<?php

namespace Plugin\StripePayment43\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailTemplateRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\StripePayment43\Entity\PaymentStatus;
use Plugin\StripePayment43\Repository\OrderRepository;
use Plugin\StripePayment43\Repository\PaymentStatusRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

/**
 * StripeからコールされるWebhookを処理するController
 */
class StripeWebhookController extends AbstractController
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var MailerInterface
     */
    protected $mailer;

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var PaymentStatusRepository
     */
    private $paymentStatusRepository;

    /**
     * @var MailTemplateRepository
     */
    protected $mailTemplateRepository;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    private $params;

    /**
     * @var \Twig\Environment
     */
    protected $twig;

    /**
     * StripeWebhookController constructor.
     *
     * @param LoggerInterface $logger
     * @param MailerInterface $mailer
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param BaseInfoRepository $baseInfoRepository
     * @param MailTemplateRepository $mailTemplateRepository
     * @param OrderStateMachine $orderStateMachine
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        LoggerInterface $logger,
        MailerInterface $mailer,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        PaymentStatusRepository $paymentStatusRepository,
        BaseInfoRepository $baseInfoRepository,
        MailTemplateRepository $mailTemplateRepository,
        OrderStateMachine $orderStateMachine,
        PurchaseFlow $shoppingPurchaseFlow,
        ParameterBagInterface $params,
        \Twig\Environment $twig,
    ) {
        $this->logger = $logger;
        $this->mailer = $mailer;
        $this->BaseInfo = $baseInfoRepository->get();
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->params = $params;
        $this->twig = $twig;
    }

    /**
     * @Route("/stripe_payment/webhook", name="stripe_payment_webhook", methods={"POST"})
     *
     * @param Request $request
     *
     * @return Response
     */
    public function handleWebhook(Request $request): Response
    {
        // リクエストペイロードを取得
        $payload = $request->getContent();

        // Stripeイベントのデコードと検証
        try {
            $this->logger->info('Stripeイベントのデコードと検証');
            $event = \Stripe\Event::constructFrom(json_decode($payload, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException|\UnexpectedValueException $e) {
            $this->logger->error('Invalid Stripe payload', ['exception' => $e]);

            return new Response('Invalid payload', Response::HTTP_BAD_REQUEST);
        }
        $this->logger->info($event->type);

        // イベントタイプごとの処理
        switch ($event->type) {
            case 'payment_intent.amount_capturable_updated':
                $this->logger->info('Stripeイベント:payment_intent.amount_capturable_updated');
                $paymentIntent = $event->data->object;
                $this->handlePaymentIntentAmountCapturableUpdated($paymentIntent);
                break;

            case 'payment_intent.succeeded':
                $this->logger->info('Stripeイベント:payment_intent.succeeded');
                $paymentIntent = $event->data->object;
                $this->handlePaymentSucceeded($paymentIntent);
                break;

            case 'payment_intent.payment_failed':
                $this->logger->info('Stripeイベント:payment_intent.payment_failed');
                $paymentIntent = $event->data->object;
                $this->handlePaymentFailed($paymentIntent);
                break;

            // 返金時の処理、または、オーソリのキャンセル時の処理(Stripe acasia/basil対応)
            case 'charge.refunded':
                $this->logger->info('Stripeイベント:charge.refunded');
                $paymentIntent = $event->data->object;
                $this->handleRefunded($paymentIntent);
                break;

            case 'charge.refund.updated':
                $this->logger->info('Stripeイベント:charge.refund.updated');
                if  ($event->data->object->status !== 'canceled') {
                    $this->logger->info('Stripeイベント:charge.refund.updated status is not canceled');
                    return new Response('Webhook received', Response::HTTP_OK);
                }
                $paymentIntent = $event->data->object;
                $this->handleRefundFailed($paymentIntent);
                break;

            case 'charge.dispute.created':
                $this->logger->info('Stripeイベント:charge.dispute.created');
                $paymentIntent = $event->data->object;
                $this->handleChargeBack($paymentIntent);
                break;

            // オーソリのキャンセル時の処理(Stripe clover以降対応)
            case 'payment_intent.canceled':
                $this->logger->info('Stripeイベント:payment_intent.canceled');
                $paymentIntent = $event->data->object;
                $this->handleCanceled($paymentIntent);
                break;

            default:
                $this->logger->warning('処理対象外のイベントです', ['type' => $event->type]);
        }

        // 成功レスポンスを返す
        return new Response('Webhook received', Response::HTTP_OK);
    }

    /**
     * オーソリ成功時の処理
     * 受注ステータスと決済ステータスを更新
     *
     * @param string $paymentIntent
     *
     * @return void
     */
    private function handlePaymentIntentAmountCapturableUpdated($paymentIntent): void
    {
        // 成功した支払い処理のロジックを記述
        $paymentInetntId = $paymentIntent->id;
        $this->logger->info('Payment Intent AmountCapturableUpdated start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        if ($Order->getOrderStatus()->getId() === OrderStatus::PENDING) {
            // 決済処理中の場合、受注ステータスを新規受付へ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
            $Order->setOrderStatus($OrderStatus);
        }

        if ($Order->getStripePaymentPaymentStatus()->getId() === PaymentStatus::OUTSTANDING) {
            // 未決済の場合決済ステータスを仮売上へ変更
            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::PROVISIONAL_SALES);
            $Order->setStripePaymentPaymentStatus($PaymentStatus);
        }

        $this->purchaseFlow->commit($Order, new PurchaseContext());

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent AmountCapturableUpdated end', ['id' => $paymentInetntId]);
    }

    /**
     * 決済成功時の処理
     * 決済ステータスを更新
     * 返金メールを送信
     *
     * @param string $paymentIntent
     *
     * @return void
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        // 成功した支払い処理のロジックを記述
        $paymentInetntId = $paymentIntent->id;
        $paymentInetntAmount = $paymentIntent->amount;
        $paymentInetntAmountReceived = $paymentIntent->amount_received;

        $this->logger->info('Payment Intent Succeeded start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        if ($Order->getOrderStatus()->getId() === OrderStatus::PENDING) {
            // 決済処理中の場合、受注ステータスを新規受付へ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
            $Order->setOrderStatus($OrderStatus);
        }

        // 決済ステータスを実売上へ変更
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::ACTUAL_SALES);
        $Order->setStripePaymentPaymentStatus($PaymentStatus);

        // キャプチャ額が相違する場合
        if ($paymentInetntAmountReceived !== $paymentInetntAmount) {
            // ショップ用メモ欄に処理が必要な旨を記載
            $Order->setNote($Order->getNote().PHP_EOL.'決済額とキャプチャ額が異なるため、差異の詳細について登録が必要です。');
            $this->partialCaptureMailToOwner($Order, $paymentInetntAmount, $paymentInetntAmountReceived);
        }

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent Succeeded end', ['id' => $paymentInetntId]);
    }

    /**
     * 決済失敗時の処理
     * 決済ステータスを更新し店舗オーナーにメールを送信する
     *
     * @param string $paymentMethod
     *
     * @return void
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        // 支払い失敗処理のロジックを記述
        $paymentInetntId = $paymentIntent->id;
        $this->logger->info('Payment Intent Failed start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        // 決済ステータスを決済失敗へ変更
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::FAILURE);
        $Order->setStripePaymentPaymentStatus($PaymentStatus);

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();

        // 店舗オーナーにメール送信
        $this->mailToOwner($Order);
        $this->logger->info('Payment Intent Failed end', ['id' => $paymentInetntId]);
    }

    /**
     * 返金時の処理またはオーソリのキャンセル(Stripe acasia/basil対応)の処理
     * 決済ステータスを更新
     * 返金メールを送信
     *
     * @param string $charge
     *
     * @return void
     */
    private function handleRefunded($charge): void
    {
        // 成功した支払い処理のロジックを記述
        $paymentInetntId = $charge->payment_intent;

        $this->logger->info('Payment Intent Refunded start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        if ($charge->captured === false) {
            // ショップ用メモ欄に返金処理が必要旨を記載
            $Order->setNote($Order->getNote().PHP_EOL.'Stripeにて支払がキャンセルされました。');

            // 決済ステータスをキャンセルへ変更
            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::CANCEL);
            $Order->setStripePaymentPaymentStatus($PaymentStatus);

            // 受注ステータスがキャンセルに更新可能であれば実施する。
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                $this->orderStateMachine->apply($Order, $OrderStatus);
            }
        }
        else
        {
            $amountCaptured = $charge->amount_captured;
            $amountRefunded = $charge->amount_refunded;

            // ショップ用メモ欄に返金処理が必要旨を記載
            $Order->setNote($Order->getNote().PHP_EOL.'Stripeにて返金が行われましたので、返金処理が必要です。');
    
            // キャプチャ額と返金額相違する場合
            if ($amountCaptured !== $amountRefunded) {
                // 決済ステータスを一部返金へ変更
                $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::PARTIAL_REFUND);
                $Order->setStripePaymentPaymentStatus($PaymentStatus);
            } else {
                // 決済ステータスを全額返金へ変更
                $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::FULL_REFUND);
                $Order->setStripePaymentPaymentStatus($PaymentStatus);
            }
    
            // 返金メール送信
            $this->refundMailToOwner($Order, $amountRefunded);
        }

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent Refunded end', ['id' => $paymentInetntId]);
    }

    /**
     * オーソリのキャンセル処理(Stripe clover以降対応)
     * 決済ステータスを更新
     * 返金メールを送信
     *
     * @param string $charge
     *
     * @return void
     */
    private function handleCanceled($charge): void {
        // 成功した支払い処理のロジックを記述
        $paymentInetntId = $charge->id;

        $this->logger->info('Payment Intent Canceled start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        // ショップ用メモ欄に返金処理が必要旨を記載
        $Order->setNote($Order->getNote() . PHP_EOL . 'Stripeにて支払がキャンセルされました。');

        // 決済ステータスをキャンセルへ変更
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::CANCEL);
        $Order->setStripePaymentPaymentStatus($PaymentStatus);

        // 受注ステータスがキャンセルに更新可能であれば実施する。
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
        if ($this->orderStateMachine->can($Order, $OrderStatus)) {
            $this->orderStateMachine->apply($Order, $OrderStatus);
        }

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent Canceled end', ['id' => $paymentInetntId]);
    }

    /**
     * 返金キャンセル時の処理
     * 決済ステータスを更新
     * $status !== 'canceled'の場合は処理を終了
     *
     * @param string $charge
     *
     * @return void
     */
    private function handleRefundFailed($charge): void
    {
        $paymentInetntId = $charge->payment_intent;

        $this->logger->info('Payment Intent RefundFauiled start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);

        // ショップ用メモ欄に返金キャンセルを記載
        $Order->setNote($Order->getNote().PHP_EOL.'Stripeにて返金キャンセルが行われました。');

        // PaymentStatus が部分返金または全額返金の場合
        if ($Order->getStripePaymentPaymentStatus()->getId() === PaymentStatus::PARTIAL_REFUND ||
            $Order->getStripePaymentPaymentStatus()->getId() === PaymentStatus::FULL_REFUND) {

            //決済ステータスを実売上へ変更
            $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::ACTUAL_SALES);
            $Order->setStripePaymentPaymentStatus($PaymentStatus);

        }

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent RefundFauiled end', ['id' => $paymentInetntId]);
    }

    /**
     * チャージバック時の処理
     * 決済ステータスを更新
     * 割引レコードを追加
     *
     * @param string $paymentIntent
     *
     * @return void
     */
    private function handleChargeBack($charge): void
    {
        // 成功した支払い処理のロジックを記述
        $paymentInetntId = $charge->payment_intent;

        $this->logger->info('Payment Intent ChargeBack start', ['id' => $paymentInetntId]);

        // dtb_order取得
        $Order = $this->orderRepository->getOrderWithStripePayment($paymentInetntId);
        $PaymentStatus = $this->paymentStatusRepository->find(PaymentStatus::CHARGEBACK);
        $Order->setStripePaymentPaymentStatus($PaymentStatus);

        // データベースに反映
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->logger->info('Payment Intent ChargeBack end', ['id' => $paymentInetntId]);
    }

    private function mailToOwner(Order $Order)
    {
        $MailTemplate = $this->mailTemplateRepository->findOneBy(['name' => '決済失敗通知']);
        $orderId = $Order->getId();

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'orderId' => $orderId,
        ]);

        $message = (new Email())
            ->subject(str_replace('orderId', $orderId, $MailTemplate->getMailSubject()))
            ->from($this->BaseInfo->getEmail01())
            ->to($this->BaseInfo->getEmail01())
            ->text($body);

        // メール送信
        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            $this->logger->info('Payment Failure Mail Failed', ['e :' => $e->getMessage()]);
        }
    }

    /**
     * 店舗オーナーに返金通知メールを送信
     *
     * @param Order $Order
     * @param string $price
     *
     * @return void
     */
    private function refundMailToOwner(Order $Order, $price)
    {
        $MailTemplate = $this->mailTemplateRepository->findOneBy(['name' => 'Stripe返金通知']);
        $orderId = $Order->getId();

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'orderId' => $orderId,
            'refundAmount' => $price,
        ]);

        $message = (new Email())
            ->subject(str_replace('orderId', $orderId, $MailTemplate->getMailSubject()))
            ->from($this->BaseInfo->getEmail01())
            ->to($this->BaseInfo->getEmail01())
            ->text($body);

        // メール送信
        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            $this->logger->info('Refund Mail Failed', ['e :' => $e->getMessage()]);
        }
    }

    /**
     * 店舗オーナーに一部キャプチャ通知メールを送信
     *
     * @param Order $Order
     * @param string $amount 決済額
     * @param string $capturedAmount キャプチャ額
     *
     * @return void
     */
    private function partialCaptureMailToOwner(Order $Order, $amount, $capturedAmount)
    {
        $MailTemplate = $this->mailTemplateRepository->findOneBy(['name' => 'Stripe一部キャプチャ通知']);
        $orderId = $Order->getId();

        $body = $this->twig->render($MailTemplate->getFileName(), [
            'orderId' => $orderId,
            'amount' => $amount,
            'capturedAmount' => $capturedAmount,
        ]);

        $message = (new Email())
            ->subject(str_replace('orderId', $orderId, $MailTemplate->getMailSubject()))
            ->from($this->BaseInfo->getEmail01())
            ->to($this->BaseInfo->getEmail01())
            ->text($body);

        // メール送信
        try {
            $this->mailer->send($message);
        } catch (TransportExceptionInterface $e) {
            $this->logger->info('Partial Capture Mail Failed', ['e :' => $e->getMessage()]);
        }
    }
}
