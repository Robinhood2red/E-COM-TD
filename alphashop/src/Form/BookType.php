<?php

namespace App\Form;

use App\Entity\Book;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class BookType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('auteur')
            ->add('isbn')
            ->add('image', FileType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File(
                        maxSize: '1024k',
                        mimeTypes: [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                        ],
                        maxSizeMessage: 'Votre image ne doit pas dépasser 1024ko',
                        mimeTypesMessage: 'Veuillez choisir un fichier de type image valide (jpeg, png, jpg) !',
                    ),
                ],
            ])
        ;

        // RÉCUPÉRATION DU LIVRE
        $book = $options['data'] ?? null;

        // SI LE LIVRE EST NOUVEAU (pas d'ID), ON AJOUTE LE CHAMP STOCK
        // SI C'EST UN EDIT, ON NE L'AJOUTE PAS, DONC SYMFONY NE LE VALIDE PAS
        if (!$book || null === $book->getId()) {
            $builder->add('stock', null, [
                'label' => 'Quantité initiale',
                'attr' => ['class' => 'form-control mb-3']
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Book::class,
        ]);
    }
}
