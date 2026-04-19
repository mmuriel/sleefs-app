<?php
namespace Sleefs\Views\Shiphero;
use \Sleefs\Models\Shiphero\PurchaseOrderItem;
use \Sleefs\Models\Shopify\VariantImage;
use \Sleefs\Models\Shopify\ProductImage;
use \Sleefs\Models\Shopify\Variant;
use \Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator;

class PurchaseOrderItemListView {

	private $poItem;

	public function __construct(\Sleefs\Models\Shiphero\PurchaseOrderItem $poItem){
		$this->poItem = $poItem;
		setlocale(LC_MONETARY, 'en_US');
	}

	public function render(\Sleefs\Helpers\Shopify\ImageUrlBySizeGenerator $urlImageGenerator){

		$variant = \DB::table('variants')->where('sku','=',$this->poItem->sku)->orderBy('created_at','DESC')->first();

		$imageUrl = null;
		if ($variant != null){
			$variantImage = \DB::table('variant_images')
							->where('idvariant','=',$variant->id)
							->first();
			if ($variantImage != null){
				$imageUrl = $variantImage->url;
			}
		}

		if ($imageUrl == null){
			$imageObject = \DB::table('product_images')
							->leftJoin('products', 'product_images.idproducto', '=', 'products.id')
							->leftJoin('variants','products.id','=','variants.idproduct')
							->select('product_images.url')
							->whereRaw(" variants.sku ='".$this->poItem->sku."' && (products.delete_status = '1' || products.delete_status is NULL)")
							->orderBy('product_images.position','ASC')
							->get();

			if (count($imageObject)>0){
				$imageUrl = $imageObject->get(0)->url;
			}
		}

		if ($imageUrl != null){
			$imageUrl = $urlImageGenerator->createImgUrlWithSizeParam($imageUrl,150,150);
			$styleObj = "width: 150px;";
		}
		else{
			$imageUrl = \App::make('url')->to('/').'/imgs/no-image-2.png';
			$styleObj = "width: 50px;";
		}


		$htmlToRet = '
								<tr>
                                    <td>'.$this->poItem->name.'</td>
                                    <td data-prdsku="'.$this->poItem->sku.'">'.$this->poItem->sku.'</td>
                                    <td data-prdBarcode="'.$this->poItem->barcode.'">'.$this->poItem->barcode.'</td>
                                    <td>'.$this->poItem->quantity.'</td>
                                    <td>'.$this->poItem->price.'</td>
                                    <td>'.($this->poItem->price * $this->poItem->quantity).'</td>
                                    <td>
                                    	<img src="'.$imageUrl.'" style="'.$styleObj.'" class="product-img" data-prdSku="'.$this->poItem->sku.'"/>
                                    </td>
                                </tr>';

		return $htmlToRet;
	}


}