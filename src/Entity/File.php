<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiResource;
use App\Repository\FileRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class File
 * @package App\Entity
 * @ORM\Entity(repositoryClass=FileRepository::class)
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="fileType", type="string")
 * @ORM\DiscriminatorMap({"image" = "Image", "video" = "Video"})
 */
abstract class File
{

//    const TYPE_IMAGE = "image";
//    const TYPE_VIDEO = "video";
    const VIDEO_EXTENSIONS = ['mkv', 'mp4', 'avi', 'm4v'];
    const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @var string
     * @ORM\Column(type="string", length=5)
     */
    private $extension;

    /**
     * @var Folder
     * @ORM\ManyToOne(targetEntity="App\Entity\Folder", inversedBy="files")
     */
    private $folder;

    /**
     * @var Movie
     * @ORM\ManyToOne(targetEntity="App\Entity\Movie", inversedBy="files")
     */
    private $movie;

    /**
     * @var string
     * @ORM\Id
     * @ORM\Column(type="string", length=255)
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $link;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $mimeType;

    /**
     * @var string
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $path;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $size;

    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    private $createdAt;

    public static function fromJSON(array $file): File
    {
        $parts = pathinfo($file['name']);
        $extension = $parts['extension'];
        $fObj = new static();
        $fObj->setId($file['id']);
        $fn = $parts['filename'];
        $fObj->setName($fn);
        $fObj->setExtension($extension);
        $fObj->setMimeType($file['mime_type']);
        $fObj->setPath($file['path'] ?? null);
        $fObj->setSize($file['size']);
        $dt = new \DateTime();

        $dt = $dt->setTimestamp($file['created_at']);
        $fObj->setLink($file['link']);
        $fObj->setCreatedAt($dt);

        return $fObj;
    }

    /**
     * @return Folder
     */
    public function getFolder(): Folder
    {
        return $this->folder;
    }

    /**
     * @param Folder $folder
     */
    public function setFolder(Folder $folder): void
    {
        $this->folder = $folder;
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLink(): string
    {
        return $this->link;
    }

    /**
     * @param string $link
     */
    public function setLink(string $link): void
    {
        $this->link = $link;
    }

    /**
     * @return mixed
     */
    public function getMimeType()
    {
        return $this->mimeType;
    }

    /**
     * @param mixed $mimeType
     */
    public function setMimeType($mimeType): void
    {
        $this->mimeType = $mimeType;
    }

    /**
     * @return string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param string|null $path
     */
    public function setPath(?string $path): void
    {
        $this->path = $path;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @param int $size
     */
    public function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return DateTime
     */
    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    /**
     * @param DateTime $createdAt
     */
    public function setCreatedAt(DateTime $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function __toString()
    {
        return sprintf("File %s.%s", $this->getName(), $this->getExtension());
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
     * @return string
     */
    public function getExtension(): string
    {
        return $this->extension;
    }

    /**
     * @param string $extension
     */
    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    /**
     * @return Movie
     */
    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    /**
     * @param Movie $movie
     */
    public function setMovie(?Movie $movie): void
    {
        $this->movie = $movie;
    }

}
