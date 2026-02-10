<?php
declare(strict_types=1);

namespace Channel3\Analytics\Block;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Block for the checkout success (thank-you) page.
 *
 * Reads checkout data stored by the observer and provides it to the
 * checkout_success.phtml template for rendering as a tracking script.
 */
class CheckoutSuccess extends Template
{
    private CheckoutSession $checkoutSession;
    private ScopeConfigInterface $scopeConfig;
    private ?array $checkoutData = null;
    private bool $dataLoaded = false;

    public function __construct(
        Template\Context $context,
        CheckoutSession $checkoutSession,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->checkoutSession = $checkoutSession;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get checkout tracking data set by the observer.
     *
     * Clears session data on first read to prevent duplicate tracking on refresh.
     */
    public function getCheckoutData(): ?array
    {
        if (!$this->dataLoaded) {
            $this->checkoutData = $this->checkoutSession->getChannel3CheckoutData(true);
            $this->dataLoaded = true;
        }
        return $this->checkoutData;
    }

    /**
     * Get the Channel3 checkout pixel endpoint.
     */
    public function getCheckoutEndpoint(): string
    {
        $customUrl = $this->scopeConfig->getValue(
            'channel3/general/api_url',
            ScopeInterface::SCOPE_STORE
        );
        if ($customUrl) {
            return rtrim($customUrl, '/') . '/v0/magento/pixel/checkout';
        }
        return 'https://internal.trychannel3.com/v0/magento/pixel/checkout';
    }

    /**
     * Only render if checkout data exists.
     */
    protected function _toHtml(): string
    {
        if (!$this->getCheckoutData()) {
            return '';
        }
        return parent::_toHtml();
    }
}
