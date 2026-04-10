<?php

namespace Plugin\AmazonPayMini\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

/**
 * @ORM\Table(name="plg_amazon_pay_mini_config")
 * @ORM\Entity(repositoryClass="Plugin\AmazonPayMini\Repository\ConfigRepository")
 */
class Config extends AbstractEntity
{
    /**
     * @var int
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $merchant_id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $store_id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $client_id;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $public_key_id;

    /**
     * @var string|null
     * @ORM\Column(type="text", nullable=true)
     */
    private $private_key;

    /**
     * @var bool
     * @ORM\Column(type="boolean", options={"default": false})
     */
    private $sandbox = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    public function setMerchantId(?string $merchant_id): self
    {
        $this->merchant_id = $merchant_id;
        return $this;
    }

    public function getStoreId(): ?string
    {
        return $this->store_id;
    }

    public function setStoreId(?string $store_id): self
    {
        $this->store_id = $store_id;
        return $this;
    }

    public function getClientId(): ?string
    {
        return $this->client_id;
    }

    public function setClientId(?string $client_id): self
    {
        $this->client_id = $client_id;
        return $this;
    }

    public function getPublicKeyId(): ?string
    {
        return $this->public_key_id;
    }

    public function setPublicKeyId(?string $public_key_id): self
    {
        $this->public_key_id = $public_key_id;
        return $this;
    }

    public function getPrivateKey(): ?string
    {
        return $this->private_key;
    }

    public function setPrivateKey(?string $private_key): self
    {
        $this->private_key = $private_key;
        return $this;
    }

    public function isSandbox(): bool
    {
        return $this->sandbox;
    }

    public function setSandbox(bool $sandbox): self
    {
        $this->sandbox = $sandbox;
        return $this;
    }
}
