<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Telegram\Bot\Api;

class BotController extends AbstractController
{
    /**
     * @Route("/bot", name="bot")
     */
    public function index()
    {
        $telegram = new Api('1175350235:AAGbjjNDfrJlbmx9FUViD25EJsA7OIwKuhc');
        $result = $telegram->getWebhookUpdates();

        var_dump($telegram);
        die();
    }
}
