<?php

namespace App\Command;

use App\Entity\File;
use App\Entity\Folder;
use App\Repository\FileRepository;
use App\Repository\FolderRepository;
use App\Repository\MovieRepository;
use App\Service\PremiumizeMeAdapter;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ImortFoldersCommand extends Command
{
    protected static $defaultName = 'app:import:folders';

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
            ->addArgument('depth', InputArgument::OPTIONAL, 'Max Depth of folders to be imported', 4)
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $depth = $input->getArgument('depth');
        $this->adapter->setLogger(new ConsoleLogger($output));

        if ($depth) {
            $io->note(sprintf('You passed an argument: %s', $depth));
        }
//
//        if ($input->getOption('option1')) {
//            // ...
//        }

        foreach ($this->fileRepository->findAll() as $file) {
            $this->allFiles[$file->getId()] = $file;
        }

        foreach ($this->folderRepository->findAll() as $folder) {
            $this->allFolders[$folder->getId()] = $folder;
        }

        $this->importFolders($io, null, $depth);
        $io->success(sprintf('Folders with depth %d imported succesfully', $depth));

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
//            $oldFolder->setSuffix($newFolder->getSuffix());

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
        if($depth > $maxDepth) {
            return;
        }
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
        $this->entityManager->persist($persistFolder);
        $this->allFolders[$persistFolder->getId()] = $persistFolder;
        if(count ($baseFolder->getFiles())) {
            foreach ($baseFolder->getFiles() as $child) {
                if(!in_array($child->getExtension(), File::VIDEO_EXTENSIONS) &&
                    !in_array($child->getExtension(), File::IMAGE_EXTENSIONS)){
                    continue;
                }

                $child = $this->updateFile($child);
                $persistFiles[] = $child;
                $child->setFolder($persistFolder);
                $this->entityManager->persist($child);
                $this->allFiles[$child->getId()] = $child;
            }
            $persistFolder->setFiles($persistFiles);
        }

        if(count ($baseFolder->getFolders())) {
            foreach ($baseFolder->getFolders() as $child) {
                $child = $this->updateFolder($child);
                $persistFolders[] = $child;
                $child->setParent($persistFolder);
                $this->entityManager->persist($child);
                $this->allFolders[$child->getId()] = $child;
            }
            $persistFolder->setFolders($persistFolders);
        }
        $this->entityManager->flush();
        foreach ($baseFolder->getFolders() as $child) {
            $this->importFolders($io, $child, $maxDepth, $depth + 1);
        }
    }
}
