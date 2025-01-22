<?php

namespace App\Controller;

use App\Entity\Trick;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ErrorController extends AbstractController
{
    protected $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    } 

    #[Route('/error', name: 'app_error')]
    public function show(): Response
    {
        return $this->render('error.html.twig', [
        ]);
    }
}
