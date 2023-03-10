<?php

namespace Assets\Support;

use Assets\AssetsGateway;
use Assets\FileGateway;
use Assets\GenericDocumentGateway;
use Assets\ImageGateway;
use Assets\Manipulators\Generic\GenericDocumentManipulator;
use Assets\Manipulators\Images\CompanyLogoManipulator;
use Assets\Manipulators\Images\ImageProfileManipulator;
use Assets\Manipulators\Images\BannerImageManipulator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\Application;

/**
 * Class AssetServiceProvider
 *
 * @package Assets\Support
 * @author Luis A. Perez <luis@coreux.io>
 */
class AssetServiceProvider extends ServiceProvider
{
    /**
     * Load migration from the package folder
     */
    public function boot()
    {
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }


    /**
     * Create the User Gateway as a singleton
     */
    public function register()
    {
        $this->app->singleton(AssetsGateway::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class];
            // build drivers array
            $drivers = array();
            foreach ($config['drivers'] as $driverName => $driverInfo) {
                $drivers[$driverName] = $app->make($driverInfo['class']);
            }
            return new AssetsGateway(
                $config,
                $drivers
            );
        });
        // Build Gateways
        $this->app->singleton(ImageGateway::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][ImageGateway::DOCUMENT_TYPE]['config'];
            // build drivers array
            $manipulators = array();
            foreach ($config['manipulators'] as $manipulatorName => $driverInfo) {
                $manipulators[$manipulatorName] = $app->make($driverInfo['class']);
            }
            return new ImageGateway(
                $config,
                $app->make(FileGateway::class),
                Storage::disk($app['config']['assets'][FileGateway::class]['local_driver']),
                $manipulators
            );
        });
        $this->app->singleton(GenericDocumentGateway::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][GenericDocumentGateway::DOCUMENT_TYPE]['config'];
            // build drivers array
            $manipulators = array();
            foreach ($config['manipulators'] as $manipulatorName => $driverInfo) {
                $manipulators[$manipulatorName] = $app->make($driverInfo['class']);
            }
            return new GenericDocumentGateway(
                $config,
                $app->make(FileGateway::class),
                Storage::disk($app['config']['assets'][FileGateway::class]['local_driver']),
                $manipulators
            );
        });
        $this->app->singleton(FileGateway::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][FileGateway::class];
            return new FileGateway(
                $config,
                Storage::disk($config['local_driver']),
                Storage::disk($config['cloud_driver'])
            );
        });
        // Build Manipulators
        $this->app->singleton(ImageProfileManipulator::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][ImageGateway::DOCUMENT_TYPE]['config']['manipulators'][ImageProfileManipulator::MANIPULATOR_NAME];
            return new ImageProfileManipulator(
                $config
            );
        });
        $this->app->singleton(BannerImageManipulator::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][ImageGateway::DOCUMENT_TYPE]['config']['manipulators'][BannerImageManipulator::MANIPULATOR_NAME];
            return new BannerImageManipulator(
                $config
            );
        });
        $this->app->singleton(GenericDocumentManipulator::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][GenericDocumentGateway::DOCUMENT_TYPE]['config']['manipulators'][GenericDocumentManipulator::MANIPULATOR_NAME];
            return new GenericDocumentManipulator(
                $config
            );
        });
        $this->app->singleton(CompanyLogoManipulator::class, function (Application $app, array $params = []) {
            $config = $app['config']['assets'][AssetsGateway::class]['drivers'][ImageGateway::DOCUMENT_TYPE]['config']['manipulators'][CompanyLogoManipulator::MANIPULATOR_NAME];
            return new CompanyLogoManipulator(
                $config
            );
        });
    }
}