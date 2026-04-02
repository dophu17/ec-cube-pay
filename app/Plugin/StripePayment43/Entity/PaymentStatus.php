<?php

namespace Plugin\StripePayment43\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * PaymentStatus
 *
 * @ORM\Table(name="plg_stripe_payment_payment_status")
 *
 * @ORM\Entity(repositoryClass="Plugin\StripePayment43\Repository\PaymentStatusRepository")
 */
class PaymentStatus extends AbstractMasterEntity
{
    /**
     * 定数名は適宜変更してください.
     */

    /**
     * 未決済
     */
    public const OUTSTANDING = 1;
    /**
     * 仮売上
     */
    public const PROVISIONAL_SALES = 2;
    /**
     * 実売上
     */
    public const ACTUAL_SALES = 3;
    /**
     * キャンセル
     */
    public const CANCEL = 4;
    /**
     * 決済失敗
     */
    public const FAILURE = 5;
    /**
     * 一部返金 (Partial Refund)
     */
    public const PARTIAL_REFUND = 6;
    /**
     * 全額返金 (Full Refund)
     */
    public const FULL_REFUND = 7;
    /**
     * チャージバック (Chargeback)
     */
    public const CHARGEBACK = 8;
}
