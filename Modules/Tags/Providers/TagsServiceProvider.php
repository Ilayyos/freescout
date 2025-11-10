<?php

namespace Modules\Tags\Providers;

use App\Option;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Support\ServiceProvider;
use Modules\Tags\Entities\Tag;
use Modules\Tags\Support\TagsPermissions;

class TagsServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Boot the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerConfig();
        $this->registerViews();
        $this->registerFactories();
        $this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');
        $this->hooks();
    }

    /**
     * Module hooks.
     */
    public function hooks()
    {
        \Eventy::addFilter('menu.selected', function ($menu) {
            $menu['manage']['tags'] = ['tags'];

            return $menu;
        });

        \Eventy::addAction('menu.manage.after_mailboxes', function () {
            if (!auth()->check()) {
                return;
            }

            echo view('tags::partials.manage_menu_item', [
                'canView' => TagsPermissions::canManage(auth()->user()),
            ])->render();
        });

        \Eventy::addFilter('stylesheets', function ($styles) {
            $asset = '/modules/tags/css/tags.css';
            if (!in_array($asset, $styles)) {
                $styles[] = $asset;
            }

            return $styles;
        });

        \Eventy::addFilter('javascripts', function ($scripts) {
            $asset = '/modules/tags/js/tags.js';
            if (!in_array($asset, $scripts)) {
                $scripts[] = $asset;
            }

            return $scripts;
        });

        \Eventy::addAction('conversation.convinfo.before_nav', function ($conversation, $mailbox = null) {
            if (!auth()->check()) {
                return;
            }

            $user = auth()->user();
            if (!$user->can('view', $conversation)) {
                return;
            }

            $tags = Tag::forConversation($conversation);
            $canEdit = $user->can('update', $conversation);
            $canView = $canEdit || $tags->isNotEmpty();

            if (!$canView) {
                return;
            }

            echo view('tags::partials.conversation_tags', [
                'conversation' => $conversation,
                'tags'         => $tags,
                'canView'      => $canView,
                'canEdit'      => $canEdit,
                'canCreate'    => $canEdit && TagsPermissions::canEditConversationTags($user),
            ])->render();
        }, 20, 1);

        \Eventy::addAction('settings.general.append', function ($settings, $errors) {
            if (!auth()->check() || !auth()->user()->isAdmin()) {
                return;
            }

            echo view('tags::partials.settings_toggle', [
                'allowUserManagement' => TagsPermissions::allowUserManagement(),
            ])->render();
        }, 20, 2);

        \Eventy::addFilter('settings.before_save', function ($request, $section, $settings) {
            if ($section !== 'general') {
                return $request;
            }

            $value = !empty($request->settings['tags_allow_user_management']);
            Option::set('tags.allow_user_management', $value);
            unset($request->settings['tags_allow_user_management']);

            return $request;
        }, 20, 3);
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerTranslations();
    }

    /**
     * Register config.
     *
     * @return void
     */
    protected function registerConfig()
    {
        $this->publishes([
            __DIR__.'/../Config/config.php' => config_path('tags.php'),
        ], 'config');
        $this->mergeConfigFrom(
            __DIR__.'/../Config/config.php', 'tags'
        );
    }

    /**
     * Register views.
     *
     * @return void
     */
    public function registerViews()
    {
        $viewPath = resource_path('views/modules/tags');

        $sourcePath = __DIR__.'/../Resources/views';

        $this->publishes([
            $sourcePath => $viewPath
        ],'views');

        $this->loadViewsFrom(array_merge(array_map(function ($path) {
            return $path . '/modules/tags';
        }, \Config::get('view.paths')), [$sourcePath]), 'tags');
    }

    /**
     * Register translations.
     *
     * @return void
     */
    public function registerTranslations()
    {
        $this->loadJsonTranslationsFrom(__DIR__ .'/../Resources/lang');
    }

    /**
     * Register an additional directory of factories.
     * @source https://github.com/sebastiaanluca/laravel-resource-flow/blob/develop/src/Modules/ModuleServiceProvider.php#L66
     */
    public function registerFactories()
    {
        if (! app()->environment('production')) {
            app(Factory::class)->load(__DIR__ . '/../Database/factories');
        }
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [];
    }
}
