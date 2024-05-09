<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Controller;

use RecentlyViewedProduct\Service\RecentlyViewedProductService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\NoContentResponse;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(defaults: ['_routeScope' => ['storefront']])]
class RecentProductController extends StorefrontController
{
    public function __construct(
        private readonly EntityRepository             $cmsSlotRepo,
        private readonly RecentlyViewedProductService $recentlyViewedProductService
    )
    {
    }

    #[Route(path: '/recent-product-slider/content', name: 'frontend.recent-product-slider.content', methods: ['GET'], options: ['seo' => 'false'], defaults: ['XmlHttpRequest' => 'true'])]
    public function recentProductSliderContent(Request $request, SalesChannelContext $context): Response
    {
        $elementId = $request->get('elementId');

        if (empty($elementId) || !Uuid::isValid($elementId)) {
            return new NoContentResponse();
        }

        $criteria = new Criteria([$elementId]);
        $result = $this->cmsSlotRepo->search($criteria, $context->getContext());

        $element = $result->first() ?? $this->recentlyViewedProductService->buildPseudoElement($context);

        $productSliderStruct = $this->recentlyViewedProductService->buildRecentProductSliderStruct($context, $request->get('excludeProductId') ? [$request->get('excludeProductId')] : []);
        $element->setData($productSliderStruct);

        if (empty($element->getData()) || $element->getData()->getProducts()->count() === 0) {
            return new NoContentResponse();
        }

        return $this->renderStorefront('@Storefront/storefront/element/cms-element-recently-viewed-product-slider-content.html.twig', ['element' => $element]);
    }
}
