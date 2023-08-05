<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use \Sleefs\Models\Shopify\Product;
use \Sleefs\Models\Shopify\Variant;
use \Sleefs\Models\Shopify\ProductImage;
use \Sleefs\Helpers\ShopifyAPI\Shopify;

class ShopifyProductIDAdjuster extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ShopifyProductIDAdjuster:adjust {--l|limit=100 : Quantity items per page, for pagination} {--p|page_info : Cursor ID for pagination }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Este comando verifica y ajusta (si es necesario) el ID nativo de shopify en la entidad products de la base de datos de la app. Esto para corregir una posible diferencia de valores en los IDs de shopify en diferentes implementaciones de Mysql.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        //

        $limit = $this->option('limit');
        $page_info = $this->option('page_info');

        if($page_info==null || $page_info==''){
            if (file_exists(storage_path()."/pager.txt")){
                $savedPage = file(storage_path()."/pager.txt");
                if (isset($savedPage[0]) && $savedPage[0] != '')
                    $page_info = trim($savedPage[0]);
                else
                    $page_info='';
            }else{
                $page_info='';    
            }
        }
        //===========================================================================================
        $shopify = new Shopify(getenv('SHPFY_BASEURL'),getenv('SHPFY_ACCESSTOKEN'));

        if ($page_info == '' || $page_info == null)
            $shopifyQueryForProductsOptions = 'fields=id,vendor,product_type,created_at,title,sku,handle,images,variants&limit='.$limit;
        else
            $shopifyQueryForProductsOptions = 'fields=id,vendor,product_type,created_at,title,sku,handle,images,variants&limit='.$limit.'&page_info='.$page_info;


        $remoteProducts = $shopify->getAllProducts($shopifyQueryForProductsOptions);
        //print_r($remoteProducts['content']);
        foreach ($remoteProducts['content']->products as $remoteProduct){

            $tmpLocalShopifyProduct = Product::where('idsp','=','shpfy_'.$remoteProduct->id)->first();
            if ($tmpLocalShopifyProduct == null){
                echo "\nRegistrando nuevo producto para ".$remoteProduct->title."\n";
                $localProduct = new Product();
                $localProduct->idsp = 'shpfy_'.$remoteProduct->id;
            }
            else{
                echo "\nActualizando datos para el producto: ".$remoteProduct->title."\n";
                $localProduct = $tmpLocalShopifyProduct;
            }

            $localProduct->title = $remoteProduct->title;
            $localProduct->vendor = $remoteProduct->vendor;
            $localProduct->product_type = $remoteProduct->product_type;
            $localProduct->handle = $remoteProduct->handle;
            $localProduct->save();
            $this->adjustLocalShopifyProduct ($localProduct,$remoteProduct);
            echo "\n------------------------------------------------------\n";
        }

        if (isset($remoteProducts['headers']->links_params)){
            $nextCursorLink = $shopify->getPaginationCursorValue($remoteProducts['headers']->links_params,'next');
            if ($nextCursorLink != ''){
                $this->saveProcessedPage($nextCursorLink);    
            }
            else{
                $this->saveProcessedPage('');   
            }
        }
    }



    private function adjustLocalShopifyProduct ($localShopifyPrdt,$remoteShopifyPrdt=null){

        //echo "\n\nLas siguientes son las imágenes relacionadas con el producto: ".$localShopifyPrdt->handle."\n";
        /*
        foreach ($localShopifyPrdt->images as $image){

            echo $image->url."\n";
            echo "Borrando... ".$image->delete()."\n";
        }
        //echo "\n\n";
        //echo "\n\nLas siguientes son las variantes relacionadas con el producto: ".$localShopifyPrdt->handle."\n";
        foreach ($localShopifyPrdt->variants as $variant){

            echo $variant->title."\n";
            echo "Borrando... ".$variant->delete()."\n";
        }
        //echo "\n\n";
        */


        if ($remoteShopifyPrdt!=null){

            //Registra las variantes y las imágenes
            //1. Variantes:
            foreach($remoteShopifyPrdt->variants as $remoteVariant){

                $tmpLocalShopifyVariant = Variant::where('idsp','=','shpfy_'.$remoteVariant->id)->first();
                if ($tmpLocalShopifyVariant == null){
                    echo "\n---- Registrando la nueva variante para: ".$remoteVariant->title." (".$remoteVariant->sku.")";
                    $localVariant = new Variant();
                    $localVariant->idsp = 'shpfy_'.$remoteVariant->id;
                }
                else{
                    echo "\n---- Actualizando datos para la variante: ".$remoteVariant->title;
                    $localVariant = $tmpLocalShopifyVariant;
                }

                //1. Elimina los posibles IDs duplicados:
                //$resDelete = Variant::where('idsp','=',"shpfy_".$remoteVariant->id)->delete();
                
                $localVariant->sku = trim($remoteVariant->sku);
                $localVariant->title = $remoteVariant->title;
                $localVariant->idproduct = $localShopifyPrdt->id;
                $localVariant->price = $remoteVariant->price;
                $localVariant->save();

            }
            

            //2. Imagenes:
            foreach($remoteShopifyPrdt->images as $remoteImg){

                //print_r($remoteImg);

                echo "\n--- Registrando la nueva imagen para: shpfy_".$remoteImg->src." (".$remoteImg->id.")\n";
                $tmpLocalShopifyImg = ProductImage::where('idsp','=','shpfy_'.$remoteImg->id)->first();
                if ($tmpLocalShopifyImg == null){
                    echo "\n---- Registrando una nueva imagen de producto: ".$remoteImg->src;
                    $localImage = new ProductImage();
                    $localImage->idsp = 'shpfy_'.$remoteImg->id;
                }
                else{
                    echo "\n---- Actualizando datos para la imagen: ".$remoteImg->src;
                    $localImage = $tmpLocalShopifyImg;
                }


                //echo "Registrando la nueva imagen para: shpfy_".$remoteImg->src." (".$remoteImg->id.")\n";
                //$resDelete = ProductImage::where('idsp','=',"shpfy_".$remoteImg->id)->delete();
                $localImage->position = $remoteImg->position;
                $localImage->url = $remoteImg->src;
                $localImage->idproducto = $localShopifyPrdt->id;
                $localImage->save();
            }

        }
    }


    private function saveProcessedPage($page_info){

        $fp = fopen(storage_path()."/pager.txt","w+");
        fwrite($fp,$page_info);
        fclose($fp);
    }
}
