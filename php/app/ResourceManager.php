<?php

declare(strict_types = 1);

namespace App;

use App\Helpers\SqliteDbHelper;
use Phpfastcache\CacheManager;
use Phpfastcache\Config\ConfigurationOption;

class ResourceManager
{
    private \Luracast\Config\Config $config;
    private \Aura\Sql\ExtendedPdo $sqliteDb;
    private \League\Plates\Engine $plates;
    private \Phpfastcache\Drivers\Files\Driver $cache;
    private \JoliCode\Slack\Client $slackClient;

    public function getConfig(): \Luracast\Config\Config
    {
        if (!isset($this->config)) {
            $dotenv = new \Dotenv\Dotenv(PROJECT_ROOT_DIR . '/..');
            $dotenv->overload();
            $dotenv->required('SLACK_TOKEN')->notEmpty();
            $this->config = \Luracast\Config\Config::init(PROJECT_ROOT_DIR . '/config');
        }
        return $this->config;
    }

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
            $sqliteDbHelper->rolloverMigrations();

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

    /**
     * @return \Phpfastcache\Drivers\Files\Driver
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverCheckException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheDriverNotFoundException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidArgumentException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheInvalidConfigurationException
     * @throws \Phpfastcache\Exceptions\PhpfastcacheLogicException
     * @throws \ReflectionException
     */
    public function getCache(): \Phpfastcache\Drivers\Files\Driver
    {
        if (!isset($this->cache)) {
            $cachePath = PROJECT_ROOT_DIR . '/../cache/';
            if (!is_dir($cachePath)) {
                mkdir($cachePath, 0700);
            }
            /** @var \Phpfastcache\Drivers\Files\Driver $cache */
            $cache = CacheManager::getInstance('files', new ConfigurationOption([
                'path' => $cachePath,
            ]));
            $this->cache = $cache;
        }

        return $this->cache;
    }

    public function getSlackClient(): \JoliCode\Slack\Client
    {
        if (!isset($this->slackClient)) {
            $this->slackClient = \JoliCode\Slack\ClientFactory::create($this->getConfig()->get('slack.token'));
        }
        return $this->slackClient;
    }
}
