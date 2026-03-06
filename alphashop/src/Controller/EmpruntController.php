<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Emprunt;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class EmpruntController extends AbstractController
{
    #[Route('/emprunt', name: 'app_emprunt_liste')]
    #[IsGranted('ROLE_ADMIN')]
    public function index(EmpruntRepository $empruntRepository): Response
    {
        // On récupère TOUS les emprunts pour la vue historique
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findAll(),
            'titre_page' => 'Historique global'
        ]);
    }

    #[Route('/emprunt/en-cours', name: 'app_emprunt_en_cours')]
    #[IsGranted('ROLE_USER')] // Tout utilisateur connecté peut voir ses emprunts
    public function enCours(EmpruntRepository $empruntRepository): Response
    {
        $user = $this->getUser();

        if ($this->isGranted('ROLE_ADMIN')) {
            // L'admin voit tous les emprunts non rendus
            $emprunts = $empruntRepository->findEnCours();
        } else {
            // L'utilisateur ne voit que les siens qui ne sont pas encore rendus
            $emprunts = $empruntRepository->findBy([
                'user' => $user,
                'dateRetour' => null
            ], ['dateEmprunt' => 'ASC']);
        }

        return $this->render('emprunt/en_cours.html.twig', [
            'emprunts' => $emprunts,
        ]);
    }

#[Route('/emprunt/new/{id}', name: 'app_emprunt_new')]
    public function new(Book $book, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour emprunter un livre.');
            return $this->redirectToRoute('app_login');
        }

        // si le livre est disponible (Critère OBLIGATOIRE)
        if ($book->getStock() <= 0) {
            $this->addFlash('danger', 'Livre non disponible'); // Message flash ROUGE
            return $this->redirectToRoute('app_book_index');
        }

        // Pour créer l'emprunt
        $emprunt = new Emprunt();
        $emprunt->setBook($book);
        $emprunt->setUser($user);
        $emprunt->setDateEmprunt(new \DateTimeImmutable());
        // On ne définit pas de dateRetour car l'emprunt est "EN_COURS"

        $book->setStock($book->getStock() - 1);

        $entityManager->persist($emprunt);
        $entityManager->flush();

        $this->addFlash('success', 'Emprunt enregistré !');
        return $this->redirectToRoute('app_emprunt_en_cours');
    }

   #[Route('/emprunt/rendre/{id}', name: 'app_emprunt_rendre')]
//    #[IsGranted('ROLE_ADMIN')]
    public function rendre(Emprunt $emprunt, EntityManagerInterface $entityManager): Response
    {
        if ($emprunt->getUser() !== $this->getUser() && !$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('danger', "Vous ne pouvez pas rendre un livre que vous n'avez pas emprunté !");
            return $this->redirectToRoute('app_emprunt_en_cours');
        }

        // Vérifier si le livre n'est pas déjà rendu (pour éviter le double stock +1)
        if ($emprunt->getDateRetour() !== null) {
            $this->addFlash('warning', "Ce livre a déjà été rendu.");
            return $this->redirectToRoute('app_emprunt_en_cours');
        }

        // Clôture de l'emprunt
        $emprunt->setDateRetour(new \DateTimeImmutable());
        
        // Mise à jour du stock
        $book = $emprunt->getBook();
        $book->setStock($book->getStock() + 1);

        $entityManager->flush();

        $this->addFlash('success', 'Merci ! Le livre "' . $book->getTitre() . '" est à nouveau disponible.');

        return $this->redirectToRoute('app_emprunt_en_cours');
    }
}