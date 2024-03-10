<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\Helpers\SqliteDbHelper;
use App\ResourceManager;
use Exception;
use gugglegum\RetryHelper\RetryHelper;
use Throwable;

class FetchFilesAction extends AbstractAction
{
    const DOWNLOAD_DELAY = 100; // Delay (ms) after each downloaded file

    private \Aura\Sql\ExtendedPdo $pdo;
    private \Aura\SqlQuery\QueryFactory $queryFactory;
    private SqliteDbHelper $sqliteDbHelper;
    private RetryHelper $retryHelper;
    private array $summary = [
        'downloaded' => 0,
        'exists' => 0,
        'total' => 0,
    ];

    /** @var resource HTTP context with added Bearer authorization header */
    private $downloadContext;

    /**
     * @param ResourceManager $resourceManager
     * @throws Exception
     */
    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->pdo = $this->resourceManager->getSqliteDb();
        $this->sqliteDbHelper = new SqliteDbHelper($this->resourceManager->getSqliteDb());
        $this->queryFactory = new \Aura\SqlQuery\QueryFactory('sqlite', \Aura\SqlQuery\QueryFactory::COMMON);
        $this->retryHelper = (new RetryHelper())
            ->setLogger(new class extends \Psr\Log\AbstractLogger {
                public function log($level, string|\Stringable $message, array $context = []): void {
                    echo "[" . strtoupper($level) . "] {$message}\n";
                }
            })
            ->setDelayBeforeNextAttempt(function (int $attempt) {
                return 5 * $attempt;
            });
        $this->downloadContext = stream_context_create([
            'http' => [
                'header' => [
                    'Authorization: Bearer ' . $this->resourceManager->getConfig()->get('slack.token'),
                ],
            ],
        ]);
    }

    /**
     * @throws Throwable
     */
    public function __invoke(): void
    {
        echo "Start downloading files\n\n";

        $skipChannels = preg_split('/\s*,\s*/', (string) $this->resourceManager->getConfig()->get('slack.skipFilesChannels'), -1, PREG_SPLIT_NO_EMPTY);
        $skipChannelsQuoted = array_map(function($str) { return $this->sqliteDbHelper->quote($str); }, $skipChannels);

        $query = $this->queryFactory->newSelect()->cols(['files.*'])
            ->from('files')
            ->join('inner', 'conversations', 'conversations.id = files.conversation_id')
            ->where("files.mode IN ('hosted', 'snippet')")
            ->where("(conversations.name IS NULL OR conversations.name not in (" . implode(', ', $skipChannelsQuoted) . "))");
        $stmt = $this->pdo->prepare($query->getStatement());
        $stmt->execute($query->getBindValues());
        $files = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo "Files to process: " . count($files) . "\n";

        $filesDir = PROJECT_ROOT_DIR . '/../files';
        if (!is_dir($filesDir)) {
            throw new Exception("Missing files folder \"{$filesDir}\"");
        }

        echo "Start scanning files\n";
        $fileIndex = 0;
        foreach ($files as $file) {
            $this->summary['total']++;
            $localFile = $this->getLocalFile($file, $filesDir);
            $tmpLocalFile = $localFile . '.tmp';

            // Clean up possible existing .tmp file if previous execution was aborted
            if (file_exists($tmpLocalFile)) {
                @unlink($tmpLocalFile);
            }

            echo (++$fileIndex) . ". ID={$file['id']} \"{$file['name']}\" (" . self::formatFileSize((int) $file['size']) . " bytes)\n";
            if (!file_exists($localFile) /*|| filesize($localFile) != $file['size']*/) {
                $this->downloadFile($file, 'url_private', $localFile, $tmpLocalFile);
                $this->summary['downloaded']++;

                if (($localFileSize = filesize($localFile)) != $file['size']) {
                    echo "\tWarning: Downloaded file size is " . self::formatFileSize($localFileSize) . " bytes\n";
                }

                usleep(self::DOWNLOAD_DELAY * 1000);
            } else {
                echo "\tAlready exists\n";
                $this->summary['exists']++;
            }

            // Download thumbnails

            foreach ([/*64, 80, 160,*/ 360, /*480, 720, 800, 960, 1024*/] as $thumbRes) {
                if ($file['thumb_' . $thumbRes] != '') {
                    $localFile = $this->getLocalFile($file, $filesDir, 'thumb_' . $thumbRes);
                    $tmpLocalFile = $localFile . '.tmp';
                    if (file_exists($tmpLocalFile)) {
                        @unlink($tmpLocalFile);
                    }
                    if (!file_exists($localFile)) {
                        echo "\tThumbnail {$thumbRes}\n";
                        $this->downloadFile($file, 'thumb_' . $thumbRes, $localFile, $tmpLocalFile);
                        usleep(self::DOWNLOAD_DELAY * 1000);
                    }
                }
            }
        }
        echo "Finished scanning\n";

        echo "\nSummary:\n";
        echo "    Downloaded: {$this->summary['downloaded']}\n";
        echo "    Already exists: {$this->summary['exists']}\n";
        echo "    Total files: {$this->summary['total']}\n";
    }

    private function getLocalFile(array $file, string $filesDir, string $suffix = ''): string
    {
        $fileName = str_replace([':', '>', '<'], '_', $file['name']);
        if (preg_match('/^(.+)(\.\w+)$/i', $fileName, $m)) {
            $fileName = $m[1] . ($suffix != '' ? '.' . $suffix : ''). $m[2];
        } else {
            $fileName .= ($suffix != '' ? '.' . $suffix : '');
        }
        return $filesDir . '/' . $file['id'] . '-' . $fileName;
    }

    /**
     * @param array $file
     * @param string $urlField
     * @param string $localFile
     * @param string $tmpLocalFile
     * @return void
     * @throws Throwable
     */
    private function downloadFile(array $file, string $urlField, string $localFile, string $tmpLocalFile): void
    {
        $this->fetchFile($file, $urlField, $tmpLocalFile);

        if (file_exists($localFile)) {
            if (!@unlink($localFile)) {
                throw new Exception("Failed to delete \"{$localFile}\" to replace by tmp file");
            }
        }
        rename($tmpLocalFile, $localFile);
    }

    /**
     * @param array $file
     * @param string $urlField
     * @param string $tmpLocalFile
     * @return void
     * @throws Throwable
     */
    private function fetchFile(array $file, string $urlField, string $tmpLocalFile): void
    {
        echo "\tDownloading {$file[$urlField]}\n";
        $retryHelper = clone $this->retryHelper;
        $retryHelper->setOnFailure(function() use ($tmpLocalFile) {
            @unlink($tmpLocalFile);
        });
        $retryHelper->execute(function() use ($tmpLocalFile, $file, $urlField) {
            $remoteHandler = @fopen($file[$urlField], 'r', false, $this->downloadContext);
            if (!$remoteHandler) {
                throw new Exception("Failed to open remote URL \"{$file[$urlField]}\"");
            }
            $localHandler = @fopen($tmpLocalFile, 'w');
            if (!$localHandler) {
                throw new Exception("Failed to create file \"{$tmpLocalFile}\"");
            }
            while (!feof($remoteHandler)) {
                $buffer = @fread($remoteHandler, 1024 * 1024);
                if ($buffer === false) {
                    throw new Exception("Failed to read from remote URL \"{$file[$urlField]}\"");
                }
                $result = @fwrite($localHandler, $buffer);
                if ($result === false || $result != strlen($buffer)) {
                    throw new Exception("Failed to write to file \"{$tmpLocalFile}\"");
                }
            }
            fclose($remoteHandler);
            fclose($localHandler);
            touch($tmpLocalFile, (int) $file['created']);
        }, 5);
    }

    private static function formatFileSize(int $fileSize): string
    {
        return number_format($fileSize, 0, '.', ' ');
    }
}
