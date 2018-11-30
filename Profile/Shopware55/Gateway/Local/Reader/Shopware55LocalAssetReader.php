<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Gateway\Local\Reader;

use Doctrine\DBAL\Connection;

class Shopware55LocalAssetReader extends Shopware55LocalAbstractReader
{
    public function read(): array
    {
        $fetchedAssets = $this->fetchData();

        $assets = $this->mapData(
            $fetchedAssets, [], ['asset']
        );

        $resultSet = $this->prepareAssets($assets);

        return $this->cleanupResultSet($resultSet);
    }

    private function fetchData(): array
    {
        $ids = $this->fetchIdentifiers('s_media', $this->migrationContext->getOffset(), $this->migrationContext->getLimit());
        $query = $this->connection->createQueryBuilder();

        $query->from('s_media', 'asset');
        $this->addTableSelection($query, 's_media', 'asset');

        $query->leftJoin('asset', 's_media_attributes', 'attributes', 'asset.id = attributes.mediaID');
        $this->addTableSelection($query, 's_media_attributes', 'attributes');

        $query->leftJoin('asset', 's_media_album', 'album', 'album.id = asset.albumID');
        $this->addTableSelection($query, 's_media_album', 'album');

        $query->leftJoin('album', 's_media_album_settings', 'album_settings', 'album.id = album_settings.albumID');
        $this->addTableSelection($query, 's_media_album_settings', 'album_settings');

        $query->where('asset.id IN (:ids)');
        $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);

        $query->addOrderBy('asset.id');

        return $query->execute()->fetchAll();
    }

    private function prepareAssets(array $assets): array
    {
        // represents the main language of the migrated shop
        $locale = $this->getDefaultShopLocale();

        foreach ($assets as &$asset) {
            $asset['_locale'] = $locale;
        }
        unset($asset);

        return $assets;
    }
}
