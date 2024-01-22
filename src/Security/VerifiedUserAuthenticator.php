<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class VerifiedUserAuthenticator extends AbstractAuthenticator
{
    private $userRepository;
    public function __construct(UserRepository $userRepository){
$this->userRepository = $userRepository;
    }
    public function supports(Request $request): ?bool
    {
        // Check if this authenticator should be used for the current request

        return str_starts_with($request->getPathInfo(), '/login_check');
        // TODO: Implement supports() method.
    }

    public function authenticate(Request $request): Passport
    {

       $requestData = json_decode($request->getContent(), true);
    
       return new SelfValidatingPassport(
        new UserBadge( $requestData['email'],function() use ($requestData){
            $user = $this->userRepository->findOneBy(['email' => $requestData['email']]);
            if(!$user->getIsActive()){
               throw new CustomUserMessageAuthenticationException('User email not verified');
        }
        return $user;
    })

);
        // TODO: Implement authenticate() method.
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return null;
        // TODO: Implement onAuthenticationSuccess() method.
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
       
        $data = [
            'error' => strtr($exception->getMessageKey(), $exception->getMessageData())
        ];
        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
        // TODO: Implement onAuthenticationFailure() method.
    }

//    public function start(Request $request, AuthenticationException $authException = null): Response
//    {
//        /*
//         * If you would like this class to control what happens when an anonymous user accesses a
//         * protected page (e.g. redirect to /login), uncomment this method and make this class
//         * implement Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface.
//         *
//         * For more details, see https://symfony.com/doc/current/security/experimental_authenticators.html#configuring-the-authentication-entry-point
//         */
//    }
}
