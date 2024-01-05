<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    private $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }
    #[Route('/register', name: 'register', methods: ['POST'], defaults: [
        '_api_resource_class' => User::class,
        '_api_item_operation_name' => 'register'
    ])]
    public function register(Request $request, ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();
        $data = json_decode($request->getContent(), true);
        $username = $data['username'];
        $password = $data['password'];


        $user = new User($username);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $user->setEmail($data['email']);
        $user->setPhone($data['phone']);
        $user->setPhone($data['phone']);
        $user->setRoles($data['roles'] ?? []);
        $em->persist($user);
        $em->flush();

        return new Response(sprintf('User %s successfully created', $user->getUsername()));
    }

    public function api()
    {
        return new Response(sprintf('Logged in as %s', $this->getUser()->getUsername()));
    }
}
