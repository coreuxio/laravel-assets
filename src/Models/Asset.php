<?php

namespace Assets\Models;

use Assets\Traits\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;

/**
 * Class Asset
 *
 * @package Assets\Models
 * @author Luis A. Perez <luis@coreux.io>
 */
class Asset extends Model
{
    use SoftDeletes, BaseModel;

    /**
     * @var array
     */
    protected $fillable = [
        'order',
        'document_id',
        'document_type',
        'active',
        'user_id'
    ];

    /**
     * Allows base controller to carry the global
     * Active scope for all models.
     * @var string
     */
    public $activeField = 'active';

    /**
     * @var array
     */
//    protected $with = ['document'];
    /**
     * @var array
     */
    protected $casts = ['image_files' => 'array'];

    /*
     * RELATIONSHIPS
     */


    /**
     * Get all of the owning document models.
     */
    public function document()
    {
        return $this->morphTo();
    }

    /*
     * RELATIONSHIPS
     */


    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithDocumentData(Builder $query)
    {
        $imageClass = Image::class;
        // Images join
        $query->leftJoin('images as images_table', function (JoinClause $join) use ($imageClass) {
            $join->on('images_table.id', 'assets.document_id')
                ->where('assets.document_type', $imageClass);
        });
        // small image
        $query->leftJoin('files as small_files_table', function (JoinClause $join) use ($imageClass) {
            $join->on('images_table.small_id', 'small_files_table.id')
                ->where('assets.document_type', $imageClass);
        });
        // medium image
        $query->leftJoin('files as medium_files_table', function (JoinClause $join) use ($imageClass) {
            $join->on('images_table.medium_id', 'medium_files_table.id')
                ->where('assets.document_type', $imageClass);
        });
        // large image
        $query->leftJoin('files as large_files_table', function (JoinClause $join) use ($imageClass) {
            $join->on('images_table.large_id', 'large_files_table.id')
                ->where('assets.document_type', $imageClass);
        });
        // original image
        $query->leftJoin('files as original_files_table', function (JoinClause $join) use ($imageClass) {
            $join->on('images_table.image_id', 'original_files_table.id')
                ->where('assets.document_type', $imageClass);
        });

        $this->appendToSelect("
        CASE WHEN assets.document_type = $imageClass
            JSON_OBJECT(
                'small' , CASE WHEN small_files_table.id IS NOT NULL THEN small_files_table.url ELSE NULL END
                'medium' , CASE WHEN medium_files_table.id IS NOT NULL THEN medium_files_table.url ELSE NULL END
                'large' , CASE WHEN large_files_table.id IS NOT NULL THEN large_files_table.url ELSE NULL END
                'original' , CASE WHEN original_files_table.id IS NOT NULL THEN original_files_table.url ELSE NULL END
            ) as image_files
        END ");
    }

    /*
     * SCOPES
     */
    /**
     * @param $query
     * @param $string
     */
    public function scopeOfType($query, $string)
    {
        $query->where('document_type', $string);
    }
    /*
     * SCOPES
     */
}