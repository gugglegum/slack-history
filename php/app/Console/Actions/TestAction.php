<?php

declare(strict_types = 1);

namespace App\Console\Actions;

class TestAction extends AbstractAction
{
    public function __invoke(): void
    {
        echo "hello\n";
    }
}
