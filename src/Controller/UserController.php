<?php

namespace App\Controller;

use App\Entity\Wallet;
use App\Form\CryptoAmountType;
use App\Form\SellCryptoType; // Add SellCryptoType
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CryptoService;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class UserController extends AbstractController
{
    #[Route('/user', name: 'user')]
    public function index(UserRepository $repository): Response
    {
        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        if (!$currentUser) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        return $this->render('user/user.html.twig', [
            "currentUser" => $currentUser,
        ]);
    }

    #[Route('/user/wallet', name: 'wallet')]
    public function show(
        UserRepository $userRepository,
        WalletRepository $walletRepository,
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $entityManager,
        CryptoService $cryptoService,
        HttpClientInterface $client // Inject HttpClientInterface
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

        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        if (!$currentUser) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            // Handle the case where the user is not found
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user'); // Redirect or handle as appropriate
        }

        // Fetch wallets for the user
        $wallets = $walletRepository->findBy(['user' => $user]);

        // Get crypto data using CryptoService
        $cryptoData = $cryptoService->getCryptoData($wallets, $currentUser);

        // Create form for adding crypto amount (Buy form)
        $buyForm = $this->createForm(CryptoAmountType::class, new Wallet());

        // Handle form submissions
        $buyForm->handleRequest($request);

        if ($buyForm->isSubmitted() && $buyForm->isValid()) {
            // Handle crypto purchase
            $wallet = $buyForm->getData();
            $totalCost = $wallet->getTotalCost(); // Assuming getTotalCost() is set correctly in CryptoAmountType
            $userEuros = $user->getEuros();

            if ($userEuros < $totalCost) {
                $this->addFlash('error', 'Insufficient funds to complete the transaction.');
            } else {
                $user->setEuros($userEuros - $totalCost);

                // Save the wallet entity to the database
                $wallet->setUser($user);
                $entityManager->persist($wallet);
                $entityManager->flush();

                $this->addFlash('success', 'Crypto amount added successfully.');

                // Redirect to avoid resubmission
                return $this->redirectToRoute('wallet', ['id' => $id]);
            }
        }   

        return $this->render('user/wallet.html.twig', [
            'currentUser' => $currentUser,
            'user' => $user,
            'userId' => $id,
            'wallets' => $wallets,
            'buyForm' => $buyForm->createView(),
            'cryptoData' => $cryptoData,
        ]);
    }
}
