<?php

namespace CheckoutCom\Magento2\Model\Service;

use CheckoutCom\Magento2\Gateway\Config\Config;
use \Checkout\CheckoutApi;
use \Checkout\Models\Phone;
use CheckoutCom\Magento2\Model\Methods\Method;

/**
 * Class for sdk handler service.
 */
class SdkHandlerService
{

    protected $config;
    protected $checkoutApi;

	/**
     * Initialize SDK wrapper.
     *
     * @param      \CheckoutCom\Magento2\Gateway\Config\Config  $config  The configuration
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
        $this->checkoutApi = new CheckoutApi(
            $this->config->getValue('secret_key'),
            $this->config->getValue('environment'),
            $this->config->getValue('public_key')
        );
    }

    public function loadClient() {



    }
}
