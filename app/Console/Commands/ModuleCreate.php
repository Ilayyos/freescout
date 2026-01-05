<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ModuleCreate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'freescout:module-create
        {name? : The name of the module (StudlyCase, e.g., CustomerPortal)}
        {--description= : Module description}
        {--author= : Author name}
        {--author-email= : Author email}
        {--with-entity= : Create an entity/model with this name}
        {--with-migration : Generate a migration for the entity}
        {--with-permissions : Generate a permissions helper class}
        {--with-menu : Add menu item hook to service provider}
        {--full : Generate with all optional features (entity, migration, permissions, menu)}
        {--force : Overwrite existing module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new FreeScout module with all boilerplate files';

    /**
     * Filesystem instance.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Module configuration.
     *
     * @var array
     */
    protected $config = [];

    /**
     * Path to stub templates.
     *
     * @var string
     */
    protected $stubPath;

    /**
     * Create a new command instance.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->stubPath = __DIR__ . '/stubs/module';
    }

    /**
     * Output a blank line (compatible with older Laravel versions).
     *
     * @param int $count
     * @return void
     */
    protected function newLine($count = 1)
    {
        for ($i = 0; $i < $count; $i++) {
            $this->line('');
        }
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║           FreeScout Module Generator v1.0.0                  ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        // Gather module information
        if (!$this->gatherModuleInfo()) {
            return 1;
        }

        // Check if module exists
        if ($this->moduleExists() && !$this->option('force')) {
            $this->error("Module '{$this->config['studly_name']}' already exists!");
            $this->line('Use --force to overwrite.');
            return 1;
        }

        // Display configuration summary
        $this->displayConfigSummary();

        // Confirm creation
        if (!$this->option('force') && !$this->confirm('Do you want to create this module?', true)) {
            $this->warn('Module creation cancelled.');
            return 0;
        }

        $this->newLine();
        $this->info('Creating module...');
        $this->newLine();

        // Create module structure
        $this->createDirectories();
        $this->createFiles();

        // Post-creation tasks
        $this->runPostCreationTasks();

        // Display success message
        $this->displaySuccessMessage();

        return 0;
    }

    /**
     * Gather module information from arguments, options, or interactive prompts.
     *
     * @return bool
     */
    protected function gatherModuleInfo(): bool
    {
        // Module name
        $name = $this->argument('name');
        if (!$name) {
            $name = $this->ask('What is the module name? (StudlyCase, e.g., CustomerPortal)');
        }

        if (!$this->validateModuleName($name)) {
            return false;
        }

        $this->config['studly_name'] = Str::studly($name);
        $this->config['lower_name'] = Str::lower($this->config['studly_name']);
        $this->config['snake_name'] = Str::snake($this->config['studly_name']);
        $this->config['kebab_name'] = Str::kebab($this->config['studly_name']);
        $this->config['title_name'] = Str::title(str_replace(['_', '-'], ' ', $this->config['snake_name']));

        // Description
        $this->config['description'] = $this->option('description')
            ?: $this->ask('Module description', "A {$this->config['title_name']} module for FreeScout.");

        // Author
        $defaultAuthor = config('modules.composer.author.name', 'FreeScout');
        $this->config['author'] = $this->option('author')
            ?: $this->ask('Author name', $defaultAuthor);

        // Author email
        $defaultEmail = config('modules.composer.author.email', 'support@freescout.net');
        $this->config['author_email'] = $this->option('author-email')
            ?: $this->ask('Author email', $defaultEmail);

        // Handle --full option
        $isFull = $this->option('full');

        // Entity
        $withEntity = $this->option('with-entity');
        if ($isFull && !$withEntity) {
            $withEntity = $this->config['studly_name'];
        }
        if (!$withEntity && !$isFull) {
            if ($this->confirm('Would you like to create an entity/model?', false)) {
                $withEntity = $this->ask('Entity name', $this->config['studly_name']);
            }
        }

        if ($withEntity) {
            $this->config['entity_name'] = Str::studly($withEntity);
            $this->config['entity_snake'] = Str::snake($this->config['entity_name']);
            $this->config['entity_plural'] = Str::plural($this->config['entity_snake']);
            $this->config['table_name'] = $this->config['entity_plural'];
        }

        // Migration
        $this->config['with_migration'] = $this->option('with-migration')
            || $isFull
            || (isset($this->config['entity_name']) && $this->confirm('Generate a migration for the entity?', true));

        // Permissions helper
        $this->config['with_permissions'] = $this->option('with-permissions')
            || $isFull
            || $this->confirm('Generate a permissions helper class?', false);

        // Menu item
        $this->config['with_menu'] = $this->option('with-menu')
            || $isFull
            || $this->confirm('Add a menu item to the Manage section?', true);

        // Set paths
        $this->config['module_path'] = base_path('Modules/' . $this->config['studly_name']);
        $this->config['namespace'] = 'Modules';
        $this->config['date_prefix'] = date('Y_m_d_His');

        return true;
    }

    /**
     * Validate module name.
     *
     * @param string|null $name
     * @return bool
     */
    protected function validateModuleName(?string $name): bool
    {
        if (empty($name)) {
            $this->error('Module name is required.');
            return false;
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $this->error('Module name must start with a letter and contain only alphanumeric characters.');
            return false;
        }

        $reserved = ['Module', 'Modules', 'App', 'Config', 'Database', 'Public', 'Resources', 'Routes', 'Storage', 'Tests', 'Vendor'];
        if (in_array(Str::studly($name), $reserved)) {
            $this->error("'{$name}' is a reserved name and cannot be used.");
            return false;
        }

        return true;
    }

    /**
     * Check if module already exists.
     *
     * @return bool
     */
    protected function moduleExists(): bool
    {
        return $this->filesystem->isDirectory(base_path('Modules/' . $this->config['studly_name']));
    }

    /**
     * Display configuration summary.
     */
    protected function displayConfigSummary(): void
    {
        $this->newLine();
        $this->info('┌─────────────────────────────────────────────────────────────┐');
        $this->info('│                    Module Configuration                     │');
        $this->info('├─────────────────────────────────────────────────────────────┤');
        $this->line("│  Name:         <comment>{$this->config['studly_name']}</comment>" . str_repeat(' ', max(0, 44 - strlen($this->config['studly_name']))) . '│');
        $this->line("│  Alias:        <comment>{$this->config['lower_name']}</comment>" . str_repeat(' ', max(0, 44 - strlen($this->config['lower_name']))) . '│');
        $desc = Str::limit($this->config['description'], 40);
        $this->line("│  Description:  <comment>{$desc}</comment>" . str_repeat(' ', max(0, 44 - strlen($desc))) . '│');
        $this->line("│  Author:       <comment>{$this->config['author']}</comment>" . str_repeat(' ', max(0, 44 - strlen($this->config['author']))) . '│');
        $this->info('├─────────────────────────────────────────────────────────────┤');
        $this->line('│  <info>Features:</info>                                                │');

        $entity = isset($this->config['entity_name']) ? $this->config['entity_name'] : 'No';
        $this->line("│    • Entity:       <comment>{$entity}</comment>" . str_repeat(' ', max(0, 40 - strlen($entity))) . '│');

        $migration = $this->config['with_migration'] ? 'Yes' : 'No';
        $this->line("│    • Migration:    <comment>{$migration}</comment>" . str_repeat(' ', max(0, 40 - strlen($migration))) . '│');

        $permissions = $this->config['with_permissions'] ? 'Yes' : 'No';
        $this->line("│    • Permissions:  <comment>{$permissions}</comment>" . str_repeat(' ', max(0, 40 - strlen($permissions))) . '│');

        $menu = $this->config['with_menu'] ? 'Yes' : 'No';
        $this->line("│    • Menu Item:    <comment>{$menu}</comment>" . str_repeat(' ', max(0, 40 - strlen($menu))) . '│');

        $this->info('└─────────────────────────────────────────────────────────────┘');
        $this->newLine();
    }

    /**
     * Create module directories.
     */
    protected function createDirectories(): void
    {
        $directories = [
            'Config',
            'Database/Migrations',
            'Database/Seeders',
            'Database/factories',
            'Entities',
            'Http/Controllers',
            'Http/Requests',
            'Providers',
            'Public/css',
            'Public/js',
            'Resources/lang',
            'Resources/views/partials',
            'Support',
        ];

        $basePath = $this->config['module_path'];

        // Remove existing if force
        if ($this->option('force') && $this->filesystem->isDirectory($basePath)) {
            $this->filesystem->deleteDirectory($basePath);
            $this->warn("  Removed existing module directory.");
        }

        foreach ($directories as $dir) {
            $path = $basePath . '/' . $dir;
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }

        $this->info("  ✓ Created directory structure");
    }

    /**
     * Create all module files.
     */
    protected function createFiles(): void
    {
        // Core files
        $this->createFile('module.json', 'module.stub');
        $this->createFile('composer.json', 'composer.stub');
        $this->createFile('start.php', 'start.stub');

        // Config
        $this->createFile('Config/config.php', 'config.stub');

        // Provider
        $this->createFile(
            "Providers/{$this->config['studly_name']}ServiceProvider.php",
            $this->config['with_menu'] ? 'provider-with-menu.stub' : 'provider.stub'
        );

        // Routes
        $this->createFile('Http/routes.php', 'routes.stub');

        // Controller
        $this->createFile(
            "Http/Controllers/{$this->config['studly_name']}Controller.php",
            'controller.stub'
        );

        // Views
        $this->createFile('Resources/views/index.blade.php', 'views/index.stub');
        $this->createFile('Resources/views/partials/manage_menu_item.blade.php', 'views/menu-item.stub');

        // Seeder
        $this->createFile(
            "Database/Seeders/{$this->config['studly_name']}DatabaseSeeder.php",
            'seeder.stub'
        );

        // Assets
        $this->createFile("Public/css/{$this->config['lower_name']}.css", 'assets/css.stub');
        $this->createFile("Public/js/{$this->config['lower_name']}.js", 'assets/js.stub');

        // Optional: Entity
        if (isset($this->config['entity_name'])) {
            $this->createFile(
                "Entities/{$this->config['entity_name']}.php",
                'entity.stub'
            );
            $this->info("  ✓ Created entity: {$this->config['entity_name']}");
        }

        // Optional: Migration
        if ($this->config['with_migration'] && isset($this->config['entity_name'])) {
            $migrationName = "{$this->config['date_prefix']}_create_{$this->config['table_name']}_table.php";
            $this->createFile(
                "Database/Migrations/{$migrationName}",
                'migration.stub'
            );
            $this->info("  ✓ Created migration: {$migrationName}");
        }

        // Optional: Permissions
        if ($this->config['with_permissions']) {
            $this->createFile(
                "Support/{$this->config['studly_name']}Permissions.php",
                'permissions.stub'
            );
            $this->info("  ✓ Created permissions helper");
        }

        $this->info("  ✓ Created all module files");
    }

    /**
     * Create a file from a stub template.
     *
     * @param string $path Relative path within module
     * @param string $stub Stub template name
     */
    protected function createFile(string $path, string $stub): void
    {
        $stubContent = $this->getStub($stub);
        $content = $this->replaceVariables($stubContent);

        $fullPath = $this->config['module_path'] . '/' . $path;
        $this->filesystem->put($fullPath, $content);
    }

    /**
     * Get stub template content.
     *
     * @param string $name
     * @return string
     */
    protected function getStub(string $name): string
    {
        $path = $this->stubPath . '/' . $name;

        if (!$this->filesystem->exists($path)) {
            throw new \RuntimeException("Stub template not found: {$path}");
        }

        return $this->filesystem->get($path);
    }

    /**
     * Replace template variables with actual values.
     *
     * @param string $content
     * @return string
     */
    protected function replaceVariables(string $content): string
    {
        $replacements = [
            '$STUDLY_NAME$' => $this->config['studly_name'],
            '$LOWER_NAME$' => $this->config['lower_name'],
            '$SNAKE_NAME$' => $this->config['snake_name'],
            '$KEBAB_NAME$' => $this->config['kebab_name'],
            '$TITLE_NAME$' => $this->config['title_name'],
            '$DESCRIPTION$' => $this->config['description'],
            '$AUTHOR$' => $this->config['author'],
            '$AUTHOR_EMAIL$' => $this->config['author_email'],
            '$MODULE_NAMESPACE$' => $this->config['namespace'],
            '$DATE_PREFIX$' => $this->config['date_prefix'],
            '$YEAR$' => date('Y'),
        ];

        // Entity-specific replacements
        if (isset($this->config['entity_name'])) {
            $replacements['$ENTITY_NAME$'] = $this->config['entity_name'];
            $replacements['$ENTITY_SNAKE$'] = $this->config['entity_snake'];
            $replacements['$ENTITY_PLURAL$'] = $this->config['entity_plural'];
            $replacements['$TABLE_NAME$'] = $this->config['table_name'];
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Run post-creation tasks.
     */
    protected function runPostCreationTasks(): void
    {
        $this->newLine();

        // Create public symlink
        $publicPath = public_path('modules/' . $this->config['lower_name']);
        $modulePath = $this->config['module_path'] . '/Public';

        if (!$this->filesystem->isDirectory(public_path('modules'))) {
            $this->filesystem->makeDirectory(public_path('modules'), 0755, true);
        }

        if ($this->filesystem->exists($publicPath)) {
            $this->filesystem->delete($publicPath);
        }

        // Create symlink
        if (function_exists('symlink')) {
            @symlink($modulePath, $publicPath);
            $this->info("  ✓ Created public symlink");
        } else {
            $this->warn("  ! Could not create symlink. Run: php artisan freescout:module-install");
        }
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════╗');
        $this->info('║                    Module Created Successfully!              ║');
        $this->info('╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("  Module: <comment>{$this->config['studly_name']}</comment>");
        $this->line("  Path:   <comment>Modules/{$this->config['studly_name']}</comment>");
        $this->newLine();

        $this->info('  Next Steps:');
        $this->line('  ─────────────────────────────────────────────────────────────');

        if ($this->config['with_migration']) {
            $this->line("  1. Run migrations:");
            $this->line("     <comment>php artisan module:migrate {$this->config['studly_name']}</comment>");
            $this->newLine();
        }

        $this->line("  2. Activate the module:");
        $this->line("     <comment>Go to Admin → Modules → Activate '{$this->config['studly_name']}'</comment>");
        $this->line("     Or run: <comment>php artisan module:enable {$this->config['studly_name']}</comment>");
        $this->newLine();

        $this->line("  3. Clear cache:");
        $this->line("     <comment>php artisan freescout:clear-cache</comment>");
        $this->newLine();

        $this->line("  4. Access your module:");
        $this->line("     <comment>" . url($this->config['lower_name']) . "</comment>");
        $this->newLine();

        $this->info('  Happy coding! 🚀');
        $this->newLine();
    }
}
