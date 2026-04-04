<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Symfony\Component\Console\Attribute\AsCommand;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

#[AsCommand(
    name: 'app:generate:entities',
    description: 'Automatically generates entity classes from the database schema',
)]
class GenerateEntitiesCommand extends Command
{
    private Connection $connection;
    // private Filesystem $filesystem;
    private ?AbstractSchemaManager $schemaManager = null;
    private array $generatedRelations = [];

    /**
     * Constructor.
     *
     * @param Connection $connection The database connection instance.
     * @param Filesystem $filesystem The filesystem instance.
     */
    public function __construct(Connection $connection, Filesystem $filesystem)
    {
        parent::__construct();
        $this->connection = $connection;
        // $this->filesystem = $filesystem;
    }

    /**
     * Executes the command to generate entity classes.
     *
     * @param InputInterface $input  Input interface.
     * @param OutputInterface $output Output interface.
     * @return int Command exit status.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title("Generating Entity Classes from Database...");

        try {
            $schemaManager = $this->getSchemaManager();
            $tables = $schemaManager->listTables();
        } catch (\Exception $e) {
            $io->error("Failed to retrieve database schema: " . $e->getMessage());
            return Command::FAILURE;
        }

        $oneToManyRelations = [];
        $manyToOneRelationsName = [];
        $oneToManyRelationsName = [];

        // Count relations for each table
        $tableRelationsCount = [];
        foreach ($tables as $table) {
            $foreignKeys = $this->getForeignKeys([$table->getName()]);
            $relationCount = count($foreignKeys);
            $tableRelationsCount[$table->getName()] = $relationCount;
        }

        // Sort tables by their relation count in ascending order
        usort($tables, function (Table $a, Table $b) use ($tableRelationsCount) {
            return $tableRelationsCount[$a->getName()] <=> $tableRelationsCount[$b->getName()];
        });

        // Generate entities in sorted order
        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);

            $io->success("Generated: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        foreach ($tables as $table) {
            $this->generateEntity($table, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
            $io->success("Relations Added: src/Entity/" . ucfirst($table->getName()) . ".php");
        }

        $io->success("Entities successfully generated in src/Entity/");
        return Command::SUCCESS;
    }


    /**
     * Retrieves the schema manager instance, caching it to avoid redundant queries.
     *
     * @return AbstractSchemaManager The schema manager.
     */
    private function getSchemaManager(): AbstractSchemaManager
    {
        if ($this->schemaManager === null) {
            $this->schemaManager = $this->connection->createSchemaManager();
        }
        return $this->schemaManager;
    }

    /**
     * Generates an entity class from a database table.
     *
     * @param Table $table The database table.
     * @param array &$oneToManyRelations Reference to OneToMany relations.
     * @param array &$manyToOneRelationsName Reference to ManyToOne relations.
     */
    private function generateEntity(Table $table, array &$oneToManyRelations, array &$manyToOneRelationsName, array &$oneToManyRelationsName): void
    {
        $className = ucfirst($table->getName());
        $entityCode = "<?php\n\nnamespace App\\Entity;\n\nuse Doctrine\\ORM\\Mapping as ORM;\n\n";

        $imports = $this->generateImports($manyToOneRelationsName, $oneToManyRelationsName, $className);

        // Add imports
        $entityCode .= $imports . "\n";

        // Add entity annotation
        $entityCode .= "#[ORM\\Entity]\n";
        $entityCode .= "class $className\n{\n";

        // Get primary key(s)
        $primaryKeys = $table->getPrimaryKey()?->getColumns() ?? [];

        // Get foreign key constraints
        $foreignKeys = $this->getForeignKeys([$table->getName()]);

        foreach ($table->getColumns() as $column) {
            $entityCode .= $this->generateProperty($column, $primaryKeys, $foreignKeys, $className, $oneToManyRelations, $manyToOneRelationsName, $oneToManyRelationsName);
        }

        // Generate getters and setters
        foreach ($table->getColumns() as $column) {
            $entityCode .= $this->generateGettersAndSetters($column);
        }

        // Inject stored OneToMany relations into the correct entities
        if (isset($oneToManyRelations[$className])) {
            $processedRelations = []; // Keep track of already added relations

            foreach ($oneToManyRelations[$className] as $relation) {
                if (!in_array($relation, $processedRelations)) {
                    $entityCode .= $relation;
                    $processedRelations[] = $relation; // Mark relation as added

                    $relationArray = $this->parseRelationAnnotation($relation);
                    $relationKey = "$className-{$relationArray['mappedBy']}";

                    if (!isset($this->generatedRelations[$relationKey])) {
                        $entityCode .= $this->generateRelationMethods($className, $relationArray['mappedBy'], $relationArray['targetEntity']);
                        $this->generatedRelations[$relationKey] = true;
                    }
                }
            }
        }


        $entityCode .= "}\n";

        // Save the entity file
        $filePath = __DIR__ . "/../../src/Entity/$className.php";
        file_put_contents($filePath, $entityCode);
    }


