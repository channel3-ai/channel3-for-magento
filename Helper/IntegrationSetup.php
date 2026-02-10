<?php
declare(strict_types=1);

namespace Channel3\Analytics\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Integration\Api\IntegrationServiceInterface;
use Magento\Integration\Api\OauthServiceInterface;
use Magento\Integration\Model\Integration;
use Magento\Integration\Model\Oauth\Token;
use Magento\Integration\Model\Oauth\TokenFactory;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Handles auto-creation and activation of the Channel3 Magento Integration.
 *
 * When a merchant enters their merchant ID and clicks Connect in the admin,
 * this helper:
 * 1. Creates a Magento Integration named "Channel3" with the required API permissions
 * 2. Activates it (generates OAuth tokens)
 * 3. Sends the tokens + merchant_id to the Channel3 backend
 * 4. Stores the connection status in Magento config
 */
class IntegrationSetup
{
    private const INTEGRATION_NAME = 'Channel3';
    private const CONFIG_CONNECTED = 'channel3/general/connected';
    private const CONFIG_MERCHANT_ID = 'channel3/general/merchant_id';

    // API resources the integration needs
    private const REQUIRED_RESOURCES = [
        'Magento_Catalog::catalog',
        'Magento_Catalog::catalog_inventory',
        'Magento_Catalog::products',
        'Magento_Catalog::categories',
        'Magento_Backend::store',
        'Magento_Backend::stores',
        'Magento_Backend::stores_settings',
    ];

    private IntegrationServiceInterface $integrationService;
    private OauthServiceInterface $oauthService;
    private TokenFactory $tokenFactory;
    private StoreManagerInterface $storeManager;
    private WriterInterface $configWriter;
    private ScopeConfigInterface $scopeConfig;
    private LoggerInterface $logger;

    public function __construct(
        IntegrationServiceInterface $integrationService,
        OauthServiceInterface $oauthService,
        TokenFactory $tokenFactory,
        StoreManagerInterface $storeManager,
        WriterInterface $configWriter,
        ScopeConfigInterface $scopeConfig,
        LoggerInterface $logger
    ) {
        $this->integrationService = $integrationService;
        $this->oauthService = $oauthService;
        $this->tokenFactory = $tokenFactory;
        $this->storeManager = $storeManager;
        $this->configWriter = $configWriter;
        $this->scopeConfig = $scopeConfig;
        $this->logger = $logger;
    }

