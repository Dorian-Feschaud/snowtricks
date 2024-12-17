<?php

namespace App\Controller;

use App\Entity\Group;
use App\Form\GroupType;
use App\Repository\GroupRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/group')]
#[IsGranted('ROLE_ADMIN')]
class GroupController extends AbstractController
{
    
    #[Route('', name: 'app_group')]
    public function index(GroupRepository $group_repository): Response
    {
        $groups = $group_repository->findAll();

        return $this->render('group/index.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/show/{slug}', name: 'app_group_show', methods: ['GET', 'POST'])]
    public function show(Group $group, Request $request, EntityManagerInterface $manager): Response
    {
        return $this->render('group/show.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route('/new', name: 'app_group_new', methods: ['GET', 'POST'])]
    public function new(Request $request, SluggerInterface $slugger, EntityManagerInterface $manager): Response
    {
        $group = new Group();
        $form = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Ajout du groupe bien efféctué');

            $group->setSlug($slugger->slug($group->getName()));
            $manager->persist($group);
            $manager->flush();

            return $this->redirectToRoute('app_group');
        }

        return $this->render('group/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{slug}/edit', name: 'app_group_edit', methods: ['GET', 'POST'])]
    public function edit(Group $group, SluggerInterface $slugger, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(GroupType::class, $group);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $this->addFlash('success', 'Modification du groupe bien efféctuée');

            $group->setSlug($slugger->slug($group->getName()));
            $manager->persist($group);
            $manager->flush();

            return $this->redirectToRoute('app_group');
        }

        return $this->render('group/edit.html.twig', [
            'form' => $form,
            'group' => $group
        ]);
    }

    #[Route('/{slug}/delete', name: 'app_group_delete', methods: ['GET', 'POST'])]
    public function delete(Group $group, EntityManagerInterface $manager): Response
    {
        if (count($group->getTricks()) > 0) {
            $this->addFlash('error', 'Certaines figures appartiennent à ce groupe');
            return $this->redirectToRoute('app_group');
        }

        $this->addFlash('success', 'Groupe bien supprimé');

        $manager->remove($group);
        $manager->flush();

        return $this->redirectToRoute('app_group');
    }
}
