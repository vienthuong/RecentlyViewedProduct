<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Struct;

use Shopware\Core\Framework\Struct\Collection;

class RecentProductCollection extends Collection
{
    public function shuffle(): self
    {
        $shuffledArray = $this->elements;
        \shuffle($shuffledArray);

        return $this->createNew($shuffledArray);
    }

    public function unshift(string $element): void
    {
        $this->validateType($element);

        array_unshift($this->elements, $element);
    }

    public function findIndex(string $productId)
    {
        $search = array_search($productId, $this->elements, true);

        return $search ?: null;
    }
}
