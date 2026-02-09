<?php
declare(strict_types=1);

namespace Channel3\Analytics\Observer\Frontend\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;

/**
 * Observer for checkout success events.
 *
 * Stores checkout data in the checkout session so it can be rendered as an
 * inline tracking script on the thank-you page template.
 *
 * The actual JS output happens in checkout_success.phtml, which reads
 * from the session data set here.
 */
class OnepageControllerSuccessAction implements ObserverInterface
{
    private const CONFIG_MERCHANT_ID = 'channel3/general/merchant_id';
    private const CONFIG_CONNECTED = 'channel3/general/connected';

    private CheckoutSession $checkoutSession;
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    public function execute(Observer $observer): void
    {
        $connected = (bool) $this->scopeConfig->getValue(
            self::CONFIG_CONNECTED,
            ScopeInterface::SCOPE_STORE
        );
        $merchantId = $this->scopeConfig->getValue(
            self::CONFIG_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE
        );

        if (!$connected || !$merchantId) {
            return;
        }

        /** @var Order|null $order */
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getEntityId()) {
            return;
        }

        // Build line items from order items
        $lineItems = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $lineItems[] = [
                'productId' => (string) $item->getProductId(),
                'title' => $item->getName(),
                'quantity' => (int) $item->getQtyOrdered(),
                'price' => number_format((float) $item->getPrice(), 2, '.', ''),
            ];
        }

        // Store checkout data in session for the template to render
        $this->checkoutSession->setChannel3CheckoutData([
            'accountId' => $merchantId,
            'orderId' => (string) $order->getIncrementId(),
            'totalPrice' => number_format((float) $order->getGrandTotal(), 2, '.', ''),
            'currencyCode' => $order->getOrderCurrencyCode(),
            'lineItems' => $lineItems,
        ]);
    }
}
