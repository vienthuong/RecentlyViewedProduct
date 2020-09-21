<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Service;

use RecentlyViewedProduct\RecentlyViewedProduct;
use RecentlyViewedProduct\Struct\RecentProductCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use Shopware\Core\Content\Cms\DataResolver\FieldConfigCollection;
use Shopware\Core\Content\Cms\SalesChannel\Struct\ProductSliderStruct;
use Shopware\Core\Content\Product\ProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class RecentlyViewedProductService
{
    /**
     * @var EntityRepositoryInterface
     */
    private $rpvRepository;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    /**
     * @var null|RecentProductCollection
     */
    private $recentProducts;

    /**
     * @var null|ProductCollection
     */
    private $productEntities;

    /**
     * @var SalesChannelRepositoryInterface
     */
    private $salesChannelProductRepository;

    public function __construct(
        EntityRepositoryInterface $rpvRepository,
        SystemConfigService $systemConfigService,
        SalesChannelRepositoryInterface $salesChannelProductRepository
    ) {
        $this->rpvRepository = $rpvRepository;
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelProductRepository = $salesChannelProductRepository;
    }

    public function loadRecentProductCollection(SalesChannelContext $context, bool $forceInOrder = false): RecentProductCollection
    {
        // Caching
        if ($this->recentProducts) {
            return $this->recentProducts;
        }

        $criteria = (new Criteria())->addFilter(new EqualsFilter('token', $context->getToken()));

        $result = $this->rpvRepository->search($criteria, $context->getContext());

        $this->recentProducts = $result->first() ? $result->first()->getRecentProduct() : new RecentProductCollection();

        $showInRandomOrder = $this->systemConfigService->get(RecentlyViewedProduct::PLUGIN_NAME . '.config.showInRandomOrder', $context->getSalesChannel()->getId()) ?? false;

        if (!$forceInOrder && $showInRandomOrder) {
            return $this->recentProducts->shuffle();
        }

        return $this->recentProducts;
    }

    public function addRecentProduct(string $productId, SalesChannelContext $context): RecentProductCollection
    {
        $contextRecentProducts = $this->loadRecentProductCollection($context, true);

        if ($contextRecentProducts->first() === $productId) {
            return $contextRecentProducts;
        }

        $productIndex = $contextRecentProducts->findIndex($productId);

        if ($contextRecentProducts->has($productIndex)) {
            $contextRecentProducts->remove((string) $productIndex);
        }

        $contextRecentProducts->unshift($productId);

        $maximumItems = $this->systemConfigService->get(RecentlyViewedProduct::PLUGIN_NAME . '.config.maximumItems', $context->getSalesChannel()->getId()) ?? RecentlyViewedProduct::DEFAULT_MAXIMUM_VIEWED_PRODUCTS;

        if ($contextRecentProducts->count() > $maximumItems) {
            $contextRecentProducts->remove((string) $contextRecentProducts->findIndex($contextRecentProducts->last()));
        }

        $this->saveRecentProductCollection($contextRecentProducts, $context);

        $this->recentProducts = $contextRecentProducts;

        return $contextRecentProducts;
    }

    public function saveRecentProductCollection(RecentProductCollection $recentProductCollection, SalesChannelContext $context): void
    {
        $this->rpvRepository->upsert([[
            'token' => $context->getToken(),
            'recentProduct' => $recentProductCollection,
        ]], $context->getContext());
    }

    public function getRecentProductEntities(SalesChannelContext $context): ?ProductCollection
    {
        // Caching
        if ($this->productEntities) {
            return $this->productEntities;
        }

        $recentProducts = $this->loadRecentProductCollection($context);

        if ($recentProducts->count() === 0) {
            return null;
        }

        $criteria = new Criteria($recentProducts->getElements());

        $multiFilter = new MultiFilter(MultiFilter::CONNECTION_AND, [
            new EqualsFilter('active', true),
            new MultiFilter(MultiFilter::CONNECTION_AND, [
                new RangeFilter('product.visibilities.visibility', [RangeFilter::GTE => 10]),
                new EqualsFilter('product.visibilities.salesChannelId', $context->getSalesChannel()->getId()),
                new EqualsFilter('product.active', true),
            ]),
        ]);

        $criteria->addFilter($multiFilter);

        $searchResult = $this->salesChannelProductRepository->search($criteria, $context);

        $this->productEntities = $searchResult->getEntities();

        $this->cleanNotAvailableRecentProducts($searchResult);

        return $this->productEntities;
    }

    public function setProductEntities(?ProductCollection $productEntities): void
    {
        $this->productEntities = $productEntities;
    }

    public function buildPseudoElement(SalesChannelContext $context): CmsSlotEntity
    {
        /** @var array $pluginConfig */
        $pluginConfig = $this->systemConfigService->get(RecentlyViewedProduct::PLUGIN_NAME . '.config', $context->getSalesChannel()->getId());

        $pseudoSlot = new CmsSlotEntity();
        $pseudoSlot->setType(RecentlyViewedProduct::RECENTLY_VIEWED_PRODUCT_TYPE);
        $pseudoSlot->setSlot('recentlyViewedProductSlider');
        $pseudoSlot->setLocked(false);

        $fieldCollection = new FieldConfigCollection();

        foreach ($pluginConfig as $key => $value) {
            $fieldCollection->add(
                new FieldConfig($key, 'static', $value)
            );
        }

        $pseudoSlot->setConfig(\array_map(function ($config) {
            return [
                'source' => 'static',
                'value' => $config,
            ];
        }, $pluginConfig));

        $pseudoSlot->setFieldConfig($fieldCollection);
        $pseudoSlot->setId(Uuid::randomHex());
        $pseudoSlot->setUniqueIdentifier(Uuid::randomHex());

        return $pseudoSlot;
    }

    public function buildRecentProductSliderStruct(SalesChannelContext $context, ?array $excludeProductIds = []): ProductSliderStruct
    {
        $products = $this->getRecentProductEntities($context) ?? new ProductCollection([]);

        if (!empty($excludeProductIds)) {
            foreach ($excludeProductIds as $productId) {
                $products->remove($productId);
            }
        }

        $productSliderStruct = new ProductSliderStruct();
        $productSliderStruct->setProducts($products);

        return $productSliderStruct;
    }

    private function cleanNotAvailableRecentProducts(EntitySearchResult $searchResult): void
    {
        $entities = $searchResult->getEntities();

        $recentProductIds = $searchResult->getCriteria()->getIds();

        if (!empty($recentProductIds) && count($recentProductIds) !== $entities->count()) {
            $recentProductIds = $searchResult->getCriteria()->getIds();
            $availableIds = [];

            foreach ($recentProductIds as $productId) {
                if ($entities->has($productId)) {
                    $availableIds[] = $productId;
                }
            }

            $this->saveRecentProductCollection(new RecentProductCollection($availableIds), $context);
        }
    }
}
