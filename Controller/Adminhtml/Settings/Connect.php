<?php
declare(strict_types=1);

namespace Channel3\Analytics\Controller\Adminhtml\Settings;

use Channel3\Analytics\Helper\IntegrationSetup;
use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Cache\TypeListInterface;
use Magento\Framework\Controller\ResultInterface;

/**
 * Admin controller for connecting to Channel3.
 *
 * Receives the merchant ID from the form, creates the Integration,
 * sends tokens to Channel3 backend, and redirects back with a status message.
 */
class Connect extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Channel3_Analytics::settings';

    private IntegrationSetup $integrationSetup;
    private TypeListInterface $cacheTypeList;

    public function __construct(
        Context $context,
        IntegrationSetup $integrationSetup,
        TypeListInterface $cacheTypeList
    ) {
        parent::__construct($context);
        $this->integrationSetup = $integrationSetup;
        $this->cacheTypeList = $cacheTypeList;
    }

    public function execute(): ResultInterface
    {
        $merchantId = trim((string) $this->getRequest()->getParam('merchant_id'));

        if (!$merchantId || strlen($merchantId) !== 4 || !ctype_alnum($merchantId)) {
            $this->messageManager->addErrorMessage(
                'Please enter a valid 4-character merchant ID from your Channel3 dashboard.'
            );
            return $this->resultRedirectFactory->create()->setPath('channel3/settings/index');
        }

        $result = $this->integrationSetup->connect($merchantId);

        if ($result['success']) {
            // Flush config cache so tracking starts immediately
            $this->cacheTypeList->cleanType('config');
            $this->messageManager->addSuccessMessage($result['message']);
        } else {
            $this->messageManager->addErrorMessage($result['message']);
        }

        return $this->resultRedirectFactory->create()->setPath('channel3/settings/index');
    }
}
