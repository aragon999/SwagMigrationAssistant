<?php declare(strict_types=1);

namespace SwagMigrationNext\Profile\Shopware55\Logging;

final class Shopware55LogTypes
{
    public const ASSOCIATION_REQUIRED_MISSING = 'SWAG-MIGRATION-SHOPWARE55-ASSOCIATION-REQUIRED-MISSING';
    public const CANNOT_DOWNLOAD_MEDIA = 'SWAG-MIGRATION-SHOPWARE55-CANNOT-DOWNLOAD-DATA';
    public const CANNOT_COPY_MEDIA = 'SWAG-MIGRATION-SHOPWARE55-CANNOT-COPY-DATA';
    public const SOURCE_FILE_NOT_FOUND = 'SWAG-MIGRATION-SHOPWARE55-SOURCE-FILE-NOT-FOUND';
    public const EMPTY_LOCALE = 'SWAG-MIGRATION-SHOPWARE55-EMPTY-LOCALE';
    public const EMPTY_LINE_ITEM_IDENTIFIER = 'SWAG-MIGRATION-SHOPWARE55-EMPTY-LINE-ITEM-IDENTIFIER';
    public const EMPTY_NECESSARY_DATA_FIELDS = 'SWAG-MIGRATION-SHOPWARE55-EMPTY-NECESSARY-DATA-FIELDS';
    public const INVALID_UNSERIALIZED_DATA = 'SWAG-MIGRATION-SHOPWARE55-INVALID-UNSERIALIZED-DATA';
    public const NO_ADDRESS_DATA = 'SWAG-MIGRATION-SHOPWARE55-NO-ADDRESS-DATA';
    public const NO_DEFAULT_BILLING_AND_SHIPPING_ADDRESS = 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-BILLING-AND-SHIPPING-ADDRESS';
    public const NO_DEFAULT_BILLING_ADDRESS = 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-BILLING-ADDRESS';
    public const NO_DEFAULT_SHIPPING_ADDRESS = 'SWAG-MIGRATION-SHOPWARE55-NO-DEFAULT-SHIPPING-ADDRESS';
    public const NOT_CONVERTABLE_OBJECT_TYPE = 'SWAG-MIGRATION-SHOPWARE55-NOT-CONVERT-ABLE-OBJECT-TYPE';
    public const PRODUCT_MEDIA_NOT_CONVERTED = 'SWAG-MIGRATION-SHOPWARE55-PRODUCT-MEDIA-NOT-CONVERTED';
    public const UNKNOWN_ORDER_STATE = 'SWAG-MIGRATION-SHOPWARE55-UNKNOWN-ORDER-STATE';
    public const UNKNOWN_PAYMENT_METHOD = 'SWAG-MIGRATION-SHOPWARE55-UNKNOWN-PAYMENT_METHOD';
    public const UNKNOWN_TRANSACTION_STATE = 'SWAG-MIGRATION-SHOPWARE55-UNKNOWN-TRANSACTION-STATE';
}
