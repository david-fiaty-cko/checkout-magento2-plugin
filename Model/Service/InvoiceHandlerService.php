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

namespace CheckoutCom\Magento2\Model\Service;

use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\Order\Invoice;

/**
 * Class InvoiceHandlerService.
 */
class InvoiceHandlerService
{
    /**
     * @var InvoiceService
     */
    public $invoiceService;

    /**
     * @var InvoiceRepositoryInterface
     */
    public $invoiceRepository;

    /**
     * @var Invoice
     */
    public $invoiceModel;

    /**
     * @var Config
     */
    public $config;

    /**
     * @var Logger
     */
    public $logger;

    /**
     * @var Order
     */
    public $order;

    /**
     * @var Transaction
     */
    public $transaction;

    /**
     * InvoiceHandlerService constructor.
     */
    public function __construct(
        \Magento\Sales\Model\Service\InvoiceService $invoiceService,
        \Magento\Sales\Api\InvoiceRepositoryInterface $invoiceRepository,
        \Magento\Sales\Model\Order\Invoice $invoiceModel,
        \CheckoutCom\Magento2\Gateway\Config\Config $config,
        \CheckoutCom\Magento2\Helper\Logger $logger
    ) {
        $this->invoiceService     = $invoiceService;
        $this->invoiceRepository  = $invoiceRepository;
        $this->invoiceModel       = $invoiceModel;
        $this->config             = $config;
        $this->logger             = $logger;
    }

    /**
     * Check if the invoice can be created.
     */
    public function processInvoice($order, $transaction = null)
    {
        try {
            // Set required properties
            $this->order = $order;
            $this->transaction = $transaction;

            // Handle the invoice
            if ($this->needsInvoicing()) {
                $this->createInvoice();
            }

            // Return the order
            return $this->order;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Create an invoice.
     */
    public function createInvoice()
    {
        try {
            // Prepare the invoice
            $invoice = $this->invoiceService->prepareInvoice($this->order);

            // Set the invoice transaction ID
            if ($this->transaction) {
                $invoice->setTransactionId($this->transaction->getTxnId());
            }

            // Set the invoice state
            $invoice = $this->setInvoiceState($invoice);

            // Finalize the invoice
            $invoice->setBaseGrandTotal($this->order->getGrandTotal());
            $invoice->register();

            // Save the invoice
            $this->invoiceRepository->save($invoice);
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }

    /**
     * Check if invoicing is needed.
     */
    public function needsInvoicing()
    {
        return $this->transaction->getTxnType() == Transaction::TYPE_CAPTURE;
    }

    /**
     * Set the invoice state.
     */
    public function setInvoiceState($invoice)
    {
        try {
            if ($this->needsInvoicing()) {
                $invoice->setRequestedCaptureCase(Invoice::CAPTURE_ONLINE);
                $invoice->setCanVoidFlag(false);
            }
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
        } finally {
            return $invoice;
        }
    }

    /**
     * Load order invoices.
     */
    public function getInvoice($order)
    {
        try {
            // Get the invoices collection
            $invoices = $order->getInvoiceCollection();

            // Retrieve the invoice increment id
            if (!empty($invoices)) {
                foreach ($invoices as $item) {
                    $invoiceIncrementId = $item->getIncrementId();
                }

                // Load an invoice
                return $this->invoiceModel->loadByIncrementId($invoiceIncrementId);
            }

            return null;
        } catch (\Exception $e) {
            $this->logger->write($e->getMessage());
            return null;
        }
    }
}
