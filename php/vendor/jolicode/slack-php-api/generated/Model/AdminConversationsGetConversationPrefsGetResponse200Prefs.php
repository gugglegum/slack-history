<?php

declare(strict_types=1);

/*
 * This file is part of JoliCode's Slack PHP API project.
 *
 * (c) JoliCode <coucou@jolicode.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace JoliCode\Slack\Api\Model;

class AdminConversationsGetConversationPrefsGetResponse200Prefs
{
    /**
     * @var array
     */
    protected $initialized = [];
    /**
     * @var AdminConversationsGetConversationPrefsGetResponse200PrefsCanThread|null
     */
    protected $canThread;
    /**
     * @var AdminConversationsGetConversationPrefsGetResponse200PrefsWhoCanPost|null
     */
    protected $whoCanPost;

    public function isInitialized($property): bool
    {
        return \array_key_exists($property, $this->initialized);
    }

    public function getCanThread(): ?AdminConversationsGetConversationPrefsGetResponse200PrefsCanThread
    {
        return $this->canThread;
    }

    public function setCanThread(?AdminConversationsGetConversationPrefsGetResponse200PrefsCanThread $canThread): self
    {
        $this->initialized['canThread'] = true;
        $this->canThread = $canThread;

        return $this;
    }

    public function getWhoCanPost(): ?AdminConversationsGetConversationPrefsGetResponse200PrefsWhoCanPost
    {
        return $this->whoCanPost;
    }

    public function setWhoCanPost(?AdminConversationsGetConversationPrefsGetResponse200PrefsWhoCanPost $whoCanPost): self
    {
        $this->initialized['whoCanPost'] = true;
        $this->whoCanPost = $whoCanPost;

        return $this;
    }
}
