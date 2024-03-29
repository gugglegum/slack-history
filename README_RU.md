# Slack History

Language: [ENG](README.md) | RUS

Если вы используете мессенджер Slack на бесплатном тарифе, вы ограничены размером хранимой истории сообщении в 90 дней (ранее было 10000 сообщений на весь Workspace). Это может создавать неудобства из-за невозможности вернуться к ранее отправленным или полученным сообщениям. Данный скрипт решает эту проблему тем, что он использует Slack API для того, чтобы выкачать весь доступный лично вам объём сообщений, сохранить их в локальную базу данных SQLite и сгенерировать HTML файлы по каждому личному чату, публичному или приватному каналу, к которому вы имеете доступ. При повторных запусках в локальную базу добавляются только новые сообщения, которых не было раньше. Таким образом, запуская скрипт время от времени (сейчас хотя бы раз в 90 дней) вы сможете поддерживать свою историю сообщений в актуальном состоянии без пропусков, обойдя таким образом ограничение бесплатного тарифа на размер отображаемой истории. Каждый пользователь Slack должен запускать данный скрипт индивидуально на своём компьютере, используя свой личный токен. Данный скрипт невозможно использовать для централизованного хранения всей переписки всех пользователей. Каждая инсталляция скрипта использует личный токен конкретного пользователя и выкачивает всё, что доступно лично ему, включая его личные сообщения. 

Поскольку я предполагал, что моим скриптом возможно захотят воспользоваться другие сотрудники компании, не все из которых программисты, я стремился сделать установку и использование данного скрипта максимально простыми. Поэтому пришлось включить в дистрибутив PHP-бинарники и файлы в папке `vendor/`, чтобы от пользователя не требовалось иметь установленный Composer и PHP нужной версии с нужными расширениями. Чтобы всё было максимально просто, без каких-либо танцев с бубном. Не уверен, правда, в том, что это не нарушает какие-то лицензии. Для хранения истории сообщения был выбран SQLite, что упрощало локальное использование скрипта и создание бэкапов.

Для того чтобы получить доступ к Slack API вам нужно будет создать приложение в вашем Workspace и задать для него список требуемых привилегий (это несложно, но требуются права администратора), а затем предоставить ему соответствующий доступ, получив токен, который будет использовать данный скрипт. Права администратора нужны только для создания Slack-приложения, пользоваться им смогут люди с обычными правами.

## Оглавление

