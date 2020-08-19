<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Controller\Web;

use App\Entity\Users;
use App\Repository\UsersRepository;
use App\Service\FileUploader;
use App\Service\UsersService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

/**
 * Class indexController
 * @Route("/users", name="users_")
 * @package App\Controller\Web
 * @IsGranted("ROLE_ADMIN")
 */
class usersController extends AbstractController
{

    /** @var UsersRepository */
    private $UsersRepository;

    private $passwordEncoder;
    private $em;
    private $protocol;

    /**
     * indexController constructor.
     * @param UsersRepository $UsersRepository
     * @param UserPasswordEncoderInterface $passwordEncoder
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(UsersRepository $UsersRepository, UserPasswordEncoderInterface $passwordEncoder, EntityManagerInterface $entityManager)
    {
        $this->UsersRepository = $UsersRepository;
        $this->passwordEncoder = $passwordEncoder;
        $this->em = $entityManager;
        $this->protocol = $_SERVER['PROTOCOL'] = isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        /** @var Users $user */
        $user = $this->getUser();

        $usersList = $this->UsersRepository->getAllUsers();

        return $this->render('users/index.html.twig',['users' => $usersList]);
    }

    /**
     * @Route("/add", name="add")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @param FileUploader $fileUploader
     * @return Response
     * @throws \Exception
     */
    public function add(Request $request, \Swift_Mailer $mailer, FileUploader $fileUploader)
    {
        /** @var Users $user */
        $user = $this->getUser();
        $errors = array();

        if ($request->isMethod('post')) {

            $usertype = $request->request->get('usertype');

            if($usertype == 2){
                $role = 'ROLE_USER';
            } else if ($usertype == 1){
                $role = 'ROLE_ADMIN';
            } else {
                $role = 'ROLE_USER';
            }

            if ($request->request->get('avatar')->getData()) {
                $avatarFile = $request->files->get('avatar');
                $avatarFileName = $fileUploader->upload($avatarFile,$this->getParameter('avatar_directory'));
            } else {
                $avatarFileName = 'noset.png';
            }

            $password = $this->generatePassword(12);
            $users = new Users();
            $users->setUsername(trim($request->request->get('username')));
            $users->setPassword($this->passwordEncoder->encodePassword($users,$password));
            $users->setEmail($request->request->get('email'));
            $users->setEmailHash(hash('sha256', $request->request->get('email')));
            $users->setFirstName($request->request->get('firstname'));
            $users->setLastName($request->request->get('lastname'));
            $users->setCreatedAt(new \DateTime('now'));
            $users->setType($usertype);
            $users->setPosition($request->request->get('position'));
            $users->setIdAuthor($user->getId());
            $users->setStatus(1);
            $users->setAvatar($this->protocol.'://'.$_SERVER['HTTP_HOST'].'/'.$this->getParameter('avatar_directory_public').$avatarFileName);
            $users->setRoles(json_encode([$role]));
            $users->setSalt(hash('sha256', 'Symfony5'));

            $this->em->persist($users);
            $this->em->flush();

            try{
            $message = (new \Swift_Message('Hello in Symfony 5 - CRUD!'))
                ->setFrom('no-reply@pawelliwocha.pl')
                ->setTo($request->request->get('email'))
                ->setBody(
                    $this->renderView(
                        'email/registration.html.twig',
                        array('firstname' => $request->request->get('firstname'),
                         'username' => $request->request->get('username'),
                         'password' => $password
                    ),
                    'text/html'
                ))
                ->addPart(
                    $this->renderView(
                        'email/registration.txt.twig',
                        array('firstname' => $request->request->get('firstname'),
                         'username' => $request->request->get('username'),
                         'password' => $password
                    ),
                    'text/plain'
                ));
                $mailer->send($message);
                return $this->redirectToRoute('app_users_index');
            }catch (\Exception $e){
                $errors = $e;
            }
        }
        return $this->render('users/add.html.twig',['errors' => $errors]);
    }

    /**
     * @Route("/{id_user}", name="show")
     * @param int $id_user
     * @return Response
     */
    public function show(int $id_user)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);

        return $this->render('users/user.html.twig',['user' => $user]);

    }

    /**
     * @Route("/{id_user}/disable", name="disable")
     * @param int $id_user
     * @return Response
     */
    public function disable(int $id_user)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
        $user->setStatus(0);

        try {
            $this->em->persist($user);
            $this->em->flush();
            return $this->redirectToRoute('app_users_index');
        }catch (\Exception $e){
            return $this->render('users/user.html.twig',['error' => $e,'user' => $user]);
        }

    }

    /**
     * @Route("/{id_user}/enable", name="enable")
     * @param int $id_user
     * @return Response
     */
    public function enable(int $id_user)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
        $user->setStatus(1);

        try {
            $this->em->persist($user);
            $this->em->flush();
            return $this->redirectToRoute('app_users_index');
        }catch (\Exception $e){
            return $this->render('users/user.html.twig',['error' => $e,'user' => $user]);
        }

    }

    /**
     * @Route("/{id_user}/edit", name="edit")
     * @param Request $request
     * @param int $id_user
     * @param FileUploader $fileUploader
     * @return Response
     */
    public function edit(Request $request, int $id_user, FileUploader $fileUploader)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);
        $errors = array();
        if ($request->isMethod('post')) {

            $usertype = $request->request->get('usertype');

            if($usertype == 3){
                $role = 'ROLE_ACCOUNT';
            } else if ($usertype == 2){
                $role = 'ROLE_GRAPHIC';
            } else if ($usertype == 1){
                $role = 'ROLE_ADMIN';
            } else {
                $role = 'ROLE_USER';
            }

            $user->setUsername(trim($request->request->get('username')));
            $user->setFirstName($request->request->get('firstname'));
            $user->setLastName($request->request->get('lastname'));
            $user->setType($usertype);
            $user->setPosition($request->request->get('position'));
            $user->setRoles(json_encode([$role]));


            if($request->request->get('avatar') == 'clear') {
                $avatarFileName = 'noset.png';
                $user->setAvatar($avatarFileName);
            } elseif ($request->files->get('avatar')) {
                $avatarFile = $request->files->get('avatar');
                $avatarFileName = $fileUploader->upload($avatarFile,$this->getParameter('avatar_directory'));
                $user->setAvatar($this->protocol.'://'.$_SERVER['HTTP_HOST'].'/'.$this->getParameter('avatar_directory_public').$avatarFileName);
            }
            try {
                $this->em->persist($user);
                $this->em->flush();
                return $this->redirectToRoute('app_users_index');
            }catch (\Exception $e){
                $errors = $e;
            }
        }
        return $this->render('users/edit.html.twig',['user' => $user, 'errors' => $errors]);
    }

    /**
     * @Route("/{id_user}/change_password", name="change_password")
     * @param int $id_user
     * @param \Swift_Mailer $mailer
     * @return Response
     */
    public function changePassword(int $id_user, \Swift_Mailer $mailer)
    {
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
                        ),
                        'text/html'
                    ))
                ->addPart(
                    $this->renderView(
                        'email/registration.txt.twig',
                        array('firstname' => $user->getFirstName(),
                            'username' => $user->getUsername(),
                            'password' => $password
                        ),
                        'text/plain'
                    ));
            $mailer->send($message);
            return $this->redirectToRoute('app_users_index');
        }catch (\Exception $e){
            return $this->render('users/user.html.twig',['error' => $e,'user' => $user]);
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