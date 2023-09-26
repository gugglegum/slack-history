<?php

/**
 * 1. Add new columns attachments.conversation_id and files.conversation_id
 * 2. Add foreign keys:
 *      attachments->messages;
 *      files->messages;
 *      messages->conversations;
 *      messages->users;
 *      conversations->users
 * 3. Add index on files("message_ts", "conversation_id")
 */
class Migration002 extends \App\AbstractMigration
{
    public function __invoke()
    {
        // Update attachments

        $this->pdo->exec('PRAGMA foreign_keys=off');
        $this->pdo->beginTransaction();
        $this->pdo->exec('CREATE TABLE "attachments_new" (
            "message_ts" TEXT NOT NULL,
            "conversation_id" TEXT NOT NULL,
            "num" INTEGER NOT NULL,
            "author_icon" TEXT,
            "author_link" TEXT,
            "author_name" BLOB,
            "color" TEXT,
            "fallback" NUMERIC,
            "image_bytes" INTEGER,
            "image_width" INTEGER,
            "image_height" INTEGER,
            "image_url" TEXT,
            "pretext" TEXT,
            "text" TEXT,
            "thumb_url" INTEGER,
            "title" INTEGER,
            "title_link" INTEGER,
            "ts" TEXT,
            PRIMARY KEY("message_ts","num","conversation_id"),
            CONSTRAINT "fk_message_ts_conversation_id" FOREIGN KEY (message_ts, conversation_id) REFERENCES messages(ts, conversation_id) ON UPDATE CASCADE ON DELETE CASCADE
        )');
        $this->pdo->exec('INSERT INTO attachments_new (message_ts, conversation_id, num, author_icon,
                author_link, author_name, color, fallback, image_bytes, image_width, image_height,
                image_url, pretext, text, thumb_url, title, title_link, ts)
            SELECT
                attachments.message_ts,
                messages.conversation_id,
                attachments.num,
                attachments.author_icon,
                attachments.author_link,
                attachments.author_name,
                attachments.color,
                attachments.fallback,
                attachments.image_bytes,
                attachments.image_width,
                attachments.image_height,
                attachments.image_url,
                attachments.pretext,
                attachments.text,
                attachments.thumb_url,
                attachments.title,
                attachments.title_link,
                attachments.ts
            FROM attachments
            LEFT JOIN messages ON messages.ts = attachments.message_ts
        ');
        $this->pdo->exec('DROP TABLE attachments');
        $this->pdo->exec('ALTER TABLE attachments_new RENAME TO attachments');
        $this->pdo->commit();
        $this->pdo->exec('PRAGMA foreign_keys=on');

        // Update files

        $this->pdo->exec('PRAGMA foreign_keys=off');
        $this->pdo->beginTransaction();
        $this->pdo->exec('CREATE TABLE "files_new" (
            "id" TEXT NOT NULL,
            "message_ts" TEXT NOT NULL,
            "conversation_id" TEXT NOT NULL,
            "user" TEXT,
            "filetype" TEXT NOT NULL,
            "mimetype" TEXT NOT NULL,
            "pretty_type" TEXT NOT NULL,
            "size" INTEGER NOT NULL,
            "name" TEXT NOT NULL,
            "title" TEXT NOT NULL,
            "mode" TEXT NOT NULL,
            "permalink" TEXT NOT NULL,
            "permalink_public" TEXT NOT NULL,
            "url_private" TEXT NOT NULL,
            "url_private_download" TEXT NOT NULL,
            "preview" TEXT,
            "has_rich_preview" INTEGER NOT NULL,
            "is_external" INTEGER NOT NULL,
            "external_id" TEXT,
            "external_type" TEXT NOT NULL,
            "external_url" TEXT,
            "is_public" INTEGER NOT NULL,
            "original_w" INTEGER,
            "original_h" INTEGER,
            "image_exif_rotation" INTEGER,
            "thumb_64" TEXT,
            "thumb_80" TEXT,
            "thumb_160" TEXT,
            "thumb_360" TEXT,
            "thumb_360_gif" TEXT,
            "thumb_360_w" TEXT,
            "thumb_360_h" TEXT,
            "thumb_480" TEXT,
            "thumb_480_w" TEXT,
            "thumb_480_h" TEXT,
            "thumb_720" TEXT,
            "thumb_720_w" TEXT,
            "thumb_720_h" TEXT,
            "thumb_800" TEXT,
            "thumb_800_w" TEXT,
            "thumb_800_h" TEXT,
            "thumb_960" TEXT,
            "thumb_960_w" TEXT,
            "thumb_960_h" TEXT,
            "thumb_1024" TEXT,
            "thumb_1024_w" TEXT,
            "thumb_1024_h" TEXT,
            "thumb_tiny" TEXT,
            "created" INTEGER NOT NULL,
            PRIMARY KEY("id"),
            UNIQUE("message_ts", "conversation_id", "id"),    
            CONSTRAINT "fk_message_ts_conversation_id" FOREIGN KEY (message_ts, conversation_id) REFERENCES messages(ts, conversation_id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT "fk_user_id" FOREIGN KEY (user) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        )');
        $this->pdo->exec('CREATE INDEX message_conversation ON files_new("message_ts", "conversation_id")');
        $this->pdo->exec('INSERT INTO files_new (id, message_ts, conversation_id, user, filetype, mimetype,
                pretty_type, size, name, title, mode, permalink, permalink_public, url_private, url_private_download,
                preview, has_rich_preview, is_external, external_id, external_type, external_url, is_public, original_w,
                original_h, image_exif_rotation, thumb_64, thumb_80, thumb_160, thumb_360, thumb_360_gif, thumb_360_w,
                thumb_360_h, thumb_480, thumb_480_w, thumb_480_h, thumb_720, thumb_720_w, thumb_720_h, thumb_800,
                thumb_800_w, thumb_800_h, thumb_960, thumb_960_w, thumb_960_h, thumb_1024, thumb_1024_w, thumb_1024_h,
                thumb_tiny, created)
            SELECT
                files.id,
                files.message_ts,
                messages.conversation_id,
                IIF(files.user != "", files.user, NULL),
                files.filetype,
                files.mimetype,
                files.pretty_type,
                files.size,
                files.name,
                files.title,
                files.mode,
                files.permalink,
                files.permalink_public,
                files.url_private,
                files.url_private_download,
                files.preview,
                files.has_rich_preview,
                files.is_external,
                files.external_id,
                files.external_type,
                files.external_url,
                files.is_public,
                files.original_w,
                files.original_h,
                files.image_exif_rotation,
                files.thumb_64,
                files.thumb_80,
                files.thumb_160,
                files.thumb_360,
                files.thumb_360_gif,
                files.thumb_360_w,
                files.thumb_360_h,
                files.thumb_480,
                files.thumb_480_w,
                files.thumb_480_h,
                files.thumb_720,
                files.thumb_720_w,
                files.thumb_720_h,
                files.thumb_800,
                files.thumb_800_w,
                files.thumb_800_h,
                files.thumb_960,
                files.thumb_960_w,
                files.thumb_960_h,
                files.thumb_1024,
                files.thumb_1024_w,
                files.thumb_1024_h,
                files.thumb_tiny,
                files.created
            FROM files
            LEFT JOIN messages ON messages.ts = files.message_ts
        ');
        $this->pdo->exec('DROP TABLE files');
        $this->pdo->exec('ALTER TABLE files_new RENAME TO files');
        $this->pdo->commit();
        $this->pdo->exec('PRAGMA foreign_keys=on');

        // Update messages

        $this->pdo->exec('PRAGMA foreign_keys=off');
        $this->pdo->beginTransaction();
        $this->pdo->exec('CREATE TABLE "messages_new" (
            "ts" TEXT NOT NULL,
            "conversation_id" TEXT NOT NULL,
            "type" TEXT NOT NULL,
            "subtype" TEXT,
            "text" TEXT NOT NULL,
            "user" TEXT,
            "thread_ts" TEXT,
            PRIMARY KEY("ts","conversation_id"),
            CONSTRAINT "fk_conversation_id" FOREIGN KEY (conversation_id) REFERENCES conversations(id) ON UPDATE CASCADE ON DELETE CASCADE,
            CONSTRAINT "fk_user_id" FOREIGN KEY (user) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        )');
        $this->pdo->exec('INSERT INTO messages_new SELECT * FROM messages');
        $this->pdo->exec('DROP TABLE messages');
        $this->pdo->exec('ALTER TABLE messages_new RENAME TO messages');
        $this->pdo->commit();
        $this->pdo->exec('PRAGMA foreign_keys=on');

        // Update conversations

        $this->pdo->exec('PRAGMA foreign_keys=off');
        $this->pdo->beginTransaction();
        $this->pdo->exec('CREATE TABLE "conversations_new" (
            "id" TEXT NOT NULL,
            "name" TEXT,
            "is_channel" INTEGER NOT NULL,
            "is_group" INTEGER NOT NULL,
            "is_im" INTEGER NOT NULL,
            "created" INTEGER NOT NULL,
            "creator" TEXT,
            "user" INTEGER,
            "is_archived" INTEGER NOT NULL,
            "is_general" INTEGER NOT NULL,
            "is_member" INTEGER NOT NULL,
            "is_private" INTEGER NOT NULL,
            "is_mpim" INTEGER NOT NULL,
            "members" TEXT,
            PRIMARY KEY("id"),
            CONSTRAINT "fk_user_id" FOREIGN KEY (user) REFERENCES users(id) ON UPDATE CASCADE ON DELETE CASCADE
        )');
        $this->pdo->exec('INSERT INTO conversations_new SELECT * FROM conversations');
        $this->pdo->exec('DROP TABLE conversations');
        $this->pdo->exec('ALTER TABLE conversations_new RENAME TO conversations');
        $this->pdo->commit();
        $this->pdo->exec('PRAGMA foreign_keys=on');
    }
}
