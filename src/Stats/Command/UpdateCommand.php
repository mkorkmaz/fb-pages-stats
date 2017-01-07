<?php

/**
 * PHP version 7
 *
 * @category Command
 * @package  Stats
 * @author   Mehmet Korkmaz <mehmet@mkorkmaz.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/mkorkmaz/social-media-stats
 */

declare(strict_types = 1);

namespace Stats\Command;

use Stats\Model;
use ServiceProvider;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\BufferedOutput;
use Monolog;
use GuzzleClient;

/**
 * Class UpdateCommand
 *
 * @package Update\Command
 */

class UpdateCommand extends Command
{
    private $logger;
    private $statsModel;
    private $guzzleClient;
    private $config;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('stats:update')
            // the short description shown while running "php bin/console list"
            ->addArgument('id', InputArgument::REQUIRED, 'ES id to get the Link info to be used to collect the data. ')
            ->setDescription('Updates data from socialbreakers.com.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to update facebook pages statistics that is recorded all ready.');
    }

    private function setServices(
        Monolog\Logger $logger,
        Model $statsModel,
        GuzzleClient $guzzleClient
    ) {
        $this->logger = $logger;
        $this->statsModel = $statsModel;
        $this->guzzleClient = $guzzleClient;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln(date('Y-m-d H:i:s') . ' stats:update command started');
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Model::factory($provider),
            $provider->get(GuzzleClient::class)
        );
        $id = $input->getArgument('id');
        $linkInfo = $this->statsModel->get($id);
        $output->writeln(date('Y-m-d H:i:s') . sprintf(' Requesting data from: %s', $linkInfo['link']));
        $data = $this->statsGetCommand($linkInfo['link']);
        $this->updateData($output, $linkInfo, $data);
        $output->writeln(date('Y-m-d H:i:s') . sprintf(' %s record updated', count($data)));
        $output->writeln(date('Y-m-d H:i:s') . ' stats:update command ended');
    }

    private function statsGetCommand(string $link)
    {
        $command = $this->getApplication()->find('stats:get');
        $arguments = array(
            'command'   => 'stats:get',
            'link'      => $link,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }

    private function updateData(OutputInterface $output, array $link_info, array $data)
    {
        foreach ($data as $item) {
            $r = $this->statsModel->update($link_info, $item);
            $output->writeln($r);
        }
    }
}
