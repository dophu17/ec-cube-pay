<?php

namespace Plugin\AmazonPayMini\Service;

use Amazon\Pay\V2\Client;
use Plugin\AmazonPayMini\Repository\ConfigRepository;
use Psr\Log\LoggerInterface;

class AmazonPayService
{
    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(ConfigRepository $configRepository, LoggerInterface $logger)
    {
        $this->configRepository = $configRepository;
        $this->logger = $logger;
    }

    /**
     * Get Amazon Pay V2 Client
     *
     * @return Client|null
     */
    public function getClient()
    {
        $Config = $this->configRepository->get();
        if (!$Config || !$Config->getMerchantId()) {
            return null;
        }

        try {
            $amazonPayConfig = [
                'public_key_id' => $Config->getPublicKeyId(),
                'private_key'   => $Config->getPrivateKey(),
                'region'        => 'jp', // Default to Japan for EC-CUBE
                'sandbox'       => $Config->isSandbox()
            ];

            return new Client($amazonPayConfig);
        } catch (\Exception $e) {
            $this->logger->error('[AmazonPayMini] Failed to initialize client: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Create Checkout Session
     *
     * @param array $payload
     * @param array $headers
     * @return array
     */
    public function createCheckoutSession($payload, $headers = [])
    {
        $client = $this->getClient();
        if (!$client) {
            throw new \Exception('Amazon Pay client not initialized');
        }

        $response = $client->createCheckoutSession($payload, $headers);
        
        if ($response['status'] !== 201) {
            $this->logger->error('[AmazonPayMini] CreateCheckoutSession failed', ['response' => $response]);
            throw new \Exception('Failed to create Amazon Pay checkout session');
        }

        return json_decode($response['response'], true);
    }

    /**
     * Get Checkout Session
     *
     * @param string $checkoutSessionId
     * @return array
     */
    public function getCheckoutSession($checkoutSessionId)
    {
        $client = $this->getClient();
        if (!$client) {
            throw new \Exception('Amazon Pay client not initialized');
        }

        $response = $client->getCheckoutSession($checkoutSessionId);
        return json_decode($response['response'], true);
    }
}
