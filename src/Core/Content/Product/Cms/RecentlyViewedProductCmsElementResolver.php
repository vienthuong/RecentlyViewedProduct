<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Core\Content\Product\Cms;

use RecentlyViewedProduct\RecentlyViewedProduct;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\Uuid\Uuid;

class RecentlyViewedProductCmsElementResolver extends AbstractCmsElementResolver
{
    public function getType(): string
    {
        return RecentlyViewedProduct::RECENTLY_VIEWED_PRODUCT_TYPE;
    }

    /**
     * This collector is just a pseudo collection, the actual data is loaded via AJAX
     */
    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        return null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $slider = new ProductSliderStruct();
        $slot->setData($slider);

        $product = new ProductEntity();
        $product->setUniqueIdentifier(Uuid::randomHex());
        $slider->setProducts(new ProductCollection([$product]));
    }
}
