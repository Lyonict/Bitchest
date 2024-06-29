<?php

namespace App\Controller;

use App\Entity\Wallet;
use App\Form\CryptoAmountType;
use App\Form\CryptoSellType;
use App\Form\ChangePasswordType;
use App\Repository\UserRepository;
use App\Repository\WalletRepository;
use App\Repository\TransactionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\CryptoService;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Entity\Transaction;
use DateTime;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bundle\SecurityBundle\Security;



class UserController extends AbstractController
{
    #[Route('/user', name: 'user')]
    public function index(UserRepository $repository, TransactionRepository $transactionRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        if (!$currentUser) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        // Fetch transactions for the current user
        $transactions = $transactionRepository->findBy(['user' => $currentUser], ['id' => 'DESC']);

        return $this->render('user/user.html.twig', [
            'currentUser' => $currentUser,
            'transactions' => $transactions,
        ]);
    }

    #[Route('/user/wallet', name: 'wallet')]
    public function show(
        UserRepository $userRepository,
        WalletRepository $walletRepository,
        TransactionRepository $transactionRepository,
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $entityManager,
        CryptoService $cryptoService,
        HttpClientInterface $client // Inject HttpClientInterface
    ): Response {

        // Fetch current user
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        /** @var User $currentUser */
        $id = $currentUser->getId();
        $session->set('walletUserId', $id); // Set in session if needed

        $user = $userRepository->find($id);

        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas trouvé
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user'); // Rediriger ou gérer comme approprié
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

        //////////////
        if ($form->isSubmitted() && $form->isValid()) {
            $wallet->setUser($user);
            $selectedCrypto = $form->get('crypto')->getData();
            $quantity = $form->get('quantity')->getData();

            $existingWallet = $walletRepository->findOneBy([
                'user' => $user,
                'cryptoId' => $selectedCrypto->getCryptoId()
            ]);

            if ($existingWallet) {
                $existingWallet->setQuantity($existingWallet->getQuantity() + $quantity);
                $existingWallet->setTotalCost($existingWallet->getTotalCost() + ($quantity * $wallet->getTotalCost()));

                $entityManager->persist($existingWallet);
            } else {
                $wallet->setCryptoId($selectedCrypto->getCryptoId());
                $wallet->setQuantity($quantity);
                $wallet->setTotalCost($wallet->getTotalCost());
                $entityManager->persist($wallet);
            }

            $transaction = new Transaction();
            $transaction->setUser($user);
            $transaction->setCryptocurrency($selectedCrypto);
            $transaction->setTransactionType('buy');
            $transaction->setQuantity($quantity);
            $transaction->setDate(new DateTime('now', new \DateTimeZone('Europe/Paris')));

            $entityManager->persist($transaction);

            $user->setEuros($user->getEuros() - $wallet->getTotalCost());

            $entityManager->flush();

            $this->addFlash('success', 'Crypto amount added successfully.');
            return $this->redirectToRoute('wallet');
        }

        if ($sellForm->isSubmitted() && $sellForm->isValid()) {
            $wallet->setUser($user);
            $selectedCrypto = $sellForm->get('crypto')->getData();
            $existingWallet = $walletRepository->findOneBy([
                'user' => $user,
                'cryptoId' => $wallet->getCryptoId()
            ]);

            if ($existingWallet) {
                $quantity = $wallet->getQuantity();
                $totalValue = $wallet->getTotalCost();
                $currentQuantity = $existingWallet->getQuantity();

                    // quantité qu'on souhaite vendre > quantité que l'on possède
                if ($quantity > $currentQuantity) {
                    $this->addFlash('error', 'Insufficient crypto quantity to complete the transaction.');
                    return $this->redirectToRoute('wallet');
                } else {
                    
                    $user->setEuros($user->getEuros() + $totalValue);

                    // Update TotalCost value after sale
                    $existingWallet->updateTotalCostAfterSale($quantity);

                    // Update Quantity value after sale
                    $existingWallet->setQuantity($currentQuantity - $quantity);

                    // Remove Wallet if quantity = 0
                    if ($existingWallet->getQuantity() == 0) {
                        $entityManager->remove($existingWallet);
                    }

                    $transaction = new Transaction();
                    $transaction->setUser($user);
                    $transaction->setCryptocurrency($selectedCrypto);
                    $transaction->setTransactionType('sell');
                    $transaction->setQuantity($quantity);
                    $transaction->setDate(new DateTime('now', new \DateTimeZone('Europe/Paris')));

                    $entityManager->persist($transaction);

                    $entityManager->flush();

                    $this->addFlash('success', 'Crypto amount sold successfully.');

                    return $this->redirectToRoute('wallet');
                }
            } else {
                $this->addFlash('error', 'No cryptocurrency found to sell.');
                return $this->redirectToRoute('wallet');
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

    #[Route('/user/account', name: 'account')]
    public function account (Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager): Response
    {
        
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        $passwordform = $this->createForm(ChangePasswordType::class);
        $passwordform->handleRequest($request);

        if ($passwordform->isSubmitted() && $passwordform->isValid()) {
            $oldPassword = $passwordform->get('oldPassword')->getData();
            $newPassword = $passwordform->get('newPassword')->getData();
            $confirmPassword = $passwordform->get('confirmPassword')->getData();

            // Check if old password is correct
            if (!$userPasswordHasher->isPasswordValid($currentUser, $oldPassword)) {
                $this->addFlash('error', 'Current password is incorrect.');
                return $this->redirectToRoute('account');
            }

            // Check if new password matches confirmation password
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'New passwords do not match.');
                return $this->redirectToRoute('account');
            }

            // Encode and set the new password
            $currentUser->setPassword(
                $userPasswordHasher->hashPassword(
                    $currentUser,
                    $newPassword
                )
            );

            $entityManager->persist($currentUser);
            $entityManager->flush();

            $this->addFlash('success', 'Password changed successfully.');

            return $this->redirectToRoute('account');
        }

        return $this->render('user/account.html.twig', [
            'currentUser' => $currentUser,
            'passwordform' => $passwordform->createView(),
        ]);
    }

    #[Route('/user/wallet/{symbol}', name: 'crypto_detail')]
    public function cryptoDetail(string $symbol, CryptoService $cryptoService, WalletRepository $walletRepository, UserRepository $userRepository): Response
    {
        $currentUser = $this->getUser();

        if (!$currentUser) {
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        /** @var User $currentUser */
        $id = $currentUser->getId();
        $user = $userRepository->find($id);
        // Fetch wallets for the user
        $wallets = $walletRepository->findBy(['user' => $user]);

        // Get crypto data using CryptoService
        $cryptoData = $cryptoService->getCryptoData($wallets, $currentUser);
        
        // Get crypto name using symbols in CryptoService
        $cryptoName = $cryptoService->getCryptoNameBySymbol($symbol);

        // Get crypto info using CryptoService
        $cryptoInfo = $cryptoService->getCryptoPrice($symbol);

        return $this->render('user/crypto_detail.html.twig', [
            'crypto' => $cryptoInfo,
            'symbol' => $symbol,
            'cryptoName' => $cryptoName,
            'wallets' => $wallets,
            'currentUser' => $currentUser,
            'cryptoData' => $cryptoData,
        ]);
    }
}

