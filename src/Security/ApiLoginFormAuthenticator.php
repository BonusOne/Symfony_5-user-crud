<?php

namespace App\Security;

use App\Entity\LoginLog;
use App\Entity\Tokens;
use App\Entity\Users;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\Exception\InvalidCsrfTokenException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\Authenticator\AbstractFormLoginAuthenticator;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class ApiLoginFormAuthenticator extends AbstractFormLoginAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'api_login';
    public const LOGOUT_ROUTE = 'api_logout';

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $session;

    public function __construct(EntityManagerInterface $entityManager, UrlGeneratorInterface $urlGenerator, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, SessionInterface $session)
    {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->session = $session;
    }

    public function supports(Request $request)
    {
        return self::LOGIN_ROUTE === $request->attributes->get('_route')
            && $request->isMethod('POST');
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => trim($request->request->get('username')),
            'password' => trim($request->request->get('password')),
            //'csrf_token' => $request->request->get('_csrf_token'),
        ];
        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider = null)
    {
        /*$token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
            //throw new InvalidCsrfTokenException();
            return ['error' => 'InvalidCsrfTokenException'];
        }*/

        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            // fail authentication with a custom error
            //throw new CustomUserMessageAuthenticationException('Username could not be found.');
            return ['error' => 'Username could not be found'];
        }
        if ($user->getStatus() != 1) {
            // account is deleted
            //throw new CustomUserMessageAuthenticationException('Account not exist.');
            return ['error' => 'Account not exist'];
        }
        // TODO: add checking and created token
        $token = $this->generateToken($user->getUsername());
        $this->session->set('userid', $user->getId());
        $this->session->set('usertype', $user->getType());
        //$this->session->set('csrf_token', $credentials['csrf_token']);
        $this->session->set('token', $token);
        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token = null, $providerKey = null)
    {
        $dateNow = new \DateTime('now');
        $dateExpied = new \DateTime('now');
        $dateExpied = $dateExpied->modify('+30 minutes');
        $tokenLog = new Tokens();
        $tokenLog->setToken($request->getSession()->get('token'));
        $tokenLog->setUserId($request->getSession()->get('userid'));
        $tokenLog->setCreatedAt($dateNow);
        $tokenLog->setExpired($dateExpied);
        $this->entityManager->persist($tokenLog);
        $this->entityManager->flush();

        $loginLog = new LoginLog();
        $loginLog->setIdUser($request->getSession()->get('userid'));
        $loginLog->setToken($request->getSession()->get('_csrf/authenticate'));
        $loginLog->setCreatedAt(new \DateTime('now'));
        $loginLog->setUserAgent($request->headers->get('User-Agent'));
        $loginLog->setIp(isset($_SERVER['HTTP_CF_CONNECTING_IP'])
            ? $_SERVER['HTTP_CF_CONNECTING_IP']
            : (isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR']
                : 'undefined'));
        $origin = '';
        if (isset($_SERVER["HTTP_REFERER"])) {
            $origin = $_SERVER["HTTP_REFERER"];
        } else if (isset($_SESSION["origURL"])) {
            $origin = $_SESSION["origURL"];
        }
        $loginLog->setOrigin($origin);
        $loginLog->setStatus(1);
        $this->entityManager->persist($loginLog);
        $this->entityManager->flush();

        $user = $this->entityManager->getRepository(Users::class)->findOneBy(['id' => $request->getSession()->get('userid')]);
        $user->setLastLogin(new \DateTime('now'));
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        /*if ($targetPath = $this->getTargetPath($request->getSession(), $providerKey)) {
            return ['Success' => true, 'targetPath' => $targetPath];
        }*/

        return ['Success' => true, 'targetPath' => $this->urlGenerator->generate('api_dashboard')];
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    protected function generateToken($username){
        $date = new \DateTime('now');
        $token = uniqid().$date->getTimestamp().md5($username);
        $token = hash('sha256', $token);
        return $token;
    }
}