    /**
     * Generates necessary import statements based on detected relations.
     *
     * @param array $oneToManyRelations OneToMany relations.
     * @param array $manyToOneRelationsName ManyToOne relations.
     * @param string $className The name of the entity class.
     * @return string Formatted import statements.
     */
    private function generateImports(array $manyToOneRelationsName, array $oneToManyRelationsName, string $className): string
    {
        $imports = [];

        foreach ($manyToOneRelationsName as $key => $value) {
            if ($key === $className) {
                $imports[] = "App\\Entity\\$value";
            }
        }

        foreach ($oneToManyRelationsName as $key => $value) {
            if ($key === $className) {
                $imports[] = "Doctrine\Common\Collections\Collection";
                $imports[] = "App\\Entity\\$value";
            }
        }

        // Remove duplicates and format imports
        $imports = array_unique($imports);

        // Return an empty string if there are no imports needed
        if (count($imports) == 0) {
            return "";
        }
        return "use " . implode(";\nuse ", $imports) . ";\n";
    }

    /**
     * Retrieves foreign key constraints from the database.
     *
     * @param array $tables List of table names.
     * @return array Associative array of foreign keys.
     */
    public function getForeignKeys(array $tables): array
    {
        $foreignKeys = [];

        // Create the schema manager
        $schemaManager = $this->connection->createSchemaManager();

        // List all tables using the schema manager
        $dbTables = $schemaManager->listTables();

        foreach ($tables as $tableName) {
            // Check if the table exists in the list of tables
            if (in_array($tableName, array_map(fn($table) => $table->getName(), $dbTables))) {
                // Run a custom SQL query to retrieve foreign keys from the INFORMATION_SCHEMA (MySQL example)
                $sql = "
                SELECT 
                    COLUMN_NAME, 
                    REFERENCED_TABLE_NAME, 
                    REFERENCED_COLUMN_NAME
                FROM 
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE 
                    TABLE_NAME = :tableName AND 
                    REFERENCED_TABLE_NAME IS NOT NULL
            ";

                // Prepare the query
                $stmt = $this->connection->prepare($sql);
                $stmt->bindValue(':tableName', $tableName);

                // Execute the query and fetch the results
                $fks = $stmt->executeQuery()->fetchAllAssociative();

                // Store foreign keys in the array
                foreach ($fks as $fk) {
                    $foreignKeys[$fk['COLUMN_NAME']] = [
                        'referencedTable' => $fk['REFERENCED_TABLE_NAME'],
                        'referencedColumn' => $fk['REFERENCED_COLUMN_NAME']
                    ];
                }
            }
        }

        return $foreignKeys;
    }

