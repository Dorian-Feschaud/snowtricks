<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Form\UserType;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route('/user')]
class UserController extends AbstractController
{
    #[Route('', name: 'app_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/{id}', name: 'app_user_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[IsGranted('ROLE_MEMBER')]
    public function show(User $user): Response
    {
        if ($user !== $this->getUser()) {
            return $this->redirectToRoute('app_default');
        }
        return $this->render('user/show.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, FileUploader $fileUploader, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory, MailerInterface $mailer, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $filename = $fileUploader->uploadImage($imageFile, $imagesDirectory);
                $user->setImage($filename);
            }
            else {
                $user->setImage('user_default_pic.png');
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $user->setRoles(['ROLE_MEMBER']);

            $entityManager->persist($user);
            $entityManager->flush();

            $email = (new TemplatedEmail())
                ->from(new Address('snowtricks@example.com', 'snowtricks'))
                ->to((string) $user->getEmail())
                ->subject('Inscription sur SnowTricks')
                ->htmlTemplate('registration/email.html.twig');
            ;

            $mailer->send($email);

            return $security->login($user, 'form_login', 'main');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }

    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    #[IsGranted('ROLE_MEMBER')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/{id}/edit', name: 'app_user_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MEMBER')]
    public function edit(User $user, FileUploader $fileUploader, #[Autowire('%kernel.project_dir%/public/uploads/images')] string $imagesDirectory, Request $request, EntityManagerInterface $manager): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $path = $this->getParameter("public_directory") . '/uploads/images/' . $user->getImage();
                $fileUploader->removeImage($path);
                $filename = $fileUploader->uploadImage($imageFile, $imagesDirectory);
                $user->setImage($filename);
            }
            else {
                $user->setImage('user_default_pic.png');
            }
            $manager->persist($user);
            $manager->flush();

            return $this->redirectToRoute('app_user_show', ['id' => $user->getId()]);
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form,
            'user' => $user
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_delete', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(User $user, EntityManagerInterface $manager): Response
    {
        $manager->remove($user);
        $manager->flush();

        return $this->redirectToRoute('app_default');
    }
    
}