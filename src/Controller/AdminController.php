<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Borsaco\TelegramBotApiBundle\Service\Bot;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\VarDumper\VarDumper;

class AdminController extends AbstractController
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
        return $this->render('admin/index.html.twig');
    }
}
