# Module Creation Utility - Implementation Plan

## Overview

Create an artisan command utility (`freescout:module-create`) that generates complete, production-ready FreeScout modules in real-time with all necessary boilerplate files.

---

## Findings: Module Structure Analysis

Based on the existing `Tags` module, a FreeScout module follows this structure:

```
Modules/{ModuleName}/
├── Config/
│   └── config.php                    # Module configuration
├── Database/
│   ├── Migrations/                   # Database migrations
│   │   └── {date}_{migration}.php
│   ├── Seeders/
│   │   └── {Module}DatabaseSeeder.php
│   └── factories/                    # Model factories (optional)
├── Entities/
│   └── {Model}.php                   # Eloquent models
├── Http/
│   ├── Controllers/
│   │   └── {Module}Controller.php
│   └── routes.php                    # Route definitions
├── Providers/
│   └── {Module}ServiceProvider.php   # Main service provider
├── Public/
│   ├── css/
│   │   └── {module}.css
│   └── js/
│       └── {module}.js
├── Resources/
│   ├── lang/                         # Translation files
│   └── views/
│       ├── index.blade.php
│       └── partials/
├── Support/                          # Helper classes (optional)
├── composer.json                     # Composer autoload config
├── module.json                       # Module metadata
└── start.php                         # Module bootstrap
```

---

## Implementation Plan

### Phase 1: Core Command Structure

**File:** `app/Console/Commands/ModuleCreate.php`

#### Features:
1. **Interactive CLI prompts** for module configuration
2. **Input validation** for module names
3. **Conflict detection** (check if module already exists)

#### Command Signature:
```bash
php artisan freescout:module-create {name?} {--description=} {--author=} {--with-entity=} {--with-migration} {--force}
```

#### Options:
| Option | Description |
|--------|-------------|
| `name` | Module name (StudlyCase, e.g., "CustomerPortal") |
| `--description` | Module description |
| `--author` | Author name |
| `--with-entity` | Create an entity/model with this name |
| `--with-migration` | Generate a migration for the entity |
| `--force` | Overwrite existing module |

---

### Phase 2: File Generation

#### Files to Generate:

| File | Template Variables |
|------|-------------------|
| `module.json` | STUDLY_NAME, LOWER_NAME, DESCRIPTION, AUTHOR |
| `composer.json` | STUDLY_NAME, LOWER_NAME, AUTHOR_NAME, AUTHOR_EMAIL |
| `start.php` | ROUTES_LOCATION |
| `Config/config.php` | STUDLY_NAME, LOWER_NAME |
| `Providers/{Module}ServiceProvider.php` | NAMESPACE, CLASS, LOWER_NAME, paths |
| `Http/routes.php` | MODULE_NAMESPACE, STUDLY_NAME, LOWER_NAME |
| `Http/Controllers/{Module}Controller.php` | NAMESPACE, CLASS, STUDLY_NAME |
| `Resources/views/index.blade.php` | STUDLY_NAME, LOWER_NAME |
| `Database/Seeders/{Module}DatabaseSeeder.php` | NAMESPACE, CLASS |
| `Public/css/{module}.css` | Basic boilerplate |
| `Public/js/{module}.js` | Basic boilerplate |

#### Optional Files (when `--with-entity`):
| File | Template Variables |
|------|-------------------|
| `Entities/{Entity}.php` | NAMESPACE, CLASS, TABLE_NAME |
| `Database/Migrations/{date}_create_{table}.php` | CLASS, TABLE_NAME |
| `Support/{Module}Permissions.php` | NAMESPACE, CLASS |

---

### Phase 3: Implementation Details

#### 1. Command Class Structure

```php
class ModuleCreate extends Command
{
    protected $signature = 'freescout:module-create
        {name? : The name of the module (StudlyCase)}
        {--description= : Module description}
        {--author= : Author name}
        {--with-entity= : Create entity with this name}
        {--with-migration : Generate migration for entity}
        {--force : Overwrite existing module}';

    protected $description = 'Create a new FreeScout module with all boilerplate files';

    // Properties
    protected $moduleName;      // StudlyCase
    protected $moduleAlias;     // lowercase
    protected $modulePath;      // Full path to module
    protected $stubPath;        // Path to stub templates

    // Methods
    public function handle();
    protected function gatherModuleInfo();
    protected function validateModuleName($name);
    protected function moduleExists();
    protected function createDirectories();
    protected function createFiles();
    protected function createOptionalFiles();
    protected function replaceStubVariables($stub, $replacements);
    protected function getStub($name);
    protected function publishAssets();
}
```

#### 2. Directory Creation Order

```php
$directories = [
    'Config',
    'Database/Migrations',
    'Database/Seeders',
    'Database/factories',
    'Entities',
    'Http/Controllers',
    'Providers',
    'Public/css',
    'Public/js',
    'Resources/lang',
    'Resources/views/partials',
    'Support',
];
```

#### 3. Stub Templates to Create

Create custom stubs in `app/Console/Commands/stubs/module/`:

