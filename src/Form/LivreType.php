<?php

namespace App\Form;

use App\Entity\Auteur;
use App\Entity\Category;
use App\Entity\Editeur;
use App\Entity\Livre;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, ['label' => 'Titre'])
            ->add('isbn', TextType::class, ['label' => 'ISBN', 'help' => 'ISBN-10 ou ISBN-13'])
            ->add('auteurs', EntityType::class, [
                'class' => Auteur::class,
                'multiple' => true,
                'required' => false,
                'label' => 'Auteurs',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'required' => false,
                'placeholder' => '— Aucune —',
                'label' => 'Catégorie',
            ])
            ->add('editeur', EntityType::class, [
                'class' => Editeur::class,
                'placeholder' => '— Choisir —',
                'label' => 'Éditeur',
            ])
            ->add('dateEdition', DateType::class, ['label' => 'Date d\'édition'])
            ->add('nbPages', IntegerType::class, ['label' => 'Nombre de pages'])
            ->add('nbExemplaires', IntegerType::class, ['label' => 'Nombre d\'exemplaires'])
            ->add('prix', MoneyType::class, ['currency' => 'TND', 'label' => 'Prix'])
            ->add('resume', TextareaType::class, ['required' => false, 'label' => 'Résumé', 'attr' => ['rows' => 5]])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livre::class,
        ]);
    }
}
