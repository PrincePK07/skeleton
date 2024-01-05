<?php

namespace App\Controller;

use App\Entity\User;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class UserController extends AbstractController
{
    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }
    #[Route('api/user/report/{id}', name: 'report', methods: ['POST'], defaults: [
        '_api_resource_class' => User::class,
        '_api_item_operation_name' => 'report'
    ])]
    public function reportUser(Request $request , $id , ManagerRegistry $doctrine){

        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }
        $user->setIsActive(false);
        $entityManager->flush();
        return new Response(sprintf('User with ID %d reported successfully', $id));

    }

    

}
