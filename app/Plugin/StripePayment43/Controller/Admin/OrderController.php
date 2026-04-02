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

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Plugin\StripePayment43\Repository\PaymentStatusRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /**
     * @var PaymentStatusRepository
     */
    protected $paymentStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * OrderController constructor.
     *
     * @param PaymentStatusRepository $paymentStatusRepository
     * @param OrderRepository $orderRepository
     */
    public function __construct(
        PaymentStatusRepository $paymentStatusRepository,
        OrderRepository $orderRepository,
    ) {
        $this->paymentStatusRepository = $paymentStatusRepository;
        $this->orderRepository = $orderRepository;
    }

    /**
     * 受注編集 > 決済のキャンセル処理
     *
     * @Route("/%eccube_admin_route%/stripe_payment/order/cancel/{id}", requirements={"id" : "\d+"}, name="stripe_payment_admin_order_cancel", methods={"POST"})
     */
    public function cancel(Request $request, Order $Order)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            // 通信処理

            $this->addSuccess('stripe_payment.admin.order.cancel.success', 'admin');

            return $this->json([]);
        }

        throw new BadRequestHttpException();
    }

    /**
     * 受注編集 > 決済の金額変更
     *
     * @Route("/%eccube_admin_route%/stripe_payment/order/change_price/{id}", requirements={"id" : "\d+"}, name="stripe_payment_admin_order_change_price", methods={"POST"})
     */
    public function changePrice(Request $request, Order $Order)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            // 通信処理

            $this->addSuccess('stripe_payment.admin.order.change_price.success', 'admin');

            return $this->json([]);
        }

        throw new BadRequestHttpException();
    }

    /**
     * 決済ステータス一括変更
     *
     * @Route("/%eccube_admin_route%/order/bulk_change_payment_status", name="stripe_payment_admin_order_bulk_update_payment_status", methods={"POST"})
     */
    public function bulkUpdatePaymentStatus(Request $request)
    {
        $this->isTokenValid();

        $Orders = $this->orderRepository->findBy(['id' => $request->get('ids')]);
        $optionBulkPaymentStatus = $request->get('option_bulk_payment_status');
        $count = 0;

        foreach ($Orders as $Order) {
            $Order->setStripePaymentPaymentStatus($this->paymentStatusRepository->find($optionBulkPaymentStatus));
            $this->entityManager->flush($Order);
            $count++;
        }

        $this->addSuccess(trans('stripe_payment.admin.payment_status.bulk_action.success', ['%count%' => $count]),
            'admin');

        return $this->redirect($this->generateUrl('admin_order', ['resume' => Constant::ENABLED]));
    }
}
