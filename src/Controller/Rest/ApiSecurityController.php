<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Controller\Rest;

use App\Entity\Tokens;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Security\ApiLoginFormAuthenticator;

class ApiSecurityController extends AbstractController
{
    /**
     * @var CsrfTokenManagerInterface
     */
    private $tokenManager;
    private $apiLoginFormAuthenticator;
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager, CsrfTokenManagerInterface $tokenManager = null,ApiLoginFormAuthenticator $apiLoginFormAuthenticator)
    {
        $this->tokenManager = $tokenManager;
        $this->apiLoginFormAuthenticator = $apiLoginFormAuthenticator;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route("/login", name="login")
     * @param AuthenticationUtils $authenticationUtils
     * @param Request $request
     * @return Response
     */
    public function login(AuthenticationUtils $authenticationUtils,Request $request): Response
    {
        if ($this->getUser()) {
            return new JsonResponse(['Success' => false, 'data' => 'You are logged']);
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        /*$csrfToken = $this->tokenManager
            ? $this->tokenManager->getToken('authenticate')->getValue()
            : null;*/

        if($request->isMethod('post')) {
            $credentials = $this->apiLoginFormAuthenticator->getCredentials($request);
            $getUser = $this->apiLoginFormAuthenticator->getUser($credentials);
            if(!is_array($getUser)) {
                $checkCredentials = $this->apiLoginFormAuthenticator->checkCredentials($credentials, $getUser);
                if ($checkCredentials) {
                    $onAuthenticationSuccess = $this->apiLoginFormAuthenticator->onAuthenticationSuccess($request, null, null);
                    $onAuthenticationSuccess['user']['id'] = $getUser->getId();
                    $onAuthenticationSuccess['user']['username'] = $getUser->getUsername();
                    $onAuthenticationSuccess['user']['first_name'] = $getUser->getFirstName();
                    $onAuthenticationSuccess['user']['last_name'] = $getUser->getLastName();
                    $onAuthenticationSuccess['user']['email'] = $getUser->getEmail();
                    $onAuthenticationSuccess['user']['type'] = $getUser->getType();
                    $onAuthenticationSuccess['user']['position'] = $getUser->getPosition();
                    $onAuthenticationSuccess['user']['avatar'] = $getUser->getAvatar();
                    $onAuthenticationSuccess['user']['roles'] = $getUser->getRoles();
                    $onAuthenticationSuccess['token'] = $request->getSession()->get('token');

                    return new JsonResponse(['Success' => true, 'data' => $onAuthenticationSuccess]);
                } else {
                    return new JsonResponse(['Success' => false, 'data' => 'Wrong password']);
                }
            } else {
                return new JsonResponse(['Success' => false, 'data' => $getUser['error']]);
            }

        }

        return new JsonResponse(['Success' => true, 'last_username' => $lastUsername,
            'error' => $error]);
        // 'csrf_token' => $csrfToken
    }


    public function expiryToken($token){
        $tokens = $this->entityManager->getRepository(Tokens::class)->findBy(['token' => $token],['id' => 'DESC'])[0];
        $dateExpied = new \DateTime('now');
        $tokens->setExpired($dateExpied);
        try{
            $this->entityManager->persist($tokens);
            $this->entityManager->flush();
            return true;
        }catch (\Exception $e){
            return false;
        }

    }

    /**
     * @Route("/logout", name="logout")
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $content = $request->request->all();

        if($this->expiryToken($content['token'])){
            return new JsonResponse(['Success' => true, 'data' => 'Logout success']);
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token has been no expiry']);
        }

    }
}
