<?php declare(strict_types=1);

namespace RecentlyViewedProduct\DAL;

use RecentlyViewedProduct\Struct\RecentProductCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;

class RecentlyViewedProductEntity extends Entity
{
    /**
     * @var string
     */
    protected $token;

    /**
     * @var RecentProductCollection|null
     */
    protected $recentProduct;

    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    public function getRecentProduct(): ?RecentProductCollection
    {
        return $this->recentProduct;
    }

    public function setRecentProduct(?RecentProductCollection $recentProduct): void
    {
        $this->recentProduct = $recentProduct;
    }
}
