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
        {name? : Module name (e.g., CustomerPortal)}
        {--template=basic : Module template (basic, crud, api, widget)}
        {--description= : Module description}
        {--author= : Author name}
        {--author-email= : Author email}
        {--with-entity= : Create entity/model with this name}
        {--fields= : Entity fields (e.g., "name:string,email:string:unique,status:enum(active,inactive)")}
        {--with-migration : Generate database migration}
        {--with-seeder : Generate database seeder}
        {--with-requests : Generate form request validation}
        {--with-policy : Generate authorization policy}
        {--with-views : Generate all CRUD views}
        {--with-api : Generate API controller and routes}
        {--with-tests : Generate unit and feature tests}
        {--with-widget : Generate dashboard widget}
        {--with-permissions : Generate permissions helper}
        {--with-menu : Add menu item to Manage section}
        {--with-readme : Generate README documentation}
        {--full : Enable all features}
        {--wizard : Interactive wizard mode (recommended for beginners)}
        {--force : Overwrite existing module}
        {--dry-run : Preview files without creating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new FreeScout module with all boilerplate files (use --wizard for guided setup)';

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
     * Available templates with descriptions.
     *
     * @var array
     */
    protected $templates = [
        'basic' => [
            'name' => 'Basic Module',
            'description' => 'Simple module with a single page and menu item. Best for settings pages or simple displays.',
            'features' => ['controller', 'view', 'routes', 'menu'],
        ],
        'crud' => [
            'name' => 'CRUD Module',
            'description' => 'Full Create, Read, Update, Delete functionality. Includes forms, tables, and validation.',
            'features' => ['entity', 'migration', 'controller', 'views', 'routes', 'menu', 'requests'],
        ],
        'api' => [
            'name' => 'API Module',
            'description' => 'REST API endpoints for external integrations. Includes API routes and JSON responses.',
            'features' => ['entity', 'migration', 'api-controller', 'api-routes', 'resource'],
        ],
        'widget' => [
            'name' => 'Dashboard Widget',
            'description' => 'Adds a widget to the FreeScout dashboard. Great for statistics or quick actions.',
            'features' => ['widget', 'widget-view'],
        ],
    ];

    /**
     * Available field types with descriptions.
     *
     * @var array
     */
    protected $fieldTypes = [
        'string' => ['description' => 'Text up to 255 characters (names, titles, etc.)', 'input' => 'text'],
        'text' => ['description' => 'Long text without limit (descriptions, notes)', 'input' => 'textarea'],
        'integer' => ['description' => 'Whole numbers (counts, quantities)', 'input' => 'number'],
        'decimal' => ['description' => 'Numbers with decimals (prices, percentages)', 'input' => 'number'],
        'boolean' => ['description' => 'Yes/No values (is_active, is_featured)', 'input' => 'checkbox'],
        'date' => ['description' => 'Date only (birth_date, due_date)', 'input' => 'date'],
        'datetime' => ['description' => 'Date and time (created_at, scheduled_at)', 'input' => 'datetime-local'],
        'email' => ['description' => 'Email address with validation', 'input' => 'email'],
        'enum' => ['description' => 'Predefined options (status, type)', 'input' => 'select'],
        'json' => ['description' => 'Structured data (settings, metadata)', 'input' => 'textarea'],
        'foreignId' => ['description' => 'Link to another table (user_id, mailbox_id)', 'input' => 'select'],
    ];

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
     * Output a blank line.
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
        $this->displayHeader();

        // Check for wizard mode
        if ($this->option('wizard') || (!$this->argument('name') && !$this->option('force'))) {
            return $this->runWizard();
        }

        // Standard mode
        if (!$this->gatherModuleInfo()) {
            return 1;
        }

        return $this->createModule();
    }

    /**
     * Display the header.
     */
    protected function displayHeader(): void
    {
        $this->newLine();
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║         🚀 FreeScout Module Creator v2.0 (Ultimate)              ║');
        $this->info('║                                                                  ║');
        $this->info('║   Create professional modules in minutes, no coding required!   ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->newLine();
    }

    /**
     * Run the interactive wizard.
     *
     * @return int
     */
    protected function runWizard(): int
    {
        $this->info('  Welcome! This wizard will guide you through creating a new module.');
        $this->line('  ─────────────────────────────────────────────────────────────────');
        $this->newLine();

        // Step 1: Basic Information
        if (!$this->wizardStep1BasicInfo()) {
            return 1;
        }

        // Step 2: Template Selection
        $this->wizardStep2Template();

        // Step 3: Entity & Fields
        $this->wizardStep3Entity();

        // Step 4: Features
        $this->wizardStep4Features();

        // Step 5: Preview & Confirm
        if (!$this->wizardStep5Confirm()) {
            return 0;
        }

        return $this->createModule();
    }

    /**
     * Wizard Step 1: Basic Information.
     *
     * @return bool
     */
    protected function wizardStep1BasicInfo(): bool
    {
        $this->displayStepHeader(1, 5, 'Basic Information');

        // Module name
        $this->line('  <comment>What would you like to name your module?</comment>');
        $this->line('  <fg=gray>Use CamelCase like "CustomerPortal" or "OrderTracker"</>');
        $this->newLine();

        $name = $this->argument('name');
        if (!$name) {
            $name = $this->ask('  Module name');
        }

        if (!$this->validateModuleName($name)) {
            return false;
        }

        $this->config['studly_name'] = Str::studly($name);
        $this->config['lower_name'] = Str::lower($this->config['studly_name']);
        $this->config['snake_name'] = Str::snake($this->config['studly_name']);
        $this->config['kebab_name'] = Str::kebab($this->config['studly_name']);
        $this->config['title_name'] = $this->generateTitleName($this->config['studly_name']);

        $this->newLine();
        $this->line('  <comment>Briefly describe what your module does:</comment>');
        $this->line('  <fg=gray>This appears in the module list and documentation</>');
        $this->newLine();

        $defaultDesc = "A {$this->config['title_name']} module for FreeScout.";
        $this->config['description'] = $this->option('description')
            ?: $this->ask('  Description', $defaultDesc);

        $this->newLine();
        $this->line('  <comment>Who is creating this module?</comment>');
        $this->newLine();

        $defaultAuthor = config('modules.composer.author.name', 'FreeScout');
        $this->config['author'] = $this->option('author')
            ?: $this->ask('  Author name', $defaultAuthor);

        $defaultEmail = config('modules.composer.author.email', 'support@freescout.net');
        $this->config['author_email'] = $this->option('author-email')
            ?: $this->ask('  Author email', $defaultEmail);

        $this->newLine();
        $this->info('  ✓ Basic information saved!');
        $this->newLine();

        return true;
    }

    /**
     * Wizard Step 2: Template Selection.
     */
    protected function wizardStep2Template(): void
    {
        $this->displayStepHeader(2, 5, 'Choose a Template');

        $this->line('  <comment>What type of module do you want to create?</comment>');
        $this->line('  <fg=gray>Each template includes different pre-built features</>');
        $this->newLine();

        // Display template options
        $templateOptions = [];
        $index = 1;
        foreach ($this->templates as $key => $template) {
            $templateOptions[$index] = $key;
            $marker = ($key === 'basic') ? ' <fg=green>(recommended for beginners)</>' : '';
            $this->line("  <comment>[{$index}]</comment> {$template['name']}{$marker}");
            $this->line("      <fg=gray>{$template['description']}</>");
            $this->newLine();
            $index++;
        }

        $choice = $this->ask('  Select template (1-4)', '1');
        $templateKey = $templateOptions[(int)$choice] ?? 'basic';
        $this->config['template'] = $templateKey;

        $this->newLine();
        $this->info("  ✓ Selected: {$this->templates[$templateKey]['name']}");
        $this->newLine();
    }

    /**
     * Wizard Step 3: Entity & Fields.
     */
    protected function wizardStep3Entity(): void
    {
        $this->displayStepHeader(3, 5, 'Data Structure');

        // Skip for widget template
        if ($this->config['template'] === 'widget') {
            $this->line('  <fg=gray>Widget modules don\'t require a data structure. Skipping...</>');
            $this->newLine();
            return;
        }

        $this->line('  <comment>Does your module need to store data in the database?</comment>');
        $this->line('  <fg=gray>For example: customers, orders, tickets, settings, etc.</>');
        $this->newLine();

        // For CRUD/API templates, entity is required
        $needsEntity = in_array($this->config['template'], ['crud', 'api']);

        if (!$needsEntity) {
            $needsEntity = $this->confirm('  Create a database table?', false);
        }

        if (!$needsEntity) {
            $this->newLine();
            $this->line('  <fg=gray>Skipping data structure...</>');
            $this->newLine();
            return;
        }

        $this->newLine();
        $this->line('  <comment>What is the main thing your module manages?</comment>');
        $this->line('  <fg=gray>Use singular form: "Customer", "Order", "Report"</>');
        $this->newLine();

        $defaultEntity = $this->config['studly_name'];
        $entityName = $this->ask('  Entity name', $defaultEntity);

        $this->config['entity_name'] = Str::studly($entityName);
        $this->config['entity_snake'] = Str::snake($this->config['entity_name']);
        $this->config['entity_plural'] = Str::plural($this->config['entity_snake']);
        $this->config['table_name'] = $this->config['entity_plural'];

        // Field builder
        $this->newLine();
        $this->line('  <comment>Now let\'s define the fields (columns) for your data:</comment>');
        $this->line('  <fg=gray>You can add as many fields as you need</>');
        $this->newLine();

        $fields = [];
        $addMore = true;

        while ($addMore) {
            $field = $this->buildField(count($fields) + 1);
            if ($field) {
                $fields[] = $field;
                $this->info("  ✓ Added field: {$field['name']} ({$field['type']})");
                $this->newLine();
            }

            if (count($fields) > 0) {
                $addMore = $this->confirm('  Add another field?', count($fields) < 3);
            }
        }

        $this->config['fields'] = $fields;
        $this->config['with_migration'] = true;

        $this->newLine();
        $this->info('  ✓ Data structure defined with ' . count($fields) . ' field(s)!');
        $this->newLine();
    }

    /**
     * Build a single field interactively.
     *
     * @param int $number
     * @return array|null
     */
    protected function buildField(int $number): ?array
    {
        $this->line("  <comment>Field #{$number}:</comment>");

        $name = $this->ask('    Field name (e.g., title, email, status)');
        if (empty($name)) {
            return null;
        }

        $name = Str::snake($name);

        // Show type options
        $this->newLine();
        $this->line('    <comment>What type of data will this field store?</comment>');
        $this->newLine();

        $typeOptions = [];
        $index = 1;
        foreach ($this->fieldTypes as $type => $info) {
            $typeOptions[$index] = $type;
            $this->line("    <comment>[{$index}]</comment> {$type} - <fg=gray>{$info['description']}</>");
            $index++;
        }
        $this->newLine();

        $typeChoice = $this->ask('    Select type (1-' . count($this->fieldTypes) . ')', '1');
        $type = $typeOptions[(int)$typeChoice] ?? 'string';

        // Handle enum options
        $enumOptions = [];
        if ($type === 'enum') {
            $this->newLine();
            $this->line('    <comment>Enter the allowed values (comma-separated):</comment>');
            $this->line('    <fg=gray>Example: active,inactive,pending</>');
            $optionsInput = $this->ask('    Options');
            $enumOptions = array_map('trim', explode(',', $optionsInput));
        }

        // Modifiers
        $this->newLine();
        $nullable = $this->confirm('    Can this field be empty (nullable)?', false);
        $unique = $this->confirm('    Must values be unique (no duplicates)?', false);

        return [
            'name' => $name,
            'type' => $type,
            'nullable' => $nullable,
            'unique' => $unique,
            'enum_options' => $enumOptions,
        ];
    }

    /**
     * Wizard Step 4: Features.
     */
    protected function wizardStep4Features(): void
    {
        $this->displayStepHeader(4, 5, 'Additional Features');

        $this->line('  <comment>Would you like any of these optional features?</comment>');
        $this->line('  <fg=gray>You can always add these later manually</>');
        $this->newLine();

        // Set defaults based on template
        $template = $this->config['template'];
        $hasEntity = isset($this->config['entity_name']);

        // Menu item
        $this->config['with_menu'] = $this->confirm(
            '  Add menu item in Manage section?',
            in_array($template, ['basic', 'crud'])
        );

        // Views (for CRUD)
        if ($hasEntity && $template !== 'api') {
            $this->config['with_views'] = $this->confirm(
                '  Generate complete CRUD views (list, create, edit, delete)?',
                $template === 'crud'
            );
        }

        // Form validation
        if ($hasEntity) {
            $this->config['with_requests'] = $this->confirm(
                '  Generate form validation rules?',
                in_array($template, ['crud', 'api'])
            );
        }

        // API
        if ($hasEntity && $template !== 'api') {
            $this->config['with_api'] = $this->confirm(
                '  Generate REST API endpoints?',
                false
            );
        } elseif ($template === 'api') {
            $this->config['with_api'] = true;
        }

        // Tests
        if ($hasEntity) {
            $this->config['with_tests'] = $this->confirm(
                '  Generate automated tests?',
                false
            );
        }

        // Permissions
        $this->config['with_permissions'] = $this->confirm(
            '  Generate permission controls (admin-only features)?',
            false
        );

        // Widget
        if ($template === 'widget' || $template !== 'widget') {
            $this->config['with_widget'] = $template === 'widget' || $this->confirm(
                '  Add a dashboard widget?',
                $template === 'widget'
            );
        }

        // README
        $this->config['with_readme'] = $this->confirm(
            '  Generate documentation (README)?',
            true
        );

        $this->newLine();
        $this->info('  ✓ Features configured!');
        $this->newLine();
    }

    /**
     * Wizard Step 5: Preview & Confirm.
     *
     * @return bool
     */
    protected function wizardStep5Confirm(): bool
    {
        $this->displayStepHeader(5, 5, 'Review & Create');

        // Set remaining config
        $this->config['module_path'] = base_path('Modules/' . $this->config['studly_name']);
        $this->config['namespace'] = 'Modules';
        $this->config['date_prefix'] = date('Y_m_d_His');

        // Check if exists
        if ($this->moduleExists() && !$this->option('force')) {
            $this->error("  Module '{$this->config['studly_name']}' already exists!");
            if (!$this->confirm('  Overwrite existing module?', false)) {
                $this->warn('  Creation cancelled.');
                return false;
            }
            $this->config['force'] = true;
        }

        // Display summary
        $this->displayConfigSummary();

        // Preview files
        $files = $this->getFilesToCreate();
        $this->newLine();
        $this->line('  <comment>Files to be created:</comment>');
        $this->line('  ─────────────────────────────────────────');
        foreach ($files as $file) {
            $this->line("    <fg=green>+</> {$file}");
        }
        $this->newLine();

        // Dry run option
        if ($this->option('dry-run')) {
            $this->warn('  Dry run mode - no files created.');
            return false;
        }

        return $this->confirm('  Create this module now?', true);
    }

    /**
     * Display step header.
     *
     * @param int $current
     * @param int $total
     * @param string $title
     */
    protected function displayStepHeader(int $current, int $total, string $title): void
    {
        $progress = str_repeat('●', $current) . str_repeat('○', $total - $current);
        $this->info("  ┌─────────────────────────────────────────────────────────────┐");
        $this->info("  │  Step {$current}/{$total}: {$title}" . str_repeat(' ', 47 - strlen($title)) . "│");
        $this->info("  │  [{$progress}]" . str_repeat(' ', 52 - strlen($progress)) . "│");
        $this->info("  └─────────────────────────────────────────────────────────────┘");
        $this->newLine();
    }

    /**
     * Gather module information from options (non-wizard mode).
     *
     * @return bool
     */
    protected function gatherModuleInfo(): bool
    {
        $name = $this->argument('name');
        if (!$name) {
            $this->error('Module name is required. Use --wizard for guided setup.');
            return false;
        }

        if (!$this->validateModuleName($name)) {
            return false;
        }

        $this->config['studly_name'] = Str::studly($name);
        $this->config['lower_name'] = Str::lower($this->config['studly_name']);
        $this->config['snake_name'] = Str::snake($this->config['studly_name']);
        $this->config['kebab_name'] = Str::kebab($this->config['studly_name']);
        $this->config['title_name'] = $this->generateTitleName($this->config['studly_name']);

        $this->config['template'] = $this->option('template') ?: 'basic';
        $this->config['description'] = $this->option('description')
            ?: "A {$this->config['title_name']} module for FreeScout.";
        $this->config['author'] = $this->option('author')
            ?: config('modules.composer.author.name', 'FreeScout');
        $this->config['author_email'] = $this->option('author-email')
            ?: config('modules.composer.author.email', 'support@freescout.net');

        // Handle entity
        $isFull = $this->option('full');
        $withEntity = $this->option('with-entity');

        if ($isFull && !$withEntity) {
            $withEntity = $this->config['studly_name'];
        }

        if ($withEntity) {
            $this->config['entity_name'] = Str::studly($withEntity);
            $this->config['entity_snake'] = Str::snake($this->config['entity_name']);
            $this->config['entity_plural'] = Str::plural($this->config['entity_snake']);
            $this->config['table_name'] = $this->config['entity_plural'];

            // Parse fields
            if ($this->option('fields')) {
                $this->config['fields'] = $this->parseFields($this->option('fields'));
            }
        }

        // Set features
        $this->config['with_migration'] = $this->option('with-migration') || $isFull || isset($this->config['entity_name']);
        $this->config['with_seeder'] = $this->option('with-seeder') || $isFull;
        $this->config['with_requests'] = $this->option('with-requests') || $isFull;
        $this->config['with_policy'] = $this->option('with-policy') || $isFull;
        $this->config['with_views'] = $this->option('with-views') || $isFull || $this->config['template'] === 'crud';
        $this->config['with_api'] = $this->option('with-api') || $this->config['template'] === 'api';
        $this->config['with_tests'] = $this->option('with-tests') || $isFull;
        $this->config['with_widget'] = $this->option('with-widget') || $this->config['template'] === 'widget';
        $this->config['with_permissions'] = $this->option('with-permissions') || $isFull;
        $this->config['with_menu'] = $this->option('with-menu') || $isFull || in_array($this->config['template'], ['basic', 'crud']);
        $this->config['with_readme'] = $this->option('with-readme') || $isFull;

        $this->config['module_path'] = base_path('Modules/' . $this->config['studly_name']);
        $this->config['namespace'] = 'Modules';
        $this->config['date_prefix'] = date('Y_m_d_His');

        return true;
    }

    /**
     * Parse fields from command line string.
     * Handles comma-separated field definitions, respecting parentheses.
     *
     * @param string $fieldsString
     * @return array
     */
    protected function parseFields(string $fieldsString): array
    {
        $fields = [];

        // Smart split: split by commas that are not inside parentheses
        $fieldDefinitions = $this->splitFieldDefinitions($fieldsString);

        foreach ($fieldDefinitions as $definition) {
            $definition = trim($definition);
            if (empty($definition)) {
                continue;
            }

            $parts = explode(':', $definition);
            $name = $parts[0] ?? '';
            $type = $parts[1] ?? 'string';

            if (empty($name)) {
                continue;
            }

            $field = [
                'name' => Str::snake($name),
                'type' => $type,
                'nullable' => in_array('nullable', $parts),
                'unique' => in_array('unique', $parts),
                'enum_options' => [],
            ];

            // Parse enum options
            if (preg_match('/enum\(([^)]+)\)/', $type, $matches)) {
                $field['type'] = 'enum';
                $field['enum_options'] = array_map('trim', explode(',', $matches[1]));
            }

            // Parse decimal precision
            if (preg_match('/decimal\((\d+),(\d+)\)/', $type, $matches)) {
                $field['type'] = 'decimal';
                $field['precision'] = (int)$matches[1];
                $field['scale'] = (int)$matches[2];
            }

            $fields[] = $field;
        }

        return $fields;
    }

    /**
     * Split field definitions by commas, respecting parentheses.
     *
     * @param string $input
     * @return array
     */
    protected function splitFieldDefinitions(string $input): array
    {
        $result = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];

            if ($char === '(') {
                $depth++;
                $current .= $char;
            } elseif ($char === ')') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $result[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty($current)) {
            $result[] = $current;
        }

        return $result;
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
            $this->error('  ✗ Module name is required.');
            return false;
        }

        if (!preg_match('/^[A-Za-z][A-Za-z0-9]*$/', $name)) {
            $this->error('  ✗ Module name must start with a letter and contain only letters and numbers.');
            $this->line('    <fg=gray>Example: CustomerPortal, OrderTracker, MyModule</>');
            return false;
        }

        if (strlen($name) < 3) {
            $this->error('  ✗ Module name must be at least 3 characters long.');
            return false;
        }

        $reserved = ['Module', 'Modules', 'App', 'Config', 'Database', 'Public', 'Resources', 'Routes', 'Storage', 'Tests', 'Vendor', 'Core', 'System'];
        if (in_array(Str::studly($name), $reserved)) {
            $this->error("  ✗ '{$name}' is a reserved name and cannot be used.");
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
     * Generate a human-readable title from StudlyCase.
     *
     * @param string $studlyName
     * @return string
     */
    protected function generateTitleName(string $studlyName): string
    {
        return trim(preg_replace('/([A-Z])/', ' $1', $studlyName));
    }

    /**
     * Get list of files to create.
     *
     * @return array
     */
    protected function getFilesToCreate(): array
    {
        $files = [
            'module.json',
            'composer.json',
            'start.php',
            'Config/config.php',
            "Providers/{$this->config['studly_name']}ServiceProvider.php",
            'Http/routes.php',
            "Http/Controllers/{$this->config['studly_name']}Controller.php",
            'Resources/views/index.blade.php',
            "Database/Seeders/{$this->config['studly_name']}DatabaseSeeder.php",
            "Public/css/{$this->config['lower_name']}.css",
            "Public/js/{$this->config['lower_name']}.js",
        ];

        if ($this->config['with_menu'] ?? false) {
            $files[] = 'Resources/views/partials/manage_menu_item.blade.php';
        }

        if (isset($this->config['entity_name'])) {
            $files[] = "Entities/{$this->config['entity_name']}.php";
        }

        if ($this->config['with_migration'] ?? false) {
            $files[] = "Database/Migrations/{$this->config['date_prefix']}_create_{$this->config['table_name']}_table.php";
        }

        if ($this->config['with_views'] ?? false) {
            $files[] = 'Resources/views/create.blade.php';
            $files[] = 'Resources/views/edit.blade.php';
            $files[] = 'Resources/views/partials/form.blade.php';
        }

        if ($this->config['with_requests'] ?? false) {
            $files[] = "Http/Requests/Store{$this->config['entity_name']}Request.php";
            $files[] = "Http/Requests/Update{$this->config['entity_name']}Request.php";
        }

        if ($this->config['with_api'] ?? false) {
            $files[] = "Http/Controllers/Api/{$this->config['entity_name']}Controller.php";
            $files[] = 'Http/api-routes.php';
        }

        if ($this->config['with_permissions'] ?? false) {
            $files[] = "Support/{$this->config['studly_name']}Permissions.php";
        }

        if ($this->config['with_widget'] ?? false) {
            $files[] = "Widgets/{$this->config['studly_name']}Widget.php";
            $files[] = 'Resources/views/widgets/dashboard.blade.php';
        }

        if ($this->config['with_tests'] ?? false) {
            $files[] = "Tests/Unit/{$this->config['entity_name']}Test.php";
            $files[] = "Tests/Feature/{$this->config['studly_name']}ControllerTest.php";
        }

        if ($this->config['with_readme'] ?? false) {
            $files[] = 'README.md';
        }

        return $files;
    }

    /**
     * Display configuration summary.
     */
    protected function displayConfigSummary(): void
    {
        $this->info('  ┌─────────────────────────────────────────────────────────────┐');
        $this->info('  │                    Module Summary                           │');
        $this->info('  ├─────────────────────────────────────────────────────────────┤');

        $this->displaySummaryRow('Name', $this->config['studly_name']);
        $this->displaySummaryRow('Template', $this->templates[$this->config['template']]['name'] ?? 'Basic');
        $this->displaySummaryRow('Description', Str::limit($this->config['description'], 35));
        $this->displaySummaryRow('Author', $this->config['author']);

        if (isset($this->config['entity_name'])) {
            $this->displaySummaryRow('Entity', $this->config['entity_name']);
            $this->displaySummaryRow('Table', $this->config['table_name']);

            if (!empty($this->config['fields'])) {
                $fieldNames = array_column($this->config['fields'], 'name');
                $this->displaySummaryRow('Fields', implode(', ', array_slice($fieldNames, 0, 3)) . (count($fieldNames) > 3 ? '...' : ''));
            }
        }

        $this->info('  ├─────────────────────────────────────────────────────────────┤');
        $this->line('  │  <info>Features:</info>                                                │');

        $features = [];
        if ($this->config['with_menu'] ?? false) $features[] = 'Menu';
        if ($this->config['with_views'] ?? false) $features[] = 'CRUD Views';
        if ($this->config['with_requests'] ?? false) $features[] = 'Validation';
        if ($this->config['with_api'] ?? false) $features[] = 'API';
        if ($this->config['with_tests'] ?? false) $features[] = 'Tests';
        if ($this->config['with_widget'] ?? false) $features[] = 'Widget';
        if ($this->config['with_permissions'] ?? false) $features[] = 'Permissions';
        if ($this->config['with_readme'] ?? false) $features[] = 'Docs';

        $featureStr = implode(', ', $features) ?: 'None';
        $this->displaySummaryRow('Enabled', $featureStr);

        $this->info('  └─────────────────────────────────────────────────────────────┘');
    }

    /**
     * Display a summary row.
     *
     * @param string $label
     * @param string $value
     */
    protected function displaySummaryRow(string $label, string $value): void
    {
        $label = str_pad($label . ':', 14);
        $value = str_pad($value, 43);
        $this->line("  │  {$label}<comment>{$value}</comment>│");
    }

    /**
     * Create the module.
     *
     * @return int
     */
    protected function createModule(): int
    {
        $this->newLine();
        $this->info('  Creating module...');
        $this->newLine();

        try {
            $this->createDirectories();
            $this->createFiles();
            $this->runPostCreationTasks();
            $this->displaySuccessMessage();
            return 0;
        } catch (\Exception $e) {
            $this->error("  ✗ Error: {$e->getMessage()}");
            return 1;
        }
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
            'Http/Controllers/Api',
            'Http/Requests',
            'Providers',
            'Public/css',
            'Public/js',
            'Resources/lang',
            'Resources/views/partials',
            'Resources/views/widgets',
            'Support',
            'Widgets',
            'Tests/Unit',
            'Tests/Feature',
        ];

        $basePath = $this->config['module_path'];

        if (($this->option('force') || ($this->config['force'] ?? false)) && $this->filesystem->isDirectory($basePath)) {
            $this->filesystem->deleteDirectory($basePath);
        }

        foreach ($directories as $dir) {
            $path = $basePath . '/' . $dir;
            $this->filesystem->makeDirectory($path, 0755, true, true);
        }

        $this->line('    <fg=green>✓</> Created directory structure');
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
        $this->createFile('Config/config.php', 'config.stub');

        // Provider
        $providerStub = ($this->config['with_menu'] ?? false) ? 'provider-with-menu.stub' : 'provider.stub';
        $this->createFile("Providers/{$this->config['studly_name']}ServiceProvider.php", $providerStub);

        // Routes
        $this->createFile('Http/routes.php', 'routes.stub');

        // Controller
        $controllerStub = ($this->config['with_views'] ?? false) ? 'controller-crud.stub' : 'controller.stub';
        $this->createFile("Http/Controllers/{$this->config['studly_name']}Controller.php", $controllerStub);

        // Views
        $this->createFile('Resources/views/index.blade.php', 'views/index.stub');

        if ($this->config['with_menu'] ?? false) {
            $this->createFile('Resources/views/partials/manage_menu_item.blade.php', 'views/menu-item.stub');
        }

        if ($this->config['with_views'] ?? false) {
            $this->createFile('Resources/views/create.blade.php', 'views/create.stub');
            $this->createFile('Resources/views/edit.blade.php', 'views/edit.stub');
            $this->createFile('Resources/views/partials/form.blade.php', 'views/form.stub');
        }

        // Seeder
        $this->createFile("Database/Seeders/{$this->config['studly_name']}DatabaseSeeder.php", 'seeder.stub');

        // Assets
        $this->createFile("Public/css/{$this->config['lower_name']}.css", 'assets/css.stub');
        $this->createFile("Public/js/{$this->config['lower_name']}.js", 'assets/js.stub');

        // Entity
        if (isset($this->config['entity_name'])) {
            $this->createFile("Entities/{$this->config['entity_name']}.php", 'entity.stub');
            $this->line("    <fg=green>✓</> Created entity: {$this->config['entity_name']}");
        }

        // Migration
        if (($this->config['with_migration'] ?? false) && isset($this->config['entity_name'])) {
            $migrationName = "{$this->config['date_prefix']}_create_{$this->config['table_name']}_table.php";
            $this->createFile("Database/Migrations/{$migrationName}", 'migration.stub');
            $this->line("    <fg=green>✓</> Created migration");
        }

        // Form Requests
        if (($this->config['with_requests'] ?? false) && isset($this->config['entity_name'])) {
            $this->createFile("Http/Requests/Store{$this->config['entity_name']}Request.php", 'request-store.stub');
            $this->createFile("Http/Requests/Update{$this->config['entity_name']}Request.php", 'request-update.stub');
            $this->line("    <fg=green>✓</> Created form requests");
        }

        // API
        if (($this->config['with_api'] ?? false) && isset($this->config['entity_name'])) {
            $this->createFile("Http/Controllers/Api/{$this->config['entity_name']}Controller.php", 'api-controller.stub');
            $this->createFile('Http/api-routes.php', 'api-routes.stub');
            $this->line("    <fg=green>✓</> Created API controller");
        }

        // Permissions
        if ($this->config['with_permissions'] ?? false) {
            $this->createFile("Support/{$this->config['studly_name']}Permissions.php", 'permissions.stub');
            $this->line("    <fg=green>✓</> Created permissions helper");
        }

        // Widget
        if ($this->config['with_widget'] ?? false) {
            $this->createFile("Widgets/{$this->config['studly_name']}Widget.php", 'widget.stub');
            $this->createFile('Resources/views/widgets/dashboard.blade.php', 'views/widget.stub');
            $this->line("    <fg=green>✓</> Created dashboard widget");
        }

        // Tests
        if (($this->config['with_tests'] ?? false) && isset($this->config['entity_name'])) {
            $this->createFile("Tests/Unit/{$this->config['entity_name']}Test.php", 'test-unit.stub');
            $this->createFile("Tests/Feature/{$this->config['studly_name']}ControllerTest.php", 'test-feature.stub');
            $this->line("    <fg=green>✓</> Created tests");
        }

        // README
        if ($this->config['with_readme'] ?? false) {
            $this->createFile('README.md', 'readme.stub');
            $this->line("    <fg=green>✓</> Created documentation");
        }

        $this->line("    <fg=green>✓</> Created all module files");
    }

    /**
     * Create a file from a stub template.
     *
     * @param string $path
     * @param string $stub
     */
    protected function createFile(string $path, string $stub): void
    {
        $stubPath = $this->stubPath . '/' . $stub;

        if (!$this->filesystem->exists($stubPath)) {
            // Skip if stub doesn't exist (for optional features)
            return;
        }

        $content = $this->filesystem->get($stubPath);
        $content = $this->replaceVariables($content);

        $fullPath = $this->config['module_path'] . '/' . $path;
        $this->filesystem->put($fullPath, $content);
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
            $replacements['$ENTITY_LOWER$'] = Str::lower($this->config['entity_name']);
            $replacements['$ENTITY_SNAKE$'] = $this->config['entity_snake'];
            $replacements['$ENTITY_PLURAL$'] = $this->config['entity_plural'];
            $replacements['$TABLE_NAME$'] = $this->config['table_name'];

            // Generate field-related content
            $replacements['$MIGRATION_FIELDS$'] = $this->generateMigrationFields();
            $replacements['$FILLABLE_FIELDS$'] = $this->generateFillableFields();
            $replacements['$CAST_FIELDS$'] = $this->generateCastFields();
            $replacements['$VALIDATION_RULES$'] = $this->generateValidationRules();
            $replacements['$FORM_FIELDS$'] = $this->generateFormFields();
            $replacements['$TABLE_HEADERS$'] = $this->generateTableHeaders();
            $replacements['$TABLE_CELLS$'] = $this->generateTableCells();
        }

        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }

    /**
     * Generate migration field definitions.
     *
     * @return string
     */
    protected function generateMigrationFields(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "\$table->string('name', 255);";
        }

        $lines = [];
        foreach ($fields as $field) {
            $line = $this->getMigrationLine($field);
            if ($line) {
                $lines[] = "            {$line}";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Get migration line for a field.
     *
     * @param array $field
     * @return string
     */
    protected function getMigrationLine(array $field): string
    {
        $name = $field['name'];
        $type = $field['type'];
        $nullable = $field['nullable'] ?? false;
        $unique = $field['unique'] ?? false;

        switch ($type) {
            case 'string':
            case 'email':
                $line = "\$table->string('{$name}', 255)";
                break;
            case 'text':
                $line = "\$table->text('{$name}')";
                break;
            case 'integer':
                $line = "\$table->integer('{$name}')";
                break;
            case 'decimal':
                $precision = $field['precision'] ?? 10;
                $scale = $field['scale'] ?? 2;
                $line = "\$table->decimal('{$name}', {$precision}, {$scale})";
                break;
            case 'boolean':
                $line = "\$table->boolean('{$name}')->default(false)";
                break;
            case 'date':
                $line = "\$table->date('{$name}')";
                break;
            case 'datetime':
                $line = "\$table->dateTime('{$name}')";
                break;
            case 'enum':
                $options = array_map(function($o) { return "'{$o}'"; }, $field['enum_options'] ?? ['active', 'inactive']);
                $optionsStr = implode(', ', $options);
                $line = "\$table->enum('{$name}', [{$optionsStr}])";
                break;
            case 'json':
                $line = "\$table->json('{$name}')";
                break;
            case 'foreignId':
                $line = "\$table->foreignId('{$name}')->nullable()->constrained()->nullOnDelete()";
                return $line . ';';
            default:
                $line = "\$table->string('{$name}')";
        }

        if ($nullable) {
            $line .= '->nullable()';
        }
        if ($unique) {
            $line .= '->unique()';
        }

        return $line . ';';
    }

    /**
     * Generate fillable fields array.
     *
     * @return string
     */
    protected function generateFillableFields(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "'name',";
        }

        $names = array_map(function($f) { return "'{$f['name']}'"; }, $fields);
        return implode(",\n        ", $names) . ',';
    }

    /**
     * Generate cast fields array.
     *
     * @return string
     */
    protected function generateCastFields(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "'name' => 'string',";
        }

        $casts = [];
        foreach ($fields as $field) {
            $cast = match($field['type']) {
                'integer' => 'integer',
                'decimal' => 'decimal:2',
                'boolean' => 'boolean',
                'date' => 'date',
                'datetime' => 'datetime',
                'json' => 'array',
                default => 'string',
            };
            $casts[] = "'{$field['name']}' => '{$cast}'";
        }

        return implode(",\n        ", $casts) . ',';
    }

    /**
     * Generate validation rules.
     *
     * @return string
     */
    protected function generateValidationRules(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "'name' => ['required', 'string', 'max:255'],";
        }

        $rules = [];
        foreach ($fields as $field) {
            $fieldRules = $this->getValidationRulesForField($field);
            $rulesStr = implode("', '", $fieldRules);
            $rules[] = "'{$field['name']}' => ['{$rulesStr}']";
        }

        return implode(",\n            ", $rules) . ',';
    }

    /**
     * Get validation rules for a field.
     *
     * @param array $field
     * @return array
     */
    protected function getValidationRulesForField(array $field): array
    {
        $rules = [];

        if (!($field['nullable'] ?? false)) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($field['type']) {
            case 'string':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'email':
                $rules[] = 'email';
                $rules[] = 'max:255';
                break;
            case 'text':
                $rules[] = 'string';
                break;
            case 'integer':
                $rules[] = 'integer';
                break;
            case 'decimal':
                $rules[] = 'numeric';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'datetime':
                $rules[] = 'date';
                break;
            case 'enum':
                $options = implode(',', $field['enum_options'] ?? []);
                $rules[] = "in:{$options}";
                break;
            case 'json':
                $rules[] = 'array';
                break;
        }

        if ($field['unique'] ?? false) {
            $table = $this->config['table_name'];
            $rules[] = "unique:{$table},{$field['name']}";
        }

        return $rules;
    }

    /**
     * Generate form fields HTML.
     *
     * @return string
     */
    protected function generateFormFields(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return $this->getFormFieldHtml(['name' => 'name', 'type' => 'string', 'nullable' => false]);
        }

        $html = [];
        foreach ($fields as $field) {
            $html[] = $this->getFormFieldHtml($field);
        }

        return implode("\n\n", $html);
    }

    /**
     * Get form field HTML for a field.
     *
     * @param array $field
     * @return string
     */
    protected function getFormFieldHtml(array $field): string
    {
        $name = $field['name'];
        $label = Str::title(str_replace('_', ' ', $name));
        $required = !($field['nullable'] ?? false) ? 'required' : '';
        $type = $field['type'];

        switch ($type) {
            case 'text':
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <textarea id="{$name}" class="form-control" name="{$name}" rows="3" {$required}>{{ old('{$name}', \$item->{$name} ?? '') }}</textarea>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'boolean':
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <div class="checkbox">
            <label>
                <input type="hidden" name="{$name}" value="0">
                <input type="checkbox" name="{$name}" value="1" {{ old('{$name}', \$item->{$name} ?? false) ? 'checked' : '' }}>
                {{ __('{$label}') }}
            </label>
        </div>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'enum':
                $options = $field['enum_options'] ?? ['active', 'inactive'];
                $optionsHtml = '';
                foreach ($options as $opt) {
                    $optLabel = Str::title($opt);
                    $optionsHtml .= "                <option value=\"{$opt}\" {{ old('{$name}', \$item->{$name} ?? '') == '{$opt}' ? 'selected' : '' }}>{{ __('{$optLabel}') }}</option>\n";
                }
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <select id="{$name}" class="form-control" name="{$name}" {$required}>
{$optionsHtml}        </select>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'date':
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <input id="{$name}" type="date" class="form-control" name="{$name}" value="{{ old('{$name}', isset(\$item->{$name}) ? \$item->{$name}->format('Y-m-d') : '') }}" {$required}>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'datetime':
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <input id="{$name}" type="datetime-local" class="form-control" name="{$name}" value="{{ old('{$name}', isset(\$item->{$name}) ? \$item->{$name}->format('Y-m-d\TH:i') : '') }}" {$required}>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'integer':
            case 'decimal':
                $step = $type === 'decimal' ? 'step="0.01"' : '';
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <input id="{$name}" type="number" class="form-control" name="{$name}" value="{{ old('{$name}', \$item->{$name} ?? '') }}" {$step} {$required}>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            case 'email':
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <input id="{$name}" type="email" class="form-control" name="{$name}" value="{{ old('{$name}', \$item->{$name} ?? '') }}" maxlength="255" {$required}>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;

            default: // string
                return <<<HTML
<div class="form-group{{ \$errors->has('{$name}') ? ' has-error' : '' }}">
    <label for="{$name}" class="col-sm-2 control-label">{{ __('{$label}') }}</label>
    <div class="col-sm-6">
        <input id="{$name}" type="text" class="form-control" name="{$name}" value="{{ old('{$name}', \$item->{$name} ?? '') }}" maxlength="255" {$required}>
        @include('partials/field_error', ['field' => '{$name}'])
    </div>
</div>
HTML;
        }
    }

    /**
     * Generate table headers.
     *
     * @return string
     */
    protected function generateTableHeaders(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "<th>{{ __('Name') }}</th>";
        }

        $headers = [];
        foreach (array_slice($fields, 0, 4) as $field) {
            $label = Str::title(str_replace('_', ' ', $field['name']));
            $headers[] = "<th>{{ __('{$label}') }}</th>";
        }

        return implode("\n                            ", $headers);
    }

    /**
     * Generate table cells.
     *
     * @return string
     */
    protected function generateTableCells(): string
    {
        $fields = $this->config['fields'] ?? [];
        if (empty($fields)) {
            return "<td>{{ \$item->name }}</td>";
        }

        $cells = [];
        foreach (array_slice($fields, 0, 4) as $field) {
            $name = $field['name'];
            $type = $field['type'];

            switch ($type) {
                case 'boolean':
                    $cells[] = "<td>{{ \$item->{$name} ? __('Yes') : __('No') }}</td>";
                    break;
                case 'date':
                    $cells[] = "<td>{{ \$item->{$name} ? \$item->{$name}->format('M j, Y') : '-' }}</td>";
                    break;
                case 'datetime':
                    $cells[] = "<td>{{ \$item->{$name} ? \$item->{$name}->format('M j, Y H:i') : '-' }}</td>";
                    break;
                case 'enum':
                    $cells[] = "<td><span class=\"label label-default\">{{ ucfirst(\$item->{$name}) }}</span></td>";
                    break;
                default:
                    $cells[] = "<td>{{ \$item->{$name} }}</td>";
            }
        }

        return implode("\n                            ", $cells);
    }

    /**
     * Run post-creation tasks.
     */
    protected function runPostCreationTasks(): void
    {
        $publicPath = public_path('modules/' . $this->config['lower_name']);
        $modulePath = $this->config['module_path'] . '/Public';

        if (!$this->filesystem->isDirectory(public_path('modules'))) {
            $this->filesystem->makeDirectory(public_path('modules'), 0755, true);
        }

        if ($this->filesystem->exists($publicPath)) {
            if (is_link($publicPath)) {
                unlink($publicPath);
            } else {
                $this->filesystem->deleteDirectory($publicPath);
            }
        }

        if (function_exists('symlink')) {
            @symlink($modulePath, $publicPath);
            $this->line("    <fg=green>✓</> Created public symlink");
        }
    }

    /**
     * Display success message with next steps.
     */
    protected function displaySuccessMessage(): void
    {
        $this->newLine();
        $this->info('  ╔══════════════════════════════════════════════════════════════╗');
        $this->info('  ║           🎉 Module Created Successfully!                    ║');
        $this->info('  ╚══════════════════════════════════════════════════════════════╝');
        $this->newLine();

        $this->line("    Module: <comment>{$this->config['studly_name']}</comment>");
        $this->line("    Location: <comment>Modules/{$this->config['studly_name']}</comment>");
        $this->newLine();

        $this->info('    Next Steps:');
        $this->line('    ────────────────────────────────────────────────────────────');
        $this->newLine();

        $step = 1;

        if ($this->config['with_migration'] ?? false) {
            $this->line("    <comment>{$step}.</comment> Run database migration:");
            $this->line("       <fg=cyan>php artisan module:migrate {$this->config['studly_name']}</>");
            $this->newLine();
            $step++;
        }

        $this->line("    <comment>{$step}.</comment> Activate the module:");
        $this->line("       <fg=cyan>php artisan module:enable {$this->config['studly_name']}</>");
        $this->line("       Or go to: <fg=cyan>Admin → Modules → Activate</>");
        $this->newLine();
        $step++;

        $this->line("    <comment>{$step}.</comment> Clear the cache:");
        $this->line("       <fg=cyan>php artisan freescout:clear-cache</>");
        $this->newLine();
        $step++;

        $this->line("    <comment>{$step}.</comment> Access your module:");
        $this->line("       <fg=cyan>" . url($this->config['lower_name']) . "</>");
        $this->newLine();

        if ($this->config['with_readme'] ?? false) {
            $this->line("    📚 Documentation: <comment>Modules/{$this->config['studly_name']}/README.md</comment>");
            $this->newLine();
        }

        $this->info('    Happy coding! 🚀');
        $this->newLine();
    }
}
