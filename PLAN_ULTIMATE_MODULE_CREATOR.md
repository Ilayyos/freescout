# Ultimate Module Creator - Enhancement Plan

## Vision

Transform the module creator into the most comprehensive, intelligent, and developer-friendly module scaffolding tool for FreeScout. It should handle everything from simple modules to complex enterprise-grade integrations.

---

## Phase 1: Module Templates & Presets

### Template Types
Create pre-built templates for common module patterns:

| Template | Description | Generated Components |
|----------|-------------|---------------------|
| `basic` | Simple module with view | Controller, View, Routes |
| `crud` | Full CRUD operations | Model, Controller, Views, Migration, Requests |
| `api` | REST API module | API Controller, Resources, Routes |
| `widget` | Dashboard widget | Widget class, Widget view, Dashboard hook |
| `integration` | Third-party integration | Service class, Config, OAuth support |
| `report` | Reporting module | Report class, Export, Charts |
| `automation` | Workflow automation | Triggers, Actions, Conditions |
| `mailbox-extension` | Mailbox enhancement | Mailbox hooks, Conversation hooks |

### Command Enhancement
```bash
php artisan freescout:module-create ModuleName --template=crud
php artisan freescout:module-create ModuleName --template=api
php artisan freescout:module-create ModuleName --template=widget
```

---

## Phase 2: Advanced Scaffolding

### CRUD Scaffolding
- **Form Requests** - Validation classes with rules
- **Policies** - Authorization policies for models
- **Complete Views** - Index, Create, Edit, Show templates
- **Pagination** - Built-in pagination support
- **Search/Filter** - Search and filtering functionality
- **Sorting** - Column sorting support

### API Scaffolding
- **API Controllers** - RESTful API controllers
- **API Resources** - Laravel API Resources for transformation
- **API Routes** - Versioned API routes (v1, v2)
- **Rate Limiting** - Built-in rate limiting
- **API Documentation** - OpenAPI/Swagger stub

### Test Generation
```
Tests/
├── Unit/
│   └── {Entity}Test.php
└── Feature/
    ├── {Module}ControllerTest.php
    └── {Module}ApiTest.php
```

---

## Phase 3: FreeScout-Specific Features

### Hook Presets
Pre-built hooks for common FreeScout integrations:

```php
// Conversation hooks
'conversation.created'
'conversation.status_changed'
'conversation.assigned'
'conversation.merged'

// Thread hooks
'thread.created'
'thread.customer_replied'

// Customer hooks
'customer.created'
'customer.updated'

// Mailbox hooks
'mailbox.settings'
'mailbox.permissions'

// Dashboard hooks
'dashboard.before'
'dashboard.after'
'dashboard.widgets'

// Email hooks
'email.before_send'
'email.after_fetch'
```

### Widget System
```php
// Dashboard Widget class
class {Module}Widget extends Widget
{
    public $id = '{module}_widget';
    public $name = '{Module} Widget';

    public function render()
    {
        return view('{module}::widgets.dashboard');
    }
}
```

### Settings Page Integration
- Module settings in Admin panel
- Per-mailbox settings support
- User preferences support

---

## Phase 4: Enterprise Features

### Multi-tenancy Support
- Mailbox-scoped data
- User-scoped data
- Global vs local settings

### Background Jobs
```php
// Jobs/
class Process{Entity}Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
}
```

### Events & Listeners
```php
// Events/
class {Entity}Created
{
    public ${entity};
}

// Listeners/
class Handle{Entity}Created
{
    public function handle({Entity}Created $event) {}
}
```

### Scheduled Tasks
```php
// Console/Commands/
class {Module}ScheduledTask extends Command
{
    protected $signature = '{module}:process';
    protected $schedule = 'daily';
}
```

### Notifications
```php
// Notifications/
class {Entity}Notification extends Notification
{
    public function toMail($notifiable) {}
    public function toDatabase($notifiable) {}
}
```

---

## Phase 5: Developer Experience

### Interactive Wizard Mode
```bash
php artisan freescout:module-create --wizard

┌─────────────────────────────────────────────────────────────┐
│              FreeScout Module Creation Wizard               │
├─────────────────────────────────────────────────────────────┤
│  Step 1/5: Basic Information                                │
│                                                             │
│  ? Module Name: [CustomerPortal]                            │
│  ? Description: [Customer self-service portal]              │
│  ? Author: [Your Name]                                      │
│                                                             │
│  Step 2/5: Module Template                                  │
│                                                             │
│  ❯ basic    - Simple module with basic structure            │
│    crud     - Full CRUD with forms and validation           │
│    api      - REST API with versioning                      │
│    widget   - Dashboard widget                              │
│    integration - Third-party service integration            │
│                                                             │
│  Step 3/5: Features                                         │
│                                                             │
│  [x] Entity/Model                                           │
│  [x] Database Migration                                     │
│  [x] Form Requests (validation)                             │
│  [x] Policy (authorization)                                 │
│  [ ] API Controller                                         │
│  [x] Unit Tests                                             │
│  [x] Feature Tests                                          │
│  [ ] Scheduled Task                                         │
│  [ ] Background Jobs                                        │
│  [ ] Notifications                                          │
│                                                             │
│  Step 4/5: Hooks                                            │
│                                                             │
│  [x] Menu item (Manage section)                             │
│  [x] Stylesheets                                            │
│  [x] JavaScript                                             │
│  [ ] Dashboard widget                                       │
│  [ ] Conversation sidebar                                   │
│  [ ] Customer sidebar                                       │
│  [ ] Settings page                                          │
│                                                             │
│  Step 5/5: Confirmation                                     │
│                                                             │
│  Creating module with 15 files...                           │
└─────────────────────────────────────────────────────────────┘
```

