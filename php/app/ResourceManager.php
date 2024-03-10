<?php

declare(strict_types = 1);

namespace App;

use App\Helpers\SqliteDbHelper;

class ResourceManager
{
    private \Luracast\Config\Config $config;
    private \Aura\Sql\ExtendedPdo $sqliteDb;
    private \League\Plates\Engine $plates;
    private \JoliCode\Slack\Client $slackClient;

    public function getConfig(): \Luracast\Config\Config
    {
        if (!isset($this->config)) {
            $dotenv = \Dotenv\Dotenv::createUnsafeImmutable(PROJECT_ROOT_DIR . '/..');
            $dotenv->load();
            $dotenv->required('SLACK_TOKEN')->notEmpty();
            $this->config = \Luracast\Config\Config::init(PROJECT_ROOT_DIR . '/config');
        }
        return $this->config;
    }

    /**
     * @return \Aura\Sql\ExtendedPdo
     * @throws \Exception
     */
    public function getSqliteDb(): \Aura\Sql\ExtendedPdo
    {
        if (!isset($this->sqliteDb)) {
            $dbRelativePath = 'db/slack-history.sqlite3';
            $sqliteDbFile = PROJECT_ROOT_DIR . '/../' . $dbRelativePath;
            $this->sqliteDb = new \Aura\Sql\ExtendedPdo('sqlite:' . $sqliteDbFile);
            $sqliteDbHelper = new SqliteDbHelper($this->sqliteDb);
            if (!file_exists($sqliteDbFile)) {
                echo "Create SQLite database at {$dbRelativePath}\n";
                $sqliteDbHelper->initDb();
            }
            $sqliteDbHelper->rolloverMigrations($sqliteDbFile);
            $this->sqliteDb->exec('PRAGMA foreign_keys=on');
        }
        return $this->sqliteDb;
    }

    public function getTemplateEngine(): \League\Plates\Engine
    {
        if (!isset($this->plates)) {
            $this->plates = new \League\Plates\Engine(PROJECT_ROOT_DIR . '/app/Console/Views', 'phtml');
//            $this->plates->loadExtension(new Web\Components\Plates\UrlFromRouteExtension($this->getWebRouter()));
        }
        return $this->plates;
    }

    public function getSlackClient(): \JoliCode\Slack\Client
    {
        if (!isset($this->slackClient)) {
            $this->slackClient = \JoliCode\Slack\ClientFactory::create($this->getConfig()->get('slack.token'));
        }
        return $this->slackClient;
    }
}
