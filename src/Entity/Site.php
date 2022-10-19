<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SiteRepository::class)]
class Site
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\OneToMany(mappedBy: 'sites', targetEntity: Participant::class)]
    private Collection $participants;

    #[ORM\OneToMany(mappedBy: 'site', targetEntity: Activity::class)]
    private Collection $activities;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->activities = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): self
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
            $participant->setSite($this);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        if ($this->participants->removeElement($participant)) {
            // set the owning side to null (unless already changed)
            if ($participant->getSite() === $this) {
                $participant->setSite(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getParties(): Collection
    {
        return $this->activities;
    }

    public function addParty(Activity $party): self
    {
        if (!$this->activities->contains($party)) {
            $this->activities->add($party);
            $party->setSite($this);
        }

        return $this;
    }

    public function removeParty(Activity $party): self
    {
        if ($this->activities->removeElement($party)) {
            // set the owning side to null (unless already changed)
            if ($party->getSite() === $this) {
                $party->setSite(null);
            }
        }

        return $this;
    }
}
