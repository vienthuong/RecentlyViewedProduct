<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Core\Content\Product\Cms;

use RecentlyViewedProduct\RecentlyViewedProduct;
use RecentlyViewedProduct\Service\RecentlyViewedProductService;
use RecentlyViewedProduct\Struct\RecentProductCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\CriteriaCollection;
use Shopware\Core\Content\Cms\DataResolver\Element\AbstractCmsElementResolver;
use Shopware\Core\Content\Cms\DataResolver\Element\ElementDataCollection;
use Shopware\Core\Content\Cms\DataResolver\ResolverContext\ResolverContext;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Content\Product\ProductDefinition;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

class RecentlyViewedProductCmsElementResolver extends AbstractCmsElementResolver
{
    private const RECENTLY_VIEWED_PRODUCT_CRITERIA_KEY = 'recently-viewed-product-key';

    /**
     * @var EntityRepositoryInterface
     */
    private $productRepository;

    /**
     * @var RecentlyViewedProductService
     */
    private $recentlyViewedProductService;

    public function __construct(
        EntityRepositoryInterface $productRepository,
        RecentlyViewedProductService $recentlyViewedProductService
    ) {
        $this->productRepository = $productRepository;
        $this->recentlyViewedProductService = $recentlyViewedProductService;
    }

    public function getType(): string
    {
        return RecentlyViewedProduct::RECENTLY_VIEWED_PRODUCT_TYPE;
    }

    public function collect(CmsSlotEntity $slot, ResolverContext $resolverContext): ?CriteriaCollection
    {
        $collection = new CriteriaCollection();

        $salesChannelContext = $resolverContext->getSalesChannelContext();

        $recentlyViewedProductCollection = $this->recentlyViewedProductService->loadRecentProductCollection($salesChannelContext);

        if ($recentlyViewedProductCollection->count() === 0) {
            return null;
        }

        $criteria = new Criteria($recentlyViewedProductCollection->getElements());

        $criteria->addFilter(new EqualsFilter('active', true));
        $collection->add(self::RECENTLY_VIEWED_PRODUCT_CRITERIA_KEY . '_' . $slot->getUniqueIdentifier(), ProductDefinition::class, $criteria);

        return $collection->all() ? $collection : null;
    }

    public function enrich(CmsSlotEntity $slot, ResolverContext $resolverContext, ElementDataCollection $result): void
    {
        $slider = new ProductSliderStruct();
        $slot->setData($slider);

        $searchResult = $result->get(self::RECENTLY_VIEWED_PRODUCT_CRITERIA_KEY . '_' . $slot->getUniqueIdentifier());

        if ($searchResult !== null) {
            /** @var ProductCollection $streamResult */
            $streamResult = $searchResult->getEntities();
            $recentProductIds = $searchResult->getCriteria()->getIds();

            if (!empty($recentProductIds) && count($recentProductIds) !== $streamResult->count()) {
                $recentProductIds = $searchResult->getCriteria()->getIds();
                $removeIds = [];

                foreach ($recentProductIds as $productId) {
                    if (!$streamResult->has($productId)) {
                        $removeIds[] = $productId;
                    }
                }

                $this->recentlyViewedProductService->saveRecentProductCollection(new RecentProductCollection($removeIds), $resolverContext->getSalesChannelContext());
            }

            foreach ($streamResult as $product) {
                if ($product->getParentId() === null) {
                    continue;
                }

                $product->setGrouped($this->isGrouped($product));
            }

            $this->recentlyViewedProductService->setProductEntities($streamResult);
            $slider->setProducts($streamResult);

            return;
        }

        $slider->setProducts(new ProductCollection([]));
    }

    private function isGrouped(ProductEntity $product): bool
    {
        // get all configured expanded groups
        $groups = array_filter(
            (array) $product->getConfiguratorGroupConfig(),
            function (array $config) {
                return $config['expressionForListings'] ?? false;
            }
        );

        // get ids of groups for later usage
        $groups = array_column($groups, 'id');

        // expanded group count matches option count? All variants are displayed
        if ($product->getOptionIds() !== null && count($groups) === count($product->getOptionIds())) {
            return false;
        }

        return true;
    }
}