    /**
     * Generates relation methods for OneToMany and ManyToOne relations.
     *
     * @param string $currentEntity The current entity name.
     * @param string $propertyName The property name representing the relation.
     * @param string $relatedEntity The related entity name.
     * @return string The generated method code.
     */
    private function generateRelationMethods(string $currentEntity, string $propertyName, string $relatedEntity): string
    {
        $collectionType = "Collection";
        $relatedEntityClass = ucfirst($relatedEntity);
        $currentEntityClass = ucfirst($currentEntity);
        $relatedEntityVariable = lcfirst($relatedEntity); // Ensure lowercase variable name

        return "
        public function get" . $relatedEntityClass . "s(): $collectionType
        {
            return \$this->" . $relatedEntityVariable . "s;
        }
    
        public function add{$relatedEntityClass}({$relatedEntityClass} \${$relatedEntityVariable}): self
        {
            if (!\$this->{$relatedEntityVariable}s->contains(\${$relatedEntityVariable})) {
                \$this->{$relatedEntityVariable}s[] = \${$relatedEntityVariable};
                \${$relatedEntityVariable}->set" . ucfirst($propertyName) . "(\$this);
            }
    
            return \$this;
        }
    
        public function remove{$relatedEntityClass}({$relatedEntityClass} \${$relatedEntityVariable}): self
        {
            if (\$this->{$relatedEntityVariable}s->removeElement(\${$relatedEntityVariable})) {
                // set the owning side to null (unless already changed)
                if (\${$relatedEntityVariable}->get" . ucfirst($propertyName) . "() === \$this) {
                    \${$relatedEntityVariable}->set" . ucfirst($propertyName) . "(null);
                }
            }
    
            return \$this;
        }\n";
    }

    /**
     * Generates entity properties based on database columns.
     *
     * @param Column $column The database column.
     * @param array $primaryKeys List of primary keys.
     * @param array $foreignKeys List of foreign keys.
     * @param string $className The entity class name.
     * @param array &$oneToManyRelations Reference to OneToMany relations.
     * @param array &$manyToOneRelationsName Reference to ManyToOne relations.
     * @return string The generated property code.
     */
    private function generateProperty(Column $column, array $primaryKeys, array $foreignKeys, string $className, array &$oneToManyRelations, array &$manyToOneRelationsName, array &$oneToManyRelationsName): string
    {
        $columnName = $column->getName();
        $typeClass = get_class($column->getType());
        $length = $column->getLength();
        $isPrimaryKey = in_array($columnName, $primaryKeys);
        $isForeignKey = isset($foreignKeys[$columnName]);

        // Map type classes to Doctrine types
        $doctrineType = match ($typeClass) {
            'Doctrine\DBAL\Types\IntegerType' => 'integer',
            'Doctrine\DBAL\Types\BigIntType' => 'bigint',
            'Doctrine\DBAL\Types\SmallIntType' => 'smallint',
            'Doctrine\DBAL\Types\BooleanType' => 'boolean',
            'Doctrine\DBAL\Types\DateTimeType', 'Doctrine\DBAL\Types\TimestampType' => 'datetime',
            'Doctrine\DBAL\Types\DateType' => 'date',
            'Doctrine\DBAL\Types\TextType' => 'text',
            'Doctrine\DBAL\Types\DecimalType', 'Doctrine\DBAL\Types\FloatType', 'Doctrine\DBAL\Types\DoubleType' => 'float',
            'Doctrine\DBAL\Types\StringType', 'Doctrine\DBAL\Types\VarCharType' => 'string',
            default => 'string',
        };

        $lengthAnnotation = ($doctrineType === 'string' && $length) ? ", length: $length" : "";

        $propertyCode = "\n    " . ($isPrimaryKey ? "#[ORM\\Id]\n    " : "");


        if ($isForeignKey) {
            $relatedEntity = $foreignKeys[$columnName]['referencedTable'];
            $relatedClassName = ucfirst($relatedEntity);

            // Get the columns of the referenced table
            $schemaManager = $this->connection->createSchemaManager();
            $columns = $schemaManager->listTableColumns($relatedEntity);

            // Find the primary key columns from the list of columns
            $primaryKeyColumns = $this->getPrimaryKeyColumns($relatedEntity);

            // Assuming the primary key is the first column in the primary key list
            $primaryKeyColumn = $primaryKeyColumns ? $primaryKeyColumns[0] : null;

            // If primary key column is found
            if ($primaryKeyColumn) {
                // Add ManyToOne in the current entity
                $propertyCode .= "    #[ORM\\ManyToOne(targetEntity: $relatedClassName::class, inversedBy: \"" . strtolower($className) . "s\")]\n";

                // Add JoinColumn for foreign key relation
                $propertyCode .= "    #[ORM\\JoinColumn(name: '$columnName', referencedColumnName: '$primaryKeyColumn', onDelete: 'CASCADE')]\n";

                $propertyCode .= "    private $relatedClassName \$$columnName;\n";

                // Store ManyToOne relation in the current entity
                $manyToOneRelationsName[$className] = $relatedClassName;
                // Store OneToMany relation in the current entity
                $oneToManyRelationsName[$relatedClassName] = $className;

                // Store OneToMany relation in the related entity
                $oneToManyRelations[$relatedClassName][] = "\n    #[ORM\\OneToMany(mappedBy: \"$columnName\", targetEntity: $className::class)]\n    private Collection \$" . strtolower($className) . "s;\n";
            }
        } else {
            $propertyCode .= "#[ORM\\Column(type: \"$doctrineType\"$lengthAnnotation)]\n";
            $propertyCode .= "    private " . $this->getPHPTypeFromDoctrine($doctrineType) . " \$$columnName;\n";
        }

        return $propertyCode;
    }

