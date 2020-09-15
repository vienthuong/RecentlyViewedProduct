<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Migration;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Migration\MigrationStep;

class Migration1600098518CreateRecentlyViewedProductTable extends MigrationStep
{
    public function getCreationTimestamp(): int
    {
        return 1600098518;
    }

    public function update(Connection $connection): void
    {
        $connection->executeUpdate('
            CREATE TABLE IF NOT EXISTS `recently_viewed_product` (
              `token` VARCHAR(50) COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,
              `recent_product` JSON NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ');
    }

    public function updateDestructive(Connection $connection): void
    {
        // implement update destructive
    }
}
