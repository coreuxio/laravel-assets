<?php

namespace Assets\Manipulators\Images;

use Assets\Contracts\Manipulator;
use Assets\Exceptions\InvalidAspectRatio;
use Assets\Exceptions\ValidationFailed;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;


/**
 * Class ImageProfileManipulator
 *
 * 
 * @author  Luis A. Perez <luis@coreux.io>
 */
class ImageProfileManipulator implements Manipulator
{
    /**
     * @var array
     */
    protected $config;
    /**
     *
     */
    const MANIPULATOR_NAME = 'ImageProfiles';


    /**
     * ImageProfileManipulator constructor.
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $this->validatesConfig($config);
    }


    /**
     * @param \Illuminate\Http\UploadedFile $file
     * @param array $options
     *
     * @return array
     */
    public function manipulate(UploadedFile $file, array $options = []): array
    {
        $dimensions = $this->checkForDimensions($options);
        // get name
        $name = $file->getClientOriginalName();
        $path = $file->getPath();
        $extension = $file->getExtension();
        // create image bag and add original data
        $imageBag = [
            'original' => ['folder' => $path, 'name' => $name, 'extension' => $extension]
        ];
        $image = Image::make($path . '/' . $name . '.' . $extension);
        if (is_array($dimensions)) {
            $image->crop($dimensions['width'], $dimensions['height'], $dimensions['x'], $dimensions['y'])->save($path . '/' . $name . '.' . $extension);
        }
        foreach ($this->config['sizes'] as $size => $imageSize) {
            // get name
            $sizeName = md5(time() . $size . '-' . $file->getClientOriginalName());
            // append size to the name
            $imagePath = $path . '/' . $sizeName . '.' . $extension;
            // make data for array
            $imageData = [$size => ['folder' => $path, 'name' => $sizeName, 'extension' => $extension]];
            // push data in
            $imageBag = array_merge($imageBag, $imageData);
            // manipulate image
            $image->fit($imageSize['x'], $imageSize['y'], function (Constraint $constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
                // save once done
            })->orientate()->save($imagePath);
        }
        return $imageBag;
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
            "sizes" => "required|array",
            "sizes.*" => "required|array",
            "sizes.*.y" => "numeric|nullable",
            "sizes.*.x" => "required|numeric|nullable",
        ];
        $validation = Validator::make($config, $validationRules);
        if ($validation->fails()) {
            throw new ValidationFailed("Validation for " . self::class . " config array failed.");
        }
        return $config;
    }

    private function checkForDimensions(array $options)
    {
        $data = [
            'width' => isset($options['width']) ? $options['width'] : 0,
            'height' => isset($options['height']) ? $options['height'] : 0,
            'x' => isset($options['x']) ? $options['x'] : 0,
            'y' => isset($options['y']) ? $options['y'] : 0,
        ];
        foreach ($data as $coordinates => $value) if ($value === 0) return false;
        if ((int)$data['width'] != (int)$data['height']) throw new InvalidAspectRatio("Height adn Width given are not 4:4 aspect ratio");
        return $data;
    }
}