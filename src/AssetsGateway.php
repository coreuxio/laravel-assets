<?php

namespace Assets;

use Assets\Contracts\HasAssetsInterface;
use Assets\Exceptions\AssetDriverNotFound;
use Assets\Exceptions\ValidationFailed;
use Assets\Models\Asset;
use Illuminate\Http\UploadedFile;

/**
 * Class AssetsGateway
 *
 * @package Assets
 * @author Luis A. Perez <luis@coreux.io>
 */
class AssetsGateway
{
    /**
     * @var array
     */
    protected $config;
    /**
     * @var DocumentsGateway[]
     */
    protected $drivers;

    /**
     * AssetsGateway constructor.
     *
     * @param array $config
     * @param array $drivers
     */
    public function __construct(array $config, array $drivers)
    {
        $this->config = $this->validatesConfig($config);
        $this->drivers = $this->validateDrivers($drivers);
    }

    /**
     * @param int $id
     * @param array $with
     * @return Asset
     */
    public function find(int $id, array $with = []):Asset{
        return Asset::with($with)->findOrFail($id);
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options
     *
     * @return \Assets\Models\Asset
     */
    public function createAsset(UploadedFile $file, array $options = []): Asset
    {
        // what type of asset is it
        $driver = $this->getDriver($file);
        // call create on gateway for whatever
        $document = $driver->create($file, $options);
        // get fresh asset
        $asset = $this->newAsset();
        // attach document to asset
        $document->asset()->save($asset);
        return $asset;
    }

    public function fileFromUrl(string $url): UploadedFile
    {
        list($localPath,$photoData) = $this->downloadInternetFile($url);
        $file = new UploadedFile($localPath,$url);
        return $file;
    }

    private function downloadInternetFile(string $url)
    {
        $localImage = storage_path('app/documents') . "/$url";
        $photo = collect($photos)->sortByDesc('width')->first();
        file_put_contents($localImage, file_get_contents($url));
        return ['local_path' => $localImage, 'data' => $photo];
    }

    /**
     * @param \Assets\Contracts\HasAssetsInterface $model
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options
     *
     * @return \Assets\Contracts\HasAssetsInterface
     */
    public function attachPrimaryAssetTo(HasAssetsInterface $model, UploadedFile $file, array $options = []): HasAssetsInterface
    {
        $options['model'] = $model;
        $asset = $this->createAsset($file, $options);
        $model->attachAsset($asset, true);
        return $model;
    }

    /**
     * @param array $attributes
     *
     * @return \Assets\Models\Asset
     */
    private function newAsset(array $attributes = []): Asset
    {
        return Asset::create($attributes);
    }


    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return \Assets\DocumentsGateway
     * @throws \Assets\Exceptions\AssetDriverNotFound
     */
    private function getDriver(UploadedFile $file): DocumentsGateway
    {
        $mimeType = $file->getClientMimeType() == "application/octet-stream" ? $file->getMimeType() : $file->getClientMimeType();
        foreach ($this->drivers as $driver) {
            if (in_array($mimeType, $driver->getConfig()['mimes'])) return $driver;
        }
        return $this->getDefaultDriver();
//        throw new AssetDriverNotFound("Driver for mime type $mimeType was not found.");
    }

    private function getDefaultDriver()
    {
        return $this->drivers[$this->config['default_driver']];
    }

    /**
     * @param array $config
     *
     * @return array
     */
    public function validatesConfig(array $config): array
    {
        // no need to validate for now
        return $config;
    }


    /**
     * @param array $drivers
     *
     * @return array
     * @throws \Assets\Exceptions\ValidationFailed
     */
    public function validateDrivers(array $drivers): array
    {
        foreach ($drivers as $driverName => $driver) {
            if (!$driver instanceof DocumentsGateway) {
                throw new ValidationFailed("Driver $driverName needs to extend the DocumentsGateway");
            }
        }
        return $drivers;
    }
}