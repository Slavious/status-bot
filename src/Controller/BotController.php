<?php

namespace App\Controller;

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
     * @Route ("/bot/webhook", name="webhook")
     */
    public function webhook(Bot $bot)
    {
        $siteStatus = new SiteStatus();
        $statusBot = $bot->getBot('status');
        $update = $statusBot->getWebhookUpdate();
        if (isset($update['message'])) {
            $chatId = $update["message"]["chat"]["id"];
            $message = $update["message"]["text"];

            $text = '';
            if (strpos($message, "/start") === 0) {
                foreach ($this->params->get('sites') as $domain) {
                    foreach ($domain as $name => $site) {
                        if (strpos($site, 'bikestore.cc') !== false || strpos($site, 'stage-bikemarket') !== false) {
                            $status = $siteStatus->getStatus($site);
                            switch ($status) {
                                case 0:
                                case 503:
                                case 403:
                                case 200:
                                    $text .= sprintf('Site "%s" answer with %s code.', $site, $status) . "\n\r";
                                    break;
                                default:
                                    $text = 'All is ok';
                                    break;
                            }
                        }
                    }
                }
                $statusBot->sendMessage(['chat_id' => $chatId, 'text' => $text]);
            }
        }
        exit();
    }

}
