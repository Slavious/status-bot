<?php

namespace App\Command;

use App\Entity\Site;
use App\Entity\TelegramAccount;
use App\Model\SiteStatus;
use Borsaco\TelegramBotApiBundle\Service\Bot;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Api;

class DaemonStatusCommand extends Command
{
    protected static $defaultName = 'daemon:status';

    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
    }

    protected function configure()
    {
        $this
            ->setDescription('Daemonize telegram bot')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bot = new Bot($this->container);
        $statusBot = $bot->getBot('status');

        $this->process($statusBot);

        return Command::SUCCESS;
    }

    private function process(Api $statusBot)
    {
        /** @var EntityManager $doctrine */
        $doctrine = $this->container->get('doctrine')->getManager();
        $sites = $doctrine->getRepository(Site::class)->findAll();
        $chat = $doctrine->getRepository(TelegramAccount::class)->find(1);

        /** @var Site $site */
        foreach ($sites as $site) {
            $statuses = new SiteStatus();
            $status = $statuses->getStatus($site->getDomain());
            switch ($status) {
                case 200:
                    $statusBot->sendMessage(['chat_id' => $chat->getChatId(), 'text' => 'test']);
            }
        }
    }
}
