<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\AccessToken;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class EmailService
{
 
    public $mailer;
    private $twig;
    private $tokenManageService;

    public function __construct(MailerInterface $mailer , \Twig\Environment $twig , 
    TokenManageService $tokenManageService)
    {
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->tokenManageService = $tokenManageService;
    }

    public function sendRegistrationToken(User $user, AccessToken $accessToken)
    {
        // Sthis->encriptData->enncryptUrlBuilder($acessToken , )
        $token =  $accessToken->getToken();
        $token = $this->tokenManageService->encriptToken($token );

        $email = (new Email())
            ->from('your_email@example.com')
            ->to($user->getEmail())
            ->subject('Your Registration Token')
            ->html($this->twig->render('mailer/registration_email.html.twig', [
                'accessToken' =>  $token,
            ]));

        $this->mailer->send($email);
    }
}
