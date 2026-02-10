<?php

namespace App\Controller\Admin;

use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Uživatel')
            ->setEntityLabelInPlural('Uživatelé')
            ->setPageTitle('index', 'Správa uživatelů')
            ->setPageTitle('new', 'Vytvořit uživatele')
            ->setPageTitle('edit', 'Upravit uživatele')
            ->setPageTitle('detail', 'Detail uživatele');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Základní údaje')->setIcon('fa fa-user'),
            IdField::new('id')->hideOnForm(),
            AvatarField::new('avatarUrl', 'Avatar')->hideOnForm(),
            EmailField::new('email', 'Email'),
            ArrayField::new('roles', 'Role')->setPermission('ROLE_ADMIN'),

            FormField::addPanel('Fakturační údaje')->setIcon('fa fa-file-invoice'),
            TextField::new('billingName', 'Fakturační jméno')->hideOnIndex()->setColumns(12),
            TextField::new('billingIco', 'IČO')->hideOnIndex()->setColumns(6),
            TextField::new('billingDic', 'DIČ')->hideOnIndex()->setColumns(6),
            TextField::new('billingStreet', 'Ulice')->hideOnIndex()->setColumns(6),
            TextField::new('billingCity', 'Město')->hideOnIndex()->setColumns(6),
            TextField::new('billingZip', 'PSČ')->hideOnIndex()->setColumns(6),
            CountryField::new('billingCountry', 'Země')->hideOnIndex()->setColumns(6),
        ];
    }
}
