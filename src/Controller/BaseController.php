<?php

namespace App\Controller;

use App\Entity\Site;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\VarDumper\VarDumper;

class BaseController extends AbstractController
{
    /**
     * @param string $view
     * @param array $parameters
     * @param Response|null $response
     * @return Response
     */
    public function render(string $view, array $parameters = [], Response $response = null) : Response
    {
        $siteLogs = $this->getDoctrine()->getRepository(Site::class)->findAll();
        $parameters = array_merge($parameters, ['siteLogs' => $siteLogs]);
        return parent::render($view, $parameters, $response);
    }

    /**
     * @Route("/", name="main")
     */
    public function main()
    {
        return $this->redirect('admin');
    }
}
