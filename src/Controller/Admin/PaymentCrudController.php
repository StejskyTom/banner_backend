<?php

namespace App\Controller\Admin;

use App\Entity\Payment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class PaymentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Payment::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Platba')
            ->setEntityLabelInPlural('Platby')
            ->setPageTitle('new', 'Vytvořit platbu')
            ->setPageTitle('index', 'Přehled plateb')
            ->setPageTitle('detail', 'Detail platby')
            ->setPageTitle('edit', 'Upravit platbu')
            ->setSearchFields(['id', 'invoiceNumber', 'user.email', 'status'])
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $downloadInvoice = Action::new('downloadInvoice', 'Stáhnout fakturu', 'fa fa-download')
            ->linkToCrudAction('downloadInvoice');

        return $actions
            ->add(Crud::PAGE_INDEX, $downloadInvoice)
            ->add(Crud::PAGE_DETAIL, $downloadInvoice);
    }

    public function __construct(
        private \Doctrine\ORM\EntityManagerInterface $entityManager
    ) {}

    public function downloadInvoice(AdminContext $context)
    {
        $id = $context->getRequest()->query->get('entityId');
        $payment = $this->entityManager->getRepository(Payment::class)->find($id);

        $adminUrlGenerator = $this->container->get(\EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator::class);
        $fallbackUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();

        if (!$payment) {
            $this->addFlash('danger', 'Platba nebyla nalezena.');
            return $this->redirect($context->getReferrer() ?? $fallbackUrl);
        }

        $billing = $payment->getBillingSnapshot();
        $user = $payment->getUser();

        // Same HTML Invoice Template as SubscriptionController
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <title>Faktura ' . $payment->getInvoiceNumber() . '</title>
            <style>
                body { font-family: "DejaVu Sans", sans-serif; max-width: 800px; mx-auto; padding: 40px; font-size: 14px; }
                .header { width: 100%; margin-bottom: 50px; }
                .title { font-size: 24px; font-weight: bold; }
                .meta { float: right; text-align: right; }
                .details { width: 100%; margin-bottom: 50px; clear: both; }
                .col { width: 48%; float: left; }
                .col-right { float: right; }
                .table { width: 100%; border-collapse: collapse; margin-bottom: 30px; clear: both; }
                .table th, .table td { border-bottom: 1px solid #ddd; padding: 10px; text-align: left; }
                .total { text-align: right; font-size: 20px; font-weight: bold; margin-top: 20px; }
                .clear { clear: both; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="meta">
                    <div>Datum vystavení: ' . $payment->getCreatedAt()->format('d.m.Y') . '</div>
                    <div>Datum zdan. plnění: ' . $payment->getCreatedAt()->format('d.m.Y') . '</div>
                </div>
                <div>
                    <div class="title">FAKTURA - DAŇOVÝ DOKLAD</div>
                    <div style="margin-top: 10px">Číslo: ' . $payment->getInvoiceNumber() . '</div>
                </div>
                <div class="clear"></div>
            </div>

            <div class="details">
                <div class="col">
                    <strong>Dodavatel:</strong><br>
                    Visualy.cz<br>
                    Testovací ulice 123<br>
                    100 00 Praha<br>
                    IČ: 12345678<br>
                    DIČ: CZ12345678
                </div>
                <div class="col col-right">
                    <strong>Odběratel:</strong><br>
                    ' . ($billing['name'] ?? $user->getEmail()) . '<br>
                    ' . ($billing['street'] ?? '') . '<br>
                    ' . ($billing['city'] ?? '') . ' ' . ($billing['zip'] ?? '') . '<br>
                    ' . ($billing['country'] ?? '') . '<br><br>
                    IČ: ' . ($billing['ico'] ?? '-') . '<br>
                    DIČ: ' . ($billing['dic'] ?? '-') . '
                </div>
                <div class="clear"></div>
            </div>

            <table class="table">
                <thead>
                    <tr>
                        <th>Položka</th>
                        <th>Množství</th>
                        <th>Cena</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>' . $payment->getDescription() . '</td>
                        <td>1</td>
                        <td>' . number_format($payment->getAmount(), 2, ',', ' ') . ' ' . $payment->getCurrency() . '</td>
                    </tr>
                </tbody>
            </table>

            <div class="total">
                Celkem k úhradě: ' . number_format($payment->getAmount(), 2, ',', ' ') . ' ' . $payment->getCurrency() . '
            </div>
        </body>
        </html>';

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new \Symfony\Component\HttpFoundation\Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="faktura-' . $payment->getInvoiceNumber() . '.pdf"'
        ]);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            FormField::addPanel('Základní informace')->setIcon('fa fa-info-circle'),
            IdField::new('id')->hideOnForm(),
            AssociationField::new('user', 'Uživatel')->setColumns(6),
            MoneyField::new('amount', 'Částka')->setCurrency('CZK')->setStoredAsCents(false)->setColumns(6)->setDisabled(),
            TextField::new('description', 'Popis')->setColumns(12),

            FormField::addPanel('Stav platby')->setIcon('fa fa-tasks'),
            ChoiceField::new('status', 'Stav')
                ->setChoices([
                    'Čeká na platbu' => 'pending',
                    'Zaplaceno' => 'paid',
                    'Zrušeno' => 'cancelled',
                    'Selhalo' => 'failed',
                ])
                ->renderAsBadges([
                    'pending' => 'warning',
                    'paid' => 'success',
                    'cancelled' => 'secondary',
                    'failed' => 'danger',
                ])->setColumns(6),
            DateTimeField::new('paidAt', 'Zaplaceno')->setColumns(6),
            DateTimeField::new('createdAt', 'Vytvořeno')->hideOnForm()->setColumns(6),

            FormField::addPanel('Fakturace')->setIcon('fa fa-file-invoice'),
            TextField::new('invoiceNumber', 'Číslo faktury')->setColumns(12),

            FormField::addPanel('Systémové údaje')->setIcon('fa fa-cogs')->collapsible(),
            TextField::new('externalId', 'Externí ID (Comgate)')->hideOnIndex()->setColumns(12)->setDisabled(),
        ];
    }
}
