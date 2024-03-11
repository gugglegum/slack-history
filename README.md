# Slack History

Language: ENG | [RUS](README_RU.md)

If you use Slack messenger on a free plan, you are limited to 90 days of stored message history (previously it was 10,000 messages for the entire Workspace). This can be inconvenient because you can't go back to previously sent or received messages. This script solves this problem by using the Slack API to download the entire volume of messages available to you personally, save them to a local SQLite database, and generate HTML files for each private chat, public or private channel you have access to. On repeated runs, only new messages that were not there before are added to the local database. Thus, by running the script from time to time (now at least once every 90 days) you will be able to keep your message history up to date without skipping, thus bypassing the free plan's limitation on the size of the displayed history. Each Slack user must run this script individually on their own computer using their personal token. This script cannot be used for centralised storage of all correspondence of all users. Each installation of the script uses the personal token of a particular user and downloads everything that is available to him/her personally, including his/her private messages.

Since I assumed that my script might be used by other employees of the company, not all of whom are programmers, I tried to make the installation and use of this script as easy as possible. Therefore, I had to include PHP-binaries and files in the `vendor/` folder in the distribution so that the user is not required to have Composer and PHP of the right version with the right extensions installed. So that everything would be as simple as possible, without any tambourine dancing. I'm not sure, though, that it doesn't violate any licenses. SQLite was chosen to store the message history, which simplified local use of the script and creation of backups.

