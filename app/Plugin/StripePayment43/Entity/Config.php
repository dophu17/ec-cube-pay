<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * https://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\StripePayment43\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_stripe_payment_config")
 *
 * @ORM\Entity(repositoryClass="Plugin\StripePayment43\Repository\ConfigRepository")
 */
class Config
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     *
     * @ORM\Id
     *
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="public_key", type="text", nullable=true)
     */
    private $public_key;

    /**
     * @var string
     *
     * @ORM\Column(name="restricted_private_key", type="text", nullable=true)
     */
    private $restricted_private_key;

    /**
     * @var integer
     *
     * @ORM\Column(name="environments", type="integer", options={"default":1})
     */
    private $environments;

    /**
     * @var boolean
     *
     * @ORM\Column(name="separate_capture", type="boolean", options={"default":false})
     */
    private $separate_capture = false;

    /**
     * @var boolean
     *
     * @ORM\Column(name="receive_notification", type="boolean", options={"default":false})
     */
    private $receive_notification = false;

    /**
     * @var string
     *
     * @ORM\Column(name="webhookEndpointId", type="text", nullable=true)
     */
    private $webhookEndpointId;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getPublicKey(): ?string
    {
        return $this->public_key;
    }

    /**
     * @param string|null $public_key
     *
     * @return self
     */
    public function setPublicKey(?string $public_key): self
    {
        $this->public_key = $public_key;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRestrictedPrivateKey(): ?string
    {
        return $this->restricted_private_key;
    }

    /**
     * @param string|null $restricted_private_key
     *
     * @return self
     */
    public function setRestrictedPrivateKey(?string $restricted_private_key): self
    {
        $this->restricted_private_key = $restricted_private_key;

        return $this;
    }

    /**
     * @return int
     */
    public function getEnvironments(): ?int
    {
        return $this->environments;
    }

    /**
     * @param int $environments
     *
     * @return self
     */
    public function setEnvironments(?int $environments): self
    {
        $this->environments = $environments;

        return $this;
    }

    /**
     * @return bool
     */
    public function getSeparateCapture(): bool
    {
        return $this->separate_capture;
    }

    /**
     * @param bool $separate_capture
     *
     * @return self
     */
    public function setSeparateCapture(bool $separate_capture): self
    {
        $this->separate_capture = $separate_capture;

        return $this;
    }

    /**
     * @return bool
     */
    public function getReceiveNotification(): bool
    {
        return $this->receive_notification;
    }

    /**
     * @param bool $receive_notification
     *
     * @return self
     */
    public function setReceiveNotification(bool $receive_notification): self
    {
        $this->receive_notification = $receive_notification;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getWebhookEndpointId(): ?string
    {
        return $this->webhookEndpointId;
    }

    /**
     * @param string|null $webhookEndpointId
     *
     * @return self
     */
    public function setWebhookEndpointId(?string $webhookEndpointId): self
    {
        $this->webhookEndpointId = $webhookEndpointId;

        return $this;
    }
}
