<?php declare(strict_types=1);
/*
 * (c) shopware AG <info@shopware.com>
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagMigrationAssistant\Profile\Shopware6\Converter;

use SwagMigrationAssistant\Migration\Converter\ConvertStruct;
use SwagMigrationAssistant\Migration\DataSelection\DefaultEntities;

abstract class ProductSortingConverter extends ShopwareConverter
{
    public function getSourceIdentifier(array $data): string
    {
        return $data['id'];
    }

    protected function convertData(array $data): ConvertStruct
    {
        $converted = $data;
        [$productSortingUuid, $isLocked] = $this->mappingService->getProductSortingUuid(
            $data['key'],
            $this->context
        );

        if ($isLocked) {
            return new ConvertStruct(null, $data);
        }

        if ($productSortingUuid !== null) {
            $converted['id'] = $productSortingUuid;
        }

        $this->mainMapping = $this->getOrCreateMappingMainCompleteFacade(
            DefaultEntities::PRODUCT_SORTING,
            $data['id'],
            $converted['id']
        );

        $this->updateAssociationIds(
            $converted['translations'],
            DefaultEntities::LANGUAGE,
            'languageId',
            DefaultEntities::PRODUCT_SORTING
        );

        return new ConvertStruct($converted, null, $this->mainMapping['id'] ?? null);
    }
}
