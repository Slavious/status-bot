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
     * @ORM\ManyToMany(targetEntity=Site::class, inversedBy="statuses")
     */
    private $site;

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

    public function __construct()
    {
        $this->site = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return Collection|Site[]
     */
    public function getSite(): Collection
    {
        return $this->site;
    }

    public function addSite(Site $site): self
    {
        if (!$this->site->contains($site)) {
            $this->site[] = $site;
        }

        return $this;
    }

    public function removeSite(Site $site): self
    {
        if ($this->site->contains($site)) {
            $this->site->removeElement($site);
        }

        return $this;
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
}
