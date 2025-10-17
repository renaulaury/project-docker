<?php

namespace App\Controller;

use App\Form\EditProfileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;

final class UserController extends AbstractController
{
    #[Route('/profile', name: 'profile')]
    public function index(): Response
    {
        return $this->render('profileUser/profile.html.twig');
    }

    #[Route('/editProfile', name: 'editProfile')]
    public function editProfile(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $form = $this->createForm(EditProfileType::class, $user);

        $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'Your e-mail address has been successfully changed..');
        
        return $this->redirectToRoute('profile'); 
    }

        return $this->render('profileUser/editProfile.html.twig', [
        'editEmailForm' => $form->createView(),
        ]);
    }

    #[Route('/deleteProfile', name: 'deleteProfile')]
    public function deleteProfile(): Response
    {
        return $this->render('profileUser/deleteProfile.html.twig');
    }

    #[Route('/yesConfirm', name: 'yesConfirm')]
    public function yesConfirm(Request $request, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        $em->remove($user);
        $em->flush();

        $request->getSession()->invalidate();
    
        $this->container->get('security.token_storage')->setToken(null);
    

        $this->addFlash('warning', 'Your profile has been delete definetely.');

        return $this->redirectToRoute('home');
    }

    #[Route('/noConfirm', name: 'noConfirm')]
    public function noConfirm(): Response
    {
        return $this->render('profileUser/profile.html.twig');
    }
}
