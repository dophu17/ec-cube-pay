<?php

namespace Plugin\AmazonPayMini\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Plugin\AmazonPayMini\Service\AmazonPayService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AmazonPayController extends AbstractController
{
    /**
     * @var AmazonPayService
     */
    protected $amazonPayService;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var OrderHelper
     */
    protected $orderHelper;

    public function __construct(
        AmazonPayService $amazonPayService,
        CartService $cartService,
        OrderHelper $orderHelper
    ) {
        $this->amazonPayService = $amazonPayService;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
    }

    /**
     * @Route("/amazon_pay_mini/checkout", name="amazon_pay_mini_checkout", methods={"POST"})
     */
    public function checkout(Request $request)
    {
        $Cart = $this->cartService->getCart();
        if (!$Cart) {
            return new JsonResponse(['error' => 'Cart is empty'], 400);
        }

        try {
            $payload = [
                'webCheckoutDetails' => [
                    'checkoutReviewReturnUrl' => $this->generateUrl('amazon_pay_mini_complete', [], UrlGeneratorInterface::ABSOLUTE_URL),
                ],
                'storeId' => $this->amazonPayService->getClient()->getStoreId(), // Assuming helper or direct config access
                'scopes' => ['name', 'email', 'phoneNumber', 'billingAddress'],
            ];

            // In a real mini plugin, we'd add more details here (amount, items, etc.)
            // For now, let's keep it simple for the "mini" version.
            
            $session = $this->amazonPayService->createCheckoutSession($payload);
            
            return new JsonResponse([
                'checkoutSessionId' => $session['checkoutSessionId']
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @Route("/amazon_pay_mini/complete", name="amazon_pay_mini_complete")
     */
    public function complete(Request $request)
    {
        $checkoutSessionId = $request->query->get('amazonCheckoutSessionId');
        if (!$checkoutSessionId) {
            return $this->redirectToRoute('cart');
        }

        try {
            $session = $this->amazonPayService->getCheckoutSession($checkoutSessionId);
            
            // Logic to complete order in EC-CUBE
            // 1. Get user info from session
            // 2. Create/Update order
            // 3. Complete payment
            
            // For the "mini" version, we might just redirect to the shopping checkout page
            // with the Amazon information pre-filled or handled by a session.
            
            $this->addSuccess('Amazon Pay authorization successful', 'front');
            return $this->redirectToRoute('shopping');
            
        } catch (\Exception $e) {
            $this->addError('Amazon Pay error: ' . $e->getMessage(), 'front');
            return $this->redirectToRoute('cart');
        }
    }
}
