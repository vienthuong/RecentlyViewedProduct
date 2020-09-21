<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Subscriber\Storefront;

use RecentlyViewedProduct\Service\RecentlyViewedProductService;
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
            $productId = $page->getProduct()->getId();

            $slot = $this->recentlyViewedProductService->buildPseudoElement($context);
            $productSliderStruct = $this->recentlyViewedProductService->buildRecentProductSliderStruct($context, [$productId]);
            $slot->setData($productSliderStruct);

            $page->setExtensions([
                'recentlyViewedProductElement' => $slot,
            ]);

            $this->recentlyViewedProductService->addRecentProduct($productId, $context);
        } catch (\Throwable $exception) {
            // nth
        }
    }

    public function addRecentlyViewedProductFromQuickView(MinimalQuickViewPageLoadedEvent $event): void
    {
        try {
            $page = $event->getPage();

            $this->recentlyViewedProductService->addRecentProduct($page->getProduct()->getId(), $event->getSalesChannelContext());
        } catch (\Throwable $exception) {
            // nth
        }
    }
}
