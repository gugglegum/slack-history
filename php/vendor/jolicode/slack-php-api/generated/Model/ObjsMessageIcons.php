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

class ObjsMessageIcons
{
    /**
     * @var array
     */
    protected $initialized = [];
    /**
     * @var string|null
     */
    protected $emoji;
    /**
     * @var string|null
     */
    protected $image64;

    public function isInitialized($property): bool
    {
        return \array_key_exists($property, $this->initialized);
    }

    public function getEmoji(): ?string
    {
        return $this->emoji;
    }

    public function setEmoji(?string $emoji): self
    {
        $this->initialized['emoji'] = true;
        $this->emoji = $emoji;

        return $this;
    }

    public function getImage64(): ?string
    {
        return $this->image64;
    }

    public function setImage64(?string $image64): self
    {
        $this->initialized['image64'] = true;
        $this->image64 = $image64;

        return $this;
    }
}
