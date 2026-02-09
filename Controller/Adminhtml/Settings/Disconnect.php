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
 * Admin controller for disconnecting from Channel3.
 */
class Disconnect extends Action implements HttpPostActionInterface
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
        $result = $this->integrationSetup->disconnect();

        if ($result['success']) {
            $this->cacheTypeList->cleanType('config');
            $this->messageManager->addSuccessMessage($result['message']);
        } else {
            $this->messageManager->addErrorMessage($result['message']);
        }

        return $this->resultRedirectFactory->create()->setPath('channel3/settings/index');
    }
}
