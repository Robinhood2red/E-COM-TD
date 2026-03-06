<?php

namespace App\Controller;

use App\Entity\Book;
use App\Form\BookType;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/book')]
final class BookController extends AbstractController
{
    #[Route(name: 'app_book_index', methods: ['GET'])]
    public function index(BookRepository $bookRepository): Response
    {
        return $this->render('book/index.html.twig', [
            'books' => $bookRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_book_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $book = new Book();
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($book);
            $entityManager->flush();

            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/new.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_book_show', methods: ['GET'])]
    public function show(Book $book): Response
    {
        return $this->render('book/show.html.twig', [
            'book' => $book,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_book_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BookType::class, $book);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('image')->getData();

            // Si une nouvelle image a été uploadée
            if ($imageFile) {
                // Création un nom unique(ex: 65f123.jpg)
                $newFilename = uniqid().'.'.$imageFile->guessExtension();

                // déplace le fichier dans le dossier public/images
                try {
                    $imageFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/images',
                        $newFilename
                    );
                } catch (FileException $e) {
                    // ... gérer l'erreur si le déplacement échoue
                }
                $book->setImage($newFilename);
            }

            $entityManager->flush();

            $this->addFlash('success', 'Le livre a bien été modifié !');
            return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('book/edit.html.twig', [
            'book' => $book,
            'form' => $form,
        ]);
    }
    #[Route('/{id}/stock/add', name: 'app_book_stock_add', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function stockAdd(Book $book, Request $request, EntityManagerInterface $entityManager): Response
    {
        // On crée l'entrée d'historique
        $stockHistory = new AddProductHistory();
        $form = $this->createForm(AddProductHistoryType::class, $stockHistory);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $addedQuantity = $stockHistory->getQuantity();

            if ($addedQuantity > 0) {
                // 1. On met à jour le stock du livre directement
                $currentStock = $book->getStock();
                $book->setStock($currentStock + $addedQuantity);

                // 2. On configure l'historique
                $stockHistory->setCreatedAt(new \DateTimeImmutable()); 
                
                // ATTENTION : Vérifie si dans ton entité AddProductHistory 
                // la méthode est setBook() ou setProduct()
                if (method_exists($stockHistory, 'setBook')) {
                    $stockHistory->setBook($book);
                } else {
                    $stockHistory->setProduct($book); 
                }

                $entityManager->persist($stockHistory);
                $entityManager->flush();

                $this->addFlash('success', 'Le stock de "' . $book->getTitre() . '" a été mis à jour !');

                return $this->redirectToRoute('app_book_index');
            }
        }

        return $this->render('book/addStock.html.twig', [
            'form' => $form->createView(),
            'book' => $book
        ]);
    }
    #[Route('/{id}', name: 'app_book_delete', methods: ['POST'])]
    public function delete(Request $request, Book $book, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$book->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($book);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_book_index', [], Response::HTTP_SEE_OTHER);
    }
}
