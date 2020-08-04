<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Service;

use App\Repository\LoginLogRepository;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;

class SecurityService
{
    /** @var LoggerInterface $logger */
    private $logger;

    /** @var LoginLogRepository */
    private $loginLogRepository;

    private $dbcon;

    public function __construct(LoggerInterface $logger, LoginLogRepository $loginLogRepository)
    {
        $this->logger = $logger;
        $this->loginLogRepository = $loginLogRepository;
        //$this->dbcon = $this->getDoctrine()->getManager()->getConnection();
    }

    public function checkValidDataToken($request): bool
    {
        $content = $request->request->all();
        $loginLog = $this->loginLogRepository->findByToken($content['token']);

        $now = new DateTime();
        $startDate = new DateTime($loginLog['created_at']);
        $endDate = new DateTime();
        $endDate->add(new DateInterval("PT1H"));

        if($startDate <= $now && $now <= $endDate) {
            return true;
        } else {
            return false;
        }
    }
}