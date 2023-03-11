<?php

namespace Assets\Models;

use Assets\Contracts\AssetDocumentInterface;
use Assets\Models\Scopes\DocumentStructureScope;
use Assets\Traits\BaseModel;
use Assets\Traits\IsDocument;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Query\JoinClause;

/**
 * Class Image
 *
 * @package Assets\Models
 * @author Luis A. Perez <luis@coreux.io>
 */
class Image extends Model implements AssetDocumentInterface
{
    use IsDocument, SoftDeletes, BaseModel;

    /**
     * @var string
     */
    protected $table = "images";

    /**
     * @var array
     */
    protected $fillable = [
        'image_id',
        'small_id',
        'medium_id',
        'large_id',
        'title',
    ];

    /*
     * RELATIONSHIPS
     */

    /**
     *
     */
    public static function boot()
    {
        parent::boot();
        static::addGlobalScope(new DocumentStructureScope());
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return (int)$this->id;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function image_file()
    {
        return $this->hasOne(File::class, 'id', 'image_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function small_file()
    {
        return $this->hasOne(File::class, 'id', 'small_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function medium_file()
    {
        return $this->hasOne(File::class, 'id', 'medium_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function large_file()
    {
        return $this->hasOne(File::class, 'id', 'large_id');
    }

    // Scopes to pull in data on the same level

    /**
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    public function scopeWithFilesData(Builder $query)
    {
        $query->join('files as small_files_table', function (JoinClause $join) {
            $join->on("small_files_table.id", "images.small_id");
        });
        $query->join('files as medium_files_table', function (JoinClause $join) {
            $join->on("medium_files_table.id", "images.medium_id");
        });
        $query->join('files as large_files_table', function (JoinClause $join) {
            $join->on("large_files_table.id", "images.large_id");
        });
        $query->join('files as original_files_table', function (JoinClause $join) {
            $join->on("original_files_table.id", "images.image_id");
        });
        // small
        $this->appendToSelect("small_files_table.id as small_file_id");
        $this->appendToSelect("small_files_table.mime as small_file_mime");
        $this->appendToSelect("small_files_table.original_name as small_file_original_name");
        $this->appendToSelect("small_files_table.url as small_file_url");
        $this->appendToSelect("small_files_table.extension as small_file_extension");
        // medium
        $this->appendToSelect("medium_files_table.id as medium_file_id");
        $this->appendToSelect("medium_files_table.mime as medium_file_mime");
        $this->appendToSelect("medium_files_table.original_name as medium_file_original_name");
        $this->appendToSelect("medium_files_table.url as medium_file_url");
        $this->appendToSelect("medium_files_table.extension as medium_file_extension");
        // large
        $this->appendToSelect("large_files_table.id as large_file_id");
        $this->appendToSelect("large_files_table.mime as large_file_mime");
        $this->appendToSelect("large_files_table.original_name as large_file_original_name");
        $this->appendToSelect("large_files_table.url as large_file_url");
        $this->appendToSelect("large_files_table.extension as large_file_extension");
        // original
        $this->appendToSelect("original_files_table.id as original_file_id");
        $this->appendToSelect("original_files_table.mime as original_file_mime");
        $this->appendToSelect("original_files_table.original_name as original_file_original_name");
        $this->appendToSelect("original_files_table.url as original_file_url");
        $this->appendToSelect("original_files_table.extension as original_file_extension");
    }
}