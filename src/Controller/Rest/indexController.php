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
use App\Repository\UsersRepository;
use App\Service\UsersService;
use App\Service\SecurityService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class indexController
 * @package App\Controller\Rest
 */
class indexController extends AbstractController
{
    /** @var LoginLogRepository */
    private $loginLogRepository;

    /** @var UsersRepository */
    private $usersRepository;

    private $content;
    private $userId;

    /**
     * indexController constructor.
     * @param LoginLogRepository $loginLogRepository
     */
    public function __construct(LoginLogRepository $loginLogRepository, UsersRepository $usersRepository)
    {
        $this->loginLogRepository = $loginLogRepository;
        $this->usersRepository = $usersRepository;
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        $this->content = $request->request->all();
        if(!(new SecurityService)->checkValidDataToken($this->content)){
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
        $loginLog = $this->loginLogRepository->findByToken($this->content['token']);
        $users = $this->usersRepository->find($loginLog['id_user']);
        file_put_contents('API__users_dash.txt', var_export($users, true));

        /** @var Users $user */
        $user = $this->getUser();


        return new JsonResponse(['Success' => true, 'user' => $user]);
    }
}