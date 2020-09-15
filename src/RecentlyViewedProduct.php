<?php declare(strict_types=1);

namespace RecentlyViewedProduct;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class RecentlyViewedProduct extends Plugin
{
    public const PLUGIN_NAME = 'RecentlyViewedProduct';
    public const DEFAULT_MAXIMUM_VIEWED_PRODUCTS = 10;
    public const RECENTLY_VIEWED_PRODUCT_TYPE = 'recently-viewed-product-slider';

    public function uninstall(UninstallContext $uninstallContext): void
    {
        if ($uninstallContext->keepUserData()) {
            parent::uninstall($uninstallContext);

            return;
        }

        /** @var EntityRepositoryInterface $cmsBlockRepo */
        $cmsBlockRepo = $this->container->get('cms_block.repository');

        $context = Context::createDefaultContext();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('type', self::RECENTLY_VIEWED_PRODUCT_TYPE));

        $cmsBlocks = $cmsBlockRepo->searchIds($criteria, $context);

        $cmsBlockRepo->delete(array_values($cmsBlocks->getData()), $context);

        $connection = $this->container->get(Connection::class);

        $connection->exec('DROP TABLE IF EXISTS recently_viewed_product;');
    }
}
