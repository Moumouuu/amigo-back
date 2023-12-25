<?php

namespace App\Controller;

use App\Entity\User;
use DateTime;
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
        //repository
        $userRepository = $entityManager->getRepository(User::class);

        // data from request
        $parameters = json_decode($request->getContent(), true);

        $userEmail = $parameters['email'];
        $userPassword = $parameters['password'];

        if (!filter_var($userEmail, FILTER_VALIDATE_EMAIL)) {
            return new JsonResponse(['error' => "Le format de l'email n'est pas valide !"], Response::HTTP_BAD_REQUEST);
        }

        // check if user already exists
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        if ($user) {
            return new JsonResponse(['error' => "L'utilisateur existe déjà !"], Response::HTTP_BAD_REQUEST);
        }

        //create user
        $user = new User();
        // hash password
        $hashedPassword = password_hash($userPassword, PASSWORD_DEFAULT);

        $token = $this->jwtManager->create($user);
        $trimmedToken = substr($token, 0, 255);

        $user
            ->setEmail($userEmail)
            ->setPassword($hashedPassword)
            ->setFirstName($parameters['firstName'])
            ->setLastName($parameters['lastName'])
            ->setDetails($parameters['details'])
            ->setDateOfBirth(array_key_exists('dateOfBirth', $parameters) ? new DateTime($parameters['dateOfBirth']) : null)
            ->setToken($trimmedToken);

        $entityManager->persist($user);
        $entityManager->flush();

        return new JsonResponse(['msg' => 'User created', 'token' => $trimmedToken]);
    }
}