    /**
     * Connect to Channel3 by creating an Integration and sending tokens to the backend.
     *
     * @param string $merchantId The 4-character Channel3 merchant ID
     * @return array{success: bool, message: string}
     */
    public function connect(string $merchantId): array
    {
        try {
            // Step 1: Create or get existing Integration
            $integration = $this->getOrCreateIntegration();

            // Step 2: Activate and get tokens
            $tokens = $this->activateIntegration($integration);

            // Step 3: Get store URL
            $storeUrl = $this->storeManager->getStore()->getBaseUrl();

            // Step 4: Send tokens to Channel3 backend
            $result = $this->sendToChannel3($merchantId, $storeUrl, $tokens);

            if (!$result['success']) {
                return $result;
            }

            // Step 5: Store connection status in Magento config
            $this->configWriter->save(self::CONFIG_CONNECTED, '1');
            $this->configWriter->save(self::CONFIG_MERCHANT_ID, $merchantId);

            $this->logger->info("Channel3: Successfully connected store to merchant {$merchantId}");

            return [
                'success' => true,
                'message' => 'Successfully connected to Channel3!',
            ];

        } catch (\Exception $e) {
            $this->logger->error("Channel3: Connection failed - " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Disconnect from Channel3.
     */
    public function disconnect(): array
    {
        try {
            $this->configWriter->save(self::CONFIG_CONNECTED, '0');
            $this->configWriter->save(self::CONFIG_MERCHANT_ID, '');

            $this->logger->info("Channel3: Store disconnected");

            return [
                'success' => true,
                'message' => 'Disconnected from Channel3.',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Disconnect failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get existing Channel3 integration or create a new one.
     */
    private function getOrCreateIntegration(): Integration
    {
        // Check if integration already exists
        $existing = $this->integrationService->findByName(self::INTEGRATION_NAME);
        if ($existing->getId()) {
            return $existing;
        }

        // Create new integration
        $integrationData = [
            'name' => self::INTEGRATION_NAME,
            'status' => Integration::STATUS_INACTIVE,
            'all_resources' => false,
            'resource' => self::REQUIRED_RESOURCES,
        ];

        $integration = $this->integrationService->create($integrationData);
        $this->logger->info("Channel3: Created new Integration (ID: {$integration->getId()})");

        return $integration;
    }

    /**
     * Activate the integration and return the OAuth tokens.
     *
     * Creates a consumer (if needed), generates an access token, and explicitly
     * marks it as authorized so Magento's REST API accepts it.
     *
     * @return array{consumer_key: string, consumer_secret: string, access_token: string, access_token_secret: string}
     */
    private function activateIntegration(Integration $integration): array
    {
        $integrationId = $integration->getId();

        // Use Magento's built-in activation flow which creates consumer + tokens
        // and sets the integration status to ACTIVE
        if ($integration->getStatus() != Integration::STATUS_ACTIVE) {
            $this->integrationService->update([
                'integration_id' => $integrationId,
                'status' => Integration::STATUS_ACTIVE,
            ]);
        }

        // Reload to get consumer ID
        $integration = $this->integrationService->get($integrationId);
        $consumerId = $integration->getConsumerId();

        if (!$consumerId) {
            // Create consumer if it doesn't exist
            $consumer = $this->oauthService->createConsumer(['name' => self::INTEGRATION_NAME . '_' . $integrationId]);
            $consumerId = $consumer->getId();

            // Link consumer to integration
            $this->integrationService->update([
                'integration_id' => $integrationId,
                'consumer_id' => $consumerId,
            ]);
        }

        // Get consumer credentials
        $consumer = $this->oauthService->loadConsumer($consumerId);

        // Create access token
        $this->oauthService->createAccessToken($consumerId, true);
        $accessToken = $this->oauthService->getAccessToken($consumerId);

        if (!$accessToken) {
            throw new \RuntimeException('Failed to generate access tokens for the Integration.');
        }

        // CRITICAL: Mark the token as authorized
        // Without this, Magento's REST API returns 401 for Integration tokens
        /** @var Token $tokenModel */
        $tokenModel = $this->tokenFactory->create()->load($accessToken->getToken(), 'token');
        if ($tokenModel->getId()) {
            $tokenModel->setData('authorized', 1);
            $tokenModel->setData('type', 'access');
            $tokenModel->save();
            $this->logger->info("Channel3: Token authorized for consumer {$consumerId}");
        }

        return [
            'consumer_key' => $consumer->getKey(),
            'consumer_secret' => $consumer->getSecret(),
            'access_token' => $accessToken->getToken(),
            'access_token_secret' => $accessToken->getSecret(),
        ];
    }

    /**
     * Send the OAuth tokens to the Channel3 backend.
     */
    private function sendToChannel3(string $merchantId, string $storeUrl, array $tokens): array
    {
        $apiUrl = $this->getChannel3ApiUrl() . '/v0/magento/connect-via-module';

        $payload = json_encode([
            'merchant_id' => $merchantId,
            'store_url' => $storeUrl,
            'consumer_key' => $tokens['consumer_key'],
            'consumer_secret' => $tokens['consumer_secret'],
            'access_token' => $tokens['access_token'],
            'access_token_secret' => $tokens['access_token_secret'],
        ]);

        // Use cURL for the HTTP request
        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            return [
                'success' => false,
                'message' => "Could not reach Channel3: {$curlError}",
            ];
        }

        if ($httpCode === 409) {
            return [
                'success' => false,
                'message' => 'This store is already connected to Channel3.',
            ];
        }

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $detail = $errorData['detail'] ?? "Connection failed (HTTP {$httpCode})";
            return [
                'success' => false,
                'message' => $detail,
            ];
        }

        return ['success' => true, 'message' => 'Connected'];
    }

    /**
     * Get the Channel3 API base URL.
     */
    private function getChannel3ApiUrl(): string
    {
        // Allow override via Magento config for local development
        $customUrl = $this->scopeConfig->getValue('channel3/general/api_url');
        if ($customUrl) {
            return rtrim($customUrl, '/');
        }

        return 'https://internal.trychannel3.com';
    }
}
