<?php

namespace Assets\Traits;


use Assets\Contracts\HasAssetsInterface;
use Assets\Models\Asset;
use Assets\Models\GenericDocument;
use Assets\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Query\JoinClause;

/**
 * Class HasAssets
 *
 * @package Assets\Traits
 * @author Luis A. Perez <luis@coreux.io>
 */
trait HasAssets
{
    /**
     * Get all of the assets for the post.
     */
    public function assets(): MorphToMany
    {
        return $this->morphToMany(Asset::class, 'resource', 'resource_asset', 'resource_id', 'asset_id')->withPivot('primary', 'resource_type')->withTimestamps();
    }

    /**
     * @return mixed
     */
    public function primaryAsset(): MorphToMany
    {
        return $this->assets()->wherePivot('primary', 1);
    }

    /**
     * Allows you to attach an asset to any model and if the asset
     * becomes primary it will change the flag for the previous
     * primary asset and set the new one as primary leaving
     * the previous one in the assets array. Also triggers
     * the event listener to bring asset url to the top
     * level of the model
     *
     * @param Asset $asset
     * @param bool $primary
     * @return HasAssetsInterface
     */
    public function attachAsset(Asset $asset, bool $primary = false): HasAssetsInterface
    {
        // otherwise attach the asset to the resource
        if ($primary) {
            // check if the current document can be adder as a primary asset
            $this->takeOutExistingPrimaryAsset();
            // take out existing primary asset if any and save new asset at primary
            $this->assets()->save($asset, ['primary' => true]);
            // once save as primary we can trigger the listener
            $this->bringPrimaryAssetUrlToTopLevelOfModel();
        } else {
            $this->assets()->save($asset);
        }
        return $this;
    }

    /**
     *
     */
    public function takeOutExistingPrimaryAsset(): bool
    {
        // fetch the primary asset
        $primary_asset = $this->primaryAsset()->first();
        // if its found
        if ($primary_asset) {
            // take out primary
            $primary_asset->pivot->primary = false;
            // save
            $primary_asset->pivot->save();
        }
        return true;
    }

    /**
     * @param Builder $query
     */
    public function scopeWithOptimizedImages(Builder $query)
    {
        $classType = $this->getMorphClass();
        $tableName = $this->getTable();
        $this->appendToSelect($tableName . '.*');
        $query->leftJoin('resource_asset as resource_asset_table', function (JoinClause $join) use ($classType, $tableName) {
            $join->on('resource_asset_table.resource_id', $tableName . '.id')
                ->where('resource_asset_table.primary', true)
                ->where('resource_asset_table.resource_type', $classType);
        })
            ->leftJoin('assets as assets_table', function (JoinClause $join) {
                $join->on('assets_table.id', 'resource_asset_table.asset_id')
                    ->where('assets_table.document_type', Image::class);
            })
            ->leftJoin('images as images_table', function (JoinClause $join) {
                $join->on('assets_table.document_id', 'images_table.id');
            })
            ->leftJoin('files as large_file_table', function (JoinClause $join) {
                $join->on('large_file_table.id', 'images_table.large_id');
            })
            ->leftJoin('files as small_file_table', function (JoinClause $join) {
                $join->on('small_file_table.id', 'images_table.small_id');
            })
            ->leftJoin('files as original_file_table', function (JoinClause $join) {
                $join->on('original_file_table.id', 'images_table.image_id');
            })
            ->leftJoin('files as medium_file_table', function (JoinClause $join) {
                $join->on('medium_file_table.id', 'images_table.medium_id');
            });
        // Large
        $this->appendToSelect("large_file_table.id as large_file_id");
        $this->appendToSelect("large_file_table.mime as large_file_mime");
        $this->appendToSelect("large_file_table.original_name as large_file_original_name");
        $this->appendToSelect("large_file_table.url as large_file_url");
        $this->appendToSelect("large_file_table.extension as large_file_extension");
        // Meium
        $this->appendToSelect("medium_file_table.id as medium_file_id");
        $this->appendToSelect("medium_file_table.mime as medium_file_mime");
        $this->appendToSelect("medium_file_table.original_name as medium_file_original_name");
        $this->appendToSelect("medium_file_table.url as medium_file_url");
        $this->appendToSelect("medium_file_table.extension as medium_file_extension");
        // Small
        $this->appendToSelect("small_file_table.id as small_file_id");
        $this->appendToSelect("small_file_table.mime as small_file_mime");
        $this->appendToSelect("small_file_table.original_name as small_file_original_name");
        $this->appendToSelect("small_file_table.url as small_file_url");
        $this->appendToSelect("small_file_table.extension as small_file_extension");
        // Original
        $this->appendToSelect("original_file_table.id as original_file_id");
        $this->appendToSelect("original_file_table.mime as original_file_mime");
        $this->appendToSelect("original_file_table.original_name as original_file_original_name");
        $this->appendToSelect("original_file_table.url as original_file_url");
        $this->appendToSelect("original_file_table.extension as original_file_extension");
        $query->PullSelectInQuery();
    }

