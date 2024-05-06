<?php

// src/Controller/Api/RegistrationController.php
namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class RegistrationController
{
    private $entityManager;
    private $passwordHasher;
    private $serializer;
    private $validator;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher, SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->entityManager = $entityManager;
        $this->passwordHasher = $passwordHasher;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * @Route("/api/register", name="api_register", methods={"POST"})
     */
    public function register(Request $request): JsonResponse
    {
        $user = $this->serializer->deserialize($request->getContent(), User::class, 'json');

        // Validate the deserialized user
        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return new JsonResponse($this->serializer->serialize($errors, 'json'), Response::HTTP_BAD_REQUEST, [], true);
        }

        // Hash the password and set it to the User entity
        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $user->getPassword())
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Return a success response
        return new JsonResponse(['status' => 'User created'], Response::HTTP_CREATED);
    }
}
