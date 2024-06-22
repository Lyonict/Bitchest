<?php

namespace App\Controller;

use App\Entity\Wallet;
use App\Entity\Cryptocurrencies;
use App\Form\CryptoAmountType;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/user/wallet', name: 'wallet')]
    public function show(
        UserRepository $userRepository,
        WalletRepository $walletRepository,
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $id = $session->get('walletUserId');
        $requestId = $request->query->get('id');

        if ($requestId) {
            $id = $requestId;
            $session->set('walletUserId', $id);
        }

        if ($id === null) {
            // Handle the case where the walletUserId is not set in the session
            $this->addFlash('error', 'No wallet user ID set in session.');
            return $this->redirectToRoute('user'); // Redirect or handle as appropriate
        }

        $user = $userRepository->find($id);

        if (!$user) {
            // Handle the case where the user is not found
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user'); // Redirect or handle as appropriate
        }

        // Fetch wallets for the user
        $wallets = $walletRepository->findBy(['user' => $user]);

        // Create form for adding crypto amount
        $wallet = new Wallet();
        $form = $this->createForm(CryptoAmountType::class, $wallet);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle form submission
            // Set the user for the wallet
            $wallet->setUser($user);

            // Save the wallet entity to the database
            $entityManager->persist($wallet);
            $entityManager->flush();

            $this->addFlash('success', 'Crypto amount added successfully.');

            // Redirect to avoid resubmission
            return $this->redirectToRoute('wallet', ['id' => $id]);
        }

        return $this->render('user/wallet.html.twig', [
            "user" => $user,
            "userId" => $id,
            "wallets" => $wallets,
            "form" => $form->createView(), // Pass the form view to the template
        ]);
    }
}

