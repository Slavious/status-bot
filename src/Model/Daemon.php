<?php

namespace App\Model;

use App\Model\SiteStatus;
use Borsaco\TelegramBotApiBundle\Service\Bot;

class Daemon
{

    public function process($object, $bot)
    {
        $stop = false;

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Error fork process' . PHP_EOL);
        } elseif ($pid) {
            die('Die parent process' . PHP_EOL);
        } else {
            while(!$stop) {
                $object->process($bot);
                sleep(5);
            }
        }

        posix_setsid();
    }
}