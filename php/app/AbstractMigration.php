<?php

namespace App;

abstract class AbstractMigration
{
    protected \Aura\Sql\ExtendedPdo $pdo;

    public function __construct(\Aura\Sql\ExtendedPdo $pdo)
    {
        $this->pdo = $pdo;
    }

    abstract function __invoke();
}
