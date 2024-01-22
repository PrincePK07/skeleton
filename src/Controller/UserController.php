<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\AccessToken;
use App\Service\EmailService;
use App\Service\TokenManageService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class UserController extends AbstractController
{
    public $entityManager;

    public $translator;

    private $tokenManager;
    private  $emailService;

    public function __construct(EntityManagerInterface $entityManager,  TokenManageService $tokenManager ,
    TranslatorInterface $translator ,  EmailService $emailService,)
    {
        $this->entityManager = $entityManager;
        $this->tokenManager = $tokenManager;
        $this->translator = $translator;
        $this->emailService = $emailService;
    }


    #[Route('/user', name: 'app_user')]
    public function index(): Response
    {
        return $this->render('user/index.html.twig', [
            'controller_name' => 'UserController',
        ]);
    }

    #[Route('/verify-active-user/{token}', name: 'verify_active_user')]
    public function verifyActiveUser(string $token): Response
    {
        $this->tokenManager->verifyToken($token);
        return $this->redirectToRoute('activation_success');
    }

    #[Route('/register/resend_mail', name: 'resend_registration_mail', methods: ['POST'])]
    public function resendRegistrationMail(Request $request)
    {
        $email = json_decode($request->getContent(), true)['email'];
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        
        if (!$user) {
            return new Response($this->translator->trans('translate.response_error.user_not_found'),
             Response::HTTP_NOT_FOUND);
        }
        $accessToken = $this->entityManager->getRepository(AccessToken::class)
            ->findOneBy(['user' => $user, 'token_type' => 'registration']);

        if ($accessToken) {
            $accessToken->updateRegistrationToken();
            $this->entityManager->flush();
        }
        $this->emailService->sendRegistrationToken($user, $accessToken);
        return new Response($this->translator->trans('translate.response_success.resend_email'));
    }

   

    #[Route('activation/success', name: 'activation_success')]
    public function activationSucesss(): Response
    {
        return $this->render('mailer/success_verification.html.twig');
    }

    #[Route('activation/error', name: 'activation_error')]
    public function activationError(): Response
    {
        return $this->render('mailer/error_verification.html.twig');
    }
}
