<?php

namespace App\Service;

use App\Entity\Trick;
use App\Entity\Video;
use Doctrine\DBAL\Exception;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{
    private $targetDirectory;
    private $slugger;

    public function __construct($targetDirectory, SluggerInterface $slugger)
    {
        $this->targetDirectory = $targetDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            dd($e);
        }

        return $fileName;
    }

    public function uploadImages(Trick $trick)
    {
        foreach($trick->getImages() as $image)
        {
            if($image->getFile() !== null)
            {
                $image->setImageName($this->upload($image->getFile()));
            }elseif ($image->getImageName() === null && $image->getFile() === null)
            {
                $trick->removeImage($image);
            }
        }
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }


    public function uploadVideos(Trick $trick)
    {
        foreach ($trick->getVideos() as $video) {

            $check = parse_url($video->getVideoname(), PHP_URL_HOST) ;
            parse_str(parse_url($video->getVideoname(), PHP_URL_QUERY), $videoId);

            if ($check === "www.youtube.com" && array_key_exists('v', $videoId)) {

                $video->setVideoId($videoId['v']);

                $trick->addVideo($video);
            }elseif ($video->getVideoname() === null || $video->getVideoId() === null) {
                $trick->removeVideo($video);
            }
        }
    }
}