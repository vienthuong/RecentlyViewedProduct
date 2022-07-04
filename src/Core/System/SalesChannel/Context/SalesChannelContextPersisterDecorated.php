<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Core\System\SalesChannel\Context;

use Doctrine\DBAL\Connection;
use Shopware\Core\Checkout\Cart\CartPersister;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SalesChannelContextPersisterDecorated extends SalesChannelContextPersister
{
    /**
     * @var SalesChannelContextPersister
     */
    private $decorated;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        SalesChannelContextPersister $decorated,
        Connection $connection,
        EventDispatcherInterface $eventDispatcher,
        CartPersister $cartPersister,
        ?string $lifetimeInterval = 'P1D'
    ) {
        $this->decorated = $decorated;
        $this->connection = $connection;

        parent::__construct($connection, $eventDispatcher, $cartPersister, $lifetimeInterval);
    }

    public function replace(string $oldToken, SalesChannelContext $context): string
    {
        $newToken = $this->decorated->replace($oldToken, $context);

        $this->connection->executeUpdate(
            'UPDATE `recently_viewed_product`
                   SET `token` = :newToken
                   WHERE `token` = :oldToken',
            [
                'newToken' => $newToken,
                'oldToken' => $oldToken,
            ]
        );

        return $newToken;
    }

    public function delete(string $token, ?string $salesChannelId = null, ?string $customerId = null): void
    {
        $this->decorated->delete($token, $salesChannelId, $customerId);

        $this->connection->executeUpdate(
            'DELETE FROM recently_viewed_product WHERE token = :token',
            [
                'token' => $token,
            ]
        );
    }
}
