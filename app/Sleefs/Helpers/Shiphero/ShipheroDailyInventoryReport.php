<?php

namespace Sleefs\Helpers\Shiphero;

use \Sleefs\Helpers\Shiphero\SkuRawCollection;
use \Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;

use \Sleefs\Helpers\Shopify\ProductGetterBySku;
use \Sleefs\Helpers\Shopify\QtyOrderedBySkuGetter;

use \Illuminate\Support\Collection;

use \Sleefs\Models\Shiphero\items;
use \Sleefs\Models\Shopify\Product;


use \Sleefs\Models\Shiphero\InventoryReport;
use \Sleefs\Models\Shiphero\InventoryReportItem;

class ShipheroDailyInventoryReport {


    /**
    * This method creates a Inventory Report, that is a tabular 
    * report with next columns
    * 
    * |-----------------+-------------------+---------------------------|
    * | Product Type    | Inventory QTY     | In order product type     |
    * |-----------------+-------------------+---------------------------|
    * 
    * The report's data is saved in the database, it's not saved or sent
    * to a any type of file.
    * 
    * The objective of the report is, to give an statimation of the 
    * availability of products by product_type.  
    *
    * @param    mixed   $shipHeroParams, an associative array with at least 
    *                   next two params:
    *
    *                   $shipHeroParams['qtyProducts']
    *                   $shipHeroParams['tries']
    *                   $shipHeroParams['graphqlUrl']
    *                   $shipHeroParams['authUrl']
    *                   $shipHeroParams['available']
    * 
    * @return   \Sleefs\Models\Shiphero\InventoryReport     $inventoryReport
    * 
    */

    public function createReport($shipHeroParams){

        
        $inMemoryProducts = new SkuRawCollection();
        $shipHeroProductsGetter = new ShipheroAllProductsGetter();
        
        $reportCollection = collect();
        $shopifyProductGetter = new ProductGetterBySku();
        //1. Recupera todos los productos desde shiphero
        //   Esta operación es costosa en términos de créditos
        //   en el API de shiphero, en términos de tiempo de ejecución,
        //   en términos peticiones de red y términos de consumo de memoria
        $inMemoryProducts = $shipHeroProductsGetter->getAllProducts($shipHeroParams,$inMemoryProducts);


        /*
            Structure of $inMemoryProducts:
            [sku] => ['qty' => 'Qty available in warehouse inventory']


            For Example:
            [SL-BRN-AS-S-M] => Array
                (
                    [qty] => 18
                )

            [SL-BRN-AS-XL] => Array
                (
                    [qty] => 9
                )
        */


        //print_r($inMemoryProducts);


        //2. Recupera (de los datos en la DB local):
        //  2.1. El tipo de producto por cada sku
        //  2.2. La cantidad ordenada de productos por SKU (ordenes abiertas)

        $ctrlQty = 1;
        foreach ($inMemoryProducts as $key=>$item){
            $tmpProduct = new Product();//It creates a generic Product.
            $shopifyProductGetter = new ProductGetterBySku(); //It looks for a product by SKU.
            $tmpProduct = $shopifyProductGetter->getProduct($key,$tmpProduct);//It takes from local DB a product by Sku ($key)

            if ($tmpProduct != null){
                //return $item;
                $item['product_type'] = $tmpProduct->product_type; //$reportCollection
                $item['inorder_qty'] = 0;
            }
            else{

                $item['product_type'] = 'n/a';
                $item['inorder_qty'] = 0;

            }
            $poItemsQty =  \DB::table('sh_purchaseorder_items')
                        ->leftJoin('sh_purchaseorders','sh_purchaseorder_items.idpo','=','sh_purchaseorders.id')
                        ->select('sh_purchaseorder_items.qty_pending')
                        ->whereRaw("(sh_purchaseorders.fulfillment_status != 'closed' and sh_purchaseorders.fulfillment_status != 'canceled') and sh_purchaseorder_items.sku='".$key."' ")
                        ->get(); //It gets the pending for delivery sku quantities in all "open" (pending) POs.

            

            //echo $ctrlQty.". Procesando para ".$key."\n";
            $ctrlQty++;


            $totalInOrder = 0; //It totalize qty pending for delivery, for a sku code.
            if ($poItemsQty->count() > 0){
                //Si hay elementos ordenados
                foreach ($poItemsQty as $rawOrderItem){
                    $totalInOrder = $totalInOrder + ((int) $rawOrderItem->qty_pending);
                }

            }
            $item['inorder_qty'] = $totalInOrder;
            $inMemoryProducts[$key]=$item;

            //----------------------------------------------------------------------------------
            if ($item['product_type']!='n/a'){
                $reportCollectionItem = $reportCollection->get($item['product_type']);
                if ($reportCollectionItem){
                    $reportCollectionItem['qty'] = $reportCollectionItem['qty'] + $item['qty'];
                    $reportCollectionItem['inorder_qty'] = $reportCollectionItem['inorder_qty'] + $item['inorder_qty'];
                }
                else{
                    $reportCollectionItem = array(
                        'qty' => $item['qty'],
                        'inorder_qty' => $item['inorder_qty']
                    );
                }
                $reportCollection->put($item['product_type'],$reportCollectionItem);

            }
        }

        //print_r($inMemoryProducts);        

        /*
            Genera el reporte de inventario y sus items
            (Este es el objetivo final de este método)
        */
        $inventoryReport = new InventoryReport();
        $inventoryReport->save();

        foreach ($reportCollection as $key=>$item){
            $reportItem = new InventoryReportItem();
            $reportItem->idreporte = $inventoryReport->id;
            $reportItem->label = $key;
            $reportItem->total_inventory = $item['qty'];
            $reportItem->total_on_order = $item['inorder_qty'];
            $reportItem->save();
        }

        return $inventoryReport;
    }

}

