<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class LivreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string { return Livre::class; }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('titre');
        yield IntegerField::new('nbPages');
        yield DateField::new('dateEdition');
        yield IntegerField::new('nbExemplaires');
        yield MoneyField::new('prix')->setCurrency('TND');
        yield TextField::new('isbn');
        yield TextareaField::new('resume')->hideOnIndex();
        yield AssociationField::new('editeur');
        yield AssociationField::new('category');
        yield AssociationField::new('auteurs');
    }
}
