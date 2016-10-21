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
            ->setName('extract_photos')
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