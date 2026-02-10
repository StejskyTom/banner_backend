<?php

namespace App\Controller\Admin;

use App\Entity\ArticleWidget;
use App\Entity\AuthorWidget;
use App\Entity\FaqWidget;
use App\Entity\HeurekaFeed;
use App\Entity\Payment;
use App\Entity\Subscription;
use App\Entity\User;
use App\Entity\Widget;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private ChartBuilderInterface $chartBuilder,
        private EntityManagerInterface $entityManager,
    ) {}

    public function index(): Response
    {
        $endDate = new \DateTimeImmutable('today');
        $startDate = $endDate->modify('-30 days');

        // --- Stat cards ---
        $totalUsers = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();

        $widgetEntities = [Widget::class, FaqWidget::class, ArticleWidget::class, AuthorWidget::class, HeurekaFeed::class];
        $totalWidgets = 0;
        foreach ($widgetEntities as $entity) {
            $totalWidgets += (int) $this->entityManager->getRepository($entity)
                ->createQueryBuilder('e')->select('COUNT(e.id)')->getQuery()->getSingleScalarResult();
        }

        $activeSubscriptions = $this->entityManager->getRepository(Subscription::class)
            ->createQueryBuilder('s')->select('COUNT(s.id)')
            ->where('s.status = :status')->setParameter('status', 'active')
            ->getQuery()->getSingleScalarResult();

        $totalRevenue = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')->select('COALESCE(SUM(p.amount), 0)')
            ->where('p.status = :status')->setParameter('status', 'paid')
            ->getQuery()->getSingleScalarResult();

        // --- Build date range ---
        $dates = [];
        $labels = [];
        $currentDate = $startDate;
        while ($currentDate <= $endDate) {
            $dates[] = $currentDate->format('Y-m-d');
            $labels[] = $currentDate->format('j.n.');
            $currentDate = $currentDate->modify('+1 day');
        }

        // --- Helper: map query results to date-indexed counts ---
        $mapCounts = function (array $queryResult) use ($dates): array {
            $indexed = [];
            foreach ($queryResult as $row) {
                $indexed[$row['date']] = (int) $row['count'];
            }
            return array_map(fn (string $d) => $indexed[$d] ?? 0, $dates);
        };

        // --- User registrations (line chart) ---
        $registrationData = $this->entityManager->getRepository(User::class)
            ->createQueryBuilder('u')
            ->select('SUBSTRING(u.createdAt, 1, 10) as date', 'COUNT(u.id) as count')
            ->where('u.createdAt >= :startDate')
            ->setParameter('startDate', $startDate)
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();

        $registrationsChart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $registrationsChart->setData([
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Registrace',
                'data' => $mapCounts($registrationData),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99,102,241,0.08)',
                'pointBackgroundColor' => '#6366f1',
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'tension' => 0.4,
                'fill' => true,
                'borderWidth' => 2.5,
            ]],
        ]);
        $registrationsChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1, 'font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['color' => 'rgba(148,163,184,0.1)'],
                    'border' => ['display' => false],
                ],
                'x' => [
                    'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 16, 'font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['display' => false],
                    'border' => ['display' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleFont' => ['size' => 13, 'weight' => '600'],
                    'bodyFont' => ['size' => 12],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => false,
                ],
            ],
        ]);

        // --- Widgets created (bar chart) — all 5 widget types summed ---
        $allWidgetCounts = array_fill_keys($dates, 0);
        foreach ($widgetEntities as $entity) {
            $rows = $this->entityManager->getRepository($entity)
                ->createQueryBuilder('e')
                ->select('SUBSTRING(e.createdAt, 1, 10) as date', 'COUNT(e.id) as count')
                ->where('e.createdAt >= :startDate')
                ->setParameter('startDate', $startDate)
                ->groupBy('date')
                ->orderBy('date', 'ASC')
                ->getQuery()
                ->getResult();
            foreach ($rows as $row) {
                if (isset($allWidgetCounts[$row['date']])) {
                    $allWidgetCounts[$row['date']] += (int) $row['count'];
                }
            }
        }

        $widgetsChart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $widgetsChart->setData([
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Widgety',
                'data' => array_values($allWidgetCounts),
                'backgroundColor' => 'rgba(14,165,233,0.7)',
                'hoverBackgroundColor' => 'rgba(14,165,233,0.9)',
                'borderRadius' => 6,
                'borderSkipped' => false,
                'barPercentage' => 0.6,
                'categoryPercentage' => 0.7,
            ]],
        ]);
        $widgetsChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['stepSize' => 1, 'font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['color' => 'rgba(148,163,184,0.1)'],
                    'border' => ['display' => false],
                ],
                'x' => [
                    'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 16, 'font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['display' => false],
                    'border' => ['display' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleFont' => ['size' => 13, 'weight' => '600'],
                    'bodyFont' => ['size' => 12],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => false,
                ],
            ],
        ]);

        // --- Subscription plan (doughnut chart): paid vs trial ---
        $planStats = $this->entityManager->getRepository(Subscription::class)
            ->createQueryBuilder('s')
            ->select('s.plan', 'COUNT(s.id) as count')
            ->where('s.status = :status')
            ->setParameter('status', 'active')
            ->groupBy('s.plan')
            ->getQuery()
            ->getResult();

        $planLabels = [];
        $planCounts = [];
        $planColors = [];
        $colorMap = [
            'monthly' => '#6366f1',
            'trial' => '#f59e0b',
        ];
        $labelMap = [
            'monthly' => 'Placené',
            'trial' => 'Trial',
        ];
        foreach ($planStats as $row) {
            $plan = $row['plan'];
            $planLabels[] = $labelMap[$plan] ?? ucfirst($plan);
            $planCounts[] = (int) $row['count'];
            $planColors[] = $colorMap[$plan] ?? '#cbd5e1';
        }

        $subscriptionsChart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $subscriptionsChart->setData([
            'labels' => $planLabels ?: ['Žádné'],
            'datasets' => [[
                'data' => $planCounts ?: [1],
                'backgroundColor' => $planColors ?: ['#e2e8f0'],
                'borderWidth' => 0,
                'hoverOffset' => 6,
            ]],
        ]);
        $subscriptionsChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'cutout' => '72%',
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 16,
                        'usePointStyle' => true,
                        'pointStyle' => 'circle',
                        'font' => ['size' => 12],
                        'color' => '#64748b',
                    ],
                ],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleFont' => ['size' => 13, 'weight' => '600'],
                    'bodyFont' => ['size' => 12],
                    'padding' => 12,
                    'cornerRadius' => 8,
                ],
            ],
        ]);

        // --- Revenue (line chart, last 30 days) ---
        $revenueData = $this->entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->select('SUBSTRING(p.paidAt, 1, 10) as date', 'SUM(p.amount) as count')
            ->where('p.paidAt >= :startDate')
            ->andWhere('p.status = :status')
            ->setParameter('startDate', $startDate)
            ->setParameter('status', 'paid')
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->getQuery()
            ->getResult();

        $revenueChart = $this->chartBuilder->createChart(Chart::TYPE_LINE);
        $revenueChart->setData([
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Příjmy (CZK)',
                'data' => $mapCounts($revenueData),
                'borderColor' => '#10b981',
                'backgroundColor' => 'rgba(16,185,129,0.08)',
                'pointBackgroundColor' => '#10b981',
                'pointBorderColor' => '#fff',
                'pointBorderWidth' => 2,
                'pointRadius' => 4,
                'pointHoverRadius' => 6,
                'tension' => 0.4,
                'fill' => true,
                'borderWidth' => 2.5,
            ]],
        ]);
        $revenueChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
            'interaction' => ['intersect' => false, 'mode' => 'index'],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => ['font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['color' => 'rgba(148,163,184,0.1)'],
                    'border' => ['display' => false],
                ],
                'x' => [
                    'ticks' => ['maxRotation' => 0, 'autoSkipPadding' => 16, 'font' => ['size' => 11], 'color' => '#94a3b8'],
                    'grid' => ['display' => false],
                    'border' => ['display' => false],
                ],
            ],
            'plugins' => [
                'legend' => ['display' => false],
                'tooltip' => [
                    'backgroundColor' => '#1e293b',
                    'titleFont' => ['size' => 13, 'weight' => '600'],
                    'bodyFont' => ['size' => 12],
                    'padding' => 12,
                    'cornerRadius' => 8,
                    'displayColors' => false,
                ],
            ],
        ]);

        return $this->render('admin/dashboard.html.twig', [
            'chart_registrations' => $registrationsChart,
            'chart_widgets' => $widgetsChart,
            'chart_subscriptions' => $subscriptionsChart,
            'chart_revenue' => $revenueChart,
            'total_users' => $totalUsers,
            'total_widgets' => $totalWidgets,
            'active_subscriptions' => $activeSubscriptions,
            'total_revenue' => $totalRevenue,
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Visualy Admin')
            ->setLocales(['cs']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Nástěnka', 'fa fa-home');
        yield MenuItem::linkToCrud('Uživatelé', 'fas fa-users', User::class);
        yield MenuItem::linkToCrud('Platby', 'fas fa-money-bill-wave', Payment::class);
    }
}
