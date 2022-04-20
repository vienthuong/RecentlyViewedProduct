<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Subscriber\Storefront;

use RecentlyViewedProduct\Service\RecentlyViewedProductService;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Product\QuickView\MinimalQuickViewPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageLoaderSubscriber implements EventSubscriberInterface
{
    /**
     * @var RecentlyViewedProductService
     */
    private $recentlyViewedProductService;

    public function __construct(
        RecentlyViewedProductService $recentlyViewedProductService
    ) {
        $this->recentlyViewedProductService = $recentlyViewedProductService;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'addRecentlyViewedProductFromDetail',
            MinimalQuickViewPageLoadedEvent::class => 'addRecentlyViewedProductFromQuickView',
        ];
    }

    public function addRecentlyViewedProductFromDetail(ProductPageLoadedEvent $event): void
    {
        try {
            $page = $event->getPage();
            $context = $event->getSalesChannelContext();
            $productId = $this->getMainProductId($page->getProduct());

            $slot = $this->recentlyViewedProductService->buildPseudoElement($context);
            $productSliderStruct = $this->recentlyViewedProductService->buildRecentProductSliderStruct($context, [$productId]);
            $slot->setData($productSliderStruct);

            $page->addExtension('recentlyViewedProductElement', $slot);

            $this->recentlyViewedProductService->addRecentProduct($productId, $context);
        } catch (\Throwable $exception) {
            // nth
        }
    }

    public function addRecentlyViewedProductFromQuickView(MinimalQuickViewPageLoadedEvent $event): void
    {
        try {
            $productId = $this->getMainProductId($event->getPage()->getProduct());

            $this->recentlyViewedProductService->addRecentProduct($productId, $event->getSalesChannelContext());
        } catch (\Throwable $exception) {
            // nth
        }
    }

    private function getMainProductId(ProductEntity $product): string {
        return $product->getParentId() ?? $product->getId();
    }
}