### Entity Field Builder
```bash
php artisan freescout:module-create CustomerPortal \
    --with-entity=Customer \
    --fields="name:string,email:string:unique,status:enum(active,inactive),user_id:foreignId"
```

### Relationship Generator
```bash
--relationships="user:belongsTo:User,orders:hasMany:Order,tags:belongsToMany:Tag"
```

### Automatic README Generation
```markdown
# {Module} Module

## Description
{description}

## Installation
1. Copy module to `Modules/{Module}`
2. Run `php artisan module:migrate {Module}`
3. Activate in Admin → Modules

## Configuration
Edit `config/{module}.php`

## Usage
...

## API Endpoints
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET    | /api/v1/{module} | List all |
| POST   | /api/v1/{module} | Create |
| GET    | /api/v1/{module}/{id} | Show |
| PUT    | /api/v1/{module}/{id} | Update |
| DELETE | /api/v1/{module}/{id} | Delete |

## Hooks
- `{module}.created` - Fired when entity created
- `{module}.updated` - Fired when entity updated

## License
{license}
```

---

## Phase 6: Code Quality

### PSR-12 Compliance
All generated code follows PSR-12 standards

### PHPDoc Generation
Complete PHPDoc blocks for all classes and methods

### Type Hints
Full PHP 7.4+ type hints throughout

### Static Analysis Ready
- PHPStan level 8 compatible
- Psalm compatible

---

## Phase 7: Post-Generation Tools

### Subcommands
```bash
# Add entity to existing module
php artisan freescout:module-add-entity ModuleName EntityName --fields="..."

# Add controller to existing module
php artisan freescout:module-add-controller ModuleName ControllerName

# Add migration to existing module
php artisan freescout:module-add-migration ModuleName migration_name

# Add test to existing module
php artisan freescout:module-add-test ModuleName TestName --unit|--feature

# Add hook to existing module
php artisan freescout:module-add-hook ModuleName hook_name

# Add API endpoint to existing module
php artisan freescout:module-add-api ModuleName ResourceName

# Add scheduled task
php artisan freescout:module-add-task ModuleName TaskName --schedule=daily

# Add notification
php artisan freescout:module-add-notification ModuleName NotificationName
```

### Module Analyzer
```bash
php artisan freescout:module-analyze ModuleName

┌─────────────────────────────────────────────────────────────┐
│                    Module Analysis: Tags                     │
├─────────────────────────────────────────────────────────────┤
│  Structure:     ✓ Complete                                  │
│  PSR-4:         ✓ Valid                                     │
│  Migrations:    ✓ 1 migration found                         │
│  Tests:         ✗ No tests found                            │
│  Documentation: ✗ No README found                           │
│                                                             │
│  Recommendations:                                           │
│  - Add unit tests for Tag entity                            │
│  - Add feature tests for TagsController                     │
│  - Create README.md documentation                           │
└─────────────────────────────────────────────────────────────┘
```

---

## Implementation Order

### Phase 1 - Core Enhancement (Priority: High)
1. [ ] Add template system (basic, crud, api, widget)
2. [ ] Add field definition parser (--fields option)
3. [ ] Enhanced migration generator with field types
4. [ ] Form Request generator with validation rules

### Phase 2 - CRUD & API (Priority: High)
5. [ ] Complete CRUD views (index, create, edit, show)
6. [ ] API Controller template
7. [ ] API Resource template
8. [ ] Policy generator

### Phase 3 - Testing & Quality (Priority: Medium)
9. [ ] Unit test generator
10. [ ] Feature test generator
11. [ ] PHPDoc generator
12. [ ] README generator

### Phase 4 - FreeScout Integration (Priority: Medium)
13. [ ] Hook presets system
14. [ ] Widget generator
15. [ ] Settings page integration
16. [ ] Conversation/Customer sidebar hooks

### Phase 5 - Advanced Features (Priority: Low)
17. [ ] Job generator
18. [ ] Event/Listener generator
19. [ ] Notification generator
20. [ ] Scheduled task generator

### Phase 6 - Developer Tools (Priority: Low)
21. [ ] Interactive wizard mode
22. [ ] Module analyzer
23. [ ] Add-* subcommands
24. [ ] Relationship generator

---

## New Command Signature

