<?php declare(strict_types=1);

namespace RecentlyViewedProduct\DAL\Field;

use RecentlyViewedProduct\DAL\FieldSerializer\RecentProductFieldSerializer;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;

class RecentProductField extends JsonField
{
    protected function getSerializerClass(): string
    {
        return RecentProductFieldSerializer::class;
    }
}
