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
 * Class UpdateAllCommand
 *
 * @package Links\Command
 */

class UpdateAllCommand extends Command
{
    private $logger;
    private $statsModel;
    private $guzzleClient;
    private $config;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('stats:update_all')
            // the short description shown while running "php bin/console list"
            ->setDescription('Updates all the data from socialbreakers.com.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to update all facebook pages\' statistics that is recorded all ready.');
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
        $output->writeln(date('Y-m-d H:i:s') . ' stats:update_all command started');
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Model::factory($provider),
            $provider->get(GuzzleClient::class)
        );

        $this->updateAllData($output);
        $output->writeln(date('Y-m-d H:i:s') . ' stats:update_all command ended');
    }

    private function updateAllData(OutputInterface $output)
    {
        $data = $this->statsModel->getAllData();
        /**
         * @var array $data
         */
        foreach ($data['data'] as $item) {
            $response = $this->statsUpdateCommand($item['_id']);
            $output->writeln($response);
            sleep(1);
        }
    }
    private function statsUpdateCommand(string $id)
    {
        $command = $this->getApplication()->find('stats:update');
        $arguments = array(
            'command'   => 'stats:update',
            'id'      => $id
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return $content;
    }

    private function updateAll(array $link_info, array $data)
    {
        foreach ($data as $item) {
            $this->statsModel->update($link_info, $item);
        }
    }
}
