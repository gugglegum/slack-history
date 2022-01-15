<?php

class Migration001 extends \App\AbstractMigration
{
    public function __invoke()
    {
        $this->pdo->exec('CREATE TABLE "users" (
            "id" TEXT NOT NULL,
            "team_id" TEXT NOT NULL,
            "name" TEXT NOT NULL,
            "deleted" INTEGER NOT NULL,
            "color" TEXT,
            "real_name" TEXT,
            "tz" TEXT,
            "tz_label" TEXT,
            "tz_offset" INTEGER,
            "is_admin" INTEGER NOT NULL,
            "is_owner" INTEGER NOT NULL,
            "is_primary_owner" INTEGER NOT NULL,
            "is_restricted" INTEGER NOT NULL,
            "is_ultra_restricted" INTEGER NOT NULL,
            "is_bot" INTEGER NOT NULL,
            "is_stranger" INTEGER NOT NULL,
            "is_app_user" INTEGER NOT NULL,
            "is_invited_user" INTEGER NOT NULL,
            "updated" INTEGER NOT NULL,
            PRIMARY KEY("id")
        )');
        $this->pdo->exec('CREATE TABLE "profiles" (
            "user_id" TEXT NOT NULL,
            "title" TEXT,
            "phone" TEXT,
            "skype" TEXT,
            "real_name" TEXT,
            "display_name" TEXT,
            "status_text" TEXT,
            "status_emoji" TEXT,
            "status_expiration" INTEGER,
            "avatar_hash" TEXT,
            "first_name" TEXT,
            "last_name" TEXT,
            "email" TEXT,
            "image_original" TEXT,
            "image_24" TEXT,
            "image_32" TEXT,
            "image_48" TEXT,
            "image_72" INTEGER,
            "image_192" TEXT,
            "image_512" TEXT,
            "team" TEXT NOT NULL,
            PRIMARY KEY("user_id"),
            CONSTRAINT "fk_user_id" FOREIGN KEY("user_id") REFERENCES "users"("id")
        )');
        $this->pdo->exec('CREATE TABLE "conversations" (
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
            PRIMARY KEY("id")
        )');
        $this->pdo->exec('CREATE TABLE "messages" (
            "ts" TEXT NOT NULL,
            "conversation_id" TEXT NOT NULL,
            "type" TEXT NOT NULL,
            "subtype" TEXT,
            "text" TEXT NOT NULL,
            "user" TEXT,
            "thread_ts" TEXT,
            PRIMARY KEY("ts","conversation_id")
        )');
        $this->pdo->exec('CREATE TABLE "attachments" (
            "message_ts" TEXT NOT NULL,
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
            PRIMARY KEY("message_ts","num")
        )');
        $this->pdo->exec('CREATE TABLE "files" (
            "id" TEXT NOT NULL,
            "message_ts" TEXT NOT NULL,
            "user" TEXT NOT NULL,
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
            PRIMARY KEY("id")
        )');
    }
}
