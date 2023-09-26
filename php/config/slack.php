<?php

return [
    'token' => getenv('SLACK_TOKEN'),
    'skipHistoryChannels' => getenv('SLACK_SKIP_HISTORY_CHANNELS'),
];
