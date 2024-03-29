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

class ConversationsJoinPostResponse200
{
    /**
     * @var array
     */
    protected $initialized = [];
    /**
     * @var ObjsConversation|null
     */
    protected $channel;
    /**
     * @var bool|null
     */
    protected $ok;
    /**
     * @var ConversationsJoinPostResponse200ResponseMetadata|null
     */
    protected $responseMetadata;
    /**
     * @var string|null
     */
    protected $warning;

    public function isInitialized($property): bool
    {
        return \array_key_exists($property, $this->initialized);
    }

    public function getChannel(): ?ObjsConversation
    {
        return $this->channel;
    }

    public function setChannel(?ObjsConversation $channel): self
    {
        $this->initialized['channel'] = true;
        $this->channel = $channel;

        return $this;
    }

    public function getOk(): ?bool
    {
        return $this->ok;
    }

    public function setOk(?bool $ok): self
    {
        $this->initialized['ok'] = true;
        $this->ok = $ok;

        return $this;
    }

    public function getResponseMetadata(): ?ConversationsJoinPostResponse200ResponseMetadata
    {
        return $this->responseMetadata;
    }

    public function setResponseMetadata(?ConversationsJoinPostResponse200ResponseMetadata $responseMetadata): self
    {
        $this->initialized['responseMetadata'] = true;
        $this->responseMetadata = $responseMetadata;

        return $this;
    }

    public function getWarning(): ?string
    {
        return $this->warning;
    }

    public function setWarning(?string $warning): self
    {
        $this->initialized['warning'] = true;
        $this->warning = $warning;

        return $this;
    }
}
