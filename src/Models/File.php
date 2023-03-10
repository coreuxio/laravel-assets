<?php

namespace Assets\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class File
 *
 * @package Assets\Models
 * @author Luis A. Perez <luis@coreux.io>
 */
class File extends Model
{
    use SoftDeletes;
    /**
     * @var string
     */
    protected $table = 'files';

    /**
     * @var array
     */
    protected $fillable = [
        'mime',
        'size',
        'original_name',
        'extension',
        'url'
    ];

    /**
     * @return array
     */
    public function validationRules(): array
    {
        return [
            'mime' => 'required|string',
            'size' => 'required|numeric',
            'original_name' => 'required|string',
            'extension' => 'required|string',
            'url' => 'required|string',
        ];
    }
}
