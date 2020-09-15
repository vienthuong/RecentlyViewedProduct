<?php declare(strict_types=1);

namespace RecentlyViewedProduct\DAL;

use RecentlyViewedProduct\DAL\Field\RecentProductField;
use Shopware\Core\Framework\DataAbstractionLayer\Field\Flag\Required;
use Shopware\Core\Framework\DataAbstractionLayer\Field\StringField;
use Shopware\Core\Framework\DataAbstractionLayer\FieldCollection;
use Shopware\Core\Framework\DataAbstractionLayer\MappingEntityDefinition;

class RecentlyViewedProductDefinition extends MappingEntityDefinition
{
    public const ENTITY_NAME = 'recently_viewed_product';

    public function getEntityName(): string
    {
        return self::ENTITY_NAME;
    }

    public function getCollectionClass(): string
    {
        return RecentlyViewedProductCollection::class;
    }

    public function getEntityClass(): string
    {
        return RecentlyViewedProductEntity::class;
    }

    public function defaultFields(): array
    {
        return [];
    }

    protected function defineFields(): FieldCollection
    {
        return new FieldCollection([
            (new StringField('token', 'token'))->setFlags(new Required()),
            new RecentProductField('recent_product', 'recentProduct', [], []),
        ]);
    }
}
