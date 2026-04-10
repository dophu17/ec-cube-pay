<?php

namespace Plugin\AmazonPayMini\Service\Method;

use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Symfony\Component\Form\FormInterface;

class AmazonPay implements PaymentMethodInterface
{
    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * {@inheritdoc}
     */
    public function verify()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function checkout()
    {
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(): ?PaymentDispatcher
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form): self
    {
        $this->form = $form;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order): self
    {
        $this->Order = $Order;

        return $this;
    }
}
