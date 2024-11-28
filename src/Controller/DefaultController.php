<?php

namespace App\Controller;

use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    } 

    #[Route('/', name: 'app_default')]
    public function index(): Response
    {
        $trickRepository = $this->em->getRepository(Trick::class);

        $tricks = $trickRepository->findBy([], null, 3, null);

        return $this->render('index.html.twig', [
            'tricks' => $tricks
        ]);
    }
}
