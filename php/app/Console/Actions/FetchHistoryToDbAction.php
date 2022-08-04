<?php

declare(strict_types = 1);

namespace App\Console\Actions;

use App\Helpers\ConfigHelper;
use App\Helpers\SqliteDbHelper;
use App\ResourceManager;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Api\Model\ObjsFile;
use JoliCode\Slack\Api\Model\ObjsMessage;
use JoliCode\Slack\Api\Model\ObjsMessageAttachmentsItem;
use JoliCode\Slack\Api\Model\ObjsReaction;
use JoliCode\Slack\Api\Model\ObjsUser;
use JoliCode\Slack\Api\Model\ObjsUserProfile;

class FetchHistoryToDbAction extends AbstractAction
{
    const SLACK_DELAY = 250;
    private SqliteDbHelper $sqliteDbHelper;
    private ConfigHelper $configHelper;
    private \JoliCode\Slack\Client $slackClient;

    /**
     * @var ObjsUser[]
     */
    private array $users;

    /**
     * @var ObjsUserProfile[]
     */
    private array $profiles;

    private int $totalMessagesAdded = 0;
    private int $totalMessagesFetched = 0;

    /**
     * @param ResourceManager $resourceManager
     * @throws \Exception
     */
    public function __construct(ResourceManager $resourceManager)
    {
        parent::__construct($resourceManager);
        $this->sqliteDbHelper = new SqliteDbHelper($this->resourceManager->getSqliteDb());
        $this->configHelper = new ConfigHelper($this->resourceManager->getConfig());
        $this->slackClient = $this->resourceManager->getSlackClient();
    }

    public function __invoke()
    {
        $this->fetchUsers();
        $this->fetchConversations();
        echo "\nTotal {$this->totalMessagesAdded} new messages of {$this->totalMessagesFetched} messages fetched\n";
        echo "Well done!\n";
    }

    private function fetchUsers()
    {
        echo "Fetch users\n";
        $listNumber = 0;
        $query = [];
        $this->users = [];
        $this->profiles = [];
        $usersFetched = 0;
        $usersAdded = 0;
        do {
            $listNumber++;
            echo "Users list #{$listNumber}\n";
            usleep(self::SLACK_DELAY * 1000);
            $list = $this->slackClient->usersList($query);
            foreach ($list->getMembers() as $user) {
                if ($this->sqliteDbHelper->upsertUser($user)) {
                    $usersAdded++;
                }
                $this->users[$user->getId()] = $user;
                $profile = $user->getProfile();
                if ($profile) {
                    $this->sqliteDbHelper->upsertProfile($profile, $user->getId());
                    $this->profiles[$user->getId()] = $profile;
                }
            }
            $usersFetched += count($list->getMembers());
            if ($list->getResponseMetadata()) {
                $nextCursor = $list->getResponseMetadata()->getNextCursor();
                $query['cursor'] = $nextCursor;
            } else {
                $nextCursor = null;
            }
            //echo "Users list NextCursor = " . json_encode($nextCursor) . "\n";
        } while (!empty($nextCursor));
        echo "Users fetched: {$usersFetched}, new users: {$usersAdded}\n";
    }

    private function fetchConversations()
    {
        echo "Fetch conversations\n";

        $listNumber = 0;
        $query = ['types' => 'public_channel, private_channel, mpim, im'];
        $conversationsFetched = 0;
        $conversationsAdded = 0;
        do {
            $listNumber++;
            echo "Conversations list #{$listNumber}\n";
            usleep(self::SLACK_DELAY * 1000);
            $list = $this->slackClient->conversationsList($query);
            foreach ($list->getChannels() as $conversation) {
                if ($conversation->getName() != '') {
                    if ($this->configHelper->isSkipChannel($conversation->getName())) {
                        continue;
                    }
                    echo "  Channel " . $conversation->getName();
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
                }
                echo ' ';
                $members = $this->fetchMembers($conversation);
                if ($this->sqliteDbHelper->upsertConversation($conversation, $members)) {
                    echo "(new conversation) ";
                    $conversationsAdded++;
                }
                $this->fetchConversationHistory($conversation);
                echo "\n";
            }
            $conversationsFetched += count($list->getChannels());
            if ($list->getResponseMetadata()) {
                $nextCursor = $list->getResponseMetadata()->getNextCursor();
                $query['cursor'] = $nextCursor;
            } else {
                $nextCursor = null;
            }
            //echo "Conversations list NextCursor = " . json_encode($nextCursor) . "\n";
        } while (!empty($nextCursor));
        echo "Conversations fetched: {$conversationsFetched}, new conversations: {$conversationsAdded}\n";
    }

