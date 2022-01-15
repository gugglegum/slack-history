<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\ResourceManager;

abstract class AbstractAction
{
    protected ResourceManager $resourceManager;

    public function __construct(ResourceManager $resourceManager)
    {
        $this->resourceManager = $resourceManager;
    }

    abstract public function __invoke();
}
