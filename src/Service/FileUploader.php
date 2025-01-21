<?php

namespace App\Service;

use Exception;
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

    public function uploadFile(UploadedFile $imageFile, string $directory):String
    {
        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $extension = $imageFile->guessExtension();
        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        try {
            $imageFile->move($directory, $newFilename);
        } catch (FileException $e) {

        }
        
        return $newFilename;
    }

    public function removeFile(String $path):void
    {
        $filesystem = new Filesystem();
        try {
            $filesystem->remove($path);
        }
        catch (Exception $e) {
            
        }
    }
}