<?php
declare(strict_types=1);

namespace Channel3\Analytics\Block\Adminhtml;

use Magento\Backend\Block\Template;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Admin block for the Channel3 settings page.
 */
class Settings extends Template
{
    private const CONFIG_MERCHANT_ID = 'channel3/general/merchant_id';
    private const CONFIG_CONNECTED = 'channel3/general/connected';

    private ScopeConfigInterface $scopeConfig;

    public function __construct(
        Template\Context $context,
        ScopeConfigInterface $scopeConfig,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->scopeConfig = $scopeConfig;
    }

    public function isConnected(): bool
    {
        return (bool) $this->scopeConfig->getValue(
            self::CONFIG_CONNECTED,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getMerchantId(): ?string
    {
        return $this->scopeConfig->getValue(
            self::CONFIG_MERCHANT_ID,
            ScopeInterface::SCOPE_STORE
        );
    }

    public function getConnectUrl(): string
    {
        return $this->getUrl('channel3/settings/connect');
    }

    public function getDisconnectUrl(): string
    {
        return $this->getUrl('channel3/settings/disconnect');
    }

    public function getDashboardUrl(): string
    {
        $merchantId = $this->getMerchantId();
        if ($merchantId) {
            return 'https://trychannel3.com/brands/' . $merchantId . '/ingest';
        }
        return 'https://trychannel3.com/dashboard';
    }
}
