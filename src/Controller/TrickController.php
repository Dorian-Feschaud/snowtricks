<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Media;
use App\Entity\Trick;
use App\Entity\User;
use App\Form\CommentType;
use App\Form\TrickType;
use App\Repository\TrickRepository;
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

#[Route('/trick')]
class TrickController extends AbstractController
{
    #[Route('', name: 'app_trick')]
    public function index(TrickRepository $trick_repository): Response
    {
        $tricks = $trick_repository->findAll();

        return $this->render('trick/index.html.twig', [
            'tricks' => $tricks,
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
    public function new(Request $request, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/medias')] string $mediasDirectory, #[Autowire('%kernel.project_dir%/public/uploads/thumbnails')] string $thumbnailsDirectory, EntityManagerInterface $manager): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Ajout du trick bien efféctué');

            /** @var UploadedFile $thumbnail */
            $thumbnail = $form->get('thumbnail')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($thumbnail) {
                $originalFilename = pathinfo($thumbnail->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $thumbnail->guessExtension();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                // Move the file to the directory where brochures are stored
                try {
                    $thumbnail->move($mediasDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents

                $filesystem = new Filesystem();
                try {
                    $path = $this->getParameter("public_directory") . '/uploads/thumbnails/' . $thumbnail->getImage();
                    $filesystem->remove($path);
                }
                catch (Exception $e) {
                    throw new Exception();
                }
                $trick->setThumbnail($newFilename);
            }
            else {
                $trick->setThumbnail('default_trick_thumbnail.jpg');
            }

            /** @var Collection<int, UploadedFile> $mediaFiles */
            $mediaFiles = $form->get('medias')->getData();

            if ($mediaFiles) {
                foreach($mediaFiles as $mediaFile) {
                    $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $extension = $mediaFile->guessExtension();
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                    try {
                        $mediaFile->move($thumbnailsDirectory, $newFilename);
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }
                    $media = new Media();
                    $media->setOriginalFilename($originalFilename.$extension);
                    $media->setFilename($newFilename);
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
    public function edit(Trick $trick, SluggerInterface $slugger, #[Autowire('%kernel.project_dir%/public/uploads/medias')] string $mediasDirectory, #[Autowire('%kernel.project_dir%/public/uploads/thumbnails')] string $thumbnailsDirectory, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Modification du trick bien efféctué');

            /** @var UploadedFile $thumbnail */
            $thumbnail = $form->get('thumbnail')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($thumbnail) {
                $originalFilename = pathinfo($thumbnail->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $extension = $thumbnail->guessExtension();
                $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                // Move the file to the directory where brochures are stored
                try {
                    $thumbnail->move($thumbnailsDirectory, $newFilename);
                } catch (FileException $e) {
                    // ... handle exception if something happens during file upload
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents

                $filesystem = new Filesystem();
                try {
                    $path = $this->getParameter("public_directory") . '/uploads/thumbnails/' . $thumbnail->getImage();
                    $filesystem->remove($path);
                }
                catch (Exception $e) {
                    throw new Exception();
                }
                $trick->setThumbnail($newFilename);
            }
            else {
                $trick->setThumbnail('default_trick_thumbnail.jpg');
            }

            /** @var Collection<int, UploadedFile> $mediaFiles */
            $mediaFiles = $form->get('medias')->getData();

            if ($mediaFiles) {
                foreach($mediaFiles as $mediaFile) {
                    $originalFilename = pathinfo($mediaFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename);
                    $extension = $mediaFile->guessExtension();
                    $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

                    try {
                        $mediaFile->move($mediasDirectory, $newFilename);
                    } catch (FileException $e) {
                        // ... handle exception if something happens during file upload
                    }

                    $media = $manager->getRepository(Media::class)->findOneBy(array('original_filename' => $originalFilename, 'trick' => $trick));
                    if (!$media) {
                        $media = new Media();                        
                        $media->setOriginalFilename($originalFilename.$extension);
                    }
                    else {
                        $filesystem = new Filesystem();
                        try {
                            $path = $this->getParameter("public_directory") . '/uploads/medias/' . $media->getFilename();
                            $filesystem->remove($path);
                        }
                        catch (Exception $e) {
                            throw new Exception();
                        }
                    }
                    $media->setFilename($newFilename);
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

            return $this->redirectToRoute('app_trick', ['id' => $trick->getId()]);
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
        $manager->remove($trick);
        $manager->flush();

        return $this->redirectToRoute('app_default');
    }
    
}
