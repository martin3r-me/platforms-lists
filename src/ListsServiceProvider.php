<?php

namespace Platform\Lists;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Gate;
use Livewire\Livewire;
use Platform\Core\PlatformCore;
use Platform\Core\Routing\ModuleRouter;

use Platform\Lists\Models\ListsBoard;
use Platform\Lists\Models\ListsList;
use Platform\Lists\Policies\BoardPolicy;
use Platform\Lists\Policies\ListPolicy;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Illuminate\Support\Str;

class ListsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Commands können später hinzugefügt werden
    }

    public function boot(): void
    {
        // Config veröffentlichen & zusammenführen
        $this->publishes([
            __DIR__.'/../config/lists.php' => config_path('lists.php'),
        ], 'config');

        $this->mergeConfigFrom(__DIR__.'/../config/lists.php', 'lists');

        // Modul-Registrierung
        if (
            config()->has('lists.routing') &&
            config()->has('lists.navigation') &&
            Schema::hasTable('modules')
        ) {
            PlatformCore::registerModule([
                'key'        => 'lists',
                'title'      => 'Listen',
                'routing'    => config('lists.routing'),
                'guard'      => config('lists.guard'),
                'navigation' => config('lists.navigation'),
                'sidebar'    => config('lists.sidebar'),
                'billables'  => config('lists.billables', []),
            ]);
        }

        // Routen nur laden, wenn das Modul registriert wurde
        if (PlatformCore::getModule('lists')) {
            ModuleRouter::group('lists', function () {
                $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
            });
        }

        // Migrations, Views, Livewire-Komponenten
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'lists');
        $this->registerLivewireComponents();

        // Policies registrieren
        $this->registerPolicies();

        // Tools registrieren
        $this->registerTools();
    }
    
    protected function registerLivewireComponents(): void
    {
        $basePath = __DIR__ . '/Livewire';
        $baseNamespace = 'Platform\\Lists\\Livewire';
        $prefix = 'lists';

        if (!is_dir($basePath)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($basePath)
        );

        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $class = $baseNamespace . '\\' . $classPath;

            if (!class_exists($class)) {
                continue;
            }

            $aliasPath = str_replace(['\\', '/'], '.', Str::kebab(str_replace('.php', '', $relativePath)));
            $alias = $prefix . '.' . $aliasPath;

            Livewire::component($alias, $class);
        }
    }

    protected function registerPolicies(): void
    {
        $policies = [
            ListsBoard::class => BoardPolicy::class,
            ListsList::class => ListPolicy::class,
        ];

        foreach ($policies as $model => $policy) {
            if (class_exists($model) && class_exists($policy)) {
                Gate::policy($model, $policy);
            }
        }
    }

    protected function registerTools(): void
    {
        try {
            $registry = resolve(\Platform\Core\Tools\ToolRegistry::class);
            
            // Board-Tools
            $registry->register(new \Platform\Lists\Tools\CreateBoardTool());
            $registry->register(new \Platform\Lists\Tools\ListBoardsTool());
            $registry->register(new \Platform\Lists\Tools\GetBoardTool());
            $registry->register(new \Platform\Lists\Tools\UpdateBoardTool());
            $registry->register(new \Platform\Lists\Tools\DeleteBoardTool());
            
            // List-Tools
            $registry->register(new \Platform\Lists\Tools\CreateListTool());
            $registry->register(new \Platform\Lists\Tools\ListListsTool());
            $registry->register(new \Platform\Lists\Tools\GetListTool());
            $registry->register(new \Platform\Lists\Tools\UpdateListTool());
            $registry->register(new \Platform\Lists\Tools\DeleteListTool());
        } catch (\Throwable $e) {
            // Silent fail - Tool-Registry könnte nicht verfügbar sein
        }
    }
}
