<?php

namespace Assets\Contracts;

use Assets\Models\Asset;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Http\UploadedFile;

/**
 * Interface Manipulator
 *
 * @package Assets\Contracts
 * @author Luis A. Perez <luis@coreux.io>
 */
interface Manipulator
{
    /**
     * @param \Illuminate\Http\UploadedFile $file
     *
     * @return \Illuminate\Http\UploadedFile
     */
    public function manipulate(UploadedFile $file, array $options = []): array;

}