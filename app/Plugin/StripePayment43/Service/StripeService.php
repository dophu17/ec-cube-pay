<?php

namespace Plugin\StripePayment43\Service;

use Plugin\StripePayment43\Repository\ConfigRepository;
use Psr\Log\LoggerInterface;
use Stripe\ConfirmationToken;
use Stripe\Customer;
use Stripe\CustomerSession;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;

class StripeService
{
    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $configRepository;

    /**
     * @var StripeClient
     */
    protected StripeClient $stripe;

    /**
     * @var bool
     */
    public bool $isAvailable;

    /**
     * @throws \Exception
     */
    public function __construct(LoggerInterface $logger, ConfigRepository $configRepository, bool $isAvailable = false)
    {
        $this->logger = $logger;
        $this->configRepository = $configRepository;

        try {
            // StripeClientを初期化
            $stripeRestrictedPrivateKey = $this->getStripeRestrictedPrivateKey();
            $this->stripe = new StripeClient($stripeRestrictedPrivateKey);
            $this->isAvailable = true;
        } catch (\Exception $e) {
            error_log('StripeService initialization failed: '.$e->getMessage());
            $this->isAvailable = false;
        }
    }

    /**
     * Stripeのパブリックキーを取得
     *
     * @return ?string
     *
     * @throws \Exception
     */
    public function getStripePublicKey(): ?string
    {
        $config = $this->configRepository->get();
        if (!$config) {
            throw new \Exception('Stripe config is not defined');
        }

        $stripePublicKey = $config->getPublicKey();

        if (empty($stripePublicKey)) {
            throw new \Exception('Stripe public key is not defined');
        }

        return $stripePublicKey;
    }

    /**
     * Stripeのプライベートキーを取得
     *
     * @return ?string
     *
     * @throws \Exception
     */
    public function getStripeRestrictedPrivateKey(): ?string
    {
        $config = $this->configRepository->get();
        if (!$config) {
            throw new \Exception('Stripe config is not defined');
        }

        $stripeRestrictedPrivateKey = $config->getRestrictedPrivateKey();

        if (empty($stripeRestrictedPrivateKey)) {
            throw new \Exception('Stripe restricted/private key is not defined');
        }

        return $stripeRestrictedPrivateKey;
    }

    /**
     * StripeClientを取得
     *
     * @return StripeClient
     */
    public function getStripeClient(): StripeClient
    {
        return $this->stripe;
    }

    /**
     * Customer Sessionを作成
     * ユーザが保存したカード情報などが使えるようになる
     * See https://docs.stripe.com/api/customers/create
     *
     * @param array $sessionData
     *
     * @return CustomerSession
     *
     * @throws ApiErrorException
     * @throws \Exception
     */
    public function createCustomerSession(array $sessionData): CustomerSession
    {
        try {
            return $this->stripe->customerSessions->create($sessionData);
        } catch (\Exception $e) {
            // StripeAPI 例外ハンドリング
            $this->logger->error('Stripe API Error: '.$e->getMessage(), [
                'exception' => $e,
                'context' => 'Failed to create customer session',
            ]);
            throw new \Exception('Failed to create customer session: '.$e->getMessage());
        }
    }

    /**
     * Stripe顧客を作成
     * See https://docs.stripe.com/api/customers/create
     *
     * @param $Customer
     *
     * @return Customer
     *
     * @throws \Exception
     */
    public function createStripeCustomer($Customer): Customer
    {
        try {
            // create customer
            return $this->stripe->customers->create([
                'name' => $Customer->getName01().' '.$Customer->getName02(),
                'email' => $Customer->getEmail(),
                'metadata' => [
                    'EC-CUBE Customer ID' => $Customer->getId(),
                ],
            ]);
        } catch (ApiErrorException $e) {
            // StripeAPI 例外ハンドリング
            $this->logger->error('Stripe API Error: '.$e->getMessage(), [
                'exception' => $e,
                'context' => 'Failed to create customer',
            ]);

            // カスタム例外をスロー
            throw new \Exception('Failed to create customer : '.$e->getMessage());
        }
    }

    /**
     * Payment Intentを作成
     * See https://docs.stripe.com/api/payment_intents/create
     *
     * @throws \Exception
     */
    public function createPaymentIntents($intentData): \Stripe\PaymentIntent
    {
        try {
            return $this->stripe->paymentIntents->create($intentData);
        } catch (ApiErrorException $e) {
            // StripeAPI 例外ハンドリング
            $this->logger->error('Stripe API Error: '.$e->getMessage(), [
                'exception' => $e,
                'context' => 'Failed to create payment intents',
            ]);

            // カスタム例外をスロー
            throw new \Exception('Failed to create payment intents : '.$e->getMessage());
        }
    }

    /**
     * Confirmation Token IDからConfirmation Tokenを取得
     * See https://docs.stripe.com/api/confirmation_tokens/retrieve
     *
     * @param string $confirmation_token
     * @param array $options
     *
     * @return ConfirmationToken
     *
     * @throws \Exception
     */
    public function retrieveConfirmationTokens(string $confirmation_token, array $options): ConfirmationToken
    {
        try {
            return $this->stripe->confirmationTokens->retrieve($confirmation_token, $options);
        } catch (ApiErrorException $e) {
            // StripeAPI 例外ハンドリング
            $this->logger->error('Stripe API Error: '.$e->getMessage(), [
                'exception' => $e,
                'context' => 'Failed to retrieve confirmation tokens',
            ]);

            // カスタム例外をスロー
            throw new \Exception('Failed to retrieve confirmation tokens : '.$e->getMessage());
        }
    }
}
