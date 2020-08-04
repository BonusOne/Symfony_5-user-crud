<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Service;

use App\Repository\LoginLogRepository;
use Psr\Log\LoggerInterface;

class UsersService
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var LoginLogRepository */
    private $loginLogRepository;

    private $dbcon;

    const TYPE_SUPER_ADMIN = 0;
    const TYPE_ADMIN = 1;
    const TYPE_USER = 2;

    public function __construct(LoggerInterface $logger, LoginLogRepository $loginLogRepository)
    {
        $this->logger = $logger;
        $this->loginLogRepository = $loginLogRepository;
        //$this->dbcon = $this->getDoctrine()->getManager()->getConnection();
    }

}