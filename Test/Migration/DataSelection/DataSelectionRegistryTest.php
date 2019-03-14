<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Migration\DataSelection;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Migration\Connection\SwagMigrationConnectionEntity;
use SwagMigrationNext\Migration\DataSelection\DataSelectionRegistry;
use SwagMigrationNext\Migration\DataSelection\DataSelectionStruct;
use SwagMigrationNext\Migration\EnvironmentInformation;
use SwagMigrationNext\Migration\MigrationContext;
use SwagMigrationNext\Migration\Profile\SwagMigrationProfileEntity;
use SwagMigrationNext\Profile\Shopware55\DataSelection\CustomerAndOrderDataSelection;
use SwagMigrationNext\Profile\Shopware55\DataSelection\MediaDataSelection;
use SwagMigrationNext\Profile\Shopware55\DataSelection\ProductCategoryTranslationDataSelection;
use SwagMigrationNext\Profile\Shopware55\Gateway\Local\Shopware55LocalGateway;
use SwagMigrationNext\Profile\Shopware55\Shopware55Profile;
use SwagMigrationNext\Test\Mock\DummyCollection;

class DataSelectionRegistryTest extends TestCase
{
    /**
     * @var DataSelectionRegistry
     */
    private $dataSelectionRegistry;

    /**
     * @var SwagMigrationConnectionEntity
     */
    private $connection;

    /**
     * @var EnvironmentInformation
     */
    private $environmentInformation;

    protected function setUp(): void
    {
        $this->environmentInformation = new EnvironmentInformation(
            '',
            '',
            '',
            [],
            [
                'product' => 100,
                'customer' => 5,
                'media' => 100,
            ]
        );
        $this->connection = new SwagMigrationConnectionEntity();
        $this->connection->setId(Uuid::uuid4()->getHex());
        $profile = new SwagMigrationProfileEntity();
        $profile->setName(Shopware55Profile::PROFILE_NAME);
        $profile->setGatewayName(Shopware55LocalGateway::GATEWAY_NAME);

        $this->connection->setProfile($profile);
        $this->connection->setCredentialFields([]);

        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([
            new MediaDataSelection(),
            new ProductCategoryTranslationDataSelection(),
            new CustomerAndOrderDataSelection(),
        ]));
    }

    public function testGetDataSelections(): void
    {
        $migrationContext = new MigrationContext(
          '',
          $this->connection,
          '',
            0,
            0
        );

        $expected = [
            0 => (new ProductCategoryTranslationDataSelection())->getData()->getId(),
            1 => (new CustomerAndOrderDataSelection())->getData()->getId(),
            2 => (new MediaDataSelection())->getData()->getId(),
        ];

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);
        static::assertCount(3, $dataSelections->getElements());

        $i = 0;
        /** @var DataSelectionStruct $selection */
        foreach ($dataSelections->getIterator() as $selection) {
            static::assertSame($expected[$i], $selection->getId());
            ++$i;
        }
    }

    public function testGetDataSelectionsWithOnlyOneDataSelection(): void
    {
        $this->dataSelectionRegistry = new DataSelectionRegistry(new DummyCollection([new MediaDataSelection()]));
        $migrationContext = new MigrationContext(
            '',
            $this->connection,
            '',
            0,
            0
        );

        $dataSelections = $this->dataSelectionRegistry->getDataSelections($migrationContext, $this->environmentInformation);

        static::assertCount(1, $dataSelections);
        static::assertSame($dataSelections->first()->getId(), (new MediaDataSelection())->getData()->getId());
    }
}
