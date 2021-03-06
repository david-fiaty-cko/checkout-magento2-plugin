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

namespace CheckoutCom\Magento2\Controller\Api;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Webapi\Exception as WebException;
use Magento\Framework\Webapi\Rest\Response as WebResponse;
use Magento\Checkout\Model\Type\Onepage;

/**
 * Class V1
 */
class V1 extends \Magento\Framework\App\Action\Action
{
    /**
     * @var CustomerRepositoryInterface
     */
    public $customerRepository;

    /**
     * @var QuoteManagement
     */
    public $quoteManagement;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var QuoteHandlerService
     */
    public $quoteHandler;

    /**
     * Callback constructor
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger,
        \CheckoutCom\Magento2\Model\Service\QuoteHandlerService $quoteHandler
    ) {
        parent::__construct($context);
        $this->customerRepository  = $customerRepository;
        $this->quoteManagement = $quoteManagement;
        $this->config = $config;
        $this->logger = $logger;
        $this->quoteHandler = $quoteHandler;
    }

    /**
     * Handles the controller method.
     */
    public function execute()
    {
        try {
            // Set the payload data
            $this->getPayload();

            // Prepare the response handler
            $resultFactory = $this->resultFactory->create(ResultFactory::TYPE_JSON);

            // Process the request
            if ($this->config->isValidAuth()) {
                // Create the quote
                $quote = $this->quoteHandler->createQuote(
                    $this->data->currency,
                    $this->getCustomer()
                );

                // Add the products
                $quote = $this->quoteHandler->addItems(
                    $quote,
                    $this->data
                );

                // Inventory
                $quote->setInventoryProcessed(false);

                // Create the order
                $order = $this->quoteManagement->submit($quote);

                // Set a valid response
                $resultFactory->setHttpResponseCode(WebResponse::HTTP_OK);

                // Return the order id
                return $order->getId();
            } else {
                $resultFactory->setHttpResponseCode(WebException::HTTP_UNAUTHORIZED);
            }
        } catch (\Exception $e) {
            $resultFactory->setHttpResponseCode(WebException::HTTP_INTERNAL_ERROR);
            $this->logger->write($e->getMessage());
            return $resultFactory->setData(['error_message' => $e->getMessage()]);
        }
    }

    /**
     * Returns a JSON payload from request.
     *
     * @return string
     */
    public function getPayload()
    {
        $this->data = json_decode($this->getRequest()->getContent());
    }

    /**
     * Load a customer
     *
     * @return string
     */
    public function getCustomer()
    {
        try {
            if (isset($this->payload->customer->id) && (int) $this->payload->customer->id > 0) {
                return $this->customerRepository->getById($this->payload->customer->id);
            } elseif (isset($this->payload->customer->email)) {
                return $this->customerRepository->get($this->payload->customer->email);
            } else {
                throw new \Magento\Framework\Exception\LocalizedException(
                    __('A valid customer ID or email is required to place an order.')
                );
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        }
    }
}
