<?php

namespace GirlsExtractor\Command;

use Illuminate\Database\Capsule\Manager;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
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
                'Please set archive url'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $http = $this->container->get('http');

        $url = $input->getArgument('url');
        $url = rtrim($url, '/');
        $archive = $url . '/archive';

        $folder = __DIR__ . '/../../../var/';
        $destination = $folder . preg_replace("/[^A-Za-z]/", null, $url);

        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }

        $continue = true;
        while($continue) {
            $response = $http->get($archive);
            if ($response->getStatusCode() == 200) {
                $source = $response->getBody();

                preg_match('#id="next_page_link" href="(.*?)"#', $source, $link);
                $link = reset(array_reverse($link));

                if(empty($link)) {
                    $continue = false;
                }

                $archive = $url . $link;

                preg_match_all('#\/post\/[0-9]*#', $source, $posts);
                foreach (reset($posts) as $post) {
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
                                $resolution = reset(array_reverse($matches));
                                $grouped[$resolution] = $photo;
                            }

                            # Download max resolution photo
                            $resolutions = array_keys($grouped);
                            $bestResolution = max($resolutions);
                            $finalPhoto = $grouped[$bestResolution];

                            preg_match('#tumblr_[A-Za-z0-9]*_[0-9]*\.[a-z]*#', $finalPhoto, $photoName);
                            $photoName = reset($photoName);

                            /*  Brokes image, fix if you want;
                                $resource = fopen($destination . '/' . $photoName, 'w');
                                $download = $http->request('GET', $finalPhoto, ['sink' => $resource]);
                            */

                            file_put_contents($destination . '/' . $photoName, file_get_contents('http://' . $finalPhoto));
                        }
                    }
                }
            }
        }



//        $io->progressStart(count($keys));
//
//        foreach ($keys as $key) {
//            $io->progressAdvance(1);
//            $io->text('Searching for ' . $formatter->truncate($key, 75));
//
//            $io->newLine();
//            $io->note('Found in ' . $unit->getRealPath());
//        }
//        $io->progressFinish();
//        $io->success(['Translations found in code: ' . count($found), 'Status saved to var/found.php']);

//        $found = array_unique($found);
//        $content = var_export($found, true);
//        $content = '<?php return ' . $content . ';';
//        $fileSystem->dumpFile('var/found.php', $content);

    }
}