    private function getPHPTypeFromDoctrine(string $doctrineType): string
    {
        $mapping = [
            'integer' => 'int',
            'smallint' => 'int',
            'bigint' => 'string', // BigInt is stored as a string in PHP
            'string' => 'string',
            'text' => 'string',
            'boolean' => 'bool',
            'decimal' => 'string', // Decimal values are stored as strings to avoid precision loss
            'float' => 'float',
            'date' => '\DateTimeInterface',
            'datetime' => '\DateTimeInterface',
            'datetimetz' => '\DateTimeInterface',
            'time' => '\DateTimeInterface',
            'array' => 'array',
            'json' => 'array', // JSON is typically decoded to an array
            'object' => 'object',
            'binary' => 'string',
            'blob' => 'string',
            'guid' => 'string',
        ];

        return $mapping[$doctrineType] ?? 'mixed'; // Default to 'mixed' if type is unknown
    }
    // Helper function to get primary key columns of a table
    private function getPrimaryKeyColumns(string $tableName): array
    {
        $schemaManager = $this->connection->createSchemaManager();
        $indexes = $schemaManager->listTableIndexes($tableName);

        // Find primary key columns from the indexes
        if (isset($indexes['primary'])) {
            return $indexes['primary']->getColumns();
        }

        return [];
    }

    /**
     * Generates getter and setter methods for entity properties.
     *
     * @param Column $column The database column.
     * @return string The generated getter and setter methods.
     */
    private function generateGettersAndSetters(Column $column): string
    {
        $columnName = $column->getName();
        $methodName = ucfirst($columnName);

        return "
    public function get$methodName()
    {
        return \$this->$columnName;
    }

    public function set$methodName(\$value)
    {
        \$this->$columnName = \$value;
    }\n";
    }

    /**
     * Parses a relation annotation string to extract mappedBy and targetEntity values.
     *
     * @param string $relation The relation annotation string.
     * @return array Associative array containing 'mappedBy' and 'targetEntity' values.
     */
    private function parseRelationAnnotation(string $relation): array
    {
        $pattern = '/mappedBy:\s*"([^"]+)",\s*targetEntity:\s*([^\s:]+)::class/';

        if (preg_match($pattern, $relation, $matches)) {
            return [
                'mappedBy' => $matches[1],      // Extracted value for mappedBy
                'targetEntity' => $matches[2]   // Extracted value for targetEntity
            ];
        }

        return ['mappedBy' => null, 'targetEntity' => null]; // Return nulls if not found
    }
}
