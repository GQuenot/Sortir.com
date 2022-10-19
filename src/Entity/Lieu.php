<?php

namespace App\Entity;

use App\Repository\LieuRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: LieuRepository::class)]
class Lieu
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['place_group'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['place_group'])]
    private ?string $name = null;

    #[ORM\Column(length: 255)]
    #[Groups(['place_group'])]
    private ?string $street = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['place_group'])]
    private ?float $latitude = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['place_group'])]
    private ?float $longitude = null;

    #[ORM\OneToMany(mappedBy: 'place', targetEntity: Activity::class)]
    private Collection $parties;

    #[ORM\ManyToOne(inversedBy: 'places')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['place_group'])]
    private ?Ville $town = null;

    public function __construct()
    {
        $this->parties = new ArrayCollection();
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

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): self
    {
        $this->street = $street;

        return $this;
    }

    public function getLatitude(): ?float
    {
        return $this->latitude;
    }

    public function setLatitude(?float $latitude): self
    {
        $this->latitude = $latitude;

        return $this;
    }

    public function getLongitude(): ?float
    {
        return $this->longitude;
    }

    public function setLongitude(?float $longitude): self
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return Collection<int, Activity>
     */
    public function getParties(): Collection
    {
        return $this->parties;
    }

    public function addParty(Activity $party): self
    {
        if (!$this->parties->contains($party)) {
            $this->parties->add($party);
            $party->setPlace($this);
        }

        return $this;
    }

    public function removeParty(Activity $party): self
    {
        if ($this->parties->removeElement($party)) {
            // set the owning side to null (unless already changed)
            if ($party->getPlace() === $this) {
                $party->setPlace(null);
            }
        }

        return $this;
    }

    public function getTown(): ?Ville
    {
        return $this->town;
    }

    public function setTown(?Ville $town): self
    {
        $this->town = $town;

        return $this;
    }
}
