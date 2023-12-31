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
        $parameters = json_decode($request->getContent(), true);
        $userEmail = $parameters['email'];
        $userPassword = $parameters['password'];

        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => "Le format de l'email n'est pas valide !"], Response::HTTP_BAD_REQUEST);
        }

        if (empty($userEmail) || empty($userPassword)) {
            return new JsonResponse(['error' => 'Il manque des champs obligatoires !'], Response::HTTP_BAD_REQUEST);
        }

        $user = $em->getRepository(User::class)->findOneBy(['email' => $userEmail]);

        if (!$user) {
            return new JsonResponse(['error' => "Ce compte n'existe pas !"], Response::HTTP_NOT_FOUND);
        }

        // check password
        if (!password_verify($userPassword, $user->getPassword())) {
            return new JsonResponse(['error' => 'Le mot de passe est incorrect'], Response::HTTP_BAD_REQUEST);
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
