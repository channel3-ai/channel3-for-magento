<?php
declare(strict_types=1);

namespace Channel3\Analytics\Controller\Adminhtml\Settings;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\PageFactory;

/**
 * Admin controller for the Channel3 settings page.
 *
 * Displays connection status and connect/disconnect actions.
 */
class Index extends Action implements HttpGetActionInterface
{
    public const ADMIN_RESOURCE = 'Channel3_Analytics::settings';

    private PageFactory $resultPageFactory;

    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute(): ResultInterface
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Channel3_Analytics::settings');
        $resultPage->getConfig()->getTitle()->prepend(__('Channel3 Settings'));
        return $resultPage;
    }
}