1. [Предыстория](#предыстория)
2. [Установка](#установка)
3. [Использование](#использование)
4. [Реализация](#реализация)

## Предыстория

Начиная с 2016 года я работал в компании, которая использовала Slack на бесплатном тарифе. Руководство компании считало, что платный тариф стоит неоправданно дорого и оставалось на бесплатном тарифе. В то время бесплатный тариф позволял видеть только последние 10000 сообщений на весь Workspace. При нашей активности переписки этого хватало примерно на 2 недели. Однако, мне лично не хватало столь узкого окна истории, и чтобы не потерять что-то важное, сначала я просто сохранял переписку в текстовые файлики, что было очень утомительно и ненадёжно, т.к. случались пропуски в сохранении истории. Но в какой-то момент лень победила и я задался вопросом автоматизации этого процесса. Я обнаружил, что Slack API позволяет получать историю сообщений. Тогда-то и родилась идея регулярно сохранять скриптом историю в какое-то локальное хранилище, по которому при необходимости можно делать поиск нужных сообщений, а для удобства можно ещё рендерить текстовые или HTML файлы. Дополнительным триггером для меня стало то, что кто-то активировал 30-дневный пробный период, в рамках которого появился шанс выкачать всю историю за несколько лет. И затем лишь раз в неделю запускать скрипт, выкачивая новые сообщения.

Где-то в начале 2023 года в Slack произошло изменение на бесплатном тарифе - теперь доступно стало не 10000 сообщений, а вся история за 90 дней, независимо от того сколько там сообщений. Пользоваться Slack стало намного удобнее. Актуальность в данном скрипте из-за этого снизилась, но я продолжаю им пользоваться.

## Установка

1. Создайте новое приложение в настройках Slack в вашем Workspace в разделе "Manage".
2. Задайте приложению список требуемых привилегии, описанные ниже.
3. Склонируйте данный репозиторий в любую директорию.
4. Предоставьте приложению доступ, в ответ вы получите токен, начинающийся с "xoxp-". Этот токен показывается лишь один раз.
5. Скопируйте файл `.env.distrib` в `.env` и подставьте туда свой токен для доступа к приложению, полученный на предыдущем шаге.

Шаги 1-2 нужны только при установке первым пользователем в компании. Остальные пользователи могут использовать уже созданное вами приложение, выполняя у себя только шаги 3-5.

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

(возможно, список привилегий слегка избыточный и в действительности требуется меньше привилегий)

## Использование

Репозиторий уже содержит в себе бинарники PHP для Windows (`php.exe`), так что установленного PHP на компьютере не требуется. Можно запустить `update.cmd`, лежащий в корне проекта, этот скрипт сделает всё необходимое. `update.cmd` уже заточен на использование `php.exe`, входящего в репозиторий в папке `bin/`. На самом деле `update.cmd` вызывает сразу 3 скрипта:

1. `fetch-history` - выкачивает историю переписки в SQLite базу данных в папке `db/` (если базы нет - автоматически создаёт её).
2. `fetch-files` - выкачивает все файлы, которые использовались как вложения в переписке, сохраняя их в `files/` (выкачивает только новые).
3. `compile-html` - генерирует HTML файлы в папке `html/` с историей переписки из SQLite БД.

На текущий момент генерируемые файлы очень просты в оформлении и по сути они сохраняются как есть в БД. Форматирование не применяется. Файлы не подставляются. Имеется оглавление в виде файла `index.html`.

## Реализация

Скрипт написан на языке PHP 8.3. Для простоты и легковесности, скрипт не использует никакие фреймворки. Мне вообще кажется этот путь не самым оптимальным, скорее просто данью моде в индустрии. Скрипт лишь использует несколько Composer пакетов:

* `jolicode/slack-php-api` — основная библиотека для работы с Slack API, сгенерирована автоматически на основе "OpenAPI specs" для Slack API;
* `symfony/http-client` — HTTP-клиент, реализующий интерфейс PSR-18, требуется для `jolicode/slack-php-api`;
* `aura/sql` — расширение нативного PDO, предоставляющего некоторые дополнительные возможности для большего удобства;
* `aura/sqlquery` — простой query-builder для SQL-запросов;
* `vlucas/phpdotenv` — считывает переменные окружения из `.env` файла и добавляет в текущее окружение;
* `luracast/config` — предоставляет доступ к конфигам, которые в свою очередь используют переменные окружения;
* `league/plates` — простой шаблонный движок, используется для генерации HTML файлов;
* `gugglegum/retry-helper` — это мой пакет, позволяющий элегантно повторять попытки выполнения действия при ошибках (с логгером, с увеличивающимися случайными задержками между попытками, с коллбэками, позволяющими гибко управлять поведением), используется для корректной обработки ошибок соединения или в случае превышения лимита на частоту обращения к Slack API.

Точкой входа для всех консольных скриптов является файл `console.php`, который получает на вход первым аргументом командной строки имя команды, и используя простейший роутер команд, вызывает объект соответствующей команды и передаёт в него простейший DI-контейнер в лице ResourceManager. Роутер команд просто преобразует имя команды вида "do-some-stuff" в имя класса "DoSomeStuffCommand", этого по идее достаточно, но дополнительно в роутере явно перечисляются классы команд. Таким образом задаётся описание для команды "help", плюс статический анализ PhpStorm перестаёт ругаться на то, что классы команд нигде не используются.

Данный скрипт ещё не является полностью законченным решением. При его написании основной упор делался на то, чтобы обеспечить максимально полную выгрузку данных из API. В то время как рендеринг красивых HTML был оставлен "на потом". В текущем виде в HTML выводятся просто сообщения с датой и временем, никакого специального форматирования, выделения ссылок, переносов строк, приложенных файлов и т.п. В БД содержатся, например, реакции на сообщения (эмодзи), которые пока не выводятся в HTML. Приложенные к сообщениям файлы также сохраняются, но в HTML не отображаются.
