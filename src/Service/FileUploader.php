<?php

namespace App\Service;

use App\Entity\Trick;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploader
{

    public function __construct(private readonly string $targetDirectory, private readonly SluggerInterface $slugger)
    {

    }

    public function upload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->getTargetDirectory(), $fileName);
        } catch (FileException $e) {
            throw new FileException($e);
        }

        return $fileName;
    }

    public function uploadImages(Trick $trick): void
    {

        foreach ($trick->getImages() as $image) {
            if ($image->getFile() !== null) {
                $image->setImageName($this->upload($image->getFile()));
            } elseif ($image->getImageName() === null && $image->getFile() === null) {
                $trick->removeImage($image);
            }
        }
    }

    public function getTargetDirectory(): string
    {
        return $this->targetDirectory;
    }


    public function uploadVideos(Trick $trick): void
    {

        foreach ($trick->getVideos() as $video) {
            $check = parse_url($video->getVideoname(), PHP_URL_HOST) ;
            parse_str(parse_url($video->getVideoname(), PHP_URL_QUERY), $videoId);


            if ($check === "www.youtube.com" && array_key_exists('v', $videoId)) {
                $video->setVideoId($videoId['v']);

                $trick->addVideo($video);
            }
            elseif ($check === "www.dailymotion.com"){
                $parsedUrl = parse_url($video->getVideoname());
                $pathSegments = explode('/', trim($parsedUrl['path'], '/'));
                $dailymotionId = end($pathSegments);
                $video->setVideoId($dailymotionId);
                $trick->addVideo($video);
            }
//            elseif ($video->getVideoname() === null || $video->getVideoId() === null) {
//                $trick->removeVideo($video);
//            }
        }
    }
}
