<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Subscriber\Storefront;

use RecentlyViewedProduct\RecentlyViewedProduct;
use RecentlyViewedProduct\Service\RecentlyViewedProductService;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsBlock\CmsBlockEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSection\CmsSectionEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Content\Cms\Events\CmsPageLoadedEvent;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CmsPageLoaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var RecentlyViewedProductService
     */
    private $recentlyViewedProductService;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    public function __construct(
        RecentlyViewedProductService $recentlyViewedProductService,
        SystemConfigService $systemConfigService
    ) {
        $this->recentlyViewedProductService = $recentlyViewedProductService;
        $this->systemConfigService = $systemConfigService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CmsPageLoadedEvent::class => 'resolveSalesChannelContext',
        ];
    }

    public function resolveSalesChannelContext(CmsPageLoadedEvent $event): void
    {
        try {
            $context = $event->getSalesChannelContext();

            /** @var CmsPageCollection $result */
            $result = $event->getResult();

            /** @var array $pluginConfig */
            $pluginConfig = $this->systemConfigService->get(RecentlyViewedProduct::PLUGIN_NAME . '.config', $context->getSalesChannel()->getId());

            if (empty($pluginConfig) || $pluginConfig['autoShowOnCmsPage'] === false) {
                return;
            }

            if ($result->count() === 0 || !$result->first() instanceof CmsPageEntity) {
                return;
            }

            /** @var CmsSectionCollection|null $cmsPageSections */
            $cmsPageSections = $result->first()->getSections();

            if (!$cmsPageSections || $cmsPageSections->count() === 0) {
                return;
            }

            $products = $this->recentlyViewedProductService->getRecentProductEntities($context);

            if (!$products || $products->count() === 0) {
                return;
            }

            /** @var CmsSectionEntity $cmsPageSection */
            $cmsPageSection = $cmsPageSections->last();

            $lastSectionBlocks = $cmsPageSection->getBlocks();

            if (!$lastSectionBlocks || $lastSectionBlocks->count() === 0) {
                return;
            }

            $pseudoSection = CmsSectionEntity::createFrom($cmsPageSection);
            $pseudoSection->setUniqueIdentifier(Uuid::randomHex());

            $mainSectionBlocks = $lastSectionBlocks->filter(function (CmsBlockEntity $cmsBlock) {
                return $cmsBlock->getSectionPosition() === 'main';
            });

            $referenceBlock = $mainSectionBlocks->last();

            if (!$referenceBlock) {
                return;
            }

            $pseudoSliderBlock = CmsBlockEntity::createFrom($referenceBlock);
            $pseudoSliderBlock->setType(RecentlyViewedProduct::RECENTLY_VIEWED_PRODUCT_TYPE);
            $pseudoSliderBlock->setSectionId($cmsPageSection->getId());
            $pseudoSliderBlock->setUniqueIdentifier(Uuid::randomHex());
            $pseudoSliderBlock->setId(Uuid::randomHex());

            $pseudoSlot = $this->recentlyViewedProductService->buildPseudoElement($context);
            $productSliderStruct = $this->recentlyViewedProductService->buildRecentProductSliderStruct($context);
            $pseudoSlot->setBlockId($pseudoSliderBlock->getId());
            $pseudoSlot->setData($productSliderStruct);

            $pseudoSliderBlock->setPosition(1);
            $pseudoSliderBlock->setSlots(new CmsSlotCollection([$pseudoSlot]));

            $pseudoSection->setBlocks(new CmsBlockCollection([$pseudoSliderBlock]));
            $pseudoSection->setType('default');
            $cmsPageSections->add($pseudoSection);
        } catch (\Throwable $exception) {
            // nth
        }
    }
}
