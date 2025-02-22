<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Media;
use App\Entity\Trick;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\TrickRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/trick')]
class TrickController extends AbstractController
{
    #[Route('', name: 'app_trick', methods: ['GET'])]
    public function index(TrickRepository $trick_repository, Request $request): Response
    {
        $maxPage = round(count($trick_repository->findAll()) / 10, 0, PHP_ROUND_HALF_DOWN) + 1;

        $page = (null !== $request->get('page') ? $request->get('page') : 1);

        $tricks = $trick_repository->findByPage($page);

        return $this->render('trick/index.html.twig', [
            'tricks' => $tricks,
            'max_page' => $maxPage,
            'current_page' => $page
        ]);
    }

    #[Route('/show/{slug}', name: 'app_trick_show', methods: ['GET', 'POST'])]
    public function show(Trick $trick, Request $request, EntityManagerInterface $manager): Response
    {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setTrick($trick);
            $comment->setUser($this->getUser());
            $manager->persist($comment);
            $manager->flush();

            return $this->redirectToRoute('app_trick_show', ['slug' => $trick->getSlug()]);
        }

        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
            'comments' => $trick->getComments(),
            'form' => $form,
        ]);
    }

    #[Route('/new', name: 'app_trick_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function new(Request $request, SluggerInterface $slugger, FileUploader $fileUploader, #[Autowire('%kernel.project_dir%/public/uploads/medias')] string $mediasDirectory, #[Autowire('%kernel.project_dir%/public/uploads/thumbnails')] string $thumbnailsDirectory, EntityManagerInterface $manager): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Ajout de la figure bien efféctué');

            /** @var UploadedFile $thumbnail */
            $thumbnail = $form->get('thumbnail')->getData();

            if ($thumbnail) {
                $filename = $fileUploader->uploadFile($thumbnail, $thumbnailsDirectory);
                $trick->setThumbnail($filename);
            }
            else {
                $trick->setThumbnail('default_trick_thumbnail.jpg');
            }

            /** @var Collection<int, UploadedFile> $mediaFiles */
            $mediaFiles = $form->get('medias')->getData();

            if ($mediaFiles) {
                foreach($mediaFiles as $mediaFile) {
                    $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $mediaFile->guessExtension();
                    $media = new Media();
                    $media->setOriginalFilename($originalFilename.$extension);
                
                    $filename = $fileUploader->uploadFile($mediaFile, $mediasDirectory);
                    $media->setFilename($filename);
                    
                    
                    if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
                        $media->setType(Media::TYPE_IMAGE);
                    }
                    else if (in_array($extension, ['mp4'])) {
                        $media->setType(Media::TYPE_VIDEO);
                    }
                    else {
                        $media->setType(Media::TYPE_UNKNOWN);
                    }

                    $manager->persist($media);
                    $trick->addMedia($media);
                }
            }

            $trick->setSlug($slugger->slug($trick->getName()));
            $trick->setUser($this->getUser());
            $manager->persist($trick);
            $manager->flush();

            return $this->redirectToRoute('app_trick');
        }

        return $this->render('trick/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_trick_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function edit(Trick $trick, SluggerInterface $slugger, FileUploader $fileUploader, #[Autowire('%kernel.project_dir%/public/uploads/medias')] string $mediasDirectory, #[Autowire('%kernel.project_dir%/public/uploads/thumbnails')] string $thumbnailsDirectory, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Modification de la figure bien efféctué');

            $deletedFiles = (null !== $request->get('deleted-files') ? $request->get('deleted-files') : []);

            foreach($deletedFiles as $file) {
                $media = $manager->getRepository(Media::class)->findOneById($file);
                $trick->removeMedia($media);
                // $path = $this->getParameter("public_directory") . '/uploads/medias/' . $media->getFilename();
                // $fileUploader->removeFile($path);
            }

            /** @var UploadedFile $thumbnail */
            $thumbnail = $form->get('thumbnail')->getData();

            if ($thumbnail) {
                $path = $this->getParameter("public_directory") . '/uploads/thumbnails/' . $trick->getThumbnail();
                $fileUploader->removeFile($path);
                $filename = $fileUploader->uploadFile($thumbnail, $thumbnailsDirectory);
                $trick->setThumbnail($filename);
            }

            /** @var Collection<int, UploadedFile> $mediaFiles */
            $mediaFiles = $form->get('medias')->getData();

            if ($mediaFiles) {
                foreach($mediaFiles as $mediaFile) {
                    $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $extension = $mediaFile->guessExtension();

                    $media = $manager->getRepository(Media::class)->findOneBy(array('original_filename' => $originalFilename, 'trick' => $trick));
                    if (!$media) {
                        $media = new Media();                        
                        $media->setOriginalFilename($originalFilename.$extension);
                    }
                    else {
                        $path = $this->getParameter("public_directory") . '/uploads/medias/' . $media->getFilename();
                        $fileUploader->removeFile($path);
                    }

                    $filename = $fileUploader->uploadFile($mediaFile, $mediasDirectory);
                    $media->setFilename($filename);

                    if (in_array($extension, ['png', 'jpg', 'jpeg'])) {
                        $media->setType(Media::TYPE_IMAGE);
                    }
                    else if (in_array($extension, ['mp4'])) {
                        $media->setType(Media::TYPE_VIDEO);
                    }
                    else {
                        $media->setType(Media::TYPE_UNKNOWN);
                    }

                    $manager->persist($media);
                    $trick->addMedia($media);
                }
            }

            $trick->setSlug($slugger->slug($trick->getName()));
            $manager->persist($trick);
            $manager->flush();

            return $this->redirectToRoute('app_trick');
        }

        return $this->render('trick/edit.html.twig', [
            'form' => $form,
            'trick' => $trick
        ]);
    }

    #[Route('/{slug}/delete', name: 'app_trick_delete', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MODERATOR')]
    public function delete(Trick $trick, EntityManagerInterface $manager): Response
    {
        $this->addFlash('success', 'Figure bien supprimée');

        $manager->remove($trick);
        $manager->flush();

        return $this->redirectToRoute('app_trick');
    }
    
}
