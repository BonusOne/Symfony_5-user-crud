<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\Service;

use App\Entity\Tokens;
use App\Repository\LoginLogRepository;
use DateInterval;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class SecurityService
 * @package App\Service
 */
class SecurityService extends AbstractController
{
    /** @var LoggerInterface */
    private $logger;

    /** @var LoginLogRepository */
    private $loginLogRepository;

    private $em;

    /**
     * SecurityService constructor.
     * @param LoginLogRepository $loginLogRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(LoginLogRepository $loginLogRepository,EntityManagerInterface $entityManager)
    {
        //$this->logger = $logger;
        $this->loginLogRepository = $loginLogRepository;
        $this->em = $entityManager;
    }

    public function checkValidDataToken(Request $request): bool
    {
        $content = $request->request->all();
        if(!array_key_exists("token",$content)) {
            return false;
        } else {
            $tokens = $this->em->getRepository(Tokens::class)->findBy(['token' => $content['token']],['id' => 'DESC'])[0];
            $now = new DateTime('now');
            $startDate = new DateTime($tokens->getCreatedAt()->format('Y-m-d H:i:s'));
            $endDate = new DateTime($tokens->getExpired()->format('Y-m-d H:i:s'));

            if ($startDate <= $now && $now <= $endDate) {
                $dateExpied = new \DateTime('now');
                $dateExpied = $dateExpied->modify('+30 minutes');
                $tokens->setExpired($dateExpied);
                $this->em->persist($tokens);
                $this->em->flush();
                return true;
            } else {
                return false;
            }
        }
    }
}