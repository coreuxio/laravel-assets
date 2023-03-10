<?php

namespace Assets\Contracts;


use Assets\Models\Asset;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Interface AssetDocumentInterface
 *
 * @package Assets\Contracts
 * @author Luis A. Perez <luis@coreux.io>
 */
interface AssetDocumentInterface
{
    /**
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function asset(): MorphMany;

    /**
     * @return int
     */
    public function getId(): int;

    /**
     * @param \Assets\Models\Asset $asset
     *
     * @return \Assets\Models\Asset
     */
    public function attachToAsset(Asset $asset): Asset;
}