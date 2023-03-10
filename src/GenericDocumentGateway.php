<?php


namespace Assets;


use Assets\Contracts\AssetDocumentInterface;
use Assets\Exceptions\ValidationFailed;
use Assets\Models\GenericDocument;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;

/**
 * Class GenericDocumentGateway
 *
 * @package Assets
 * @author  Luis A. Perez <luis@coreux.io>
 */
class GenericDocumentGateway extends DocumentsGateway
{
    /**
     * @var array
     */
    protected $config;
    /**
     * @var \Assets\FileGateway
     */
    protected $fileGateway;
    /**
     * @var \Illuminate\Filesystem\FilesystemAdapter
     */
    protected $localDriver;
    /**
     * @var array
     */

    /**
     * Mime type will be empty since this gateway will upload any document
     */
    const   DOCUMENT_TYPE = 'documents';


    public function __construct(array $config, FileGateway $fileGateway, FilesystemAdapter $localDriver, array $manipulators)
    {
        $this->fileGateway = $fileGateway;
        $this->localDriver = $localDriver;
        $this->config = $this->validatesConfig($config);
        $this->manipulators = $manipulators;
    }


    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options
     *
     * @return \Assets\Contracts\AssetDocumentInterface
     */
    public function create(UploadedFile $file, array $options = []): AssetDocumentInterface
    {
        // get Document manipulators and pass the options
        $manipulator = $this->getManipulator($options);
        // manipulate document as needed
        $filesBag = $manipulator->manipulate($this->moveToLocalDisk($file), $options);
        // get thumbnail file id
        $thumbnailId = is_null($filesBag['thumbnail']['file']) ? $filesBag['thumbnail']['id'] : $this->fileGateway->createFile($filesBag['thumbnail']['file'])->getAttribute('id');
        // get document file id
        $document = $this->fileGateway->createFile($filesBag['document']['file']);
        $title = isset($options['title']) ? $options['title'] : $document->getAttribute('original_name');
        $documentData = [
            'title' => $title,
            'thumbnail_id' => $thumbnailId,
            'document_id' => $document->getAttribute('id')
        ];
        return GenericDocument::create($documentData);
    }

    /**
     * @param int $imageId
     *
     * @return \Assets\Contracts\AssetDocumentInterface
     */
    public function getOrFail(int $imageId): AssetDocumentInterface
    {
        // TODO: Implement getOrFail() method.
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
            "mimes" => "required|array",
            "mimes.*" => "required|string",
            "manipulators" => "required|array",
            "default_manipulator" => "required|string",
        ];
        $validation = Validator::make($config, $validationRules);
        if ($validation->fails()) {
            throw new ValidationFailed("Validation for " . self::class . " config array failed.");
        }
        return $config;
    }


}