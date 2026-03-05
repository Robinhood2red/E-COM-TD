<?php

namespace App\Controller;

use App\Entity\Book;
use App\Entity\Emprunt;
use App\Repository\EmpruntRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EmpruntController extends AbstractController
{
    #[Route('/emprunt', name: 'app_emprunt_liste')]
    public function index(EmpruntRepository $empruntRepository): Response
    {
        // On récupère TOUS les emprunts pour la vue historique
        return $this->render('emprunt/index.html.twig', [
            'emprunts' => $empruntRepository->findAll(),
            'titre_page' => 'Historique global'
        ]);
    }

    #[Route('/emprunt/en-cours', name: 'app_emprunt_en_cours')]
    public function enCours(EmpruntRepository $empruntRepository): Response
    {
        // On utilise ta méthode personnalisée
        $emprunts = $empruntRepository->findEnCours();

        return $this->render('emprunt/en_cours.html.twig', [
            'emprunts' => $emprunts,
        ]);
    }

#[Route('/emprunt/new/{id}', name: 'app_emprunt_new')]
    public function new(Book $book, EntityManagerInterface $entityManager): Response
    {
        // 1. Vérifier si l'utilisateur est connecté (Critère de sécurité)
        $user = $this->getUser();
        if (!$user) {
            $this->addFlash('danger', 'Vous devez être connecté pour emprunter un livre.');
            return $this->redirectToRoute('app_login');
        }

        // 2. Vérifier si le livre est disponible (Critère OBLIGATOIRE)
        if ($book->getStock() <= 0) {
            $this->addFlash('danger', 'Livre non disponible'); // Message flash ROUGE
            return $this->redirectToRoute('app_book_index');
        }

        // 3. Créer l'emprunt
        $emprunt = new Emprunt();
        $emprunt->setBook($book);
        $emprunt->setUser($user);
        $emprunt->setDateEmprunt(new \DateTimeImmutable());
        // On ne définit pas de dateRetour car l'emprunt est "EN_COURS"

        // 4. Décrémenter le stock du livre
        $book->setStock($book->getStock() - 1);

        // 5. Enregistrer en base de données
        $entityManager->persist($emprunt);
        $entityManager->flush();

        // 6. Message de succès et redirection
        $this->addFlash('success', 'Emprunt enregistré !'); // Message flash VERT
        return $this->redirectToRoute('app_emprunt_en_cours');
    }

}