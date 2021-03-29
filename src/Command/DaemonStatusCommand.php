<?php

namespace App\Command;

use App\Entity\Site;
use App\Entity\Status;
use App\Entity\TelegramAccount;
use App\Model\Daemon;
use App\Model\SiteStatus;
use App\Repository\StatusRepository;
use Borsaco\TelegramBotApiBundle\Service\Bot;
use DateTime;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Facades\Date;
use PDOException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\VarDumper\VarDumper;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;
use function Symfony\Component\String\b;

class DaemonStatusCommand extends Command
{
    protected static $defaultName = 'daemon:status';

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManager
     */
    private $doctrine;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $logDir;

    /**
     * DaemonStatusCommand constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;
        $this->doctrine = $this->container->get('doctrine')->getManager();
        $this->logDir = $this->container->get('kernel')->getLogDir();
    }

    /**
     *
     */
    protected function configure()
    {
        $this
            ->setDescription('Daemonize telegram bot');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $bot = new Bot($this->container);
        $statusBot = $bot->getBot('status');
        $this->filesystem = $this->container->get('filesystem');

        $runningFile = $this->logDir . '/status.run';

        if (!$this->filesystem->exists($runningFile)) {
            file_put_contents($runningFile, (new DateTime())->format('Y-m-d H:m:s'));
            $output->writeln('Proccess started');
            if ($this->process($statusBot, $output)) {
                $output->writeln('Proccess finished');
                @unlink($runningFile);
            }
        } else {
            $output->writeln('Proccess is already running');
            $fileCreated = fgets(fopen($runningFile, 'r'));
            $dateCreated = false;
            if ($fileCreated !== '') {
                $dateCreated = new DateTime($fileCreated);
            }
            $now = new DateTime();
            $dateDiff = $now->diff($dateCreated);
            if ($dateDiff->i >= 5 || !$fileCreated) {
                unlink($runningFile);
            }
        }
        return Command::SUCCESS;
    }

    /**
     * @param Api $statusBot
     * @param OutputInterface $output
     * @throws TelegramSDKException
     */
    private function process(Api $statusBot, OutputInterface $output)
    {
        /** @var EntityManager $doctrine */
        $sites = $this->doctrine->getRepository(Site::class)->findAll();
        //TODO Rework this hardcode shit
        $chat = $this->doctrine->getRepository(TelegramAccount::class)->find(1);

        /** @var Site $site */
        foreach ($sites as $site) {
            $statusModel = new SiteStatus($site->getDomain());
            $currentStatus = $statusModel->getStatus();
            $latency = $statusModel->getLatency();
            $content = strip_tags($statusModel->getContent());
            /** @var Status $lastStatus */
            $lastStatus = $site->getLogStatuses()->last();
            $lastFailedStatus = $this->doctrine->getRepository(Status::class)->getFailedLastStatus($site);

            if (!$lastStatus) {
                $consoleLogMessage = sprintf('Site %s creating first record', $site->getName());
                $output->writeln($consoleLogMessage);
                $this->log($currentStatus, $latency, $site, $content);
                continue;
            }

            $consoleLogMessage = sprintf('Site %s ', $site->getName());
            $output->writeln($consoleLogMessage);

            switch ($currentStatus) {
                case StatusRepository::CODE_OK:
                    $this->log($currentStatus, $latency, $site, $content);
                    if ($this->checkContentIsError($content)) {
                        $text = sprintf('Exception or Error exists on page %site', $site->getDomain()) . "\n\r";
                        $this->sendMessage($statusBot, $site, $chat, $text);
                    } else {
                        if ($lastStatus->getHttpCode() !== 200) {
                            if ($lastFailedStatus) {
                                $now = new DateTime('now');
                                $downTimeDiff = $now->diff($lastFailedStatus->getDatetime());
                                $days = $downTimeDiff->d;
                                $hours = $downTimeDiff->h;
                                $minutes = $downTimeDiff->i <= 10 ? 0 : $downTimeDiff->i;
                                $seconds = $downTimeDiff->s;
                                $downtime = sprintf('%s days, %s hours, %s minutes, %s seconds', $days, $hours, $minutes, $seconds);
                                $text = sprintf('Site "%s" is currenty UP. Downtime: %s', $site->getDomain(), $downtime) . "\n\r";
                                $output->writeln($text);
                            } else {
                                $text = sprintf('Site "%s" is currenty UP.', $site->getDomain()) . "\n\r";
                            }
                            $this->sendMessage($statusBot, $site, $chat, $text);
                        }
                    }
                    break;

//                case StatusRepository::CODE_SERVER_0:
                case StatusRepository::CODE_SERVER_500:
                case StatusRepository::CODE_SERVER_502:
                case StatusRepository::CODE_SERVER_503:
                case StatusRepository::CODE_SERVER_504:
                    if ($lastStatus->getHttpCode() === 200) {
                        $this->log($currentStatus, $latency, $site, $content);
                        $text = sprintf('Site "%s" answer with %s code. Time to response %s.', $site->getDomain(), $currentStatus, $latency) . "\n\r";
                        $output->writeln($text);
                        $this->sendMessage($statusBot, $site, $chat, $text);
                    }
                    break;
            }
        }
        return true;
    }

    private function checkContentIsError($content)
    {
        if ($content) {
            return preg_match('/(Exception)|(Error)|(Report)/m', $content);
        }
        return false;
    }

    /**
     * @param int $httCode
     * @param string $latency
     * @param Site $site
     * @param string $content
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function log(int $httCode, string $latency, Site $site, $content = null)
    {
        $log = new Status();
        $log->setDatetime(new DateTime());
        $log->setHttpCode($httCode);
        $log->setLatency($latency);
        $log->setLogSite($site);
        $log->setContent($content);

        try {
            $this->doctrine->persist($log);
            $this->doctrine->flush();
        } catch (PDOException $exception) {
            var_dump($exception->getMessage());
        }
    }

    /**
     * @param Api $statusBot
     * @param Site $site
     * @param TelegramAccount $chat
     * @param string $text
     */
    public function sendMessage(Api $statusBot, Site $site, TelegramAccount $chat, string $text)
    {
        if ($site->getPriority() === 3) {
            $statusBot->sendMessage(['chat_id' => $chat->getChatId(), 'text' => $text]);
        }
    }
}
