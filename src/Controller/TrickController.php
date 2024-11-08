<?php

namespace App\Controller;

use App\Entity\Trick;
use App\Form\TrickType;
use App\Repository\TrickRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

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

    #[Route('/{id}', name: 'app_trick_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Trick $trick): Response
    {
        return $this->render('trick/show.html.twig', [
            'trick' => $trick,
        ]);
    }

    #[Route('/new', name: 'app_trick_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $manager): Response
    {
        $trick = new Trick();
        $form = $this->createForm(TrickType::class, $trick);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager->persist($trick);
            $manager->flush();

            return $this->redirectToRoute('app_trick');
        }

        return $this->render('trick/new.html.twig', [
            'form' => $form,
        ]);
    }

}
