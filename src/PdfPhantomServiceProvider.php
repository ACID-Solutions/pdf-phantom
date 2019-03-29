<?php

namespace AcidSolutions\PdfPhantom;

use Illuminate\Foundation\AliasLoader;
use Illuminate\Support\ServiceProvider;

class PdfPhantomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/config/pdf_phantom.php', 'pdf_phantom');

        $this->app->singleton('pdf_phantom', function ($app) {
            $generator = new PdfGenerator;
            $generator->setBaseUrl($app['config']['pdf_phantom.base_url'] ?: url('/'));
            $generator->setBinaryPath($app['config']['pdf_phantom.binary_path']);
            $generator->setStoragePath($app['config']['pdf_phantom.temporary_file_path']);
            $generator->setTimeout($app['config']['pdf_phantom.timeout']);
	    $generator->useScript($app['config']['pdf_phantom.generation_script']);

            foreach ($app['config']['pdf_phantom.command_line_options'] as $option) {
                $generator->addCommandLineOption($option);
            }

            return $generator;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/config/pdf_phantom.php' => config_path('pdf_phantom.php')
        ]);

        // $this->app->alias('pdf_phantom', PdfGenerator::class);

        if (class_exists('Illuminate\Foundation\AliasLoader')) {
            AliasLoader::getInstance()->alias(
                'PdfPhantom',
                'AcidSolutions\PdfPhantom\PdfPhantomFacade'
            );
        } else {
            class_alias('AcidSolutions\PdfPhantom\PdfPhantomFacade', 'PdfPhantom');
        }
    }
}
