<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\MovieRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ApiResource()
 * @ORM\Entity(repositoryClass=MovieRepository::class)
 *
 */
class Movie
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\OneToMany(mappedBy="movie", targetEntity="App\Entity\File")
     * @var File[]|Collection
     */
    private $files;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $altTitle;

    /**
     * @var File|null
     * @ORM\OneToOne(targetEntity="App\Entity\Image")
     * @ORM\JoinColumn(name="screens_id", referencedColumnName="id")
     */
    private $screens;

    /**
     * @var File
     * @ORM\OneToOne(targetEntity="App\Entity\Image")
     * @ORM\JoinColumn(name="cover_id", referencedColumnName="id")
     */
    private $cover;

    private $directory;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(string $Id): self
    {
        $this->Id = $Id;

        return $this;
    }

    public function getPmId(): ?string
    {
        return $this->pmId;
    }

    public function setPmId(string $pmId): self
    {
        $this->pmId = $pmId;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAltTitle(): ?string
    {
        return $this->altTitle;
    }

    public function setAltTitle(?string $altTitle): self
    {
        $this->altTitle = $altTitle;

        return $this;
    }

    public function getScreens(): ?string
    {
        return $this->screens;
    }

    public function setScreens(?string $screens): self
    {
        $this->screens = $screens;

        return $this;
    }

    /**
     * @return File[]|Collection
     */
    public function getFiles()
    {
        return $this->files;
    }

    /**
     * @param File[]|Collection $files
     */
    public function setFiles($files): void
    {
        $this->files = $files;
    }

    /**
     * @return File
     */
    public function getCover(): ?File
    {
        return $this->cover;
    }

    /**
     * @param File $cover
     */
    public function setCover(?File $cover): void
    {
        $this->cover = $cover;
    }
}
