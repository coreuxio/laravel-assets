<?php

namespace Assets\Contracts;


use Assets\Models\Asset;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Interface HasAssetsInterface
 *
 * @package Assets\Contracts
 * @author Luis A. Perez <luis@coreux.io>
 */
interface HasAssetsInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function assets(): MorphToMany;

    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function primaryAsset(): MorphToMany;

    /**
     * @return bool
     */
    public function takeOutExistingPrimaryAsset(): bool;

    /**
     * @return bool
     */
    public function bringPrimaryAssetUrlToTopLevelOfModel(): bool;

    /**
     * @param \Assets\Models\Asset $asset
     * @param bool $primary
     *
     * @return \Assets\Contracts\HasAssetsInterface
     */
    public function attachAsset(Asset $asset, bool $primary = false): HasAssetsInterface;
}