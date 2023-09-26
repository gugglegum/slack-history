<?php

namespace App\Helpers;

class ConfigHelper
{
    private \Luracast\Config\Config $config;

    public function __construct(\Luracast\Config\Config $config)
    {
        $this->config = $config;
    }

    public function isSkipHistoryChannel(string $channel): bool
    {
        static $skipChannels;
        if (!isset($skipChannels)) {
            $skipChannels = preg_split('/\s*,\s*/', (string) $this->config->get('slack.skipHistoryChannels'), -1, PREG_SPLIT_NO_EMPTY);
        }
        return in_array($channel, $skipChannels);
    }
}
