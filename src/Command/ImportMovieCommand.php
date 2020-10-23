<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Folder;
use App\Repository\FileRepository;
use App\Repository\FolderRepository;
use App\Repository\MovieRepository;
use App\Service\PremiumizeMeAdapter;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImportMovieCommand extends Command
{
    protected static $defaultName = 'app:import:movie';

    /** @var PremiumizeMeAdapter */
    private $adapter;
    /** @var FileRepository */
    private $fileRepository;
    /** @var FolderRepository */
    private $folderRepository;
    /** @var MovieRepository */
    private $movieRepository;
    private $entityManager;
    private $allFiles = [];
    private $allFolders = [];


    public function __construct(
        string $name = null,
        PremiumizeMeAdapter $adapter,
        FileRepository $fileRepository,
        FolderRepository $folderRepository,
        MovieRepository $movieRepository,
        EntityManagerInterface $entityManager
    ) {
        parent::__construct($name);
        $this->adapter = $adapter;
        $this->fileRepository = $fileRepository;
        $this->folderRepository = $folderRepository;
        $this->movieRepository = $movieRepository;
        $this->entityManager = $entityManager;
    }

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('arg1');

        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }
//        $allFiles = [];
//
        foreach ($this->fileRepository->findAll() as $file) {
            $this->allFiles[$file->getId()] = $file;
        }
//
//
//        $files = $this->adapter->getAllMovies();
//        $files = array_slice($files, 0, 5);
//        foreach ($files as $key => $file) {
//            $io->writeln(sprintf("Got Video: %s.%s", $file->getFileName(), $file->getExtension()));
////            $io->write('x');
//            try {
//                $files[$key] = $this->adapter->getFileDetails($file);
//            } catch (Exception $exception) {
//                $io->warning($exception->getMessage());
//                unset($files[$key]);
//            }
//            $id = $files[$key]->getId();
//            if(isset($allFiles[$id])) {
//                $this->updateFile($allFiles[$id], $files[$key]);
//            } else {
//                $this->entityManager->persist($files[$key]);
//            }
//        }
//
//        $allFolders = [];
        foreach ($this->folderRepository->findAll() as $folder) {
            $this->allFolders[$folder->getId()] = $folder;
        }
//
//
//        foreach ($files as $file) {
//
//            if(isset($allFolders[$file->getFolder()->getId()])) {
//                $file->setFolder($this->updateFolder($allFolders[$file->getFolder()->getId()], $file->getFolder()));
//                $file->getFolder()->setSuffix("Persisted");
//                $io->writeln(sprintf("Updating old Folder %s", $folder));
//            }
//            $io->writeln(sprintf("Trying to persist %s", $folder));
//            $this->entityManager->persist($file->getFolder());
//
//        }
        $this->importFolders($io, null, 4);
//        $this->entityManager->flush();
//        $io->writeln(sprintf("Found a total of %d movie files", count($files)));
//        $allFolders = $this->folderRepository->findAll();
        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }

    /**
     * @param File $newFile
     */
    private function updateFile(File $newFile): File
    {
        if (isset ($this->allFiles[$newFile->getId()])) {
            $oldFile = $this->allFiles[$newFile->getId()];
            $oldFile->setLink($newFile->getLink());
            $oldFile->setFolder($newFile->getFolder());
            $oldFile->setPath($newFile->getPath());

            return $oldFile;
        }

        return $newFile;
    }

    private function updateFolder(Folder $newFolder): Folder
    {
        if (isset($this->allFolders[$newFolder->getId()])) {
            $oldFolder = $this->allFolders[$newFolder->getId()];
            $oldFolder->setName($newFolder->getName());
            $oldFolder->setSuffix($newFolder->getSuffix());

            return $oldFolder;
        }

        return $newFolder;
    }

    /**
     * @param int $maxDepth
     * @param int $depth
     * @param Folder|null $folder
     * @throws Exception
     */
    private function importFolders(SymfonyStyle  $io, ?Folder $folder, int $maxDepth = 10, int $depth = 0)
    {
        $io->writeln(sprintf("Running import for folder %s with current depth %d and max depth %d", $folder, $depth, $maxDepth));
        $currentFolder = null;
        if ($folder) {
            $baseFolder = $this->adapter->getFolder($folder->getId());
        } else {
            $baseFolder = $this->adapter->getFolder();
        }
        $persistFolder = $this->updateFolder($baseFolder);
        $persistFiles = [];
        $persistFolders = [];
        $this->entityManager->persist($baseFolder);
        foreach ($baseFolder->getFiles() as $child) {
            $child = $this->updateFile($child);
            $persistFiles[] = $child;
            $child->setFolder($baseFolder);
            $this->entityManager->persist($child);
        }
        $baseFolder->setFiles($persistFiles);
        foreach ($baseFolder->getFolders() as $child) {
            $child = $this->updateFolder($child);
            $persistFolders[] = $child;
            $child->setParent($baseFolder);
            $this->entityManager->persist($child);
        }
        $baseFolder->setFolders($persistFolders);
        $this->entityManager->flush();
        foreach ($baseFolder->getFolders() as $child) {
            $this->importFolders($io, $child, $maxDepth, $depth + 1);
        }



    }
}
