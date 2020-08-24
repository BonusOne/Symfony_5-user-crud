<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Controller\Rest;

use App\Entity\Users;
use App\Repository\LoginLogRepository;
use App\Repository\TokensRepository;
use App\Repository\UsersRepository;
use App\Service\FileUploader;
use App\Service\SecurityService;
use App\Service\UsersService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class indexController
 * @Route("/users", name="users_")
 * @package App\Controller\Rest
 */
class usersController extends AbstractController
{
    private $UsersRepository;
    private $passwordEncoder;
    private $loginLogRepository;
    private $tokensRepository;
    private $user;
    private $em;
    private $protocol;

    /**
     * indexController constructor.
     * @param UsersRepository $UsersRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param LoginLogRepository $loginLogRepository
     * @param EntityManagerInterface $entityManager
     * @param TokensRepository $tokensRepository
     */
    public function __construct(UsersRepository $UsersRepository, UserPasswordEncoderInterface $passwordEncoder, LoginLogRepository $loginLogRepository, EntityManagerInterface $entityManager, TokensRepository $tokensRepository)
    {
        $this->UsersRepository = $UsersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->loginLogRepository = $loginLogRepository;
        $this->em = $entityManager;
        $this->tokensRepository = $tokensRepository;
        $this->protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    private function checkToken(Request $request){
        $content = $request->request->all();
        $securityService = new SecurityService($this->loginLogRepository,$this->em);
        if(!$securityService->checkValidDataToken($request)){
            return false;
        } else {
            $loginLog = $this->tokensRepository->findByToken($content['token'])[0];
            $this->user = $this->UsersRepository->find($loginLog['user_id']);
            return true;
        }
    }

    /**
     * @Route("/", name="index")
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $usersList = $this->UsersRepository->getAllUsers();

                return new JsonResponse(['Success' => true, 'data' => $usersList]);
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    /**
     * @Route("/add", name="add")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @param ObjectManager $manager
     * @param FileUploader $fileUploader
     * @return Response
     * @throws \Exception
     */
    public function add(Request $request, \Swift_Mailer $mailer, ObjectManager $manager, FileUploader $fileUploader)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $errors = array();

                if ($request->isMethod('post')) {
                    $content = $request->request->all();
                    /*$content = $request->getContent();
                    $content = json_decode($content, true);*/

                    if($content['usertype'] == 3){
                        $role = 'ROLE_ACCOUNT';
                    } else if ($content['usertype'] == 2){
                        $role = 'ROLE_GRAPHIC';
                    } else if ($content['usertype'] == 1){
                        $role = 'ROLE_ADMIN';
                    } else {
                        $role = 'ROLE_USER';
                    }

                    if($content['avatar'] != '' or $content['avatar'] != null){
                        $avatarFile = $content['avatar'];
                        if (preg_match('/^data:image\/(\w+);base64,/', $avatarFile, $type)) {
                            $avatarFile = substr($avatarFile, strpos($avatarFile, ',') + 1);
                            $type = strtolower($type[1]); // jpg, png, gif

                            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                                return new JsonResponse(['Success' => false, 'data' => 'Invalid image type']);
                            }

                            $avatarFile = base64_decode($avatarFile);

                            if ($avatarFile === false) {
                                return new JsonResponse(['Success' => false, 'data' => 'base64_decode failed']);
                            }
                        } else {
                            return new JsonResponse(['Success' => false, 'data' => 'Did not match data URI with image data']);
                        }
                        $avatarFileName = $this->getParameter('avatar_directory').uniqid().'.'.$type;
                        file_put_contents($avatarFileName, $avatarFile);
                    } else {
                        $avatarFileName = 'noset.png';
                    }

                    $password = $this->generatePassword(12);
                    $users = new Users();
                    $users->setUsername(trim($content['username']));
                    $users->setPassword($this->passwordEncoder->encodePassword($users,$password));
                    $users->setEmail($content['email']);
                    $users->setEmailHash(hash('sha256', $content['email']));
                    $users->setFirstName($content['firstname']);
                    $users->setLastName($content['lastname']);
                    $users->setCreatedAt(new \DateTime('now'));
                    $users->setType($content['usertype']);
                    $users->setPosition($content['position']);
                    $users->setIdAuthor($this->user->getId());
                    $users->setStatus(1);
                    $users->setAvatar($this->protocol.'://'.$_SERVER['HTTP_HOST'].'/'.$this->getParameter('avatar_directory_public').$avatarFileName);
                    $users->setRoles(json_encode([$role]));
                    $users->setSalt(hash('sha256', 'Symfony5'));

                    $this->em->persist($users);
                    $this->em->flush();

                    try{
                        $message = (new \Swift_Message('Hello in Symfony 5 - CRUD!'))
                            ->setFrom('no-reply@pawelliwocha.pl')
                            ->setTo($content['email'])
                            ->setBody(
                                $this->renderView(
                                    'email/registration.html.twig',
                                    array('firstname' => $content['firstname'],
                                        'username' => $content['username'],
                                        'password' => $password
                                    )),'text/html');
                        $mailer->send($message);
                        return new JsonResponse(['Success' => true, 'data' => $errors]);
                    }catch (\Exception $e){
                        $errors = $e;
                        return new JsonResponse(['Success' => false, 'data' => $errors]);
                    }
                }
                return new JsonResponse(['Success' => true, 'data' => 'No post']);
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    /**
     * @Route("/{id_user}", name="show")
     * @param Request $request
     * @param int $id_user
     * @return Response
     */
    public function show(Request $request, int $id_user)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);

                return new JsonResponse(['Success' => true, 'data' => $user]);
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    /**
     * @Route("/{id_user}/disable", name="disable")
     * @param Request $request
     * @param int $id_user
     * @return Response
     */
    public function disable(Request $request, int $id_user)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
                $user->setStatus(0);

                try {
                    $this->em->persist($user);
                    $this->em->flush();
                    return new JsonResponse(['Success' => true, 'data' => 'Disable user']);
                }catch (\Exception $e){
                    return new JsonResponse(['Success' => false, 'data' => $e]);
                }
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }

    }

    /**
     * @Route("/{id_user}/enable", name="enable")
     * @param Request $request
     * @param int $id_user
     * @return Response
     */
    public function enable(Request $request, int $id_user)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
                $user->setStatus(1);

                try {
                    $this->em->persist($user);
                    $this->em->flush();
                    return new JsonResponse(['Success' => true, 'data' => 'Enable user']);
                }catch (\Exception $e){
                    return new JsonResponse(['Success' => false, 'data' => $e]);
                }
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    /**
     * @Route("/{id_user}/edit", name="edit")
     * @param Request $request
     * @param int $id_user
     * @return Response
     * @throws \Exception
     */
    public function edit(Request $request, int $id_user)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);

                if ($request->isMethod('post')) {
                    $content = $request->request->all();
                    /*$content = $request->getContent();
                    $content = json_decode($content, true);*/

                    $usertype = $content['usertype'];

                    if($usertype == 3){
                        $role = 'ROLE_ACCOUNT';
                    } else if ($usertype == 2){
                        $role = 'ROLE_GRAPHIC';
                    } else if ($usertype == 1){
                        $role = 'ROLE_ADMIN';
                    } else {
                        $role = 'ROLE_USER';
                    }

                    $user->setUsername(trim($content['username']));
                    $user->setFirstName($content['firstname']);
                    $user->setLastName($content['lastname']);
                    $user->setType($usertype);
                    $user->setPosition($content['position']);
                    $user->setRoles(json_encode([$role]));

                    if ($content['avatar'] == 'clear') {
                        $avatarFileName = 'noset.png';
                        $user->setAvatar($avatarFileName);
                    } elseif($content['avatar'] != '' or $content['avatar'] != null){
                        $avatarFile = $content['avatar'];
                        if (preg_match('/^data:image\/(\w+);base64,/', $avatarFile, $type)) {
                            $avatarFile = substr($avatarFile, strpos($avatarFile, ',') + 1);
                            $type = strtolower($type[1]); // jpg, png, gif

                            if (!in_array($type, [ 'jpg', 'jpeg', 'gif', 'png' ])) {
                                return new JsonResponse(['Success' => false, 'data' => 'Invalid image type']);
                            }

                            $avatarFile = base64_decode($avatarFile);

                            if ($avatarFile === false) {
                                return new JsonResponse(['Success' => false, 'data' => 'base64_decode failed']);
                            }
                        } else {
                            return new JsonResponse(['Success' => false, 'data' => 'Did not match data URI with image data']);
                        }
                        $avatarFileName = $this->getParameter('avatar_directory').uniqid().'.'.$type;
                        file_put_contents($avatarFileName, $avatarFile);
                        $user->setAvatar($this->protocol.'://'.$_SERVER['HTTP_HOST'].'/'.$this->getParameter('avatar_directory_public').$avatarFileName);
                    }

                    $this->em->persist($user);
                    $this->em->flush();

                    return new JsonResponse(['Success' => true, 'data' => 'Edit user']);
                }
                return new JsonResponse(['Success' => false, 'data' => 'No edit user']);
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    /**
     * @Route("/{id_user}/change_password", name="change_password")
     * @param Request $request
     * @param int $id_user
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function changePassword(Request $request, int $id_user, \Swift_Mailer $mailer)
    {
        if($this->checkToken($request)) {
            if($this->user->getType() == 1 or $this->user->getType() == 0){
                $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
                $password = $this->generatePassword(12);
                $user->setPassword($this->passwordEncoder->encodePassword($user,$password));

                try{
                    $this->em->persist($user);
                    $this->em->flush();
                    $message = (new \Swift_Message('New password Symfony 5 - CRUD!'))
                        ->setFrom('no-reply@pawelliwocha.pl')
                        ->setTo($user->getEmail())
                        ->setBody(
                            $this->renderView(
                                'email/registration.html.twig',
                                array('firstname' => $user->getFirstName(),
                                    'username' => $user->getUsername(),
                                    'password' => $password
                                )),'text/html');
                    $mailer->send($message);
                    return new JsonResponse(['Success' => true, 'data' => 'Password change']);
                }catch (\Exception $e){
                    return new JsonResponse(['Success' => false, 'data' => $e]);
                }
            } else {
                return new JsonResponse(['Success' => false, 'data' => "You don't have permission to this content"]);
            }
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }

    private function generatePassword($length = 10) {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?';
        $count = mb_strlen($chars);

        for ($i = 0, $result = ''; $i < $length; $i++) {
            $index = rand(0, $count - 1);
            $result .= mb_substr($chars, $index, 1);
        }

        return $result;
    }
}