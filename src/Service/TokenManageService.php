<?php

namespace App\Service;

use App\Entity\AccessToken;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class TokenManageService extends AbstractController
{
    private $translator;
    public $entityManager;
    public function __construct(EntityManagerInterface $entityManager, TranslatorInterface $translator)
    {
        $this->entityManager = $entityManager;
        $this->translator = $translator;
    }

    public function verifyToken(string $token)
    {

        $decrptToken = $this->decryptToken($token);
        $accessToken = $this->entityManager->getRepository(AccessToken::class)->findOneBy(['token' => $decrptToken]);

        if (!$accessToken) {
            return $this->redirectToRoute('activation_error');
        }
        $expires_at = $accessToken->getExpiresAt();

        if ($expires_at < new \DateTime()) {
            $error = $this->translator->trans('translate.response_error.token_expiry');
            return new Response($error);
        }
        $user = $accessToken->getUser();
        $user->setIsActive(true);
        $this->entityManager->remove($accessToken);
        $this->entityManager->flush();
    }

    public function encriptToken(string $token)
    {
        //  encryption method
        $method = "AES-256-CBC";
        // Define the secret key
        $key = "secret";
        // Generate a random initialization vector (IV)
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
        // encript token
        $encrypted = openssl_encrypt($token, $method, $key, 0, $iv);
        $encrypted = base64_encode($iv . $encrypted);
        return $encrypted;
    }

    public function decryptToken(string $token)
    {
        $method = "AES-256-CBC";
        $key = "secret";
        //decode
        $encrypted = base64_decode($token);
        $iv = substr($encrypted, 0, openssl_cipher_iv_length($method));
        $encrypted = substr($encrypted, openssl_cipher_iv_length($method));
        $decrypted = openssl_decrypt($encrypted, $method, $key, 0, $iv);

        return $decrypted;
    }
    public function enncryptUrlBuilder()
    {
        $key = $this->container->getParameter('app_secret');
        dd($key);
    }
}
