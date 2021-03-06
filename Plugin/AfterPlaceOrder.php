<?php

/**
 * Checkout.com
 * Authorized and regulated as an electronic money institution
 * by the UK Financial Conduct Authority (FCA) under number 900816.
 *
 * PHP version 7
 *
 * @category  Magento2
 * @package   Checkout.com
 * @author    Platforms Development Team <platforms@checkout.com>
 * @copyright 2010-2019 Checkout.com
 * @license   https://opensource.org/licenses/mit-license.html MIT License
 * @link      https://docs.checkout.com/
 */

namespace CheckoutCom\Magento2\Plugin;

use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\Data\OrderInterface;

/**
 * Class AfterPlaceOrder.
 */
class AfterPlaceOrder
{
    /**
     * Disable order email sending on order creation
     */
    public function afterPlace(OrderManagementInterface $subject, OrderInterface $order)
    {
        $order->setCanSendNewEmailFlag(false);
        return $order;
    }
}
