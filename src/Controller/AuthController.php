<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\AccessToken;
use App\Service\EmailService;
use ApiPlatform\Annotation\ApiParam;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    private $doctrine;
    private $passwordHasher;
    private $emailService;

    private $translator;

    public function __construct(
        UserPasswordHasherInterface $passwordHasher,
        EmailService $emailService,
        TranslatorInterface $translator,
        EntityManagerInterface $doctrine
    ) {
        $this->passwordHasher = $passwordHasher;
        $this->emailService = $emailService;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
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

        $this->doctrine->getConnection()->beginTransaction();

        try {
            $user = new User($username);
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            $user->setEmail($data['email']);
            $user->setPhone($data['phone']);
            $user->setPhone($data['phone']);
            $user->setRoles($data['roles'] ?? []);
            // Generate and associate access token with the user
            $accessToken = new AccessToken($user);
            $accessToken->setTokenType('registration');
            $em->persist($accessToken);

            // Persist the user and access token
            $em->persist($user);
            $em->flush();

            $this->emailService->sendRegistrationToken($user, $accessToken);
            $this->doctrine->getConnection()->commit();
            $message = $this->translator->trans('translate.response_success.register');
            return new Response($message);
        } catch (\Exception $e) {
            $this->doctrine->getConnection()->rollBack();
            $error = $this->translator->trans('translate.response_error.register');
            return new Response($error, 500);
        }
        // Send email with the generated token
    }

    public function api()
    {
        return new Response(sprintf('Logged in as %s', $this->getUser()->getUsername()));
    }
}
