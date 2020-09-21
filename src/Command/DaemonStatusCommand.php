<?php

namespace App\Command;

use App\Entity\Site;
use App\Entity\Status;
use App\Entity\TelegramAccount;
use App\Model\Daemon;
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

    private $doctrine;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
        $this->doctrine = $this->container->get('doctrine')->getManager();
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

        $this->process($statusBot, $output);

        return Command::SUCCESS;
    }

    private function process(Api $statusBot, OutputInterface $output)
    {
        /** @var EntityManager $doctrine */
        $sites = $this->doctrine->getRepository(Site::class)->findAll();
        $chat = $this->doctrine->getRepository(TelegramAccount::class)->find(1);

        $text = '';
        /** @var Site $site */
        foreach ($sites as $site) {
            $statuses = new SiteStatus($site->getDomain());
            $status = $statuses->getStatus();
            $latency = $statuses->getLatency();
            if ($status !== 200) {
                $text = sprintf('Site "%s" answer with %s code. Time to response %s.', $site->getDomain(), $status, $latency) . "\n\r";
                $output->writeln($text);
                if ($site->getPriority() === 3) {
                    $statusBot->sendMessage(['chat_id' => $chat->getChatId(), 'text' => $text]);
                }
            }
            $this->log($status, $latency, $site);
        }
    }

    public function log($httCode, $latency, $site)
    {
        $log = new Status();
        $log->setDatetime(new \DateTime());
        $log->setHttpCode($httCode);
        $log->setLatency($latency);
        $log->setLogSite($site);

        try {
            $this->doctrine->persist($log);
            $this->doctrine->flush();
        } catch (\PDOException $exception) {

        }

    }
}
