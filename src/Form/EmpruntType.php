<?php

namespace App\Form;

use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\User;
use App\Repository\LivreRepository;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * À la création : livre + adhérent (+ date de retour optionnelle).
 * En modification (option "modification") : seule la date de retour prévue change (prolongation).
 */
class EmpruntType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $modification = $options['modification'];

        $builder
            ->add('livre', EntityType::class, [
                'class' => Livre::class,
                'query_builder' => static fn (LivreRepository $r) => $r->createQueryBuilder('l')->orderBy('l.titre'),
                'placeholder' => '— Choisir un livre —',
                'disabled' => $modification,
                'label' => 'Livre',
            ])
            ->add('adherent', EntityType::class, [
                'class' => User::class,
                'query_builder' => static fn (UserRepository $r) => $r->createQueryBuilder('u')->orderBy('u.nom')->addOrderBy('u.prenom'),
                'choice_label' => static fn (User $u) => $u->getNomComplet().' ('.$u->getEmail().')',
                'placeholder' => '— Choisir un adhérent —',
                'disabled' => $modification,
                'label' => 'Adhérent',
            ])
            ->add('dateRetourPrevue', DateType::class, [
                'input' => 'datetime_immutable',
                'required' => $modification,
                'label' => 'Date de retour prévue',
                'help' => $modification ? null : 'Laisser vide pour la durée de prêt par défaut.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Emprunt::class,
            'modification' => false,
            // À la création, la date d'emprunt n'existe pas encore : le service s'occupe des règles.
            'validation_groups' => static fn ($form) => $form->getConfig()->getOption('modification') ? ['Default'] : ['creation'],
        ]);
        $resolver->setAllowedTypes('modification', 'bool');
    }
}
