<?php

return [
    'token' => getenv('SLACK_TOKEN'),
    'skipHistoryChannels' => getenv('SLACK_SKIP_HISTORY_CHANNELS'),
    'skipFilesChannels' => getenv('SLACK_SKIP_FILES_CHANNELS'),
];
