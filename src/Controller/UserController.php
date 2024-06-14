<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

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

    #[Route('/user/set-wallet/{id}', name: 'set_wallet')]
    public function setWalletUserId(SessionInterface $session, $id): Response
    {
        $session->set('walletUserId', $id);
        return $this->redirectToRoute('wallet');
    }

    #[Route('/user/wallet', name: 'wallet')]
    public function show(UserRepository $repository, SessionInterface $session): Response
    {
        $id = $session->get('walletUserId');
        $user = $repository->find($id);

        return $this->render('user/wallet.html.twig', [
            "user" => $user,
            "userId" => $id,
        ]);
    }
}
