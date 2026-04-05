<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class GenerateRepositoriesCommand extends Command
{
    protected static $defaultName = 'app:generate-repositories';

    private $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();

        $this->filesystem = $filesystem;
    }

    protected function configure()
    {
        $this
            ->setDescription('Generates repository classes for all entities.')
            ->setHelp('This command will generate repository classes for all entities in src/Entity.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Generating repositories for all entities...');

        // Create Finder manually (IMPORTANT FIX)
        $finder = new Finder();
        $finder->files()->in('src/Entity')->name('*.php');

        foreach ($finder as $file) {

            $entityClass = $file->getBasename('.php');
            $repositoryClass = 'App\\Repository\\' . $entityClass . 'Repository';

            $repositoryCode = <<<PHP
<?php

namespace App\Repository;

use App\Entity\\$entityClass;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class {$entityClass}Repository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, $entityClass::class);
    }
}
PHP;

            $repositoryPath = 'src/Repository/' . $entityClass . 'Repository.php';

            if (!$this->filesystem->exists($repositoryPath)) {
                $this->filesystem->dumpFile($repositoryPath, $repositoryCode);
                $output->writeln("Generated repository: $repositoryClass");
            } else {
                $output->writeln("Repository already exists for: $repositoryClass");
            }
        }

        $output->writeln('Repository generation complete!');

        return Command::SUCCESS;
    }
}
