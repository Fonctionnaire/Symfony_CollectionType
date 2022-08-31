<?php

namespace App\Service;

use App\Entity\Trick;
use App\Entity\Video;
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
            if($image->getImageName() === null)
            {
                $image->setImageName($this->upload($image->getFile()));
            }

        }
    }

    public function getTargetDirectory()
    {
        return $this->targetDirectory;
    }

    public function getVideoId(Video $video) {

        parse_str(parse_url($video->getVideoname(), PHP_URL_QUERY), $videoId);

        if (isset($videoId['v'])) {
            $video->setVideoname($videoId['v']);
        }
    }

    public function uploadVideos(Trick $trick)
    {
        foreach ($trick->getVideos() as $video) {

            $check = parse_url($video->getVideoname(), PHP_URL_HOST);

            if ($check == "www.youtube.com") {

                $this->getVideoId($video);

                $trick->addVideo($video);
            }
        }
    }
}