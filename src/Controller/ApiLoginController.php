<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[Route('/api', name: 'api_login')]
class ApiLoginController extends AbstractController
{
    private $jwtManager;

    public function __construct(JWTTokenManagerInterface $jwtManager)
    {
        $this->jwtManager = $jwtManager;
    }

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function index(
        Request $request,
        EntityManagerInterface $em,
    ): Response {
        $userEmail = $request->request->get('email');
        $userPassword = $request->request->get('password');

        if (empty($userEmail) || empty($userPassword)) {
            return new JsonResponse(['msg' => 'Missing parameters'], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            return new JsonResponse(['msg' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        // check password
        if (!password_verify($userPassword, $user->getPassword())) {
            return new JsonResponse(['msg' => 'Bad credentials'], Response::HTTP_BAD_REQUEST);
        }

        $token = $this->jwtManager->create($user);
        $trimmedToken = substr($token, 0, 255);
        $user->setToken($trimmedToken);

        $user->setToken($trimmedToken);
        $em->flush();

        return $this->json([
            'user'  => $user->getUserIdentifier(),
            'token' => $trimmedToken,
        ]);
    }
}
