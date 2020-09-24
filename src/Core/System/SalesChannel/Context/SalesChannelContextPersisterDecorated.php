<?php declare(strict_types=1);

namespace RecentlyViewedProduct\Core\System\SalesChannel\Context;

use Doctrine\DBAL\Connection;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextPersister;
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
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->decorated = $decorated;
        $this->connection = $connection;

        parent::__construct($connection, $eventDispatcher);
    }

    public function replace(string $oldToken/*, ?SalesChannelContext $context = null*/): string
    {
        $newToken = $this->decorated->replace($oldToken);

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

    public function delete(string $token): void
    {
        $this->decorated->delete($token);

        $this->connection->executeUpdate(
            'DELETE FROM recently_viewed_product WHERE token = :token',
            [
                'token' => $token,
            ]
        );
    }
}
