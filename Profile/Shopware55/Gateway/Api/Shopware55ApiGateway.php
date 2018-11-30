<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Api;

use GuzzleHttp\Client;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\Gateway\AbstractGateway;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiEnvironmentReader;
use SwagMigrationNext\Profile\Shopware55\Gateway\Api\Reader\Shopware55ApiReader;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;

class Shopware55ApiGateway extends AbstractGateway
{
    public const GATEWAY_TYPE = 'api';

    public function read(): array
    {
        $reader = new Shopware55ApiReader($this->getClient(), $this->migrationContext);

        return $reader->read();
    }

    public function readEnvironmentInformation(): EnvironmentInformation
    {
        $reader = new Shopware55ApiEnvironmentReader($this->getClient(), $this->migrationContext);
        $environmentData = $reader->read();
        $environmentDataArray = $environmentData['environmentInformation'];

        if (empty($environmentDataArray)) {
            return new EnvironmentInformation(
                Shopware55Profile::SOURCE_SYSTEM_NAME,
                Shopware55Profile::SOURCE_SYSTEM_VERSION,
                '',
                [],
                0,
                0,
                0,
                0,
                0,
                0,
                $environmentData['warning']['code'],
                $environmentData['warning']['detail'],
                $environmentData['error']['code'],
                $environmentData['error']['detail']
            );
        }

        if (!isset($environmentDataArray['translations'])) {
            $environmentDataArray['translations'] = 0;
        }

        return new EnvironmentInformation(
            Shopware55Profile::SOURCE_SYSTEM_NAME,
            $environmentDataArray['shopwareVersion'],
            $environmentDataArray['structure'][0]['host'],
            $environmentDataArray['structure'],
            $environmentDataArray['categories'],
            $environmentDataArray['products'],
            $environmentDataArray['customers'],
            $environmentDataArray['orders'],
            $environmentDataArray['assets'],
            $environmentDataArray['translations'],
            $environmentData['warning']['code'],
            $environmentData['warning']['detail'],
            $environmentData['error']['code'],
            $environmentData['error']['detail']
        );
    }

    private function getClient(): Client
    {
        $credentials = $this->migrationContext->getCredentials();

        $options = [
            'base_uri' => $credentials['endpoint'] . '/api/',
            'auth' => [$credentials['apiUser'], $credentials['apiKey'], 'digest'],
            'verify' => false,
        ];

        return new Client($options);
    }
}
