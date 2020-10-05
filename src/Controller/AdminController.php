<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\Status;
use App\Repository\StatusRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\LineChart;
use Doctrine\DBAL\Driver\PDOException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class AdminController extends BaseController
{
    /**
     * @Route("/admin", name="admin")
     */
    public function index()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }
        $sites = $this->getDoctrine()->getRepository(Site::class)->findAll();
        return $this->render('admin/list.html.twig', ['sites' => $sites]);
    }

    /**
     * @Route("/admin/new-site", name="new_site")
     */
    public function newSite(Request $request)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }
        $name = $request->get('name');
        $domain = $request->get('domain');
        $priority = $request->get('priority');

        if (!$name || !$domain) {
            return $this->json(['success' => false, 'error' => 'Name or domain is required']);
        }

        $site = new Site();
        $site->setName($name);
        $site->setDomain($domain);
        $site->setPriority($priority);

        $this->getDoctrine()->getManager()->persist($site);
        $this->getDoctrine()->getManager()->flush();

        return $this->json(['success' => true]);
    }

    /**
     * @Route("/admin/site-list", name="site_list")
     */
    public function siteList()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }
        $sites = $this->getDoctrine()->getRepository(Site::class)->findAll();
        return $this->render('admin/list.html.twig', ['sites' => $sites]);
    }

    /**
     * @Route("/admin/site-log/{site}/{period}/{code}", name="site_log", defaults={"period": "day", "code": "all"})
     */
    public function siteLog($site, $period)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }

        $statuses = $this
            ->getDoctrine()
            ->getRepository(Status::class)
            ->getLogsBySite($site, $period);

        $dateFormat = '';
        switch ($period) {
            case StatusRepository::PERIOD_DAY:
                $dateFormat = 'H:m:s';
                break;
            case StatusRepository::PERIOD_WEEK:
                $dateFormat = 'd M h:m';
                break;
            case StatusRepository::PERIOD_MONTH:
                $dateFormat = 'M Y';
                break;
            case StatusRepository::PERIOD_YEAR:
                $dateFormat = 'Y';
                break;
        }
        $chartArray = [['datetime', 'max latency(seconds)', 'average latency(seconds)']];
        foreach ($statuses as $status) {
            $chartArray[] = [$status['datetime']->format($dateFormat), round($status['max_latency'], 2), round($status['latency'], 2)];
        }

        $chart = new LineChart();
        $chart->getData()->setArrayToDataTable(
            $chartArray
        );

        $chart->getOptions()
            ->setHeight(700)
            ->setWidth(1400)
            ->setSeries([['axis' => 'Time']]);

        return $this->render('admin/log.html.twig', ['statuses' => $statuses, 'piechart' => $chart]);
    }

    /**
     * @Route("/admin/site-add", name="site_add")
     */
    public function addSite()
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }
        return $this->render('admin/add.html.twig');
    }

    /**
     * @Route ("/admin/site-edit/{siteId}", name="site_edit")
     */
    public function editSite($siteId, Request $request)
    {
        $site = $this->getDoctrine()->getRepository(Site::class)->find($siteId);

        if ($request->isXmlHttpRequest()) {
            $name = $request->get('name');
            $domain = $request->get('domain');
            $priority = $request->get('priority');

            if ($name && $domain && $priority) {
                try {
                    $site->setName($name);
                    $site->setDomain($domain);
                    $site->setPriority($priority);
                    $this->getDoctrine()->getManager()->persist($site);
                    $this->getDoctrine()->getManager()->flush();
                } catch (PDOException $exception) {
                    throw $exception;
                }
                return $this->json(['success' => true]);
            }

        }

        return $this->render('admin/edit.html.twig', ['site' => $site]);
    }

    /**
     * @Route ("/admin/statistic/{site}")
     */
    public function statistic($site)
    {
        $site = $this->getDoctrine()->getRepository(Site::class)->find($site);
        $logs = $this->getDoctrine()->getRepository(StatusRepository::class)->findBy(['log_site' => $site]);

        /** @var Status $log */
        foreach ($logs as $log) {
            $code[$log] = $log->getHttpCode();
        }
    }
}
