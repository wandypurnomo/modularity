<?php

namespace Wandypurnomo\Modularity\Providers;

use Eventy;
use File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Nadar\PhpComposerReader\Autoload;
use Nadar\PhpComposerReader\AutoloadSection;
use Nadar\PhpComposerReader\ComposerReader;

class ModularityProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->updateComposerAutoload();
        $this->discoverMeta();
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    private function discoverMeta()
    {
        // containers
        $providers = [];
        $aliases = [];
        $migrationPaths = [];
        $metas = [];

        $base = base_path("packages");
        $exclude = [".", ".."];
        try{
            $dirs = scandir($base);
            foreach ($dirs as $dir) {
                if (!in_array($dir, $exclude)) {
                    $subDirs = scandir($base . DIRECTORY_SEPARATOR . $dir);
                    foreach ($subDirs as $subdir) {
                        if (!in_array($subdir, $exclude)) {
                            $packageDir = $base . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $subdir;
                            $srcDir = $packageDir . DIRECTORY_SEPARATOR . "src";
                            $meta = include $packageDir . DIRECTORY_SEPARATOR . "meta.php";

                            if (is_dir($packageDir . DIRECTORY_SEPARATOR . "resources/views")) {
                                $meta["viewPath"] = $packageDir . DIRECTORY_SEPARATOR . "resources/views";

                                // set view namespace
                                $this->app["view"]->addNamespace($subdir, $meta["viewPath"]);
                            }

                            // scan helper
                            if (file_exists($srcDir . DIRECTORY_SEPARATOR . "helper.php")) {
                                include $srcDir . DIRECTORY_SEPARATOR . "helper.php";
                            }

                            // scan routes
                            if (file_exists($srcDir . DIRECTORY_SEPARATOR . "routes.php")) {
                                include $srcDir . DIRECTORY_SEPARATOR . "routes.php";
                            }

                            // scan assets
                            if (is_dir($packageDir . DIRECTORY_SEPARATOR . "assets") && !is_dir(public_path("vendors") . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $subdir)) {
                                $sourcePath = $packageDir . DIRECTORY_SEPARATOR . "assets";
                                $publicPath = public_path("vendors/" . $dir . DIRECTORY_SEPARATOR . $subdir);
                                File::makeDirectory($publicPath, 493, true, true);
                                File::link($sourcePath, $publicPath);
                            }

                            // scan migration
                            if (is_dir($packageDir . DIRECTORY_SEPARATOR . "database/migrations")) {
                                $migrationPath = $packageDir . DIRECTORY_SEPARATOR . "database/migrations";
                                $meta["migrationPath"] = $migrationPath;

                                $migrationPaths[] = $migrationPath;
                            }

                            $metas[$subdir] = $meta;

                            // get value from meta file
                            $metaProvider = array_key_exists("providers", $meta) ? $meta["providers"] : [];
                            $metaAliases = array_key_exists("aliases", $meta) ? $meta["aliases"] : [];

                            $providers = array_merge($providers, $metaProvider);
                            $aliases = array_merge($aliases, $metaAliases);
                        }

                    }
                }
            }

            Eventy::addFilter("meta-packages", function ($old) use ($metas) {
                if (!is_array($old)) {
                    $old = [];
                }

                return array_merge($old, $metas);
            });

            // set providers
            foreach ($providers as $provider) {
                $this->app->register($provider);
            }

            // set aliases
            foreach ($aliases as $alias) {
                $this->app->alias($alias[1], $alias[0]);
            }

            // set migration path
            $migrationPaths[] = "database/migrations";
            $this->loadMigrationsFrom($migrationPaths);
        }catch (\Exception $exception){
            printf("packages directory not found \n");
        }

    }

    private function updateComposerAutoload()
    {
        $composerFile = base_path("composer.json");
        $reader = new ComposerReader($composerFile);
        $section = new AutoloadSection($reader, AutoloadSection::TYPE_PSR4);
        $packages = $this->getPackages();
        $composerPackages = [];
        foreach ($section as $autoload) {
            $namespace = Str::replaceFirst("\\", "/", $autoload->namespace);
            $composerPackages[] = Str::replaceFirst("\\", "", $namespace);
        }

        $unavailableYet = array_diff(array_keys($packages), $composerPackages);

        if (count($unavailableYet) > 0) {
            foreach ($unavailableYet as $k => $v) {
                $namespace = Str::replaceFirst("/", "\\", $v) . "\\";
                $new = new Autoload($reader, $namespace, $packages[$v] . DIRECTORY_SEPARATOR . "src", AutoloadSection::TYPE_PSR4);
                $x = new AutoloadSection($reader);
                $x->add($new)->save();
            }
        }
    }

    private function getPackages()
    {
        $container = [];
        $base = base_path("packages");
        $exclude = [".", ".."];
        try{
            $dirs = scandir($base);
            foreach ($dirs as $dir) {
                if (!in_array($dir, $exclude)) {
                    $subDirs = scandir($base . DIRECTORY_SEPARATOR . $dir);
                    foreach ($subDirs as $subdir) {
                        if (!in_array($subdir, $exclude)) {
                            $container[Str::ucfirst($dir) . DIRECTORY_SEPARATOR . Str::ucfirst($subdir)] = "packages/" . $dir . DIRECTORY_SEPARATOR . $subdir;
                        }
                    }
                }
            }

            return $container;
        }catch (\Exception $exception){
            return [];
        }

    }
}