```
stubs/module/
├── module.stub              # module.json template
├── composer.stub            # composer.json template
├── start.stub               # start.php template
├── config.stub              # Config/config.php template
├── provider.stub            # ServiceProvider template
├── routes.stub              # Http/routes.php template
├── controller.stub          # Controller template
├── view-index.stub          # index.blade.php template
├── seeder.stub              # DatabaseSeeder template
├── entity.stub              # Model template
├── migration.stub           # Migration template
├── permissions.stub         # Permissions helper template
├── css.stub                 # CSS boilerplate
└── js.stub                  # JS boilerplate
```

#### 4. Template Variables Reference

| Variable | Example Value | Description |
|----------|---------------|-------------|
| `$STUDLY_NAME$` | `CustomerPortal` | StudlyCase module name |
| `$LOWER_NAME$` | `customerportal` | Lowercase module alias |
| `$SNAKE_NAME$` | `customer_portal` | Snake_case for tables |
| `$MODULE_NAMESPACE$` | `Modules` | Module namespace prefix |
| `$DESCRIPTION$` | `Customer portal module` | Module description |
| `$AUTHOR$` | `FreeScout` | Author name |
| `$AUTHOR_EMAIL$` | `support@freescout.net` | Author email |
| `$DATE_PREFIX$` | `2026_01_05_000001` | Migration date prefix |
| `$ENTITY_NAME$` | `Customer` | Entity class name |
| `$TABLE_NAME$` | `customers` | Database table name |

---

### Phase 4: Post-Creation Tasks

After module creation, the command should:

1. **Create public symlink:**
   ```bash
   php artisan freescout:module-install {module}
   ```

2. **Display next steps:**
   ```
   Module "CustomerPortal" created successfully!

   Next steps:
   1. Run migrations: php artisan module:migrate CustomerPortal
   2. Activate module in Admin → Modules
   3. Clear cache: php artisan freescout:clear-cache
   ```

---

## File Contents Templates

### module.json
```json
{
    "name": "$STUDLY_NAME$",
    "alias": "$LOWER_NAME$",
    "description": "$DESCRIPTION$",
    "version": "1.0.0",
    "detailsUrl": "",
    "author": "$AUTHOR$",
    "authorUrl": "",
    "requiredAppVersion": "1.0.2",
    "license": "AGPL-3.0",
    "keywords": [],
    "active": 0,
    "order": 0,
    "providers": [
        "Modules\\$STUDLY_NAME$\\Providers\\$STUDLY_NAME$ServiceProvider"
    ],
    "aliases": {},
    "files": ["start.php"],
    "requires": []
}
```

### ServiceProvider (hooks example)
```php
public function hooks()
{
    // Add menu item
    \Eventy::addAction('menu.manage.after_mailboxes', function () {
        if (!auth()->check()) {
            return;
        }
        echo view('$LOWER_NAME$::partials.manage_menu_item')->render();
    });

    // Register stylesheets
    \Eventy::addFilter('stylesheets', function ($styles) {
        $styles[] = '/modules/$LOWER_NAME$/css/$LOWER_NAME$.css';
        return $styles;
    });

    // Register scripts
    \Eventy::addFilter('javascripts', function ($scripts) {
        $scripts[] = '/modules/$LOWER_NAME$/js/$LOWER_NAME$.js';
        return $scripts;
    });
}
```

---

## Implementation Steps

### Step 1: Create Command File
- [ ] Create `app/Console/Commands/ModuleCreate.php`
- [ ] Define command signature and description
- [ ] Implement `handle()` method

### Step 2: Create Stub Templates
- [ ] Create `app/Console/Commands/stubs/module/` directory
- [ ] Create all stub templates listed above

### Step 3: Implement Core Methods
- [ ] `gatherModuleInfo()` - Interactive prompts
- [ ] `validateModuleName()` - Name validation
- [ ] `createDirectories()` - Directory structure
- [ ] `createFiles()` - Generate files from stubs
- [ ] `replaceStubVariables()` - Variable substitution

### Step 4: Implement Optional Features
- [ ] Entity generation (`--with-entity`)
- [ ] Migration generation (`--with-migration`)
- [ ] Force overwrite (`--force`)

### Step 5: Post-Creation
- [ ] Auto-run module install
- [ ] Display success message with next steps

### Step 6: Testing
- [ ] Test basic module creation
- [ ] Test with entity and migration
- [ ] Test conflict detection
- [ ] Test force overwrite

---

## Usage Examples

### Basic Module Creation
```bash
php artisan freescout:module-create CustomerPortal
```

### With All Options
```bash
php artisan freescout:module-create CustomerPortal \
    --description="Customer self-service portal" \
    --author="MyCompany" \
    --with-entity=Customer \
    --with-migration
```

### Interactive Mode
```bash
php artisan freescout:module-create
# Prompts for: name, description, author, entity creation
```

---

## Summary

This utility will create a fully functional FreeScout module with:
- ✅ Proper directory structure
- ✅ Service provider with hooks boilerplate
- ✅ Routes and controller
- ✅ Views with partials
- ✅ CSS/JS assets
- ✅ Database migration and seeder stubs
- ✅ Optional entity/model generation
- ✅ Proper namespace configuration

**Estimated files to create:** 15-20 files (command + stubs)
