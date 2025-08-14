<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/api/users', name: 'api_users_')]
class ApiUserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private ValidatorInterface $validator,
        private SerializerInterface $serializer,
        private TranslatorInterface $translator,
        private UserPasswordHasherInterface $userPasswordHasher
    ) {}

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $users = $this->userRepository->findAll();

        $format = $request->query->get('format');

        return $this->cleanResponse($users, $format);
    }

    /**
     * @param int $id
     * @param Request $request
     * @return Response
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id, Request $request): Response
    {
        $user = $this->userRepository->find($id);

        if (!$user) {
            return new JsonResponse(['error' => $this->translator->trans('errors.user_not_found')], Response::HTTP_NOT_FOUND);
        }

        $format = $request->query->get('format');

        return $this->cleanResponse($user, $format);
    }

    /**
     * @param Request $request
     * @return Response
     */
    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => $this->translator->trans('errors.invalid_json')], Response::HTTP_BAD_REQUEST);
        }

        $user = $this->userRepository->init($data);

        if (isset($data['password'])) {
            if (empty($data['password'])) {
                return new JsonResponse(['error' => $this->translator->trans('validation.password.not_blank')], Response::HTTP_BAD_REQUEST);
            }
            $hashedPassword = $this->userPasswordHasher->hashPassword($user, $data['password']);
            $user->setPassword($hashedPassword);
        }

        $errors = $this->validator->validate($user);

        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }

            return new JsonResponse(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->userRepository->save($user);

        return new JsonResponse(['message' => $this->translator->trans('errors.user_created_successfully'), 'id' => $user->getId()], Response::HTTP_CREATED);
    }

    /**
     * @param $data
     * @param string|null $format
     * @return Response
     */
    private function cleanResponse($data, ?string $format = 'json'): Response
    {
        $context = ['groups' => ['user:read']];
        $currentDate = (new \DateTime())->format('Y-m-d');
        $fileExtension = $format === 'yaml' ? 'yaml' : 'json';
        $filename = "data_{$currentDate}.{$fileExtension}";

        if ($format === 'yaml') {
            return $this->createYamlResponse($data, $context, $filename);
        }

        return $this->json($data, Response::HTTP_OK, [], $context);
    }

    /**
     * @param array|User $data
     * @param array $context
     * @param string $filename
     * @return Response
     */
    private function createYamlResponse(array|User $data, array $context, string $filename): Response
    {
        $serializedData = $this->serializer->serialize($data, 'json', $context);
        $decodedData = json_decode($serializedData, true);
        $yamlContent = Yaml::dump($decodedData, 4, 2);

        $response = new Response($yamlContent);
        $response->headers->set('Content-Type', 'application/x-yaml');
        $response->headers->set('Content-Disposition', "attachment; filename={$filename}");

        return $response;
    }
}
