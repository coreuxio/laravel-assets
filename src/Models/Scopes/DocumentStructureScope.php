<?php


namespace Assets\Models\Scopes;


use Assets\Models\GenericDocument;
use Assets\Models\Image;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Class DocumentStructureScope
 *
 * @package Assets\Models\Scopes
 * @author  Luis A. Perez <luis@coreux.io>
 */
class DocumentStructureScope implements Scope
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model $model
     */
    public function apply(Builder $builder, Model $model)
    {
        if ($model instanceof Image) {
            // refer to model to see scopeWithFilesData(Builder $query)
            $builder
                ->AlsoSelect('images.*')
                ->WithFilesData()
                ->PullSelectInQuery();
        }

        if ($model instanceof GenericDocument) {
            // refer to model to see scopeWithFilesData(Builder $query)
            $builder
                ->AlsoSelect(GenericDocument::TABLE_NAME . '.*')
                ->WithFilesData()
                ->PullSelectInQuery();
        }
    }
}