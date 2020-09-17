<?php

namespace App\Controller;

use App\Entity\Site;
use App\Entity\Status;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\AreaChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\CalendarChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\Histogram;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\LineChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\PieChart;
use CMEN\GoogleChartsBundle\GoogleCharts\Charts\ScatterChart;
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
     * @Route("/new-site", name="new_site")
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
     * @Route("/site-list", name="site_list")
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
     * @Route("/site-log/{site}", name="site_log")
     */
    public function siteLog($site)
    {
        $user = $this->container->get('security.token_storage')->getToken()->getUser();
        if (!$user) {
            return $this->redirect('login');
        }
        $statuses = $this
            ->getDoctrine()
            ->getRepository(Status::class)
            ->findBy([
                'log_site' => $site,
            ]);

        $chartArray = [['day', 'latency(seconds)']];
        foreach ($statuses as $status) {
            $chartArray[] = [$status->getDatetime()->format('m-d h:m'), round($status->getLatency(), 2)];
        }

        $chart = new \CMEN\GoogleChartsBundle\GoogleCharts\Charts\Material\LineChart();
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
     * @Route("/site-add", name="site_add")
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
