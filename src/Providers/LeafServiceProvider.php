<?php namespace CubeSystems\Leaf\Providers;

use CubeSystems\Leaf\Admin\Admin;
use CubeSystems\Leaf\Admin\Form\Fields\Checkbox;
use CubeSystems\Leaf\Admin\Form\Fields\DateTime;
use CubeSystems\Leaf\Admin\Form\Fields\Hidden;
use CubeSystems\Leaf\Admin\Form\Fields\LeafFile;
use CubeSystems\Leaf\Admin\Form\Fields\Link;
use CubeSystems\Leaf\Admin\Form\Fields\Richtext;
use CubeSystems\Leaf\Admin\Form\Fields\Text;
use CubeSystems\Leaf\Admin\Form\Fields\Textarea;
use CubeSystems\Leaf\Admin\Form\Fields\Translatable;
use CubeSystems\Leaf\Admin\Form\FieldSet;
use CubeSystems\Leaf\Console\Commands\GenerateCommand;
use CubeSystems\Leaf\Console\Commands\GeneratorCommand;
use CubeSystems\Leaf\Console\Commands\InstallCommand;
use CubeSystems\Leaf\Console\Commands\SeedCommand;
use CubeSystems\Leaf\Files\LeafImage;
use CubeSystems\Leaf\Http\Middleware\LeafAdminAuthMiddleware;
use CubeSystems\Leaf\Http\Middleware\LeafAdminGuestMiddleware;
use CubeSystems\Leaf\Http\Middleware\LeafAdminHasAccessMiddleware;
use CubeSystems\Leaf\Http\Middleware\LeafAdminInRoleMiddleware;
use CubeSystems\Leaf\Http\Middleware\LeafRouteRedirectMiddleware;
use CubeSystems\Leaf\Menu\Menu;
use CubeSystems\Leaf\Menu\MenuFactory;
use CubeSystems\Leaf\Services\AssetPipeline;
use CubeSystems\Leaf\Services\FieldTypeRegistry;
use CubeSystems\Leaf\Services\StubRegistry;
use CubeSystems\Leaf\Views\LayoutViewComposer;
use Dimsav\Translatable\TranslatableServiceProvider;
use File;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\AliasLoader;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Maatwebsite\Excel\ExcelServiceProvider;
use Roboc\Glide\GlideImageServiceProvider;
use Route;

/**
 * Class LeafServiceProvider
 * @package CubeSystems\Leaf
 */
class LeafServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerResources();
        $this->registerServiceProviders();
        $this->registerAliases();
        $this->registerModuleRegistry();
        $this->registerCommands();
        $this->registerRoutesAndMiddlewares();
        $this->registerFields();
        $this->registerGeneratorStubs();
        $this->registerLocales();
        $this->registerViewComposers();
        $this->registerValidationRules();

        $this->loadTranslationsFrom( __DIR__ . '/resources/lang', 'leaf' );
    }

    /**
     * Register related service providers
     */
    private function registerServiceProviders()
    {
        $this->app->register( LeafTranslationServiceProvider::class );
        $this->app->register( TranslatableServiceProvider::class );
        $this->app->register( LeafFileServiceProvider::class );
        $this->app->register( LeafAuthServiceProvider::class );
        $this->app->register( GlideImageServiceProvider::class );
        $this->app->register( AssetServiceProvider::class );
        $this->app->register( SettingsServiceProvider::class );
        $this->app->register( ExcelServiceProvider::class );
    }

    /**
     * Register related aliases
     */
    private function registerAliases()
    {
        $aliasLoader = AliasLoader::getInstance();
//        $aliasLoader->alias( 'TranslationCache', \Waavi\Translation\Facades\TranslationCache::class );
        $aliasLoader->alias( 'Activation', \Cartalyst\Sentinel\Laravel\Facades\Activation::class );
        $aliasLoader->alias( 'Reminder', \Cartalyst\Sentinel\Laravel\Facades\Reminder::class );
        $aliasLoader->alias( 'Sentinel', \Cartalyst\Sentinel\Laravel\Facades\Sentinel::class );
        $aliasLoader->alias( 'GlideImage', \Roboc\Glide\Support\Facades\GlideImage::class );
        $aliasLoader->alias( 'Excel', \Maatwebsite\Excel\Facades\Excel::class );
    }

    /**
     * Publish configuration file.
     */
    private function registerResources()
    {
        $configFilename = __DIR__ . '/../../config/leaf.php';

        $this->mergeConfigFrom( $configFilename, 'leaf' );

        $this->publishes( [
            $configFilename => config_path( 'leaf.php' )
        ], 'config' );

        $this->publishes( [
            __DIR__ . '/../../stubs/settings.stub' => config_path( 'settings.php' )
        ], 'config' );

        $this->publishes( [
            __DIR__ . '/../../stubs/admin_routes.stub' => base_path( '/routes/admin.php' )
        ], 'config' );

        $this->publishes([
            __DIR__ . '/../../resources/lang/' => base_path('resources/lang/vendor/leaf')
        ], 'lang');

        $this->loadMigrationsFrom( __DIR__ . '/../../database/migrations' );
        $this->loadViewsFrom( __DIR__ . '/../../resources/views', 'leaf' );
    }

    /**
     * Load admin routes and register middleware
     */
    private function registerRoutesAndMiddlewares()
    {
        /**
         * @var Router $router
         */
        $router = $this->app[ 'router' ];

        $router->middlewareGroup( 'admin', [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ] );

        $router->pushMiddlewareToGroup( 'web', \CubeSystems\Leaf\Http\Middleware\LeafRouteRedirectMiddleware::class );

        $router->aliasMiddleware( 'leaf.admin_auth', LeafAdminAuthMiddleware::class );
        $router->aliasMiddleware( 'leaf.admin_quest', LeafAdminGuestMiddleware::class );
        $router->aliasMiddleware( 'leaf.admin_in_role', LeafAdminInRoleMiddleware::class );
        $router->aliasMiddleware( 'leaf.admin_has_access', LeafAdminHasAccessMiddleware::class );
        $router->aliasMiddleware( 'leaf.route_redirect', LeafRouteRedirectMiddleware::class );

        $this->registerLeafRoutes();
        $this->registerAppRoutes();
    }

    private function registerLeafRoutes()
    {
        $this->app['router']->group( [
            'as' => 'admin.',
            'middleware' => 'admin',
            'namespace' => '\CubeSystems\Leaf\Http\Controllers',
            'prefix' => config( 'leaf.uri' )
        ], function ()
        {
            include __DIR__ . '/../../routes/admin.php';
        } );
    }

    private function registerAppRoutes()
    {
        $adminRoutes = base_path( 'routes/admin.php' );

        if( !File::exists( $adminRoutes ) )
        {
            return;
        }

        $this->app['router']->group( [
            'as' => 'admin.',
            'middleware' => [ 'admin', 'leaf.admin_auth' ],
            'namespace' => '',
            'prefix' => config( 'leaf.uri' )
        ],function () use ($adminRoutes)
        {
            include $adminRoutes;
        } );
    }

    /**
     * Register Leaf commands
     */
    private function registerCommands()
    {
        $commands = [
            'leaf.seed' => SeedCommand::class,
            'leaf.install' => InstallCommand::class,
            'leaf.generator' => GeneratorCommand::class,
            'leaf.generate' => GenerateCommand::class
        ];

        foreach( $commands as $containerKey => $commandClass )
        {
            $this->registerCommand( $containerKey, $commandClass );
        }
    }

    /**
     * @param string $containerKey
     * @param string $commandClass
     * @return void
     */
    private function registerCommand( string $containerKey, string $commandClass )
    {
        $this->app->singleton( $containerKey, function () use ( $commandClass )
        {
            return $this->app->make( $commandClass );
        } );

        $this->commands( $containerKey );
    }

    /**
     * Register Leaf module registry
     */
    private function registerModuleRegistry()
    {
        $this->app->singleton( 'leaf', function ()
        {
            return new Admin(
                $this->app['sentinel'],
                new Menu(),
                new AssetPipeline()
            );
        } );

        $this->app->singleton( Admin::class, function ()
        {
            return $this->app['leaf'];
        } );
    }

    /**
     * Register leaf fields
     */
    private function registerFields()
    {
        $this->app->singleton( FieldTypeRegistry::class, function ( Application $app )
        {
            $fieldTypeRegistry = new FieldTypeRegistry();

            $fieldTypeRegistry->registerByType( 'integer', Hidden::class, 'int' );
            $fieldTypeRegistry->registerByType( 'string', Text::class, 'string' );
            $fieldTypeRegistry->registerByType( 'text', Textarea::class, 'string' );
            $fieldTypeRegistry->registerByType( 'longtext', Richtext::class, 'string' );
            $fieldTypeRegistry->registerByType( 'datetime', DateTime::class, 'string' );
            $fieldTypeRegistry->registerByType( 'boolean', Checkbox::class, 'bool' );

            $fieldTypeRegistry->registerByRelation( 'file', LeafFile::class );
            $fieldTypeRegistry->registerByRelation( 'image', LeafImage::class );
            $fieldTypeRegistry->registerByRelation( 'link', Link::class );

            return $fieldTypeRegistry;
        } );
    }

    /**
     * Register stubs used by generators
     */
    private function registerGeneratorStubs()
    {
        $this->app->singleton( StubRegistry::class, function( Application $app )
        {
            $stubRegistry = new StubRegistry();

            $stubRegistry->registerStubs(
                $app[ Filesystem::class ],
                base_path( 'vendor/cubesystems/leaf/stubs' )
            );

            return $stubRegistry;
        } );
    }

    /**
     * @return void
     */
    private function registerLocales()
    {
        config()->set( 'translator.locales', config( 'leaf.locales' ) );
        config()->set( 'translatable.locales', config( 'leaf.locales' ) );
    }

    /**
     * @return void
     */
    private function registerViewComposers()
    {
        $this->app->make( 'view' )->composer( '*layout*', LayoutViewComposer::class );
    }

    /**
     * @return void
     */
    private function registerValidationRules()
    {
        \Validator::extendImplicit( 'leaf_file_required', function( $attribute )
        {
            /** @var FieldSet $fields */
            $request = \request();
            $fields = $request->request->get( 'fields' );
            $field = $fields->findFieldByInputName( $attribute );
            $file = $request->file( $attribute );

            if( !$field )
            {
                return (bool) $file;
            }

            return $field->getValue() || $file;
        } );

        \Validator::extendImplicit( 'leaf_require_one_localized', function( $attribute, $value )
        {
            /** @var FieldSet $fieldSet */
            $request = \request();
            $fieldSet = $request->request->get( 'fields' );
            $fields = $fieldSet->findFieldsByInputName( $attribute );
            $translatable = null;

            foreach( array_reverse( $fields ) as $index => $field )
            {
                if ( $field instanceof Translatable )
                {
                    $translatable = $field;
                }
            }

            if ( !$translatable || $value )
            {
                return (bool) $value;
            }

            $attributeLocale = null;
            $checkLocales = $translatable->getLocales();

            foreach( $checkLocales as $index => $checkLocale )
            {
                if( str_contains( $attribute, $checkLocale ) )
                {
                    $attributeLocale = $checkLocale;
                    unset( $checkLocales[ $index ] );
                    break;
                }
            }

            foreach( $checkLocales as $index => $checkLocale )
            {
                $checkByAttribute = str_replace( $attributeLocale, $checkLocale, $attribute );
                $field = $fieldSet->findFieldByInputName( $checkByAttribute );

                if( $request->input( $checkByAttribute ) || $field->getValue() )
                {
                    return true;
                }
            }

            return false;
        } );
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ 'leaf', 'leaf.seed', 'leaf.modules', 'leaf.menu' ];
    }
}