    public function scopeDocumentData(Builder $query)
    {
        $classType = $this->getMorphClass();
        $tableName = $this->getTable();
        $this->appendToSelect($tableName . '.*');
        $query->leftJoin('resource_asset as resource_asset_table', function (JoinClause $join) use ($classType, $tableName) {
            $join->on('resource_asset_table.resource_id', $tableName . '.id')
                ->where('resource_asset_table.primary', true)
                ->where('resource_asset_table.resource_type', $classType);
        })
            ->leftJoin('assets as assets_table', function (JoinClause $join) {
                $join->on('assets_table.id', 'resource_asset_table.asset_id')
                    ->where('assets_table.document_type', GenericDocument::class);
            })
            ->leftJoin(GenericDocument::TABLE_NAME . ' as generic_document_table', function (JoinClause $join) {
                $join->on('assets_table.document_id', 'generic_document_table.id');
            })
            ->leftJoin('files as thumbnail_table', function (JoinClause $join) {
                $join->on('generic_document_table.' . GenericDocument::THUMBNAIL_ID, 'thumbnail_table.id');
            })
            ->leftJoin('files as document_table', function (JoinClause $join) {
                $join->on('generic_document_table.' . GenericDocument::DOCUMENT_ID, 'document_table.id');
            });
        // Thumbnail
        $this->appendToSelect("thumbnail_table.id as thumbnail_file_id");
        $this->appendToSelect("thumbnail_table.mime as thumbnail_file_mime");
        $this->appendToSelect("thumbnail_table.original_name as thumbnail_file_original_name");
        $this->appendToSelect("thumbnail_table.url as thumbnail_file_url");
        $this->appendToSelect("thumbnail_table.extension as thumbnail_file_extension");
        // Document
        $this->appendToSelect("document_table.id as document_file_id");
        $this->appendToSelect("document_table.mime as document_file_mime");
        $this->appendToSelect("document_table.original_name as document_file_original_name");
        $this->appendToSelect("document_table.url as document_file_url");
        $this->appendToSelect("document_table.extension as document_file_extension");
        $query->PullSelectInQuery();
    }

    /**
     * Grab the url of the primary asset that is
     * buried under four layers of data and
     * brings it to the to p level of the
     * model
     */
    public function bringPrimaryAssetUrlToTopLevelOfModel(): bool
    {
        // if the model implementing hasAssetsInterface has primaryAssetsField
        if (isset($this->primaryAssetsField)) {
            $fieldName = $this->primaryAssetsField;
            // put all urls on the top level
            $this->$fieldName = [
                'original' => $this->primaryAsset()->get()->first()->document->image_file->url,
                'small' => $this->primaryAsset()->get()->first()->document->small_file->url,
                'large' => $this->primaryAsset()->get()->first()->document->large_file->url,
                'medium' => $this->primaryAsset()->get()->first()->document->medium_file->url,
            ];
        }
        // if the object has primary assetField
        if (isset($this->primaryAssetField)) {
            $fieldName = $this->primaryAssetField;
            // just place the original image
            $this->$fieldName = $this->primaryAsset()->get()->first()->document->image_file->url;
        }
        // save changes
        $this->save();
        return true;
        // this method can be overwritten inside of the model implementing hasAssetsInterface
    }
}