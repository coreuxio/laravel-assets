<?php

namespace Assets\Traits;


use Assets\Models\Asset;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Class IsDocument
 *
 * @package Assets\Traits
 * @author Luis A. Perez <luis@coreux.io>
 */
trait IsDocument
{
    /**
     * Documents can belong to many assets
     */
    public function asset(): MorphMany
    {
        return $this->morphMany(Asset::class, 'document', 'document_type');
    }

    /**
     * @param Asset $asset
     * @return Asset
     */
    public function attachToAsset(Asset $asset): Asset
    {
        // pull resource
        $document = $this;
        // if resource already has asset
        if ($document->hasAsset($asset)) {
            // return resource
            return $asset;
        }
        // otherwise attach the asset to the resource
        $document->asset()->save($asset);
        return $asset;
    }

    /**
     * @param Asset $asset
     * @return bool
     */
    protected function hasAsset(Asset $asset)
    {
        $resource = $this;
        // for each asset
        foreach ($resource->asset as $resource_asset) {
            // if it exists
            if ($resource_asset->id == $asset->id) {
                return true;
            }
        }
        // other wise
        return false;
    }

}