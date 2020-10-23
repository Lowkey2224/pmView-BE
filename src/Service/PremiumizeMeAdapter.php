<?php

namespace App\Service;

use App\Entity\File;
use App\Entity\Folder;
use App\Entity\Image;
use App\Entity\Video;
use Doctrine\Common\Collections\ArrayCollection;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class PremiumizeMeAdapter implements LoggerAwareInterface
{
    private $apiKey = "yx3gizdf4gcxmg9f";
    private $baseUri = "https://www.premiumize.me/api/";
    /** @var HttpClientInterface */
    private $client;
    private $logger;

    public function __construct(HttpClientInterface $client, LoggerInterface $logger, $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->logger = $logger;
        $this->client = $client;
    }

    public function getFolder(string $id = null): Folder
    {
        if ($id) {
            $this->logger->info("Checking with ID");
            $response = $this->get('folder/list', ['id' => $id, 'includebreadcrumbs' => true]);
        } else {
            $this->logger->info("Checking without ID");
            $response = $this->get('folder/list', ['includebreadcrumbs' => true]);
        }
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new \Exception("Failed request folder/list with statuscode".$response->getStatusCode());
        }
        $json = json_decode($response->getContent(false), true);
        if ($json['status'] !== "success") {
            throw new \Exception(
                "Failed request folder/list with error ".$response->getContent(false)."\n".json_encode(
                    $response->getInfo()
                )
            );
        }
        $this->logger->info("Git Content");
        $this->logger->info($response->getContent(false));

        $breadCount = count($json['breadcrumbs']);
        $this->logger->info("Breadcount is ".$breadCount);
        $thisFolder = Folder::fromJSON($json['breadcrumbs'][$breadCount - 1]);
        if ($breadCount > 1) {
            $parent = Folder::fromJSON($json['breadcrumbs'][$breadCount - 2]);
            $thisFolder->setParent($parent);
        }
        $containedFolders = [];
        $containedFiles = [];
        foreach ($json['content'] as $contentItem) {
            if ($contentItem['type'] == 'folder') {
                $containedFolders[] = Folder::fromJSON($contentItem);
            } elseif ($contentItem['type'] == 'file') {
                $parts = pathinfo($contentItem['name']);
                $extension = $parts['extension'];
                if(in_array($extension, File::VIDEO_EXTENSIONS)) {
                    $containedFiles[] = Video::fromJSON($contentItem);
                } elseif (in_array($extension, File::IMAGE_EXTENSIONS)) {
                    $containedFiles[] = Image::fromJSON($contentItem);
                }

            }

        }
        $thisFolder->setFolders(new ArrayCollection($containedFolders));
        $thisFolder->setFiles(new ArrayCollection($containedFiles));

        return $thisFolder;
    }

    /**
     * @param string $address
     * @return ResponseInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function get(string $address, $queryParams = []): ResponseInterface
    {
        $queryParams = array_merge(
            $queryParams,
            [
                'apikey' => $this->apiKey,
            ]
        );

        return $this->client->request(
            'GET',
            $this->baseUri.$address,
            [
                'query' => $queryParams,
            ]
        );
    }

    public function getAllMovies()
    {
        return $this->getAllFiles();
    }

    /**
     * @return File[]
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function getAllFiles(): array
    {
        $response = $this->get("item/listall");
        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new \Exception("Failed request item/listall with statuscode".$response->getStatusCode());
        }
        $json = json_decode($response->getContent(false), true);
        if ($json['status'] !== "success") {
            throw new \Exception("Failed request item/listall with error".$response->getContent(false));
        }
        $files = $json['files'];
        $res = [];
        foreach ($files as $file) {
            $fObj = File::fromJSON($file);
            if (!in_array($fObj->getExtension(), File::VIDEO_EXTENSIONS)) {
                continue;
            }
            $res[] = $fObj;
        }

        return $res;
    }

    public function getFileDetails(File $file): File
    {
        $response = $this->get('item/details', ['id' => $file->getId()]);
        $content = json_decode($response->getContent(), true);
        $folder = new Folder();
        $folder->setId($content['folder_id']);
        $folder->setSuffix("New");
        $parts = pathinfo($file->getPath());

        $folder->setName($parts['dirname']);
        $file->setLink($content['stream_link']);
        $file->setFolder($folder);

        return $file;
    }

    public function getLink(File $file): ?string
    {
        $response = $this->get('item/details', ['id' => $file->getId()]);
        $content = json_decode($response->getContent(), true);

        return $content['stream_link'];
    }

    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

//    private function getFileForId(string $id): File
//    {
//        $response = $this->client->request('GET', '');
//
//        return new File();
//    }
}
