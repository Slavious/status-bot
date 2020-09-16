<?php

namespace App\Model;

use App\Model\SiteStatus;
use Borsaco\TelegramBotApiBundle\Service\Bot;

class Daemon
{
    private $params;

    public function process()
    {
        $stop = false;

        $pid = pcntl_fork();
        if ($pid == -1) {
            die('Error fork process' . PHP_EOL);
        } elseif ($pid) {
            die('Die parent process' . PHP_EOL);
        } else {
            while(!$stop) {



            }
        }

        posix_setsid();
    }
}