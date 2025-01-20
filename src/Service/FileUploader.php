<?php

namespace App\Service;

use Exception;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader {

    private readonly SluggerInterface $slugger;

    public function __construct($slugger)
    {
        $this->slugger = $slugger;
    }

    public function uploadImage(UploadedFile $imageFile, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory):String
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $imageFile->guessExtension();
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        try {
            $imageFile->move($imagesDirectory, $newFilename);
        } catch (FileException $e) {

        }
        
        return $newFilename;
    }

    public function removeImage(String $path):void
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->remove($path);
        }
        catch (Exception $e) {
            
        }
    }
}