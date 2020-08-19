<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Controller\Web;

use App\Entity\Users;
use App\Service\UsersService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * Class indexController
 * @package App\Controller\Web
 * @IsGranted("ROLE_USER")
 */
class indexController extends AbstractController
{

    /**
     * indexController constructor.
     */
    public function __construct()
    {

    }

    /**
     * @Route("/", name="index")
     */
    public function index()
    {
        /** @var Users $user */
        $user = $this->getUser();

        $dashboardData = [
            'assignee' => [], //account
            'publisher' => [], //graphic
            'admin' => []
        ];


        return $this->render('base.html.twig',['data' => $dashboardData, 'user' => $user]);
    }
}