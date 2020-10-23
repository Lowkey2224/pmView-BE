<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\FolderRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class Folder
 * @package App\Entity
 * @ORM\Entity(repositoryClass=FolderRepository::class)
 * @ApiResource(
 *     collectionOperations={"get"},
 *     itemOperations={"get"}
 * )
 */
class Folder
{

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     * @ORM\Id
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var File[]|Collection
     * @ORM\OneToMany(mappedBy="folder", targetEntity="App\Entity\File")
     */
    private $files = [];

    /**
     * @var Folder[]|Collection
     * @ORM\OneToMany(mappedBy="parent", targetEntity="App\Entity\Folder")
     */
    private $folders = [];

    /**
     * @var Folder|null
     * @ORM\ManyToOne(targetEntity="App\Entity\Folder", inversedBy="folders")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id")
     */
    private $parent = null;

    /**
     * @return int
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function __toString()
    {
        return sprintf("Folder %s", $this->getName());
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * @return Folder|null
     */
    public function getParent(): ?Folder
    {
        return $this->parent;
    }

    /**
     * @param Folder|null $parent
     */
    public function setParent(?Folder $parent): void
    {
        $this->parent = $parent;
    }


    public static function fromJSON(array $json): Folder
    {
        $f = new Folder;
        $f->setId($json['id']);
        $f->setName($json['name']);

        return $f;
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
     * @return Folder[]|Collection
     */
    public function getFolders()
    {
        return $this->folders;
    }

    /**
     * @param Folder[]|Collection $folders
     */
    public function setFolders($folders): void
    {
        $this->folders = $folders;
    }


}
