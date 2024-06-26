<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractDashboardController
{
    private $logger;
    private $entityManager;

    public function __construct(LoggerInterface $logger, EntityManagerInterface $entityManager)
    {
        $this->logger = $logger;
        $this->entityManager = $entityManager;
    }


    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {

        // Vérifiez si l'utilisateur est authentifié
        $user = $this->getUser();
        
        if (!$user) {
            $this->logger->warning('Unauthenticated access attempt to /admin');
            $this->addFlash('error', 'You must be logged in to access this page.');
            return $this->redirectToRoute('home'); // Redirigez vers la page de connexion ou une autre page appropriée
        }

        // Option 1. You can make your dashboard redirect to some common page of your backend
        //
        // $adminUrlGenerator = $this->container->get(AdminUrlGenerator::class);
        // return $this->redirect($adminUrlGenerator->setController(OneOfYourCrudController::class)->generateUrl());

        // Option 2. You can make your dashboard redirect to different pages depending on the user
        
        // Ajoutez un message de log pour vérifier l'utilisateur et ses rôles
        // $this->logger->info('User roles: ' . implode(', ', $this->getUser()->getRoles()));

        if ($this->isGranted('ROLE_ADMIN')) {
            // $this->logger->info('User has ROLE_ADMIN');
            $users = $this->entityManager->getRepository(User::class)->findAll();
            return $this->render('admin/dashboard.html.twig', [
                'users' => $users,
            ]);
        } else {
            // $this->logger->warning('User does not have ROLE_ADMIN');
            $this->addFlash('error', 'Access Denied. You do not have the necessary permissions to access this page.');
            return $this->redirectToRoute('user'); // Redirigez vers une autre route appropriée
        }

        // Option 3. You can render some custom template to display a proper dashboard with widgets, etc.
        // (tip: it's easier if your template extends from @EasyAdmin/page/content.html.twig)
        //
        // return $this->render('some/path/my-dashboard.html.twig');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bitchest - Administration');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Users', 'fas fa-users-gear', User::class);
    }
}
