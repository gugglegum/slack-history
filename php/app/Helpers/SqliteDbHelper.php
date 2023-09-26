<?php

namespace App\Helpers;

use App\AbstractMigration;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Api\Model\ObjsFile;
use JoliCode\Slack\Api\Model\ObjsMessage;
use JoliCode\Slack\Api\Model\ObjsMessageAttachmentsItem;
use JoliCode\Slack\Api\Model\ObjsReaction;
use JoliCode\Slack\Api\Model\ObjsUser;
use JoliCode\Slack\Api\Model\ObjsUserProfile;

class SqliteDbHelper
{
    private \Aura\Sql\ExtendedPdo $pdo;

    public function __construct(\Aura\Sql\ExtendedPdo $pdo)
    {
        $this->pdo = $pdo;
    }

    public function initDb(): void
    {
        $this->pdo->exec('CREATE TABLE "migrations" (
            "version"	INTEGER NOT NULL,
            "ts"	INTEGER NOT NULL,
            PRIMARY KEY("version")
        )');
    }

    /**
     * @param string $sqliteDbFile
     * @return void
     * @throws \Exception
     */
    public function rolloverMigrations(string $sqliteDbFile): void
    {
        $version = (int) $this->pdo->fetchValue("SELECT IFNULL(MAX(version), 0) FROM migrations");
        do {
            $version++;
            $migrationClassName = "Migration" . str_pad((string) $version, 3, '0', STR_PAD_LEFT);
            $migrationFileName = PROJECT_ROOT_DIR . '/migrations/' . $migrationClassName . '.php';
            if (file_exists($migrationFileName)) {
                echo "Rolling over {$migrationClassName}\n";
                require_once $migrationFileName;
                if (class_exists($migrationClassName)) {
                    $migration = new $migrationClassName($this->pdo);
                    if ($migration instanceof AbstractMigration) {
                        $isNotEmptyDb = ($version > 1) && $this->pdo->fetchValue("SELECT EXISTS(SELECT * FROM messages)");
                        if ($isNotEmptyDb) {
                            copy($sqliteDbFile, $sqliteDbFile . "-backup_before_upgrade_to_version_{$version}");
                        }
                        $migration();
                    } else {
                        throw new \Exception("Found migration {$migrationClassName} but it's not inherited from AbstractMigration");
                    }
                    $this->pdo->exec("INSERT INTO migrations (version, ts) VALUES (
                    " . $this->quote($version) . ",
                    " . $this->quote(time()) . ")");
                } else {
                    throw new \Exception("Found migration {$migrationClassName} but it's not contain class {$migrationClassName}");
                }
            } else {
                break;
            }
        } while ($version < 999);
    }

