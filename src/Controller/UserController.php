<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user')]
    public function index(UserRepository $repository): Response
    {
        $users = $repository->findAll();

        return $this->render('user/user.html.twig', [
            "users" => $users,
        ]);
    }

    #[Route('/user/wallet', name: 'wallet')]
    public function show(UserRepository $repository): Response
    {
        return $this->render('user/wallet.html.twig', [
        ]);
    }
}

