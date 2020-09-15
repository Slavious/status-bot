<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Borsaco\TelegramBotApiBundle\Service\Bot;


class BotController extends AbstractController
{
    /**
     * @Route("/bot", name="bot")
     */
    public function index(Bot $bot)
    {
        $firstBot = $bot->getBot('status');
        $me = $firstBot->getMe();
        $updateArray = $firstBot->getWebhookUpdate();
        var_dump($me);


        die();
    }
}
