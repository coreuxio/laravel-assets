<?php

namespace Assets;

use Assets\Contracts\AssetDocumentInterface;
use Assets\Contracts\Manipulator;
use Assets\Exceptions\ModelMustHaveHasAssetsTrait;
use Assets\Traits\HasAssets;
use Illuminate\Http\UploadedFile;

/**
 * Class DocumentsGateway
 *
 * @package Assets
 * @author  Luis A. Perez <luis@coreux.io>
 */
abstract class DocumentsGateway
{
    /**
     * @var
     */
    protected $manipulators;

    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options
     *
     * @return \Assets\Contracts\AssetDocumentInterface
     */
    public abstract function create(UploadedFile $file, array $options = []): AssetDocumentInterface;

    /**
     * @param int $documentId
     *
     * @return \Assets\Contracts\AssetDocumentInterface
     */
    public abstract function getOrFail(int $documentId): AssetDocumentInterface;

    /**
     * @return array
     */
    public abstract function getConfig(): array;

    /**
     * @param array|null $options
     *
     * @return \Assets\Contracts\Manipulator
     * @throws \Assets\Exceptions\ModelMustHaveHasAssetsTrait
     */
    public function getManipulator(array $options = []): Manipulator
    {
        // if model is not sent return default manipulator
        if (!isset($options['model'])) return $this->manipulators[$this->config['default_manipulator']];
        // if it was sent make sure it has the HasAssets trait otherwise throw exception
        if (!isset(class_uses($options['model'])[HasAssets::class])) throw new ModelMustHaveHasAssetsTrait(get_class($options['model']) . " does not have HasAssets trait");
        // if the method does exist return getManipulator from model
        if (method_exists($options['model'], 'getManipulator')) return $this->manipulators[$options['model']->getManipulator()];
        // otherwise return default manipulator again
        return $this->manipulators[$this->config['default_manipulator']];
    }

//    /**
//     * @param array $options
//     * @return Manipulator
//     * @throws DefaultException
//     * @throws ModelMustHaveHasAssetsTrait
//     */
//    public function getManipulator(array $options=[]) : Manipulator
//    {
//        // if model is not sent return default manipulator
//        if(!isset($options['model']))return $this->manipulators[$this->config['default_manipulator']];
//        // if it was sent make sure it has the HasAssets trait otherwise throw exception
//        if(!isset(class_uses($options['model'])[HasAssets::class])) throw new ModelMustHaveHasAssetsTrait(get_class($options['model'])." does not have HasAssets trait");
//        // if the method does exist return getManipulator from model
//        if(method_exists($options['model'],'getManipulator'))
//        {
//            if(in_array($options['model']->getManipulator(),$this->manipulators))
//            {
//                return $this->manipulators[$options['model']->getManipulator()];
//            }
//            else
//            {
//                throw new DefaultException(get_class($options['model'])." cant have Generic Document as asset.");
//            }
//        }
//        // otherwise return default manipulator again
//        return $this->manipulators[$this->config['default_manipulator']];
//    }

    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function moveToLocalDisk(UploadedFile $file): UploadedFile
    {
        $disk = $this->localDriver;
        $newFileName = md5(time() . $file->getClientOriginalName());
        $fileDestination = $this->fileGateway->getConfig()['local_document_folder_name'] . '/' . $newFileName . '.' . $file->getClientOriginalExtension();
        $fileLocation = $this->fileGateway->getConfig()['local_document_folder'] . '/' . $newFileName . '.' . $file->getClientOriginalExtension();
        $disk->put($fileDestination, file_get_contents($file));
        return new UploadedFile($fileLocation, $newFileName, $file->getClientMimeType());
    }
}