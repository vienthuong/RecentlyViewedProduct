<?php declare(strict_types=1);

namespace RecentlyViewedProduct\DAL;

use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;

/**
 * @method void                             add(RecentlyViewedProductEntity $entity)
 * @method void                             set(string $key, RecentlyViewedProductEntity $entity)
 * @method RecentlyViewedProductEntity[]    getIterator()
 * @method RecentlyViewedProductEntity[]    getElements()
 * @method RecentlyViewedProductEntity|null get(string $key)
 * @method RecentlyViewedProductEntity|null first()
 * @method RecentlyViewedProductEntity|null last()
 */
class RecentlyViewedProductCollection extends EntityCollection
{
    public function getApiAlias(): string
    {
        return 'recently_viewed_product_collection';
    }

    protected function getExpectedClass(): string
    {
        return RecentlyViewedProductEntity::class;
    }
}
