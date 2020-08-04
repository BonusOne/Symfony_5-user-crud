<?php
/**
 * Symfony 5 - User CRUD
 *
 * @author Paweł Liwocha PAWELDESIGN <pawel.liwocha@gmail.com>
 * @copyright Copyright (c) 2020  Paweł Liwocha PAWELDESIGN (https://paweldesign.com)
 */

namespace App\DataFixtures;

use App\Entity\Users;
use App\Service\UsersService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UsersFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $user = new Users();
        $user->setUsername('test');
        $user->setPassword($this->passwordEncoder->encodePassword($user,'qwerty'));
        $user->setEmail('testowy@pawelliwocha.com');
        $user->setEmailHash(hash('sha256', 'testowy@pawelliwocha.com'));
        $user->setFirstName('Test');
        $user->setLastName('Testowy');
        $user->setCreatedAt(new \DateTime('now'));
        $user->setType(UsersService::TYPE_SUPER_ADMIN);
        $user->setPosition('Super Admin');
        $user->setIdAuthor(1);
        $user->setStatus(1);
        $user->setAvatar('Avatar');
        $user->setRoles(json_encode(['ROLE_SUPER_ADMIN']));
        $user->setSalt(hash('sha256', 'SaltSalt@'));

        $manager->persist($user);
        $manager->flush();
    }
}