To access the Slack API you will need to create an application in your Workspace and set a list of required privileges for it (it's not difficult, but requires administrator rights), and then grant it the appropriate access, getting a token that will use this script. Administrator privileges are only needed to create the Slack application, people with regular privileges can use it then.

## Table of Contents

1. [Prehistory](#prehistory).
2. [Installation](#installation)
3. [Usage](#usage)
4. [Implementation](#implementation)

## Prehistory

Starting in 2016, I worked for a company that used Slack on a free plan. The management of the company thought that the paid plan was unnecessarily expensive and stayed on the free plan. At the time, the free plan only allowed you to see the last 10,000 messages for the entire Workspace. With our correspondence activity, this was enough for about 2 weeks. However, personally, such a narrow window of history was not enough for me, and in order not to lose something important, at first I just saved correspondence to text files, which was very tedious and unreliable, because there were omissions in saving history. But at some point laziness won out and I wondered about automating this process. I discovered that the Slack API allows you to retrieve the history of messages. That's when I came up with the idea to regularly save the history to some local storage by a script, which can be used to search for the necessary messages if necessary, and for convenience you can also render text or HTML files. An additional trigger for me was the fact that someone activated a 30-day trial period, within which there was a chance to download the entire history for several years. And then only run the script once a week, pumping out new posts.

Sometime in the beginning of 2023, Slack made a change to its free plan - now not 10,000 messages are available, but the entire history for 90 days, regardless of how many messages there are. Using Slack has become much more convenient. The relevance in this script has decreased because of this, but I continue to use it.

## Installation

1. Create a new app in your Slack settings in your Workspace under "Manage".
2. Set the app to the list of required privileges described below.
3. Slack this repository to any directory.
4. Grant the application access, in return you will receive a token starting with "xoxp-". This token is only shown once.
5. Copy the `.env.distrib` file to `.env` and substitute there your application access token obtained in the previous step.

Steps 1-2 are only needed when installing by the first user in the company. Other users can use the application you have already created by following steps 3-5.

### Список привилегий, которые требуются приложению
<table>
<tr><td><a href="https://api.slack.com/scopes/identify">identify</a></td><td>View information about a user’s identity</td></tr>
<tr><td><a href="https://api.slack.com/scopes/users.profile:read">users.profile:read</a></td><td>View profile details about people in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/channels:history">channels:history</a></td><td>View messages and other content in a user’s public channels, private channels, direct messages, and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/groups:history">groups:history</a></td><td>View messages and other content in a user’s public channels, private channels, direct messages, and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/im:history">im:history</a></td><td>View messages and other content in a user’s public channels, private channels, direct messages, and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/mpim:history">mpim:history</a></td><td>View messages and other content in a user’s public channels, private channels, direct messages, and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/im:read">im:read</a></td><td>View basic information about a user’s direct and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/mpim:read">mpim:read</a></td><td>View basic information about a user’s direct and group direct messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/channels:read">channels:read</a></td><td>View basic information about public channels in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/groups:read">groups:read</a></td><td>View basic information about a user’s private channels</td></tr>
<tr><td><a href="https://api.slack.com/scopes/links:read">links:read</a></td><td>View URLs in messages</td></tr>
<tr><td><a href="https://api.slack.com/scopes/reactions:read">reactions:read</a></td><td>View emoji reactions in a user’s channels and conversations and their associated content</td></tr>
<tr><td><a href="https://api.slack.com/scopes/files:read">files:read</a></td><td>View files shared in channels and conversations that a user has access to</td></tr>
<tr><td><a href="https://api.slack.com/scopes/remote_files:read">remote_files:read</a></td><td>View remote files added by the app in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/team:read">team:read</a></td><td>View the name, email domain, and icon for workspaces a user is connected to</td></tr>
<tr><td><a href="https://api.slack.com/scopes/usergroups:read">usergroups:read</a></td><td>View user groups in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/users:read">users:read</a></td><td>View people in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/users:read.email">users:read.email</a></td><td>View email addresses of people in a workspace</td></tr>
<tr><td><a href="https://api.slack.com/scopes/team.preferences:read">team.preferences:read</a></td><td>Allows test to read a workspace's preferences</td></tr>
</table>

(perhaps the list of privileges is slightly redundant and fewer privileges are actually required)

## Usage

The repository already contains the PHP for Windows binary (`php.exe`), so you don't need PHP installed on your computer. You can run `update.cmd` which is in the root of the project, this script will do what is needed. `update.cmd` is already customised to use `php.exe`, which is included in the repository in the `bin/` folder. In fact, `update.cmd` calls 3 scripts at once:

1. `fetch-history` - fetches the correspondence history into SQLite database in `db/` folder (if there is no database - it automatically creates it).
2. `fetch-files` - downloads all files that were used as attachments in correspondence, saving them in `files/` (only new files are downloaded).
3. `compile-html` - generates HTML files in `html/` folder with correspondence history from SQLite database.

At the moment the generated files are basic in formatting and are essentially saved as is in the database. No formatting is applied. Files are not substituted. There is a table of contents in the form of an `index.html` file.

## Implementation

The script is written in PHP 8.3. For simplicity and lightweight, the script doesn't use any frameworks. I don't think this is the most optimal way to do it at all, rather just a tribute to the fashion in the industry. The script only uses a few Composer packages:

* `jolicode/slack-php-api` — main library for Slack API, generated automatically based on "OpenAPI specs" for Slack API;
* `symfony/http-client` — HTTP client implementing PSR-18 interface, required for `jolicode/slack-php-api`;
* `aura/sql` — an extension to the native PDO that provides some additional features for greater convenience;
* `aura/sqlquery` — a simple query-builder for SQL queries;
* `vlucas/phpdotenv` — reads environment variables from `.env` file and adds to the current environment;
* `luracast/config` — provides access to configs, which in turn use environment variables;
* `league/plates` — a simple template engine, used to generate HTML files;
* `gugglegum/retry-helper` — this is my package that allows to elegantly retry an action when errors occur (with a logger, with increasing random delays between attempts, with callbacks that allow flexible behavior control), used to correctly handle connection errors or in case of exceeding the limit on the frequency of Slack API calls.

The entry point for all console scripts is the `console.php` file, which receives the command name as the first argument of the command line, and using a simple command router, calls the object of the corresponding command and passes a simple DI-container in the form of ResourceManager to it. The command router simply converts the command name of the form "do-some-stuff" into the class name "DoSomeStuffCommand", which is supposedly enough, but additionally, the router explicitly lists the classes of commands. In this way the description for the "help" command is set, plus PhpStorm static analysis stops swearing that command classes are not used anywhere.

This script is not yet a fully completed solution. When writing it, the main focus was on providing the most complete upload of data from the API. While the rendering of beautiful HTML was left "for later". In the current form, the HTML output is simply messages with date and time, no special formatting, link highlighting, line breaks, attached files, etc. The database contains, for example, reactions to messages (emoji) that are not yet output in HTML. Files attached to messages are also saved, but are not displayed in HTML.
