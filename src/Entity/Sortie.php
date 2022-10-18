<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column]
    private ?int $duration = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $subLimitDate = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxSubscription = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $informations = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etat $state = null;

    #[ORM\ManyToOne(inversedBy: 'organizedParties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organizer = null;

    #[ORM\ManyToOne(inversedBy: 'parties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Site $site = null;

    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'parties')]
    private Collection $participants;

    #[ORM\ManyToOne(inversedBy: 'parties')]
    private ?Lieu $place = null;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
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

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(int $duration): self
    {
        $this->duration = $duration;

        return $this;
    }

    public function getSubLimitDate(): ?\DateTimeInterface
    {
        return $this->subLimitDate;
    }

    public function setSubLimitDate(\DateTimeInterface $subLimitDate): self
    {
        $this->subLimitDate = $subLimitDate;

        return $this;
    }

    public function getMaxSubscription(): ?int
    {
        return $this->maxSubscription;
    }

    public function setMaxSubscription(?int $maxSubscription): self
    {
        $this->maxSubscription = $maxSubscription;

        return $this;
    }

    public function getInformations(): ?string
    {
        return $this->informations;
    }

    public function setInformations(?string $informations): self
    {
        $this->informations = $informations;

        return $this;
    }

    public function getState(): ?Etat
    {
        return $this->state;
    }

    public function setState(?Etat $state): self
    {
        $this->state = $state;

        return $this;
    }

    public function getOrganizer(): ?Participant
    {
        return $this->organizer;
    }

    public function setOrganizer(?Participant $organizer): self
    {
        $this->organizer = $organizer;

        return $this;
    }

    public function getSite(): ?Site
    {
        return $this->site;
    }

    public function setSite(?Site $site): self
    {
        $this->site = $site;

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
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): self
    {
        $this->participants->removeElement($participant);

        return $this;
    }

    public function getPlace(): ?Lieu
    {
        return $this->place;
    }

    public function setPlace(?Lieu $place): self
    {
        $this->place = $place;

        return $this;
    }
}