    /**
     * @param ObjsConversation $conversation
     * @return array
     */
    private function fetchMembers(ObjsConversation $conversation): array
    {
        $listNumber = 0;
        $members = [];
        $query = ['channel' => $conversation->getId()];
        do {
            $listNumber++;
            usleep(self::SLACK_DELAY * 1000);
            $list = $this->slackClient->conversationsMembers($query);
            foreach ($list->getMembers() as $member) {
                $members[] = $member;
            }
            if ($list->getResponseMetadata()) {
                $nextCursor = $list->getResponseMetadata()->getNextCursor();
                $query['cursor'] = $nextCursor;
            } else {
                $nextCursor = null;
            }
        } while (!empty($nextCursor));
        return $members;
    }

    /**
     * @param ObjsConversation $conversation
     * @return void
     */
    private function fetchConversationHistory(ObjsConversation $conversation)
    {
        $queryNumber = 0;
        $query = [
            'channel' => $conversation->getId(),
        ];
        $messagesFetched = 0;
        $messagesAdded = 0;
        do {
            $queryNumber++;
//            echo "\tQuery #{$queryNumber}\n";
            echo '.';
            usleep(self::SLACK_DELAY * 1000);
            $history = $this->slackClient->conversationsHistory($query);
            foreach ($history->getMessages() as $message) {
                if ($this->sqliteDbHelper->upsertMessage($message, $conversation->getId())) {
                    $messagesAdded++;
                }

                if ($message->getReactions() != null) {
                    foreach ($message->getReactions() as $reaction) {
                        $this->sqliteDbHelper->upsertReaction($reaction, $message->getTs(), $conversation->getId());
                    }
                }

                if ($message->getThreadTs() !== null) {
                    $this->fetchReplies($conversation, $message->getThreadTs());
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
            $messagesFetched += count($history->getMessages());
            if ($history->getResponseMetadata()) {
                $nextCursor = $history->getResponseMetadata()->getNextCursor();
                $query['cursor'] = $nextCursor;
            } else {
                $nextCursor = null;
            }
            //echo "\tHistory NextCursor = " . json_encode($nextCursor) . "\n";
        } while (!empty($nextCursor));
        if ($messagesFetched > 0) {
            echo " (fetched: {$messagesFetched}, new: {$messagesAdded})";
            $this->totalMessagesFetched += $messagesFetched;
            $this->totalMessagesAdded += $messagesAdded;
        }
    }

    /**
     * @param ObjsConversation $conversation
     * @param string $threadTs
     * @return void
     */
    private function fetchReplies(ObjsConversation $conversation, string $threadTs)
    {
        $listNumber = 0;
        $query = ['channel' => $conversation->getId(), 'ts' => $threadTs];
        do {
            $listNumber++;
            usleep(self::SLACK_DELAY * 1000);
            $thread = $this->slackClient->conversationsReplies($query);
            foreach ($thread->getMessages() as $messageAssoc) {
                if ($messageAssoc['ts'] == $threadTs) {
                    continue;
                }
                $message = (new ObjsMessage())
                    ->setTs($messageAssoc['ts'])
                    ->setThreadTs($messageAssoc['thread_ts'])
                    ->setText($messageAssoc['text'])
                    ->setType($messageAssoc['type'])
                    ->setUser($messageAssoc['user'] ?? null);

                $this->sqliteDbHelper->upsertMessage($message, $conversation->getId());

                if (!empty($messageAssoc['reactions'])) {
                    foreach ($messageAssoc['reactions'] as $reactionAssoc) {
                        $reaction = (new ObjsReaction())
                            ->setName($reactionAssoc['name'])
                            ->setUsers($reactionAssoc['users'])
                            ->setCount($reactionAssoc['count']);
                        $this->sqliteDbHelper->upsertReaction($reaction, $message->getTs(), $conversation->getId());
                    }
                }

                if (isset($messageAssoc['attachments'])) {
                    foreach ($messageAssoc['attachments'] as $index => $attachmentAssoc) {
                        $attachment = new ObjsMessageAttachmentsItem();
                        if (isset($attachmentAssoc['author_icon'])) {
                            $attachment->setAuthorIcon($attachmentAssoc['author_icon']);
                        }
                        if (isset($attachmentAssoc['author_link'])) {
                            $attachment->setAuthorLink($attachmentAssoc['author_link']);
                        }
                        if (isset($attachmentAssoc['author_name'])) {
                            $attachment->setAuthorName($attachmentAssoc['author_name']);
                        }
                        if (isset($attachmentAssoc['color'])) {
                            $attachment->setColor($attachmentAssoc['color']);
                        }
                        if (isset($attachmentAssoc['fallback'])) {
                            $attachment->setFallback($attachmentAssoc['fallback']);
                        }
                        if (isset($attachmentAssoc['image_bytes'])) {
                            $attachment->setImageBytes($attachmentAssoc['image_bytes']);
                        }
                        if (isset($attachmentAssoc['image_width'])) {
                            $attachment->setImageWidth($attachmentAssoc['image_width']);
                        }
                        if (isset($attachmentAssoc['image_height'])) {
                            $attachment->setImageHeight($attachmentAssoc['image_height']);
                        }
                        if (isset($attachmentAssoc['image_url'])) {
                            $attachment->setImageUrl($attachmentAssoc['image_url']);
                        }
                        if (isset($attachmentAssoc['pretext'])) {
                            $attachment->setPretext($attachmentAssoc['pretext']);
                        }
                        if (isset($attachmentAssoc['text'])) {
                            $attachment->setText($attachmentAssoc['text']);
                        }
                        if (isset($attachmentAssoc['thumb_url'])) {
                            $attachment->setThumbUrl($attachmentAssoc['thumb_url']);
                        }
                        if (isset($attachmentAssoc['title'])) {
                            $attachment->setTitle($attachmentAssoc['title']);
                        }
                        if (isset($attachmentAssoc['title_link'])) {
                            $attachment->setTitleLink($attachmentAssoc['title_link']);
                        }
                        if (isset($attachmentAssoc['ts'])) {
                            $attachment->setTs($attachmentAssoc['ts']);
                        }
                        $this->sqliteDbHelper->upsertAttachment($attachment, $message->getTs(), $conversation->getId(), $index);
                    }
                }

                if (isset($messageAssoc['files'])) {
                    foreach ($messageAssoc['files'] as $fileAssoc) {
                        $file = (new ObjsFile())
                            ->setId($fileAssoc['id'])
                            ->setCreated($fileAssoc['created'] ?? null)
                            ->setName($fileAssoc['name'] ?? '')
                            ->setTitle($fileAssoc['title'] ?? '')
                            ->setMimetype($fileAssoc['mimetype'] ?? '')
                            ->setFiletype($fileAssoc['filetype'] ?? '')
                            ->setPrettyType($fileAssoc['pretty_type'] ?? '')
                            ->setUser($fileAssoc['user'] ?? null)
                            ->setSize($fileAssoc['size'] ?? 0)
                            ->setMode($fileAssoc['mode'])
                            ->setIsExternal($fileAssoc['is_external'] ?? false)
                            ->setExternalType($fileAssoc['external_type'] ?? '')
                            ->setIsPublic($fileAssoc['is_public'] ?? false)
                            ->setUrlPrivate($fileAssoc['url_private'] ?? '')
                            ->setUrlPrivateDownload($fileAssoc['url_private_download'] ?? '')
                            ->setPermalink($fileAssoc['permalink'] ?? '')
                            ->setPermalinkPublic($fileAssoc['permalink_public'] ?? '')
                            ->setHasRichPreview($fileAssoc['has_rich_preview'] ?? false);
                        if (isset($fileAssoc['preview'])) {
                            $file->setPreview($fileAssoc['preview']);
                        }
                        if (isset($fileAssoc['external_id'])) {
                            $file->setExternalId($fileAssoc['external_id']);
                        }
                        if (isset($fileAssoc['external_url'])) {
                            $file->setExternalUrl($fileAssoc['external_url']);
                        }
                        if (isset($fileAssoc['original_w'])) {
                            $file->setOriginalW($fileAssoc['original_w']);
                        }
                        if (isset($fileAssoc['original_h'])) {
                            $file->setOriginalH($fileAssoc['original_h']);
                        }
                        if (isset($fileAssoc['thumb_64'])) {
                            $file->setThumb64($fileAssoc['thumb_64']);
                        }
                        if (isset($fileAssoc['thumb_80'])) {
                            $file->setThumb80($fileAssoc['thumb_80']);
                        }
                        if (isset($fileAssoc['thumb_160'])) {
                            $file->setThumb160($fileAssoc['thumb_160']);
                        }
                        if (isset($fileAssoc['thumb_360'])) {
                            $file->setThumb360($fileAssoc['thumb_360']);
                        }
                        if (isset($fileAssoc['thumb_360_gif'])) {
                            $file->setThumb360Gif($fileAssoc['thumb_360_gif']);
                        }
                        if (isset($fileAssoc['thumb_360_w'])) {
                            $file->setThumb360W($fileAssoc['thumb_360_w']);
                        }
                        if (isset($fileAssoc['thumb_360_h'])) {
                            $file->setThumb360H($fileAssoc['thumb_360_h']);
                        }
                        if (isset($fileAssoc['thumb_480'])) {
                            $file->setThumb480($fileAssoc['thumb_480']);
                        }
                        if (isset($fileAssoc['thumb_480_w'])) {
                            $file->setThumb480W($fileAssoc['thumb_480_w']);
                        }
                        if (isset($fileAssoc['thumb_480_h'])) {
                            $file->setThumb480H($fileAssoc['thumb_480_h']);
                        }
                        if (isset($fileAssoc['thumb_720'])) {
                            $file->setThumb720($fileAssoc['thumb_720']);
                        }
                        if (isset($fileAssoc['thumb_720_w'])) {
                            $file->setThumb720W($fileAssoc['thumb_720_w']);
                        }
                        if (isset($fileAssoc['thumb_720_h'])) {
                            $file->setThumb720H($fileAssoc['thumb_720_h']);
                        }
                        if (isset($fileAssoc['thumb_800'])) {
                            $file->setThumb800($fileAssoc['thumb_800']);
                        }
                        if (isset($fileAssoc['thumb_800_w'])) {
                            $file->setThumb800W($fileAssoc['thumb_800_w']);
                        }
                        if (isset($fileAssoc['thumb_800_h'])) {
                            $file->setThumb800H($fileAssoc['thumb_800_h']);
                        }
                        if (isset($fileAssoc['thumb_960'])) {
                            $file->setThumb960($fileAssoc['thumb_960']);
                        }
                        if (isset($fileAssoc['thumb_960_w'])) {
                            $file->setThumb960W($fileAssoc['thumb_960_w']);
                        }
                        if (isset($fileAssoc['thumb_960_h'])) {
                            $file->setThumb960H($fileAssoc['thumb_960_h']);
                        }
                        if (isset($fileAssoc['thumb_1024'])) {
                            $file->setThumb1024($fileAssoc['thumb_1024']);
                        }
                        if (isset($fileAssoc['thumb_1024_w'])) {
                            $file->setThumb1024W($fileAssoc['thumb_1024_w']);
                        }
                        if (isset($fileAssoc['thumb_1024_h'])) {
                            $file->setThumb1024H($fileAssoc['thumb_1024_h']);
                        }
                        if (isset($fileAssoc['thumb_tiny'])) {
                            $file->setThumbTiny($fileAssoc['thumb_tiny']);
                        }
                        $this->sqliteDbHelper->upsertFile($file, $message->getTs(), $conversation->getId());
                    }
                }
            }
            if ($thread->getResponseMetadata()) {
                $nextCursor = $thread->getResponseMetadata()->getNextCursor();
                $query['cursor'] = $nextCursor;
            } else {
                $nextCursor = null;
            }
        } while (!empty($nextCursor));
    }

}
