<?php

namespace App\Iterators;

use Aura\Sql\ExtendedPdo;
//use Aura\SqlQuery\Common\SelectInterface;
use Iterator;
use PDO;
use PDOStatement;

class MessageIterator implements Iterator
{
    private PDOStatement $stmt;
//    private SelectInterface $query;

    private int $key;
    private array|bool $current;

    public function __construct(ExtendedPdo $pdo, string $conversationId)
    {
//        $queryFactory = new QueryFactory('sqlite', QueryFactory::COMMON);
//        $this->query = $queryFactory->newSelect()->cols(['messages.*', 'IIF(att.count > 0, 1, 0) has_attachments'])
//            ->from('messages')
//            ->join('left', '(SELECT message_ts, COUNT(*) count FROM attachments GROUP BY message_ts) att', 'att.message_ts = messages.ts')
//            ->where('conversation_id = ?', $conversationId)
//            ->orderBy(['ts']);

        $query = "SELECT messages.*, IIF(att.att_count > 0, 1, 0) has_attachments
            FROM messages
            LEFT JOIN (SELECT message_ts, COUNT(*) att_count FROM attachments GROUP BY message_ts) att ON att.message_ts = messages.ts
            WHERE conversation_id = :conversation_id
            ORDER BY ts";

        $this->stmt = $pdo->prepare($query);
        $this->stmt->bindValue('conversation_id', $conversationId);
    }

    public function current(): mixed
    {
        return $this->current;
    }

    public function next(): void
    {
        $this->current = $this->stmt->fetch(PDO::FETCH_ASSOC);
        $this->key++;
    }

    public function key(): mixed
    {
        return $this->key;
    }

    public function valid(): bool
    {
        return $this->current !== false;
    }

    public function rewind(): void
    {
        $this->stmt->execute();
        $this->key = -1;
        $this->next();
    }
}
