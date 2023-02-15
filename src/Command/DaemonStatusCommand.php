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
use Telegram\Bot\Exceptions\TelegramResponseException;
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

        /** @var Site $site */
        foreach ($sites as $site) {
            $chat = $site->getTelegramGroup();
            $statusModel = new SiteStatus($site->getDomain());
            $currentStatus = $statusModel->getStatus();
            $latency = $statusModel->getLatency();
            $content = $statusModel->getContent();
            /** @var Status $lastStatus */
            $lastStatus = $site->getLogStatuses()->last();
            $lastFailedStatus = $this->doctrine->getRepository(Status::class)->getFailedLastStatus($site);

            if (!$lastStatus) {
                $consoleLogMessage = sprintf('Site %s creating first record', $site->getName());
                $output->writeln($consoleLogMessage);
                $this->log($currentStatus, $latency, $site, $content);
                continue;
            }

            if (!$statusModel->getStatus() || $statusModel->getError()) {
                $message = sprintf('Site %s timeout expired! %s', $site->getName(), $site->getDomain());
                $this->sendMessage($statusBot, $site, $chat, $message);
            }

            $consoleLogMessage = sprintf('Site %s ', $site->getName());
            $output->writeln($consoleLogMessage);

            switch ($currentStatus) {
                case StatusRepository::CODE_OK:
                    $this->log($currentStatus, $latency, $site, $content);
                    if (
                        ($message = $this->checkContentIsError($content))
                        && $chat->getId() === 1
                        && $site->getDomainName() !== 'pizzastores.de'
                        && $site->getDomainName() !== 'pizzastores.de'
                        && $site->getDomainName() !== 'system.meisterpizza.de'
                    ) {
                        $text = sprintf($message, $site->getDomain());
                        $this->sendMessage($statusBot, $site, $chat, $text);
                    } else {
                        /*if (
                            $lastStatus->getHttpCode() !== 200
                            or strpos($content, "Magento") === false
                        ) {
                            if ($lastFailedStatus) {
                                $now = new DateTime('now');
                                $downTimeDiff = $now->diff($lastFailedStatus['datetime']);
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
                        }*/
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
                    } else {
                        $text = sprintf('Site "%s" answer with %s code. Time to response %s.', $site->getDomain(), $currentStatus, $latency) . "\n\r";
                    }
                $this->sendMessage($statusBot, $site, $chat, $text);
                break;
            }
        }

        $this->checkImports($statusBot);

        return true;
    }

    public function checkImports($statusBot)
    {
        $sites = $this->doctrine->getRepository('App:Site')->findAll();
        /** @var Site $site */
        foreach ($sites as $site) {
            $chat = $site->getTelegramGroup();
            if (stripos($site->getDomain(), 'cube-store') !== false) continue;
            $url = "https://".$site->getDomainName()."/import?bot";
            $siteStatus = new SiteStatus($url);
            $content = $siteStatus->getContent();
            if ($content && $site->getPriority() === 3 && stripos($content, 'not working!!!') !== false) {
                $this->sendMessageImport($statusBot, $url, $chat, $content . ' on ' . $site->getDomainName());
            }
        }
    }

    const CATEGORY_EMPTY_STRING = '<div>Leider können wir keine passenden Produkte zu ihrer Auswahl finden.</div>';

    /**
     * @param $content
     * @return bool|int|void
     */
    private function checkContentIsError($content)
    {

        if ($content) {
            /*if (stripos($content, self::CATEGORY_EMPTY_STRING) !== false) {
                return 'Category page is empty! %s';
            }
            if (preg_match('/(Exception)|(Error)|(Report)/m', $content)) {
                return 'Exception or Error exists on page %s';
            }*/
            if (stripos($content, 'Magento') === false) {
                return 'Magento is not find on page %s';
            }
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
            try {
                $statusBot->sendMessage(['chat_id' => $chat->getChatId(), 'text' => $text]);
            } catch (TelegramResponseException $exception) {
                var_dump($exception->getMessage());
            }
        }
    }

    /**
     * @param Api $statusBot
     * @param string $site
     * @param TelegramAccount $chat
     * @param string $text
     */
    public function sendMessageImport(Api $statusBot, $site, TelegramAccount $chat, string $text)
    {
        $statusBot->sendMessage(['chat_id' => $chat->getChatId(), 'text' => $text]);
    }
}
