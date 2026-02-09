<?php
declare(strict_types=1);

namespace Channel3\Analytics\Block;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Framework\Registry;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\View\Element\Template;
use Magento\Store\Model\ScopeInterface;

/**
 * Frontend block that injects the Channel3 tracking script on all pages.
 *
 * On product pages, injects the product ID server-side (from Magento's registry)
 * instead of relying on fragile DOM scraping.
 */
class Tracking extends Template
{
    private const CONFIG_MERCHANT_ID = 'channel3/general/merchant_id';
    private const CONFIG_CONNECTED = 'channel3/general/connected';

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var ScopeConfigInterface
     */
    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Template\Context $context,
        Registry $registry,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->registry = $registry;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Get the Channel3 merchant ID from module config.
     */
    public function getMerchantId(): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Check if the module is connected to Channel3.
     */
    public function isConnected(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_CONNECTED,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Get the current product ID if on a product page.
     *
     * Uses Magento's registry (server-side) instead of DOM scraping.
     * Returns null on non-product pages.
     */
    public function getProductId(): ?string
    {
        /** @var ProductInterface|null $product */
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            return (string) $product->getId();
        }
        return null;
    }

    /**
     * Get the store currency code.
     */
    public function getCurrencyCode(): ?string
    {
        try {
            return $this->_storeManager->getStore()->getCurrentCurrencyCode();
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the Channel3 API base URL.
     */
    public function getApiBaseUrl(): string
    {
        return 'https://internal.trychannel3.com/v0';
    }

    /**
     * Get tracking data as a JSON-encodable array for the template.
     */
    public function getTrackingData(): array
    {
        return [
            'accountId' => $this->getMerchantId(),
            'endpoint' => $this->getApiBaseUrl() . '/magento/pixel/page-view',
            'productId' => $this->getProductId(),
            'currency' => $this->getCurrencyCode(),
        ];
    }

    /**
     * Only render if the module is connected and has a merchant ID.
     */
    protected function _toHtml(): string
    {
        if (!$this->isConnected() || !$this->getMerchantId()) {
            return '';
        }
        return parent::_toHtml();
    }
}
