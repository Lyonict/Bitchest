<?php

namespace App\Controller;

use App\Entity\Wallet;
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
        EntityManagerInterface $entityManager
    ): Response {
        $id = $session->get('walletUserId');
        $requestId = $request->query->get('id');

        if ($requestId) {
            $id = $requestId;
            $session->set('walletUserId', $id);
        }

        if ($id === null) {
            // Gérer le cas où walletUserId n'est pas défini en session
            $this->addFlash('error', 'No wallet user ID set in session.');
            return $this->redirectToRoute('user'); // Rediriger ou gérer comme approprié
        }

        // Récupérer l'utilisateur connecté
        $currentUser = $this->getUser();

        if (!$currentUser) {
            // Gérer le cas où l'utilisateur n'est pas connecté
            throw $this->createAccessDeniedException('User not authenticated.');
        }

        $user = $userRepository->find($id);

        if (!$user) {
            // Gérer le cas où l'utilisateur n'est pas trouvé
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('user'); // Rediriger ou gérer comme approprié
        }

        // Fetch wallets for the user
        $wallets = $walletRepository->findBy(['user' => $user]);

        // Créer le formulaire pour ajouter le montant de crypto
        $wallet = new Wallet();
        $form = $this->createForm(CryptoAmountType::class, $wallet);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Gérer la soumission du formulaire
            // Définir l'utilisateur pour le portefeuille
            $wallet->setUser($user);

            // Enregistrer l'entité de portefeuille dans la base de données
            $entityManager->persist($wallet);
            $entityManager->flush();

            $this->addFlash('success', 'Crypto amount added successfully.');

            // Rediriger pour éviter la soumission multiple
            return $this->redirectToRoute('wallet', ['id' => $id]);
        }

        return $this->render('user/wallet.html.twig', [
            "currentUser" => $currentUser,
            "user" => $user,
            "userId" => $id,
            "wallets" => $wallets,
            "form" => $form->createView(), // Passer la vue du formulaire au template
        ]);
    }
}
