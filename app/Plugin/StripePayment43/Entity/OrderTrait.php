<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * トークンを保持するカラム.
     *
     * dtb_order.stripe_payment_token
     *
     * @var ?string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $stripe_payment_token;

    /**
     * 決済方法名.
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $stripe_payment_method_name;

    /**
     * クレジットカード番号の末尾4桁.
     *
     * @var string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private $stripe_payment_card_no_last4;

    /**
     * 決済ステータスを保持するカラム.
     *
     * dtb_order.stripe_payment_payment_status_id
     *
     * @var StripePaymentPaymentStatus
     *
     * @ORM\ManyToOne(targetEntity="Plugin\StripePayment43\Entity\PaymentStatus")
     *
     * @ORM\JoinColumns({
     *
     *   @ORM\JoinColumn(name="stripe_payment_payment_status_id", referencedColumnName="id")
     * })
     */
    private $StripePaymentPaymentStatus;

    /**
     * Stripe決済IDを保持するカラム.
     *
     * dtb_order.stripe_payment_id
     *
     * @var ?string
     *
     * @ORM\Column(type="string", nullable=true)
     */
    private ?string $stripe_payment_id;

    /**
     * @return ?string
     */
    public function getStripePaymentToken(): ?string
    {
        return $this->stripe_payment_token;
    }

    /**
     * @param $stripe_payment_token
     */
    public function setStripePaymentToken($stripe_payment_token): void
    {
        $this->stripe_payment_token = $stripe_payment_token;
    }

    /**
     * @return string
     */
    public function getStripePaymentMethodName(): ?string
    {
        return $this->stripe_payment_method_name;
    }

    /**
     * @param string $stripe_payment_method_name
     */
    public function setStripePaymentMethodName(?string $stripe_payment_method_name): void
    {
        $this->stripe_payment_method_name = $stripe_payment_method_name;
    }

    /**
     * @return string
     */
    public function getStripePaymentCardNoLast4()
    {
        return $this->stripe_payment_card_no_last4;
    }

    /**
     * @param string $stripe_payment_card_no_last4
     */
    public function setStripePaymentCardNoLast4($stripe_payment_card_no_last4)
    {
        $this->stripe_payment_card_no_last4 = $stripe_payment_card_no_last4;
    }

    /**
     * @return PaymentStatus
     */
    public function getStripePaymentPaymentStatus()
    {
        return $this->StripePaymentPaymentStatus;
    }

    /**
     * @param PaymentStatus $StripePaymentPaymentStatus|null
     */
    public function setStripePaymentPaymentStatus(?PaymentStatus $StripePaymentPaymentStatus = null)
    {
        $this->StripePaymentPaymentStatus = $StripePaymentPaymentStatus;
    }

    /**
     * @return ?string
     */
    public function getStripePaymentId(): ?string
    {
        return $this->stripe_payment_id;
    }

    /**
     * @param string $stripe_payment_id
     */
    public function setStripePaymentId(string $stripe_payment_id)
    {
        $this->stripe_payment_id = $stripe_payment_id;
    }
}
