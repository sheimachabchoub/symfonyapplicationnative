<?php

namespace App\Controller\Admin;

use App\Entity\Editeur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class EditeurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Editeur::class; }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('nomEditeur');
        yield TextField::new('pays');
        yield TextField::new('adresse');
        yield TextField::new('telephone');
        yield AssociationField::new('livres')->onlyOnDetail();
    }
}
