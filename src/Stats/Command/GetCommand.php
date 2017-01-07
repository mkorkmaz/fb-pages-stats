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
 * Class GetCommand
 *
 * @package Stats\Command
 */

class GetCommand extends Command
{
    private $logger;
    private $statsModel;
    private $guzzleClient;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/console")
            ->setName('stats:get')
            // the short description shown while running "php bin/console list"
            ->addArgument('link', InputArgument::REQUIRED, 'Link to be used to collect the data. ')
            ->addOption(
                'return',
                'r',
                InputOption::VALUE_OPTIONAL,
                'Returns data instead of printing.',
                0
            )
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
        $provider = ServiceProvider::getInstance();
        $this->setServices(
            $provider->get(Monolog\Logger::class),
            Model::factory($provider),
            $provider->get(GuzzleClient::class)
        );

        $link = $input->getArgument('link');
        $return = $input->getOption('return');

        $request = $this->guzzleClient->get($link);

        $pageContent = trim($request['response']->getContents());

        $items  = [];
        if ($this->verifyLink($link, $pageContent)) {
            $items = $this->aggregateItems($pageContent);
        }
        if ($return === 0) {
            var_dump($items);
        } else {
            $output->writeln(json_encode($items));
        }
    }

    private function verifyLink($link, $pageContent)
    {
        preg_match('#<meta property="og:url" content="(.*?)">#msi', $pageContent, $match);
        return ($match[1] === $link);
    }

    private function aggregateItems(string $pageContent)
    {
        preg_match('#<table class="brand-table-list"(.*?)</table>#msi', $pageContent, $tableMatches);

        preg_match_all(
            '#<tr(.*?)href="(.*?)"(.*?)src="(.*?)"(.*?)'
            . '<i class="item-count">(.*?)</i>(.*?)<h2><span>(.*?)</span>'
            . '</h2>(.*?)<strong>(.*?)</strong>(.*?)</tr>#msi',
            $tableMatches[0],
            $trMatches,
            PREG_SET_ORDER
        );
        $items = [];
        /**
         * @var array $trMatches
         */
        foreach ($trMatches as $match) {
            $items[] = [
                trim(strip_tags($match[2])),
                trim(strip_tags($match[4])),
                (int) trim(strip_tags($match[6])),
                trim(strip_tags($match[8])),
                (int) trim(str_replace('&nbsp;', '', strip_tags($match[10])))
            ];
        }
        return $items;
    }
}