```php
protected $signature = 'freescout:module-create
    {name? : Module name (StudlyCase)}
    {--template=basic : Module template (basic, crud, api, widget, integration, report)}
    {--description= : Module description}
    {--author= : Author name}
    {--author-email= : Author email}

    {--with-entity= : Create entity/model}
    {--fields= : Entity fields (name:type:modifiers,...)}
    {--relationships= : Entity relationships}

    {--with-migration : Generate migration}
    {--with-seeder : Generate seeder with sample data}
    {--with-factory : Generate model factory}

    {--with-requests : Generate form requests}
    {--with-policy : Generate authorization policy}
    {--with-resource : Generate API resource}

    {--with-views : Generate all CRUD views}
    {--with-api : Generate API controller and routes}
    {--with-tests : Generate unit and feature tests}

    {--with-widget : Generate dashboard widget}
    {--with-settings : Generate settings page}
    {--with-hooks= : Hook presets (menu,css,js,conversation,customer)}

    {--with-jobs : Generate job classes}
    {--with-events : Generate events and listeners}
    {--with-notifications : Generate notification classes}
    {--with-tasks : Generate scheduled tasks}

    {--with-readme : Generate README documentation}

    {--full : All features enabled}
    {--wizard : Interactive wizard mode}
    {--force : Overwrite existing module}
    {--dry-run : Preview files without creating}';
```

---

## New Stub Templates Required

### Core (existing + enhanced)
- module.stub ✓
- composer.stub ✓
- start.stub ✓
- config.stub ✓ (enhanced with more options)
- provider.stub ✓ (enhanced with hook presets)
- provider-with-menu.stub ✓
- routes.stub ✓ (enhanced with API routes)
- controller.stub ✓ (enhanced with complete CRUD)
- entity.stub ✓ (enhanced with relationships)
- migration.stub ✓ (enhanced with field types)
- seeder.stub ✓ (enhanced with sample data)
- permissions.stub ✓

### Views
- views/index.stub ✓ (enhanced with table, search, pagination)
- views/create.stub (new)
- views/edit.stub (new)
- views/show.stub (new)
- views/partials/form.stub (new)
- views/partials/table.stub (new)
- views/partials/filters.stub (new)
- views/menu-item.stub ✓

### API
- api-controller.stub (new)
- api-resource.stub (new)
- api-collection.stub (new)
- api-routes.stub (new)

### Validation & Auth
- request-store.stub (new)
- request-update.stub (new)
- policy.stub (new)

### Testing
- test-unit.stub (new)
- test-feature.stub (new)
- test-api.stub (new)
- factory.stub (new)

### Background Processing
- job.stub (new)
- event.stub (new)
- listener.stub (new)
- notification.stub (new)
- scheduled-task.stub (new)

### Widgets & Settings
- widget.stub (new)
- widget-view.stub (new)
- settings.stub (new)
- settings-view.stub (new)

### FreeScout Hooks
- hook-conversation.stub (new)
- hook-customer.stub (new)
- hook-mailbox.stub (new)
- hook-dashboard.stub (new)

### Documentation
- readme.stub (new)
- changelog.stub (new)

---

## Field Type Parser

```
--fields="name:string:255,email:string:unique,status:enum(active,inactive,pending),
          price:decimal(8,2),user_id:foreignId:cascadeOnDelete,
          metadata:json:nullable,published_at:timestamp:nullable:index"
```

### Supported Types
| Type | Migration | Cast | Example |
|------|-----------|------|---------|
| string | string | string | `name:string:100` |
| text | text | string | `description:text` |
| integer | integer | integer | `count:integer` |
| bigInteger | bigInteger | integer | `views:bigInteger` |
| decimal | decimal | decimal | `price:decimal(10,2)` |
| boolean | boolean | boolean | `is_active:boolean` |
| date | date | date | `birth_date:date` |
| datetime | dateTime | datetime | `published_at:datetime` |
| timestamp | timestamp | datetime | `expires_at:timestamp` |
| json | json | array | `metadata:json` |
| enum | enum | string | `status:enum(a,b,c)` |
| foreignId | foreignId | integer | `user_id:foreignId` |
| uuid | uuid | string | `uuid:uuid` |

### Modifiers
| Modifier | Migration | Example |
|----------|-----------|---------|
| nullable | nullable() | `email:string:nullable` |
| unique | unique() | `email:string:unique` |
| index | index() | `name:string:index` |
| default(x) | default(x) | `status:string:default(active)` |
| unsigned | unsigned() | `count:integer:unsigned` |
| cascadeOnDelete | cascadeOnDelete() | `user_id:foreignId:cascadeOnDelete` |

---

## Summary

This enhancement plan transforms the module creator into:

1. **Template-Based** - Pre-built patterns for common module types
2. **Field-Aware** - Parse field definitions to generate migrations, models, forms
3. **Test-Inclusive** - Auto-generate unit and feature tests
4. **API-Ready** - RESTful API scaffolding with resources
5. **FreeScout-Native** - Deep integration with hooks, widgets, settings
6. **Enterprise-Ready** - Jobs, events, notifications, scheduled tasks
7. **Developer-Friendly** - Interactive wizard, dry-run, analyzer
8. **Extensible** - Subcommands to add components to existing modules

**Total new stub templates:** ~30
**Total new features:** 24 major enhancements
