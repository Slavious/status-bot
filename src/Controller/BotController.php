<?php

namespace App\Controller;

use App\Entity\TelegramAccount;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Borsaco\TelegramBotApiBundle\Service\Bot;
use Symfony\Component\VarDumper\VarDumper;
use App\Model\SiteStatus;


class BotController extends AbstractController
{
    private $params;

    public function __construct(ParameterBagInterface $bag)
    {
        $this->params = $bag;
    }

    /**
     * @Route("/bot", name="bot")
     */
    public function index(Bot $bot)
    {
        $firstBot = $bot->getBot('status');
//        $mess = $firstBot->sendMessage(['chat_id' => '@u-charged notification', 'text' => '1111']);
        $updateArray = $firstBot->getWebhookUpdate(true);
        VarDumper::dump($updateArray);


        die();
    }

    /**
     * @Route ("/bot/check-updates", name="webhook")
     */
    public function webhook(Bot $bot)
    {
        $statusBot = $bot->getBot('status');
        $update = $statusBot->getUpdates();

        if ($update) {
            foreach ($update as $item) {
                $chatId = $item["message"]["chat"]["id"];
                $title = $item['message']['chat']['title'];

                $chatExists = $this->getDoctrine()->getRepository(TelegramAccount::class)->isChatExists($chatId);
                if (!$chatExists) {
                    $telegramAccount = new TelegramAccount();
                    $telegramAccount->setName($title);
                    $telegramAccount->setChatId($chatId);
                    $telegramAccount->setActive(true);

                    $this->getDoctrine()->getManager()->persist($telegramAccount);
                    $this->getDoctrine()->getManager()->flush();
                }
            }
        }
    }

}
