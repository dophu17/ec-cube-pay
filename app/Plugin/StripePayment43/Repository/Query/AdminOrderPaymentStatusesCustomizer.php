<?php

namespace Plugin\StripePayment43\Repository\Query;

use Eccube\Doctrine\Query\WhereClause;
use Eccube\Doctrine\Query\WhereCustomizer;
use Eccube\Repository\QueryKey;

class AdminOrderPaymentStatusesCustomizer extends WhereCustomizer
{
    /**
     * 出荷日で絞り込み
     *
     * @see https://umebius.com/eccube/v4-plugin-order_list_filter_shipping_date/
     *
     * @param array $params
     * @param $queryKey
     *
     * @return WhereClause[]
     */
    protected function createStatements($params, $queryKey)
    {
        $rtn = [];
        if (!empty($params['PaymentStatuses']) && count($params['PaymentStatuses']) > 0) {
            $rtn[] = WhereClause::in('o.StripePaymentPaymentStatus', ':PaymentStatuses', ['PaymentStatuses' => $params['PaymentStatuses']]);
        }

        return $rtn;
    }

    /**
     * カスタマイズ対象のキーを返します。
     *
     * @return string
     */
    public function getQueryKey()
    {
        return QueryKey::ORDER_SEARCH_ADMIN;
    }
}
