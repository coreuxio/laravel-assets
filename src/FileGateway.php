<?php

namespace Assets;

use Assets\Exceptions\FileCouldNotBeMovedToCloud;
use Assets\Exceptions\ValidationFailed;
use Assets\Models\File;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Class FileGateway
 *
 * @package Assets
 * @author Luis A. Perez <luis@coreux.io>
 */
class FileGateway
{
    /**
     * @var array
     */
    protected $config;
    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $localDriver;
    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $cloudDriver;


    /**
     * FileGateway constructor.
     *
     * @param array $config
     * @param \Illuminate\Filesystem\FilesystemAdapter $localDriver
     * @param \Illuminate\Filesystem\FilesystemAdapter $couldDriver
     */
    public function __construct(array $config, FilesystemAdapter $localDriver, FilesystemAdapter $couldDriver)
    {
        $this->config = $this->validatesConfig($config);
        $this->localDriver = $localDriver;
        $this->cloudDriver = $couldDriver;
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return \Assets\Models\File
     */
    public function createFile(UploadedFile $file): File
    {
        $mimeType = $file->getClientMimeType() == "application/octet-stream" ? $file->getMimeType() : $file->getClientMimeType();
        // extract information needed from file
        $file_information = [
            'mime' => $mimeType,
            'size' => $file->getSize(),
            'original_name' => $file->getClientOriginalName(),
            'url' => $this->buildUrl($this->buildCloudPath($file->getClientOriginalName(), $file->getExtension())),
            'extension' => $file->getExtension(),
        ];
        $this->moveToS3($file);
        $file = new File();
        $file->fill($file_information)->save();
        return $file;
    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return bool
     * @throws \Assets\Exceptions\FileCouldNotBeMovedToCloud
     */
    protected function moveToS3(UploadedFile $file): bool
    {
        $disk = $this->cloudDriver;
        try {
            $disk->put($this->buildCloudPath($file->getClientOriginalName(), $file->getExtension()), file_get_contents($file));
        } catch (\Exception $exception) {
            throw new FileCouldNotBeMovedToCloud($exception->getMessage(), $exception->getCode(), $exception);
        }
        if (isset($this->config['keep_local_copy']) && !$this->config['keep_local_copy']) {
            exec('rm ' . $file->getRealPath());
        }
        return true;
    }

    /**
     * @param string $fileName
     * @param string $fileExtension
     *
     * @return string
     */
    public function buildCloudPath(string $fileName, string $fileExtension): string
    {
        $url = $this->getStorageFolder();
        $url .= '/' . $fileName;
        $url .= '.' . $fileExtension;
        return $url;
    }

    /**
     * @param string $couldPath
     *
     * @return string
     */
    public function buildUrl(string $couldPath)
    {
        return $this->getBaseUrl() . '/' . $couldPath;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        if (isset($this->config['cloud_base_url'])) return $this->config['cloud_base_url'];
        if (!is_null(env('CLOUD_STORAGE_BASE_URL', null))) return env('CLOUD_STORAGE_BASE_URL');
        return 'https://checkConfigForFileGateway.now';
    }

    /**
     * @return string
     */
    public function getStorageFolder(): string
    {
        if (isset($this->config['cloud_folder'])) return $this->config['cloud_folder'];
        if (!is_null(env('CLOUD_FOLDER', null))) return env('CLOUD_FOLDER');
        return 'documents';
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @param array $config
     *
     * @return array
     * @throws \Assets\Exceptions\ValidationFailed
     */
    public function validatesConfig(array $config): array
    {
        $validationRules = [
            "cloud_base_url" => "required|string",
            "cloud_folder" => "required|string",
            "local_driver" => "required|string",
            "local_document_folder" => "required|string",
            "local_document_folder_name" => "required|string",
            "keep_local_copy" => "required|boolean",
        ];
        $validation = Validator::make($config, $validationRules);
        if ($validation->fails()) {
            throw new ValidationFailed("Validation for FileGateway config array failed.");
        }
        return $config;
    }
}