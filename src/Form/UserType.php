<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Formulaire d'un adhérent, rempli par le bibliothécaire.
 * Le mot de passe est obligatoire à la création, facultatif en modification.
 */
class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $creation = $options['creation'];

        $builder
            ->add('prenom', TextType::class, ['label' => 'Prénom'])
            ->add('nom', TextType::class, ['label' => 'Nom'])
            ->add('email', EmailType::class, ['label' => 'Email'])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôle',
                'choices' => [
                    'Adhérent' => User::ROLE_USER,
                    'Bibliothécaire (administrateur)' => User::ROLE_ADMIN,
                ],
                'multiple' => true,
                'expanded' => true,
                'help' => 'Un adhérent a toujours le rôle Adhérent.',
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'mapped' => false,
                'required' => $creation,
                'first_options' => ['label' => 'Mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'second_options' => ['label' => 'Confirmer le mot de passe', 'attr' => ['autocomplete' => 'new-password']],
                'invalid_message' => 'Les deux mots de passe ne correspondent pas.',
                'help' => $creation ? null : 'Laisser vide pour ne pas changer le mot de passe.',
                'constraints' => array_filter([
                    $creation ? new Assert\NotBlank(message: 'Saisissez un mot de passe.') : null,
                    new Assert\Length(min: 6, max: 4096, minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères.'),
                ]),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
            'creation' => true,
        ]);
        $resolver->setAllowedTypes('creation', 'bool');
    }
}
