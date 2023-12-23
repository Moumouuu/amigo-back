<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;

#[Route('/api', name: 'api_register')]
class RegistrationController extends AbstractController
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    #[Route('/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $userRepository = $entityManager->getRepository(User::class);
        $emailUser = $request->request->get('email');
        $passwordUser = $request->request->get('password');

        // check if email and password are sent
        if (empty($emailUser) || empty($passwordUser)) {
            return new JsonResponse(['msg' => 'Missing parameters'], Response::HTTP_BAD_REQUEST);
        }

        $user = $userRepository->findOneBy(['email' => $emailUser]);
        // check if user already exists
        if ($user) {
            return new JsonResponse(['msg' => 'User already exists']);
        }

        $user = new User();

        $hashedPassword = password_hash($passwordUser, PASSWORD_DEFAULT);

        $user->setPassword($hashedPassword);
        $user->setEmail($emailUser);

        $token = $this->jwtManager->create($user);
        $trimmedToken = substr($token, 0, 255);
        $user->setToken($trimmedToken);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['msg' => 'User created']);
    }
}
