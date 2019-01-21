<?php declare(strict_types=1);

namespace SwagMigrationNext\Migration\Mapping;

use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Util\AccessKeyHelper;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\Country\CountryDefinition;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\Language\LanguageDefinition;
use Shopware\Core\System\Language\LanguageEntity;
use Shopware\Core\System\Locale\LocaleEntity;
use Shopware\Core\System\SalesChannel\Aggregate\SalesChannelType\SalesChannelTypeEntity;
use Shopware\Core\System\SalesChannel\SalesChannelDefinition;
use SwagMigrationNext\Exception\LocaleNotFoundException;

class MappingService implements MappingServiceInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    protected $migrationMappingRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $localeRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $languageRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $countryRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $currencyRepository;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepo;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelTypeRepo;

    protected $uuids = [];

    protected $writeArray = [];

    public function __construct(
        EntityRepositoryInterface $migrationMappingRepo,
        EntityRepositoryInterface $localeRepository,
        EntityRepositoryInterface $languageRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $salesChannelRepo,
        EntityRepositoryInterface $salesChannelTypeRepo
    ) {
        $this->migrationMappingRepo = $migrationMappingRepo;
        $this->localeRepository = $localeRepository;
        $this->languageRepository = $languageRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->salesChannelRepo = $salesChannelRepo;
        $this->salesChannelTypeRepo = $salesChannelTypeRepo;
    }

    public function getUuid(string $profileId, string $entityName, string $oldId, Context $context): ?string
    {
        if (isset($this->uuids[$profileId][$entityName][$oldId])) {
            return $this->uuids[$profileId][$entityName][$oldId];
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('profileId', $profileId));
        $criteria->addFilter(new EqualsFilter('entity', $entityName));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $oldId));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();
            $uuid = $element->getEntityUuid();

            $this->uuids[$profileId][$entityName][$oldId] = $uuid;

            return $uuid;
        }

        return null;
    }

    public function createNewUuid(
        string $profileId,
        string $entityName,
        string $oldId,
        Context $context,
        array $additionalData = null,
        string $newUuid = null
    ): string {
        $uuid = $this->getUuid($profileId, $entityName, $oldId, $context);
        if ($uuid !== null) {
            return $uuid;
        }

        $uuid = Uuid::uuid4()->getHex();
        if ($newUuid !== null) {
            $uuid = $newUuid;
        }

        $this->saveMapping(
            [
                'profileId' => $profileId,
                'entity' => $entityName,
                'oldIdentifier' => $oldId,
                'entityUuid' => $uuid,
                'additionalData' => $additionalData,
            ]
        );

        return $uuid;
    }

    public function getLanguageUuid(string $profileId, string $localeCode, Context $context): array
    {
        // TODO: Revert the override of localeCode and test localCode, if the core can handle translations in a right way
        $_localeCode = 'en_GB';
        if ($localeCode === 'swagMigrationTestingLocaleCode') {
            $_localeCode = $localeCode;
        }
        $localeCode = $_localeCode;

        $languageUuid = $this->searchLanguageInMapping($localeCode, $context);
        $localeUuid = $this->searchLocale($localeCode, $context);

        if ($languageUuid !== null) {
            return [
                'uuid' => $languageUuid,
                'createData' => [
                    'localeId' => $localeUuid,
                    'localeCode' => $localeCode,
                ],
            ];
        }

        $languageUuid = $this->searchLanguageByLocale($localeUuid, $context);

        if ($languageUuid !== null) {
            return ['uuid' => $languageUuid];
        }

        return [
            'uuid' => $this->createNewUuid($profileId, LanguageDefinition::getEntityName(), $localeCode, $context),
            'createData' => [
                'localeId' => $localeUuid,
                'localeCode' => $localeCode,
            ],
        ];
    }

    public function getCountryUuid(string $oldId, string $iso, string $iso3, string $profileId, Context $context): ?string
    {
        $countryUuid = $this->getUuid($profileId, CountryDefinition::getEntityName(), $oldId, $context);

        if ($countryUuid !== null) {
            return $countryUuid;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', $iso));
        $criteria->addFilter(new EqualsFilter('iso3', $iso3));
        $criteria->setLimit(1);
        $result = $this->countryRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CountryEntity $element */
            $element = $result->getEntities()->first();

            $countryUuid = $element->getId();

            $this->saveMapping(
                [
                    'profileId' => $profileId,
                    'entity' => CountryDefinition::getEntityName(),
                    'oldIdentifier' => $oldId,
                    'entityUuid' => $countryUuid,
                ]
            );

            return $countryUuid;
        }

        return null;
    }

    public function getCurrencyUuid(string $oldShortName, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('shortName', $oldShortName));
        $criteria->setLimit(1);
        $result = $this->currencyRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var CurrencyEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }

    public function deleteMapping(string $entityUuid, string $profileId, Context $context): void
    {
        foreach ($this->writeArray as $writeMapping) {
            if ($writeMapping['profileId'] === $profileId && $writeMapping['entityUuid'] === $entityUuid) {
                unset($writeMapping);
                break;
            }
        }

        if (isset($this->uuids[$profileId])) {
            foreach ($this->uuids[$profileId] as $entityName => $entityArray) {
                foreach ($entityArray as $oldId => $uuid) {
                    if ($uuid === $entityUuid) {
                        unset($this->uuids[$profileId][$entityName][$oldId]);
                        break;
                    }
                }
            }
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entityUuid', $entityUuid));
        $criteria->addFilter(new EqualsFilter('profileId', $profileId));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();

            $this->migrationMappingRepo->delete([['id' => $element->getId()]], $context);
        }
    }

    public function writeMapping(Context $context): void
    {
        if (empty($this->writeArray)) {
            return;
        }

        $this->migrationMappingRepo->create($this->writeArray, $context);
        $this->writeArray = [];
        $this->uuids = [];
    }

    public function createSalesChannelMapping(string $profileId, array $structure, Context $context): void
    {
        foreach ($structure as $structureItem) {
            $uuid = $this->getStructureToSalesChannelMapping($structureItem['id'], $profileId, $context);

            if ($uuid !== null && !$this->existsSalesChannel($uuid, $context)) {
                $this->deleteMapping($uuid, $profileId, $context);
                $uuid = null;
            }

            if ($uuid === null) {
                $uuid = $this->createSalesChannel($structureItem, $context);
                $this->insertSalesChannelMapping($structureItem['id'], $profileId, $uuid, $context);
            }

            if (isset($structureItem['children'])) {
                $this->createChildrenMapping($profileId, $structureItem['children'], $uuid, $context);
            }
        }

        $this->writeMapping($context);
    }

    protected function saveMapping(array $mapping): void
    {
        $profileId = $mapping['profileId'];
        $entity = $mapping['entity'];
        $oldId = $mapping['oldIdentifier'];
        $uuid = $mapping['entityUuid'];

        $this->uuids[$profileId][$entity][$oldId] = $uuid;
        $this->writeArray[] = $mapping;
    }

    private function createChildrenMapping(string $profileId, array $children, string $uuid, Context $context): void
    {
        foreach ($children as $child) {
            $oldUuid = $this->getStructureToSalesChannelMapping($child['id'], $profileId, $context);

            if ($oldUuid !== null && $oldUuid === $uuid) {
                continue;
            }

            if ($oldUuid !== null && $oldUuid !== $uuid) {
                $this->deleteMapping($oldUuid, $profileId, $context);
            }

            $this->insertSalesChannelMapping($child['id'], $profileId, $uuid, $context);
        }
    }

    private function getStructureToSalesChannelMapping(string $structureId, string $profileId, Context $context): ?string
    {
        return $this->getUuid(
            $profileId,
            SalesChannelDefinition::getEntityName(),
            $structureId,
            $context
        );
    }

    private function createSalesChannel(array $structureItem, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->setLimit(1);
        /** @var SalesChannelTypeEntity $salesChannelType */
        $salesChannelType = $this->salesChannelTypeRepo->search($criteria, $context)->first();

        // Todo: Replace default values with external values
        $createEvent = $this->salesChannelRepo->create([
            [
                'typeId' => $salesChannelType->getId(),

                'languageId' => Defaults::LANGUAGE_SYSTEM,
                'languages' => [
                    [
                        'id' => Defaults::LANGUAGE_SYSTEM,
                    ],
                ],

                'currencyId' => Defaults::CURRENCY,
                'currencies' => [
                    [
                        'id' => Defaults::CURRENCY,
                    ],
                ],

                'paymentMethodId' => Defaults::PAYMENT_METHOD_INVOICE,
                'paymentMethods' => [
                    [
                        'id' => Defaults::PAYMENT_METHOD_INVOICE,
                    ],
                ],

                'shippingMethodId' => Defaults::SHIPPING_METHOD,
                'shippingMethods' => [
                    [
                        'id' => Defaults::SHIPPING_METHOD,
                    ],
                ],

                'countryId' => Defaults::COUNTRY,
                'countries' => [
                    [
                        'id' => Defaults::COUNTRY,
                    ],
                ],

                'name' => $structureItem['name'],
                'accessKey' => AccessKeyHelper::generateAccessKey('sales-channel'),
            ],
        ], $context);

        /** @var EntityWrittenEvent $writtenEvent */
        $writtenEvent = $createEvent->getEvents()->first();
        $ids = $writtenEvent->getIds();

        return $ids[0]['salesChannelId'];
    }

    private function insertSalesChannelMapping(string $structureId, string $profileId, string $salesChannelUuid, Context $context): void
    {
        $this->createNewUuid(
            $profileId,
            SalesChannelDefinition::getEntityName(),
            $structureId,
            $context,
            [],
            $salesChannelUuid
        );
    }

    private function existsSalesChannel(string $salesChannelUuid, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $salesChannelUuid));

        return $this->salesChannelRepo->search($criteria, $context)->count() > 0;
    }

    private function searchLanguageInMapping(string $localeCode, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('entity', LanguageDefinition::getEntityName()));
        $criteria->addFilter(new EqualsFilter('oldIdentifier', $localeCode));
        $criteria->setLimit(1);
        $result = $this->migrationMappingRepo->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var SwagMigrationMappingEntity $element */
            $element = $result->getEntities()->first();

            return $element->getEntityUuid();
        }

        return null;
    }

    /**
     * @throws LocaleNotFoundException
     */
    private function searchLocale(string $localeCode, Context $context): string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('code', $localeCode));
        $criteria->setLimit(1);
        $result = $this->localeRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LocaleEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        throw new LocaleNotFoundException($localeCode);
    }

    private function searchLanguageByLocale(string $localeUuid, Context $context): ?string
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('localeId', $localeUuid));
        $criteria->setLimit(1);
        $result = $this->languageRepository->search($criteria, $context);

        if ($result->getTotal() > 0) {
            /** @var LanguageEntity $element */
            $element = $result->getEntities()->first();

            return $element->getId();
        }

        return null;
    }
}
