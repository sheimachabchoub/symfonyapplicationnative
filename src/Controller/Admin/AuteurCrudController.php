<?php

namespace App\Controller\Admin;

use App\Entity\Auteur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class AuteurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Auteur::class; }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nom');
        yield TextField::new('prenom');
        yield TextEditorField::new('biographie');
        yield AssociationField::new('livres')->onlyOnDetail();
    }
}
