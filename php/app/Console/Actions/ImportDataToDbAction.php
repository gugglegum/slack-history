<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\Helpers\ConfigHelper;
use App\Helpers\SqliteDbHelper;
use App\ResourceManager;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Api\Model\ObjsUser;
use JoliCode\Slack\Api\Model\ObjsUserProfile;

class ImportDataToDbAction extends AbstractAction
{
    private SqliteDbHelper $sqliteDbHelper;
    private ConfigHelper $configHelper;

    /**
     * @var ObjsUser[]
     */
    private array $users;

    /**
     * @var ObjsUserProfile[]
     */
    private array $profiles;

    /**
     * @param ResourceManager $resourceManager
     * @throws \Exception
     */
    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->sqliteDbHelper = new SqliteDbHelper($this->resourceManager->getSqliteDb());
        $this->configHelper = new ConfigHelper($this->resourceManager->getConfig());
    }

    public function __invoke()
    {
        $this->importUsers();
        $this->importConversations();
    }

    private function importUsers()
    {
        echo "Import users\n";
        $listNumber = 1;
        $dir = PROJECT_ROOT_DIR . '/../data/users.list';
        $this->users = [];
        $this->profiles = [];
        do {
            $listFile = $dir . "/list-{$listNumber}.serialized";
            if (file_exists($listFile)) {
                /** @var \JoliCode\Slack\Api\Model\UsersListGetResponse200 $list */
                $list = unserialize(file_get_contents($listFile));
                foreach ($list->getMembers() as $user) {
                    $this->sqliteDbHelper->upsertUser($user);
                    $this->users[$user->getId()] = $user;
                    $profile = $user->getProfile();
                    if ($profile) {
                        $this->sqliteDbHelper->upsertProfile($profile, $user->getId());
                        $this->profiles[$user->getId()] = $profile;
                    }
                }
                $listNumber++;
            } else {
                break;
            }
        } while (true);
    }

    private function importConversations()
    {
        echo "Import conversations\n";
        $listNumber = 1;
        $dir = PROJECT_ROOT_DIR . '/../data/conversations.list';
        do {
            $listFile = $dir . "/list-{$listNumber}.serialized";
            if (file_exists($listFile)) {
                /** @var \JoliCode\Slack\Api\Model\ConversationsListGetResponse200 $list */
                $list = unserialize(file_get_contents($listFile));
                foreach ($list->getChannels() as $conversation) {
                    if ($conversation->getName() != '') {
                        if ($this->configHelper->isSkipChannel($conversation->getName())) {
                            continue;
                        }
                        echo "  Channel " . $conversation->getName();
                        $folder = $conversation->getId() . '-' . $conversation->getName();
                    } else {
                        echo "  User ";
                        $user = array_key_exists($conversation->getUser(), $this->users) ? $this->users[$conversation->getUser()] : null;
                        if ($user) {
                            $profile = array_key_exists($conversation->getUser(), $this->profiles) ? $this->profiles[$conversation->getUser()] : null;
                            if ($profile) {
                                if ($profile->getDisplayName() != '') {
                                    echo $profile->getDisplayName();
                                } else {
                                    echo $profile->getRealName();
                                }
                            } else {
                                echo $user->getRealName() != '' ? $user->getRealName() : $user->getName();
                            }
                        } else {
                            echo $conversation->getId();
                        }
                        $folder = $conversation->getId() . '-user-' . $conversation->getUser();
                    }
                    echo ' ';
                    $this->sqliteDbHelper->upsertConversation($conversation);
                    $this->importConversationHistory($conversation, PROJECT_ROOT_DIR . "/../data/conversations.history/{$folder}");
                    echo "\n";
                }
                $listNumber++;
            } else {
                break;
            }
        } while (true);
    }

    /**
     * @param ObjsConversation $conversation
     * @param string $dir
     * @return void
     */
    private function importConversationHistory(ObjsConversation $conversation, string $dir)
    {
        $historyNumber = 1;
        do {
            $file = $dir . "/history-{$historyNumber}.serialized";
            if (file_exists($file)) {
                echo ".";
                /** @var \JoliCode\Slack\Api\Model\ConversationsHistoryGetResponse200 $list */
                $list = unserialize(file_get_contents($file));
                foreach ($list->getMessages() as $message) {
                    $this->sqliteDbHelper->upsertMessage($message, $conversation->getId());

                    if ($message->getReactions() != null) {
                        foreach ($message->getReactions() as $reaction) {
                            $this->sqliteDbHelper->upsertReaction($reaction, $message->getTs(), $conversation->getId());
                        }
                    }

                    if ($attachments = $message->getAttachments()) {
                        foreach ($attachments as $index => $attachment) {
                            $this->sqliteDbHelper->upsertAttachment($attachment, $message->getTs(), $conversation->getId(), $index);
                        }
                    }
                    if ($files = $message->getFiles()) {
                        foreach ($files as $file) {
                            $this->sqliteDbHelper->upsertFile($file, $message->getTs(), $conversation->getId());
                        }
                    }
                }
                $historyNumber++;
            } else {
                break;
            }
        } while (true);
    }
}
