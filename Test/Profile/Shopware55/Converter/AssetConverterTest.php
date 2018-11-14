<?php declare(strict_types=1);

namespace SwagMigrationNext\Test\Profile\Shopware55\Converter;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Media\MediaDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\Uuid;
use SwagMigrationNext\Profile\Shopware55\Converter\AssetConverter;
use SwagMigrationNext\Profile\Shopware55\Converter\ConverterHelperService;
use SwagMigrationNext\Test\Mock\Migration\Asset\DummyMediaFileService;
use SwagMigrationNext\Test\Mock\Migration\Mapping\DummyMappingService;

class AssetConverterTest extends TestCase
{
    /**
     * @var AssetConverter
     */
    private $assetConverter;

    protected function setUp()
    {
        $mediaFileService = new DummyMediaFileService();
        $mappingService = new DummyMappingService();
        $converterHelperService = new ConverterHelperService();
        $this->assetConverter = new AssetConverter($mappingService, $converterHelperService, $mediaFileService);
    }

    public function testSupports(): void
    {
        $supportsDefinition = $this->assetConverter->supports();

        static::assertSame(MediaDefinition::getEntityName(), $supportsDefinition);
    }

    public function testConvert(): void
    {
        $mediaData = require __DIR__ . '/../../../_fixtures/media_data.php';

        $context = Context::createDefaultContext();
        $convertResult = $this->assetConverter->convert($mediaData[0], $context, Uuid::uuid4()->getHex(), Uuid::uuid4()->getHex(), Defaults::CATALOG);

        $converted = $convertResult->getConverted();

        static::assertNull($convertResult->getUnmapped());
        static::assertArrayHasKey('id', $converted);
        static::assertSame(Defaults::CATALOG, $converted['catalogId']);
    }
}
