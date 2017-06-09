<?php

namespace GirlsExtractor\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;


class ExtractPhotos extends Command
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('extract')
            ->setDescription('Seriously?')
            ->addArgument(
                'url',
                InputArgument::REQUIRED,
                'Please enter blog url'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        /**
         * @var $fs Filesystem
         * @var $http \GuzzleHttp\Client
         */

        $http = $this->container->get('http');
        $fs = $this->container->get('fs');

        $url = $input->getArgument('url');
        $url = rtrim($url, '/');
        $archive = $url . '/archive';

        $folder = 'var/' . preg_replace("/[^A-Za-z]/", null, $url);

        if (!$fs->exists($folder)) {
            $fs->mkdir($folder);
        }

        $fetched = [];
        if ($fs->exists($folder . '/fetched.php')) {
            $fetched = require_once $folder . '/fetched.php';
            $io->note('Already fetched list loaded.');
        }

        $continue = true;
        while ($continue) {
            $response = $http->get($archive);
            if ($response->getStatusCode() == 200) {
                $source = $response->getBody();

                preg_match('#id="next_page_link" href="(.*?)"#', $source, $link);
                $link = array_reverse($link);
                $link = reset($link);

                if (empty($link)) {
                    $continue = false;
                }

                $archive = $url . $link;

                preg_match_all('#\/post\/[0-9]*#', $source, $posts);
                foreach (reset($posts) as $post) {
                    if (!empty($fetched[$post])) {
                        $io->note('Post ' . $post . ' already checked, skipping');
                        continue;
                    }

                    $response = $http->get($url . $post);
                    if ($response->getStatusCode() == 200) {
                        $source = $response->getBody();

                        # Get all post photos
                        $regexp = '#[0-9]{1,3}.media.tumblr.com\/[A-Za-z0-9]*\/tumblr_[A-Za-z0-9]*_[0-9]*\.[a-z]*#';
                        preg_match_all($regexp, $source, $photos);
                        $photos = reset($photos);

                        if (!empty($photos)) {
                            $photos = array_unique($photos);
                            $grouped = [];
                            foreach ($photos as $photo) {
                                preg_match('#_([0-9]{1,4})\.[a-z]*#', $photo, $matches);
                                $matches = array_reverse($matches);
                                $resolution = reset($matches);
                                $grouped[$resolution] = $photo;
                            }

                            # Download max resolution photo
                            $resolutions = array_keys($grouped);
                            $bestResolution = max($resolutions);
                            $finalPhoto = $grouped[$bestResolution];

                            preg_match('#tumblr_[A-Za-z0-9]*_[0-9]*\.[a-z]*#', $finalPhoto, $photoName);
                            $photoName = reset($photoName);

                            #  Breaks downloaded image sometimes, fix if you want;
                            #  $resource = fopen($destination . '/' . $photoName, 'w');
                            #  $download = $http->request('GET', $finalPhoto, ['sink' => $resource]);

                            $result = true;
                            $content = file_get_contents('http://' . $finalPhoto);
                            $fs->dumpFile($folder . '/' . $photoName, $content);

                            if ($result) {
                                $io->success('Downloaded ' . $photoName);
                                $fetched[$post] = $photoName;

                                # Log is necessary after each downloaded image,
                                # otherwise exception will fuckup whole work done;
                                $content = var_export($fetched, true);
                                $content = '<?php return ' . $content . ';';
                                $fs->dumpFile($folder . '/fetched.php', $content);
                            } else {
                                $io->error('Failed to download ' . $photoName);
                            }
                        }
                    }
                }
            }
        }

        $io->success('Finished.');
    }
}