<?php

namespace Sleefs\Models\Shopify;

use Illuminate\Database\Eloquent\Model;

class VariantImage extends Model
{
    protected $table = 'variant_images';

    public function variant(){
        return $this->belongsTo('Sleefs\Models\Shopify\Variant','idvariant');
    }
}