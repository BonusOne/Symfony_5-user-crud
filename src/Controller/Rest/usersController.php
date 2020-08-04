<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Controller\Rest;

use App\Entity\Users;
use App\Repository\UsersRepository;
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
 * @IsGranted("ROLE_ADMIN")
 */
class usersController extends AbstractController
{
    /** @var UsersRepository */
    private $UsersRepository;

    private $passwordEncoder;
    private $em;

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
    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        /** @var Users $user */
        $user = $this->getUser();

        $usersList = $this->UsersRepository->getAllUsers();

        return new JsonResponse(['Success' => true, 'data' => $usersList]);
    }

    /**
     * @Route("/add", name="add")
     * @param Request $request
     * @param \Swift_Mailer $mailer
     * @param ObjectManager $manager
     * @return Response
     * @throws \Exception
     */
    public function add(Request $request, \Swift_Mailer $mailer, ObjectManager $manager)
    {
        /** @var Users $user */
        $user = $this->getUser();
        $errors = array();

        if ($request->isMethod('post')) {

            $content = $request->getContent();
            $content = json_decode($content, true);

            if ($content['usertype'] = 2){
                $role = 'ROLE_USER';
            } else if ($content['usertype'] = 1){
                $role = 'ROLE_ADMIN';
            } else {
                $role = 'ROLE_USER';
            }

            $password = $this->generatePassword(12);
            $users = new Users();
            $users->setUsername($content['username']);
            $users->setPassword($this->passwordEncoder->encodePassword($users,$password));
            $users->setEmail($content['email']);
            $users->setEmailHash(hash('sha256', $content['email']));
            $users->setFirstName($content['firstname']);
            $users->setLastName($content['lastname']);
            $users->setCreatedAt(new \DateTime('now'));
            $users->setType($content['usertype']);
            $users->setPosition($content['position']);
            $users->setIdAuthor($user->getId());
            $users->setStatus(1);
            $users->setAvatar($content['avatar']);
            $users->setRoles(json_encode([$role]));
            $users->setSalt(hash('sha256', 'SaltSalt@'));

            $this->em->persist($users);
            $this->em->flush();

            try{
                $message = (new \Swift_Message('Hello in Symfony 5-crud!'))
                    ->setFrom('no-reply@pawelliwocha.com')
                    ->setTo($content['email'])
                    ->setBody(
                        $this->renderView(
                            'email/registration.html.twig',
                            array('firstname' => $content['firstname'],
                                'username' => $content['username'],
                                'password' => $password
                            ),
                            'text/html'
                        ))
                    ->addPart(
                        $this->renderView(
                            'email/registration.txt.twig',
                            array('firstname' => $content['firstname'],
                                'username' => $content['username'],
                                'password' => $password
                            ),
                            'text/plain'
                        ));
                $mailer->send($message);
                return new JsonResponse(['Success' => true, 'data' => $errors]);
            }catch (\Exception $e){
                $errors = $e;
                return new JsonResponse(['Success' => false, 'data' => $errors]);
            }
        }
        return new JsonResponse(['Success' => true, 'data' => 'No post']);
    }

    /**
     * @Route("/{id_user}", name="show")
     * @param int $id_user
     * @return Response
     */
    public function show(int $id_user)
    {
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);

        return new JsonResponse(['Success' => true, 'data' => $user]);

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
            return new JsonResponse(['Success' => true, 'data' => 'Disable user']);
        }catch (\Exception $e){
            return new JsonResponse(['Success' => false, 'data' => $e]);
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
            return new JsonResponse(['Success' => true, 'data' => 'Enable user']);
        }catch (\Exception $e){
            return new JsonResponse(['Success' => false, 'data' => $e]);
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
        $user = $this->em->getRepository(Users::class)->findOneBy(['id' => $id_user]);

        if ($request->isMethod('post')) {

            $content = $request->getContent();
            $content = json_decode($content, true);

            $usertype = $content['usertype'];

            if ($usertype == 2){
                $role = 'ROLE_USER';
            } else if ($usertype == 1){
                $role = 'ROLE_ADMIN';
            } else {
                $role = 'ROLE_USER';
            }

            $users = new Users();
            $users->setUsername($content['username']);
            $users->setFirstName($content['firstname']);
            $users->setLastName($content['lastname']);
            $users->setType($usertype);
            $users->setPosition($content['position']);
            $users->setAvatar($content['avatar']);
            $users->setRoles(json_encode([$role]));

            $this->em->persist($users);
            $this->em->flush();

            return new JsonResponse(['Success' => true, 'data' => 'Edit user']);
        }
        return new JsonResponse(['Success' => false, 'data' => 'No edit user']);
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
            $message = (new \Swift_Message('New password Symfony 5 - crud!'))
                ->setFrom('no-reply@pawelliwocha.com')
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
            return new JsonResponse(['Success' => true, 'data' => 'Password change']);
        }catch (\Exception $e){
            return new JsonResponse(['Success' => false, 'data' => $e]);
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