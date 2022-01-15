<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\Iterators\MessageIterator;
use App\ResourceManager;

class CompileHtmlAction extends AbstractAction
{
    private \Aura\Sql\ExtendedPdo $pdo;
    private \Aura\SqlQuery\QueryFactory $queryFactory;

    /**
     * @var array[]
     */
    private array $users;

    private string $htmlDir;

    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->pdo = $this->resourceManager->getSqliteDb();
        $this->queryFactory = new \Aura\SqlQuery\QueryFactory('sqlite', \Aura\SqlQuery\QueryFactory::COMMON);

        $this->users = $this->getUsers();
        $this->htmlDir = PROJECT_ROOT_DIR . '/../html';
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function __invoke()
    {
        if (!is_dir($this->htmlDir)) {
            mkdir($this->htmlDir, 0700);
        }

        $query = $this->queryFactory->newSelect()->cols(['*'])
            ->from('conversations');
        $stmt = $this->pdo->prepare($query->getStatement());
        $stmt->execute($query->getBindValues());
        $conversations = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        echo "Render index\n";
        file_put_contents($this->htmlDir . '/index.html', $this->renderIndex($conversations));

        foreach ($conversations as $conversation) {
            if ($conversation['name'] != '') {
                echo "Render channel " . $conversation['name'];
            } else {
                echo "Render user ";
                $user = array_key_exists($conversation['user'], $this->users) ? $this->users[$conversation['user']] : null;
                if ($user) {
                    $profile = $user['profile'];
                    if ($profile) {
                        if ($profile['display_name'] != '') {
                            echo $profile['display_name'];
                        } else {
                            echo $profile['real_name'];
                        }
                    } else {
                        echo $user['real_name'] != '' ? $user['real_name'] : $user['name'];
                    }
                } else {
                    echo $conversation['id'];
                }
            }
            echo "\n";

            file_put_contents($this->htmlDir . '/' . $conversation['id'] . '.html', $this->renderConversation($conversation));
        }

        echo "Well done!\n";
    }

    /**
     * @param array $conversations
     * @return string
     * @throws \Exception
     */
    private function renderIndex(array $conversations): string
    {
        foreach ($conversations as &$conversation) {
            $memberIds = preg_split('/\s*,\s*/', (string) $conversation['members'], -1, PREG_SPLIT_NO_EMPTY);
            $members = [];
            foreach ($memberIds as $memberId) {
                $members[] = $this->getUser($memberId);
            }
            $conversation['members'] = $members;
            if ($conversation['user'] !== null) {
                $conversation['user'] = $this->getUser($conversation['user']);
            }
        }
        return $this->resourceManager->getTemplateEngine()
            ->render('index', [
                'conversations' => $conversations,
            ]);
    }

    /**
     * @param array $conversation
     * @return string
     */
    private function renderConversation(array $conversation): string
    {
//        $query = $this->queryFactory->newSelect()->cols(['*'])
//            ->from('messages')
//            ->where('conversation_id = ?', $conversation['id'])
//            ->orderBy(['ts']);
//        $stmt = $this->pdo->prepare($query->getStatement());
//        $stmt->execute($query->getBindValues());
        $messages = new MessageIterator($this->pdo, $conversation['id']);

        return $this->resourceManager->getTemplateEngine()
            ->render('conversation', [
                'messages' => $messages,
                'users' => $this->users,
            ]);
    }

    private function getUsers(): array
    {
        $query = $this->queryFactory->newSelect()->cols(['*'])->from('users');
        $stmt = $this->pdo->prepare($query->getStatement());
        $stmt->execute($query->getBindValues());
        $users = [];
        while ($user = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $users[$user['id']] = $user;
            $users[$user['id']]['profile'] = null;
        }
        $stmt->closeCursor();

        $query = $this->queryFactory->newSelect()->cols(['*'])->from('profiles');
        $stmt = $this->pdo->prepare($query->getStatement());
        $stmt->execute($query->getBindValues());
        while ($profile = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $users[$profile['user_id']]['profile'] = $profile;
        }
        $stmt->closeCursor();

        return $users;
    }

    /**
     * @param string $userId
     * @return array
     * @throws \Exception
     */
    private function getUser(string $userId): array
    {
        if (array_key_exists($userId, $this->users)) {
            return $this->users[$userId];
        } else {
            throw new \Exception("Missing user with ID={$userId}");
        }
    }
}
