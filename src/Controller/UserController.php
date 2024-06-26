<?php

namespace App\Controller;

use App\Entity\Wallet;
use App\Form\CryptoAmountType;
use App\Form\CryptoSellType;
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

        // Create form for adding crypto amount
        $wallet = new Wallet();
        $form = $this->createForm(CryptoAmountType::class, $wallet);
        $sellForm = $this->createForm(CryptoSellType::class, $wallet);

        $form->handleRequest($request);
        $sellForm->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $wallet->setUser($user);
            $selectedCrypto = $form->get('crypto')->getData();
            $quantity = $form->get('quantity')->getData();
    
            // Check if the cryptocurrency already exists in the user's wallet
            $existingWallet = $walletRepository->findOneBy([
                'user' => $user,
                'cryptoId' => $selectedCrypto->getCryptoId()
            ]);
    
            if ($existingWallet) {
                // Update existing wallet entry
                $existingWallet->setQuantity($existingWallet->getQuantity() + $quantity);
                $existingWallet->setTotalCost($existingWallet->getTotalCost() + ($quantity * $wallet->getTotalCost()));
    
                $entityManager->persist($existingWallet);
            } else {
                // Create a new wallet entry
                $wallet->setCryptoId($selectedCrypto->getCryptoId());
                $wallet->setQuantity($quantity);
                $wallet->setTotalCost($wallet->getTotalCost());
                $entityManager->persist($wallet);
            }
    
            $user->setEuros($user->getEuros() - $wallet->getTotalCost());
    
            $entityManager->flush();
    
            $this->addFlash('success', 'Crypto amount added successfully.');
            return $this->redirectToRoute('wallet', ['id' => $id]);
        }
    

        if ($sellForm->isSubmitted() && $sellForm->isValid()) {
            // Handle form submission for selling
            $wallet->setUser($user);

            // Find the existing wallet entry for the selected cryptocurrency
            $existingWallet = $walletRepository->findOneBy([
                'user' => $user,
                'cryptoId' => $wallet->getCryptoId()
            ]);

            if ($existingWallet) {
                $quantity = $wallet->getQuantity();
                $totalValue = $wallet->getTotalCost();
                $currentQuantity = $existingWallet->getQuantity();

                if ($quantity > $currentQuantity) {
                    $this->addFlash('error', 'Insufficient crypto quantity to complete the transaction.');
                    // Redirect to avoid resubmission
                    return $this->redirectToRoute('wallet', ['id' => $id]);
                } else {
                    // Deduct the quantity of crypto
                    $existingWallet->setQuantity($currentQuantity - $quantity);

                    // Add the total value to the user's euros
                    $user->setEuros($user->getEuros() + $totalValue);

                    // Remove the wallet entry if the quantity is zero
                    if ($existingWallet->getQuantity() == 0) {
                        $entityManager->remove($existingWallet);
                    }

                    $entityManager->flush();

                    $this->addFlash('success', 'Crypto amount sold successfully.');

                    // Redirect to avoid resubmission
                    return $this->redirectToRoute('wallet', ['id' => $id]);
                }
            } else {
                $this->addFlash('error', 'No cryptocurrency found to sell.');
                // Redirect to avoid resubmission
                return $this->redirectToRoute('wallet', ['id' => $id]);
            }
        }

        return $this->render('user/wallet.html.twig', [
            'currentUser' => $currentUser,
            'user' => $user,
            'userId' => $id,
            'wallets' => $wallets,
            'form' => $form->createView(),
            'sellForm' => $sellForm->createView(),
            'cryptoData' => $cryptoData,
        ]);
    }
}
