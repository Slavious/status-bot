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

class ClearLogDb extends Command
{
    protected static $defaultName = 'daemon:clear:log';

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
            ->setDescription('');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Deleting');
        $this->doctrine->createQuery('DELETE App:Status s')->execute();
        $output->writeln('Done');

        return 0;
    }
}