<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\Status;
use App\Repository\StatusRepository;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\LineChart;
use Symfony\Component\HttpFoundation\Request;
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
     * @Route("/admin/site-log/{site}/{period}", name="site_log", defaults={"period": "hour"})
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
            case StatusRepository::PERIOD_HOUR:
                $dateFormat = 'H:m:s';
                break;
            case StatusRepository::PERIOD_DAY:
                $dateFormat = 'H:m';
                break;
            case StatusRepository::PERIOD_MONTH:
                $dateFormat = 'M Y';
                break;
            case StatusRepository::PERIOD_YEAR:
                $dateFormat = 'Y';
                break;
        }

        $chartArray = [['datetime', 'latency(seconds)']];
        foreach ($statuses as $status) {
            $chartArray[] = [$status['datetime']->format($dateFormat), round($status['latency'], 2)];
        }

        $chart = new LineChart();
        $chart->getData()->setArrayToDataTable(
            $chartArray
        );

        $chart->getOptions()
            ->setHeight(400)
            ->setWidth(900)
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
}
