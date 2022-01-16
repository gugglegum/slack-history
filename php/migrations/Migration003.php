<?php

/**
 * Add reactions table
 */
class Migration003 extends \App\AbstractMigration
{
    public function __invoke()
    {
        $this->pdo->exec('CREATE TABLE "reactions" (
            "message_ts" TEXT NOT NULL,
            "conversation_id" TEXT NOT NULL,
            "name" TEXT NOT NULL,
            "users" TEXT,
            PRIMARY KEY("message_ts","conversation_id","name"),
            FOREIGN KEY (message_ts, conversation_id) REFERENCES messages(ts, conversation_id)
        )');
    }

}
