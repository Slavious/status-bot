<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SiteRepository::class)
 */
class Site
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $priority;

    /**
     * @ORM\OneToMany(targetEntity=Status::class, mappedBy="log_site", cascade={"remove"})
     */
    private $log_statuses;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $domain_name;

    public function __toString()
    {
        return $this->name;
    }

    public function __construct()
    {
        $this->statuses = new ArrayCollection();
        $this->log_statuses = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getPriority(): ?int
    {
        return (int)$this->priority;
    }

    public function setPriority(string $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    /**
     * @return Collection|Status[]
     */
    public function getLogStatuses(): Collection
    {
        return $this->log_statuses;
    }

    public function addLogStatus(Status $logStatus): self
    {
        if (!$this->log_statuses->contains($logStatus)) {
            $this->log_statuses[] = $logStatus;
            $logStatus->setLogSite($this);
        }

        return $this;
    }

    public function removeLogStatus(Status $logStatus): self
    {
        if ($this->log_statuses->contains($logStatus)) {
            $this->log_statuses->removeElement($logStatus);
            // set the owning side to null (unless already changed)
            if ($logStatus->getLogSite() === $this) {
                $logStatus->setLogSite(null);
            }
        }

        return $this;
    }

    public function getDomainName(): ?string
    {
        return $this->domain_name;
    }

    public function setDomainName(?string $domain_name): self
    {
        $this->domain_name = $domain_name;

        return $this;
    }
}
