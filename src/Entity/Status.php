<?php

namespace App\Entity;

use App\Repository\StatusRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=StatusRepository::class)
 */
class Status
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */
    private $http_code;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $latency;

    /**
     * @ORM\Column(type="datetime")
     */
    private $datetime;

    /**
     * @ORM\ManyToOne(targetEntity=Site::class, inversedBy="log_statuses")
     */
    private $log_site;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $content;

    public function __toString()
    {
       return $this->getLogSite()->getName() ?: 'Site';
    }

    public function __construct()
    {
        $this->site = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getHttpCode(): ?int
    {
        return $this->http_code;
    }

    public function setHttpCode(int $http_code): self
    {
        $this->http_code = $http_code;

        return $this;
    }

    public function getLatency(): ?string
    {
        return $this->latency;
    }

    public function setLatency(?string $latency): self
    {
        $this->latency = $latency;

        return $this;
    }

    public function getDatetime(): ?\DateTimeInterface
    {
        return $this->datetime;
    }

    public function setDatetime(\DateTimeInterface $datetime): self
    {
        $this->datetime = $datetime;

        return $this;
    }

    public function getLogSite(): ?Site
    {
        return $this->log_site;
    }

    public function setLogSite(?Site $log_site): self
    {
        $this->log_site = $log_site;

        return $this;
    }

    public function isError(): bool
    {
        return $this->getHttpCode() !== 200;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;

        return $this;
    }
}
