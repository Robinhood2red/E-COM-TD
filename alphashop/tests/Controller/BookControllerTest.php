<?php

namespace App\Tests\Controller;

use App\Entity\Book;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class BookControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $bookRepository;
    private string $path = '/book/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->bookRepository = $this->manager->getRepository(Book::class);

        foreach ($this->bookRepository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $this->client->followRedirects();
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Book index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first()->text());
    }

    public function testNew(): void
    {
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'book[titre]' => 'Testing',
            'book[auteur]' => 'Testing',
            'book[isbn]' => 'Testing',
            'book[stock]' => 'Testing',
        ]);

        self::assertResponseRedirects('/book');

        self::assertSame(1, $this->bookRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }

    public function testShow(): void
    {
        $fixture = new Book();
        $fixture->setTitre('My Title');
        $fixture->setAuteur('My Title');
        $fixture->setIsbn('My Title');
        $fixture->setStock('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Book');

        // Use assertions to check that the properties are properly displayed.
        $this->markTestIncomplete('This test was generated');
    }

    public function testEdit(): void
    {
        $fixture = new Book();
        $fixture->setTitre('Value');
        $fixture->setAuteur('Value');
        $fixture->setIsbn('Value');
        $fixture->setStock('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'book[titre]' => 'Something New',
            'book[auteur]' => 'Something New',
            'book[isbn]' => 'Something New',
            'book[stock]' => 'Something New',
        ]);

        self::assertResponseRedirects('/book');

        $fixture = $this->bookRepository->findAll();

        self::assertSame('Something New', $fixture[0]->getTitre());
        self::assertSame('Something New', $fixture[0]->getAuteur());
        self::assertSame('Something New', $fixture[0]->getIsbn());
        self::assertSame('Something New', $fixture[0]->getStock());

        $this->markTestIncomplete('This test was generated');
    }

    public function testRemove(): void
    {
        $fixture = new Book();
        $fixture->setTitre('Value');
        $fixture->setAuteur('Value');
        $fixture->setIsbn('Value');
        $fixture->setStock('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/book');
        self::assertSame(0, $this->bookRepository->count([]));

        $this->markTestIncomplete('This test was generated');
    }
}