    public function upsertUser(ObjsUser $user): bool
    {
        $lastRowId = $this->getTableMaxRowId('users');
        $this->pdo->exec("INSERT INTO users (id, team_id, name, deleted, color, real_name, tz, tz_label, tz_offset,
            is_admin, is_owner, is_primary_owner, is_restricted, is_ultra_restricted, is_bot, is_stranger,
            is_app_user, is_invited_user, updated) VALUES (
                " . $this->quote($user->getId()) . ", 
                " . $this->quote($user->getTeamId()) . ", 
                " . $this->quote($user->getName()) . ",
                " . $this->quote($user->getDeleted()) . ",
                " . $this->quoteNullable($user->getColor()) . ",
                " . $this->quoteNullable($user->getRealName()) . ", 
                " . $this->quoteNullable($user->getTz()) . ", 
                " . $this->quoteNullable($user->getTzLabel()) . ", 
                " . $this->quoteNullable($user->getTzOffset()) . ", 
                " . $this->quote($user->getIsAdmin()) . ", 
                " . $this->quote($user->getIsOwner()) . ", 
                " . $this->quote($user->getIsPrimaryOwner()) . ", 
                " . $this->quote($user->getIsRestricted()) . ", 
                " . $this->quote($user->getIsUltraRestricted()) . ", 
                " . $this->quote($user->getIsBot()) . ", 
                " . $this->quote($user->getIsStranger()) . ", 
                " . $this->quote($user->getIsAppUser()) . ", 
                " . $this->quote($user->getIsInvitedUser()) . ", 
                " . $this->quote($user->getUpdated()) . "
            ) ON CONFLICT (id) DO UPDATE SET
                team_id = excluded.team_id,
                name = excluded.name,
                deleted = excluded.deleted,
                color = excluded.color,
                real_name = excluded.real_name,
                tz = excluded.tz,
                tz_label = excluded.tz_label,
                tz_offset = excluded.tz_offset,
                is_admin = excluded.is_admin,
                is_owner = excluded.is_owner,
                is_primary_owner = excluded.is_primary_owner,
                is_restricted = excluded.is_restricted,
                is_ultra_restricted = excluded.is_ultra_restricted,
                is_bot = excluded.is_bot,
                is_stranger = excluded.is_stranger,
                is_app_user = excluded.is_app_user,
                is_invited_user = excluded.is_invited_user,
                updated = excluded.updated"
        );
        return $lastRowId != $this->getTableMaxRowId('users');
    }

    public function upsertProfile(ObjsUserProfile $profile, string $userId): bool
    {
        $lastRowId = $this->getTableMaxRowId('profiles');
        $this->pdo->exec("INSERT INTO profiles (user_id, title, phone, skype, real_name, display_name,
            status_text, status_emoji, status_expiration, avatar_hash, first_name, last_name, email,
            image_original, image_24, image_32, image_48, image_72, image_192, image_512, team) VALUES (
                " . $this->quote($userId) . ",
                " . $this->quoteNullable($profile->getTitle()) . ",
                " . $this->quoteNullable($profile->getPhone()) . ",
                " . $this->quoteNullable($profile->getSkype()) . ",
                " . $this->quoteNullable($profile->getRealName()) . ",
                " . $this->quoteNullable($profile->getDisplayName()) . ",
                " . $this->quoteNullable($profile->getStatusText()) . ",
                " . $this->quoteNullable($profile->getStatusEmoji()) . ",
                " . $this->quoteNullable($profile->getStatusExpiration()) . ",
                " . $this->quoteNullable($profile->getAvatarHash()) . ",
                " . $this->quoteNullable($profile->getFirstName()) . ",
                " . $this->quoteNullable($profile->getLastName()) . ",
                " . $this->quoteNullable($profile->getEmail()) . ",
                " . $this->quoteNullable($profile->getImageOriginal()) . ",
                " . $this->quoteNullable($profile->getImage24()) . ",
                " . $this->quoteNullable($profile->getImage32()) . ",
                " . $this->quoteNullable($profile->getImage48()) . ",
                " . $this->quoteNullable($profile->getImage72()) . ",
                " . $this->quoteNullable($profile->getImage192()) . ",
                " . $this->quoteNullable($profile->getImage512()) . ",
                " . $this->quote($profile->getTeam()) . "
            ) ON CONFLICT (user_id) DO UPDATE SET
                title = excluded.title,
                phone = excluded.phone,
                skype = excluded.skype,
                real_name = excluded.real_name,
                display_name = excluded.display_name,
                status_text = excluded.status_text,
                status_emoji = excluded.status_emoji,
                status_expiration = excluded.status_expiration,
                avatar_hash = excluded.avatar_hash,
                first_name = excluded.first_name,
                last_name = excluded.last_name,
                email = excluded.email,
                image_original = excluded.image_original,
                image_24 = excluded.image_24,
                image_32 = excluded.image_32,
                image_48 = excluded.image_48,
                image_72 = excluded.image_72,
                image_192 = excluded.image_192,
                image_512 = excluded.image_512,
                team = excluded.team"
        );
        return $lastRowId != $this->getTableMaxRowId('profiles');
    }

    /**
     * @param ObjsConversation $conversation
     * @param string[] $members
     * @return bool
     */
    public function upsertConversation(ObjsConversation $conversation, array $members = null): bool
    {
        $lastRowId = $this->getTableMaxRowId('conversations');
        $this->pdo->exec("INSERT INTO conversations (id, name, is_channel, is_group, is_im, created, creator,
            user, is_archived, is_general, is_member, is_private, is_mpim, members) VALUES (
                " . $this->quote($conversation->getId()) . ",
                " . $this->quoteNullable($conversation->getName()) . ",
                " . $this->quote((int) $conversation->getIsChannel()) . ",
                " . $this->quote((int) $conversation->getIsGroup()) . ",
                " . $this->quote((int) $conversation->getIsIm()) . ",
                " . $this->quote($conversation->getCreated()) . ",
                " . $this->quoteNullable($conversation->getCreator()) . ",
                " . $this->quoteNullable($conversation->getUser()) . ",
                " . $this->quote((int) $conversation->getIsArchived()) . ",
                " . $this->quote((int) $conversation->getIsGeneral()) . ",
                " . $this->quote((int) $conversation->getIsMember()) . ",
                " . $this->quote((int) $conversation->getIsPrivate()) . ",
                " . $this->quote((int) $conversation->getIsMpim()) . ",
                " . $this->quoteNullable($members !== null ? implode(',', $members) : null) . "
            ) ON CONFLICT (id) DO UPDATE SET
                name = excluded.name,
                is_channel = excluded.is_channel,
                is_group = excluded.is_group,
                is_im = excluded.is_im,
                created = excluded.created,
                creator = excluded.creator,
                user = excluded.user,
                is_archived = excluded.is_archived,
                is_general = excluded.is_general,
                is_member = excluded.is_member,
                is_private = excluded.is_private,
                is_mpim = excluded.is_mpim,
                members = excluded.members"
        );
        return $lastRowId != $this->getTableMaxRowId('conversations');
    }

    public function upsertMessage(ObjsMessage $message, string $conversationId): bool
    {
        $lastRowId = $this->getTableMaxRowId('messages');
        $this->pdo->exec("INSERT INTO messages (ts, conversation_id, type, subtype, text, user, thread_ts)
            VALUES (
                " . $this->quote($message->getTs()) . ",
                " . $this->quoteNullable($conversationId) . ",
                " . $this->quote($message->getType()) . ",
                " . $this->quoteNullable($message->getSubtype()) . ",
                " . $this->quote($message->getText()) . ",
                " . $this->quoteNullable($message->getUser()) . ",
                " . $this->quoteNullable($message->getThreadTs()) . "
            ) ON CONFLICT (ts, conversation_id) DO UPDATE SET
                type = excluded.type,
                subtype = excluded.subtype,
                text = excluded.text,
                user = excluded.user,
                thread_ts = excluded.thread_ts"
        );
        return $lastRowId != $this->getTableMaxRowId('messages');
    }

    public function upsertAttachment(ObjsMessageAttachmentsItem $attachment, string $messageTs, string $conversationId, int $num): bool
    {
        $lastRowId = $this->getTableMaxRowId('attachments');
        $this->pdo->exec("INSERT INTO attachments (message_ts, conversation_id, num, author_icon, author_link, author_name,
            color, fallback, image_bytes, image_width, image_height, image_url, pretext, text, thumb_url,
            title, title_link, ts) VALUES (
                " . $this->quote($messageTs) . ",
                " . $this->quote($conversationId) . ",
                " . $this->quote($num) . ",
                " . $this->quoteNullable($attachment->getAuthorIcon()) . ",
                " . $this->quoteNullable($attachment->getAuthorLink()) . ",
                " . $this->quoteNullable($attachment->getAuthorName()) . ",
                " . $this->quoteNullable($attachment->getColor()) . ",
                " . $this->quoteNullable($attachment->getFallback()) . ",
                " . $this->quoteNullable($attachment->getImageBytes()) . ",
                " . $this->quoteNullable($attachment->getImageWidth()) . ",
                " . $this->quoteNullable($attachment->getImageHeight()) . ",
                " . $this->quoteNullable($attachment->getImageUrl()) . ",
                " . $this->quoteNullable($attachment->getPretext()) . ",
                " . $this->quoteNullable($attachment->getText()) . ",
                " . $this->quoteNullable($attachment->getThumbUrl()) . ",
                " . $this->quoteNullable($attachment->getTitle()) . ",
                " . $this->quoteNullable($attachment->getTitleLink()) . ",
                " . $this->quoteNullable($attachment->getTs()) . "
            ) ON CONFLICT (message_ts, conversation_id, num) DO UPDATE SET
                author_icon = excluded.author_icon,
                author_link = excluded.author_link,
                author_name = excluded.author_name,
                color = excluded.color,
                fallback = excluded.fallback,
                image_bytes = excluded.image_bytes,
                image_width = excluded.image_width,
                image_height = excluded.image_height,
                image_url = excluded.image_url,
                pretext = excluded.pretext,
                text = excluded.text,
                thumb_url = excluded.thumb_url,
                title = excluded.title,
                title_link = excluded.title_link,
                ts = excluded.ts"
        );
        return $lastRowId != $this->getTableMaxRowId('attachments');
    }

    public function upsertFile(ObjsFile $file, string $messageTs, string $conversationId): bool
    {
        $lastRowId = $this->getTableMaxRowId('files');
        $this->pdo->exec("INSERT INTO files (id, message_ts, conversation_id, user, filetype, mimetype, pretty_type, size, name,
                title, mode, permalink, permalink_public, url_private, url_private_download, preview, has_rich_preview,
                is_external, external_id, external_type, external_url, is_public, original_w, original_h, thumb_64,
                thumb_80, thumb_160, thumb_360, thumb_360_gif, thumb_360_w, thumb_360_h, thumb_480, thumb_480_w,
                thumb_480_h, thumb_720, thumb_720_w, thumb_720_h, thumb_800, thumb_800_w, thumb_800_h, thumb_960,
                thumb_960_w, thumb_960_h, thumb_1024, thumb_1024_w, thumb_1024_h, thumb_tiny, created        
            ) VALUES (
                " . $this->quote($file->getId()) . ",
                " . $this->quote($messageTs) . ",
                " . $this->quote($conversationId) . ",
                " . $this->quoteNullable($file->getUser()) . ",
                " . $this->quote($file->getFiletype()) . ",
                " . $this->quote($file->getMimetype()) . ",
                " . $this->quote($file->getPrettyType()) . ",
                " . $this->quote($file->getSize()) . ",
                " . $this->quote($file->getName()) . ",
                " . $this->quote($file->getTitle()) . ",
                " . $this->quote($file->getMode()) . ",
                " . $this->quote($file->getPermalink()) . ",
                " . $this->quote($file->getPermalinkPublic()) . ",
                " . $this->quote($file->getUrlPrivate()) . ",
                " . $this->quote($file->getUrlPrivateDownload()) . ",
                " . $this->quoteNullable($file->getPreview()) . ",
                " . $this->quote($file->getHasRichPreview()) . ",
                " . $this->quote($file->getIsExternal()) . ",
                " . $this->quoteNullable($file->getExternalId()) . ",
                " . $this->quote($file->getExternalType()) . ",
                " . $this->quoteNullable($file->getExternalUrl()) . ",
                " . $this->quote($file->getIsPublic()) . ",
                " . $this->quoteNullable($file->getOriginalW()) . ",
                " . $this->quoteNullable($file->getOriginalH()) . ",
                " . $this->quoteNullable($file->getThumb64()) . ",
                " . $this->quoteNullable($file->getThumb80()) . ",
                " . $this->quoteNullable($file->getThumb160()) . ",
                " . $this->quoteNullable($file->getThumb360()) . ",
                " . $this->quoteNullable($file->getThumb360Gif()) . ",
                " . $this->quoteNullable($file->getThumb360W()) . ",
                " . $this->quoteNullable($file->getThumb360H()) . ",
                " . $this->quoteNullable($file->getThumb480()) . ",
                " . $this->quoteNullable($file->getThumb480W()) . ",
                " . $this->quoteNullable($file->getThumb480H()) . ",
                " . $this->quoteNullable($file->getThumb720()) . ",
                " . $this->quoteNullable($file->getThumb720W()) . ",
                " . $this->quoteNullable($file->getThumb720H()) . ",
                " . $this->quoteNullable($file->getThumb800()) . ",
                " . $this->quoteNullable($file->getThumb800W()) . ",
                " . $this->quoteNullable($file->getThumb800H()) . ",
                " . $this->quoteNullable($file->getThumb960()) . ",
                " . $this->quoteNullable($file->getThumb960W()) . ",
                " . $this->quoteNullable($file->getThumb960H()) . ",
                " . $this->quoteNullable($file->getThumb1024()) . ",
                " . $this->quoteNullable($file->getThumb1024W()) . ",
                " . $this->quoteNullable($file->getThumb1024H()) . ",
                " . $this->quoteNullable($file->getThumbTiny()) . ",
                " . $this->quote($file->getCreated()) . "
            ) ON CONFLICT (id) DO UPDATE SET
                message_ts = excluded.message_ts,
                conversation_id = excluded.conversation_id,
                user = excluded.user,
                filetype = excluded.filetype,
                mimetype = excluded.mimetype,
                pretty_type = excluded.pretty_type,
                size = excluded.size,
                name = excluded.name,
                title = excluded.title,
                mode = excluded.mode,
                permalink = excluded.permalink,
                permalink_public = excluded.permalink_public,
                url_private = excluded.url_private,
                url_private_download = excluded.url_private_download,
                preview = excluded.preview,
                has_rich_preview = excluded.has_rich_preview,
                is_external = excluded.is_external,
                external_id = excluded.external_id,
                external_type = excluded.external_type,
                external_url = excluded.external_url,
                is_public = excluded.is_public,
                original_w = excluded.original_w,
                original_h = excluded.original_h,
                thumb_64 = excluded.thumb_64,
                thumb_80 = excluded.thumb_80,
                thumb_160 = excluded.thumb_160,
                thumb_360 = excluded.thumb_360,
                thumb_360_gif = excluded.thumb_360_gif,
                thumb_360_w = excluded.thumb_360_w,
                thumb_360_h = excluded.thumb_360_h,
                thumb_480 = excluded.thumb_480,
                thumb_480_w = excluded.thumb_480_w, 
                thumb_480_h = excluded.thumb_480_h,
                thumb_720 = excluded.thumb_720,
                thumb_720_w = excluded.thumb_720_w,
                thumb_720_h = excluded.thumb_720_h,
                thumb_800 = excluded.thumb_800,
                thumb_800_w = excluded.thumb_800_w,
                thumb_800_h = excluded.thumb_800_h,
                thumb_960 = excluded.thumb_960,
                thumb_960_w = excluded.thumb_960_w,
                thumb_960_h = excluded.thumb_960_h, 
                thumb_1024 = excluded.thumb_1024,
                thumb_1024_w = excluded.thumb_1024_w,
                thumb_1024_h = excluded.thumb_1024_h,
                thumb_tiny = excluded.thumb_tiny,                
                created = excluded.created"
        );
        return $lastRowId != $this->getTableMaxRowId('files');
    }

    public function upsertReaction(ObjsReaction $reaction, string $messageTs, string $conversationId): bool
    {
        $lastRowId = $this->getTableMaxRowId('reactions');
        $this->pdo->exec("INSERT INTO reactions (message_ts, conversation_id, name, users) VALUES (
                " . $this->quote($messageTs) . ",
                " . $this->quote($conversationId) . ",
                " . $this->quote($reaction->getName()) . ",
                " . $this->quoteNullable(implode(',', $reaction->getUsers())) . "
            ) ON CONFLICT (message_ts, conversation_id, name) DO UPDATE SET
                users = excluded.users"
        );
        return $lastRowId != $this->getTableMaxRowId('reactions');
    }


    public function getTableMaxRowId(string $table): int
    {
        return (int) $this->pdo->fetchValue("SELECT MAX(rowid) FROM `{$table}`");
    }
    
    public function quote($value): string
    {
        if (is_bool($value)) {
            $value = (int) $value;
        }
        if (is_string($value) || is_null($value)) {
//            $value = "'" . str_replace("'", "''", $value) . "'";
            $value = $this->pdo->quote($value);
        }
        return $value;
    }

    public function quoteNullable($value): string
    {
        if (is_null($value)) {
            $value = 'NULL';
        } else {
            $value = $this->quote($value);
        }
        return $value;
    }
}
