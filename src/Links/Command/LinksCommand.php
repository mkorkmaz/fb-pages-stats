<?php

/**
 * PHP version 7
 *
 * @category Command
 * @package  Users
 * @author   Mehmet Korkmaz <mehmet@mkorkmaz.com>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     https://github.com/mkorkmaz/social-media-stats
 */

declare(strict_types = 1);

namespace Links\Command;

use Links\Model;
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
 * Class LinksCommand
 *
 * @package Links\Command
 */

class LinksCommand extends Command
{
    private $logger;
    private $linksModel;
    private $guzzleClient;
    private $config;

    protected function configure()
    {
        $this
            // the name of the command (the part after "bin/sma")
            ->setName('links:get')
            // the short description shown while running "php bin/sma list"
            ->setDescription('Gets links from socialbreakers.com.')
            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp('This command allows you to get facebook pages statistics links for industries and countries.');
    }

    private function setServices(
        Monolog\Logger $logger,
        Model $linksModel,
        GuzzleClient $guzzleClient
    ) {
        $this->logger = $logger;
        $this->linksModel = $linksModel;
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
        $this->config = $provider->get('config');
        $baseLink = $this->config['root'];
        $request = $this->guzzleClient->get($baseLink);
        $pageContent = trim($request['response']->getContents());
        $countries = $this->aggregateCountries($pageContent);
        $countries[]=['', 'All'];
        $industries = $this->aggregateIndustries($pageContent);
        $industries[]=['all', 'All',''];
        $this->insertLinks($baseLink, $countries, $industries);
    }

    private function aggregateCountries(string $pageContent)
    {
        preg_match('#Select Country</option>(.*?)</select>#msi', $pageContent, $comboBoxMatches);
        preg_match_all(
            '#<option value="(.*?)"(.*?)>(.*?)</option>#msi',
            $comboBoxMatches[0],
            $countryMatches,
            PREG_SET_ORDER
        );
        $countries = [];
        /**
         * @var array $countryMatches
         */
        foreach ($countryMatches as $match) {
            $countries[]=[
                trim(strip_tags($match[1])),
                trim(strip_tags($match[3]))
            ];
        }
        return $countries;
    }

    private function aggregateIndustries(string $pageContent)
    {
        preg_match(
            '#<div class="multi-dropdown">(.*?)</div>(\s+)</div>(\s+)</div>#msi',
            $pageContent,
            $industriesListMatches
        );

        preg_match_all(
            '#<a(.*?)href="(.*?)"(.*?)data-tag="(.*?)"(.*?)>(.*?)</a>#msi',
            $industriesListMatches[0],
            $industriesMatches,
            PREG_SET_ORDER
        );

        $industries = [];
        /**
         * @var array $industriesMatches
         */
        foreach ($industriesMatches as $match) {
            $industries[] = [
                trim(strip_tags($match[4])), // slug
                trim(strip_tags($match[6])), // Industry Name
                str_replace('/statistics/facebook/pages/total/', '', trim(strip_tags($match[2]))) //Link
            ];
        }
        return $industries;
    }

    private function insertLinks(string $baseLink, array $countries, array $industries)
    {
        foreach ($countries as $country) {
            foreach ($industries as $industry) {
                $withIndustryLink = $baseLink . str_replace('//', '/', '/'
                        . $country[0] .'/'. $industry[2]);
                if (empty($country[1])) {
                    $country_slug = 'all';
                } else {
                    $country_slug = $country[1];
                }
                $this->linksModel->add($industry[2], $industry[1], $country[1], $country_slug, $withIndustryLink);
            }
        }
    }

    private function runTwitterUserStatsCommand(string $twitterUserName)
    {
        $command = $this->getApplication()->find('twitter:user_stats');
        $arguments = array(
            'command'   => 'twitter:user_stats',
            'username'  => $twitterUserName,
            '--return'  => 1
        );
        $userStatsInput = new ArrayInput($arguments);
        $buffer = new BufferedOutput();
        $command->run($userStatsInput, $buffer);
        $content = $buffer->fetch();
        return json_decode(trim($content), true);
    }
}
