<?php

namespace App\Controller;

use App\Repository\BookRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home_page')]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('home/index.html.twig', [
            // On récupère les livres pour les afficher comme des produits
            'products' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/product/{id}', name: 'app_home_product_show')]
    public function show(int $id, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->find($id);

        if (!$book) {
            throw $this->createNotFoundException('Ce produit n\'existe pas.');
        }

        return $this->render('home/show.html.twig', [
            'product' => $book,
        ]);
    }
}