<?php

namespace App\Helpers;

use App\AbstractMigration;
use JoliCode\Slack\Api\Model\ObjsConversation;
use JoliCode\Slack\Api\Model\ObjsFile;
use JoliCode\Slack\Api\Model\ObjsMessage;
use JoliCode\Slack\Api\Model\ObjsMessageAttachmentsItem;
use JoliCode\Slack\Api\Model\ObjsUser;
use JoliCode\Slack\Api\Model\ObjsUserProfile;

class SqliteDbHelper
{
    private \Aura\Sql\ExtendedPdo $pdo;

    public function __construct(\Aura\Sql\ExtendedPdo $pdo)
    {
        $this->pdo = $pdo;
    }

    public function initDb()
    {
        $this->pdo->exec('CREATE TABLE "migrations" (
            "version"	INTEGER NOT NULL,
            "ts"	INTEGER NOT NULL,
            PRIMARY KEY("version")
        )');
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function rolloverMigrations()
    {
        $version = $this->pdo->fetchValue("SELECT IFNULL(MAX(version), 0) FROM migrations");
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
                " . SqliteDbHelper::quote($conversation->getId()) . ",
                " . SqliteDbHelper::quoteNullable($conversation->getName()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsChannel()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsGroup()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsIm()) . ",
                " . SqliteDbHelper::quote($conversation->getCreated()) . ",
                " . SqliteDbHelper::quoteNullable($conversation->getCreator()) . ",
                " . SqliteDbHelper::quoteNullable($conversation->getUser()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsArchived()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsGeneral()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsMember()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsPrivate()) . ",
                " . SqliteDbHelper::quote((int) $conversation->getIsMpim()) . ",
                " . SqliteDbHelper::quoteNullable($members !== null ? implode(',', $members) : null) . "
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
                " . SqliteDbHelper::quote($message->getTs()) . ",
                " . SqliteDbHelper::quoteNullable($conversationId) . ",
                " . SqliteDbHelper::quote($message->getType()) . ",
                " . SqliteDbHelper::quoteNullable($message->getSubtype()) . ",
                " . SqliteDbHelper::quote($message->getText()) . ",
                " . SqliteDbHelper::quoteNullable($message->getUser()) . ",
                " . SqliteDbHelper::quoteNullable($message->getThreadTs()) . "
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
                " . SqliteDbHelper::quote($messageTs) . ",
                " . SqliteDbHelper::quote($conversationId) . ",
                " . SqliteDbHelper::quote($num) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getAuthorIcon()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getAuthorLink()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getAuthorName()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getColor()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getFallback()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getImageBytes()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getImageWidth()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getImageHeight()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getImageUrl()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getPretext()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getText()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getThumbUrl()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getTitle()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getTitleLink()) . ",
                " . SqliteDbHelper::quoteNullable($attachment->getTs()) . "
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
                " . SqliteDbHelper::quote($file->getId()) . ",
                " . SqliteDbHelper::quote($messageTs) . ",
                " . SqliteDbHelper::quote($conversationId) . ",
                " . SqliteDbHelper::quote($file->getUser()) . ",
                " . SqliteDbHelper::quote($file->getFiletype()) . ",
                " . SqliteDbHelper::quote($file->getMimetype()) . ",
                " . SqliteDbHelper::quote($file->getPrettyType()) . ",
                " . SqliteDbHelper::quote($file->getSize()) . ",
                " . SqliteDbHelper::quote($file->getName()) . ",
                " . SqliteDbHelper::quote($file->getTitle()) . ",
                " . SqliteDbHelper::quote($file->getMode()) . ",
                " . SqliteDbHelper::quote($file->getPermalink()) . ",
                " . SqliteDbHelper::quote($file->getPermalinkPublic()) . ",
                " . SqliteDbHelper::quote($file->getUrlPrivate()) . ",
                " . SqliteDbHelper::quote($file->getUrlPrivateDownload()) . ",
                " . SqliteDbHelper::quoteNullable($file->getPreview()) . ",
                " . SqliteDbHelper::quote($file->getHasRichPreview()) . ",
                " . SqliteDbHelper::quote($file->getIsExternal()) . ",
                " . SqliteDbHelper::quoteNullable($file->getExternalId()) . ",
                " . SqliteDbHelper::quote($file->getExternalType()) . ",
                " . SqliteDbHelper::quoteNullable($file->getExternalUrl()) . ",
                " . SqliteDbHelper::quote($file->getIsPublic()) . ",
                " . SqliteDbHelper::quoteNullable($file->getOriginalW()) . ",
                " . SqliteDbHelper::quoteNullable($file->getOriginalH()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb64()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb80()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb160()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb360()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb360Gif()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb360W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb360H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb480()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb480W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb480H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb720()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb720W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb720H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb800()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb800W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb800H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb960()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb960W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb960H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb1024()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb1024W()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumb1024H()) . ",
                " . SqliteDbHelper::quoteNullable($file->getThumbTiny()) . ",
                " . SqliteDbHelper::quote($file->getCreated()) . "
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
