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
use App\Service\UsersService;
use App\Service\SecurityService;
use Doctrine\ORM\EntityManagerInterface;
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
    private $loginLogRepository;
    private $usersRepository;
    private $tokensRepository;
    private $user;
    private $em;

    /**
     * indexController constructor.
     * @param LoginLogRepository $loginLogRepository
     * @param UsersRepository $usersRepository
     * @param EntityManagerInterface $entityManager
     * @param TokensRepository $tokensRepository
     */
    public function __construct(LoginLogRepository $loginLogRepository, UsersRepository $usersRepository, EntityManagerInterface $entityManager, TokensRepository $tokensRepository)
    {
        $this->loginLogRepository = $loginLogRepository;
        $this->usersRepository = $usersRepository;
        $this->em = $entityManager;
        $this->tokensRepository = $tokensRepository;
    }

    private function checkToken(Request $request){
        $content = $request->request->all();
        $securityService = new SecurityService($this->loginLogRepository,$this->em);
        if(!$securityService->checkValidDataToken($request)){
            return false;
        } else {
            $loginLog = $this->tokensRepository->findByToken($content['token'])[0];
            $this->user = $this->usersRepository->find($loginLog['user_id']);
            return true;
        }
    }

    /**
     * @Route("/dashboard", name="dashboard")
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request)
    {
        if($this->checkToken($request)) {

            $dashboardData = [
                'assignee' => [], //account
                'publisher' => [], //graphic
                'admin' => []
            ];


            return new JsonResponse(['Success' => true, 'data' => $dashboardData]);
        } else {
            return new JsonResponse(['Success' => false, 'data' => 'Token expired']);
        }
    }
}