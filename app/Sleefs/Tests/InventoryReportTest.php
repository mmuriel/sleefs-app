<?php

namespace Sleefs\Tests;

use Illuminate\Foundation\Testing\TestCase ;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;

use Sleefs\Helpers\Shiphero\SkuRawCollection;
use Sleefs\Helpers\Shiphero\ShipheroAllProductsGetter;
use Sleefs\Models\Shopify\Variant;
use Sleefs\Models\Shopify\Product;
use Sleefs\Models\Shiphero\InventoryReport;
use Sleefs\Models\Shiphero\InventoryReportItem;
use Sleefs\Models\Shiphero\PurchaseOrder;
use Sleefs\Models\Shiphero\PurchaseOrderItem;
use Sleefs\Helpers\Shopify\ProductGetterBySku;
use Sleefs\Helpers\Shopify\QtyOrderedBySkuGetter;
use Sleefs\Helpers\Shiphero\ShipheroDailyInventoryReport;
use Sleefs\Helpers\ShipheroGQLApi\ShipheroGQLApi;
use Sleefs\Helpers\GraphQL\GraphQLClient;

class InventoryReportTest extends TestCase {

	use RefreshDatabase;

	private $products = array();
    private $variants = array();
    private $pos = array();
	private $items = array();
	private $inventoryReports = array();
	private $inventoryReportsItems = array();

	public function setUp():void
    {
        parent::setUp();
        $this->prepareForTests();
      
    }
 
 	public function testGetting5ProductsFromShiphero(){

 		$gqlClient = new GraphQLClient('https://public-api.shiphero.com/graphql');
    	$shipHeroApi = new ShipheroGQLApi($gqlClient,'https://public-api.shiphero.com/graphql','https://public-api.shiphero.com/auth',env('SHIPHERO_ACCESSTOKEN'),env('SHIPHERO_REFRESHTOKEN'));
	
 		$options = array('qtyProducts'=>5,'createdFrom' => '2023-06-01','createdTo' => '2023-07-01');
 		$products = $shipHeroApi->getProducts($options);
		$this->assertEquals(5,count($products->products->results),"No se han retornado 100 productos");

		$prdsCollection = new SkuRawCollection();
		$prdsCollection->addElementsFromShipheroApi($products->products->results);

		$this->assertEquals(5,$prdsCollection->count(),"Se esperan 5 y la colección tiene".$prdsCollection->count());

 	}

 	public function testGettingAllShipheroProducts(){
 		$shProductsGetter = new ShipheroAllProductsGetter();
 		$prdsCollection = new SkuRawCollection();
 		
 		$prdsCollection = $shProductsGetter->getAllProducts(['graphqlUrl'=>'https://public-api.shiphero.com/graphql','authUrl'=>'https://public-api.shiphero.com/auth','qtyProducts'=>980,'tries' => 3],$prdsCollection);

 		/*
 		echo "MMA-----\n";
 		print_r($prdsCollection->count());
 		echo "MMA-----\n";
 		*/
 		$this->assertGreaterThan(1700,$prdsCollection->count());
 	}


 	public function testGetProductTypeBySku(){
 		$variant = Variant::find(1);
 		$productFinder = new ProductGetterBySku();
 		$product = new Product();
 		$product = $productFinder->getProduct($variant->sku,$product);
 		$this->assertMatchesRegularExpression('/hot\-orange\-arm\-sleeve/',$product->handle);
 		$this->assertMatchesRegularExpression('/Sleeve/',$product->product_type);
 	}


 	public function testGetAllQtyOrderedBySku(){
 		$qtyGetter = new QtyOrderedBySkuGetter();
 		$qtyOrderedBySku = $qtyGetter->getQtyOrdered('SL-HOTORG-AS-L');
 		$this->assertEquals(50,$qtyOrderedBySku);
 	}


 	public function testGetAllOrderedQtyByProductType(){

 		//1. Define los SKUs para el product type: 3/4 Tights
 		/*
 		$skus = $variants = array('SL-BBLC-WH-KCL-YS','SL-BBLC-WH-KCL-YM','SL-BBLC-WH-KCL-YL','SL-BB-RIP-B-KCL-YS','SL-BB-RIP-B-KCL-YM','SL-BB-RIP-B-KCL-YL','SL-BSTMST-BLU-KCL-YS','SL-BSTMST-BLU-KCL-YM','SL-BSTMST-BLU-KCL-YL','SL-BLKFIRE-BO-KCL-YS','SL-BLKFIRE-BO-KCL-YM','SL-BLKFIRE-BO-KCL-YL','SL-BLK-KCL-YS','SL-BLK-KCL-YM','SL-BLK-KCL-YL','SL-BLKRAIN-B-KCL-YS','SL-BLKRAIN-B-KCL-YM','SL-BLKRAIN-B-KCL-YL','SL-ELEC-YELL-KCL-YS','SL-ELEC-YELL-KCL-YM','SL-ELEC-YELL-KCL-YL','SL-BLK-GLD-ICA-KCL-YS','SL-BLK-GLD-ICA-KCL-YM','SL-BLK-GLD-ICA-KCL-YL','SL-ICA-WHT-GLD-KCL-YS','SL-ICA-WHT-GLD-KCL-YM','SL-ICA-WHT-GLD-KCL-YL','SL-NEB-BBP-KCL-YS','SL-NEB-BBP-KCL-YM','SL-NEB-BBP-KCL-YL','SL-BB-OLD-KCL-YS','SL-BB-OLD-KCL-YM','SL-BB-OLD-KCL-YL','SL-RDLIGHT-R-KCL-YS','SL-RDLIGHT-R-KCL-YM','SL-RDLIGHT-R-KCL-YL','SL-SPI-RED-KCL-YS','SL-SPI-RED-KCL-YM','SL-SPI-RED-KCL-YL','SL-RIP-BR-BW-KCL-YS','SL-RIP-BR-BW-KCL-YM','SL-RIP-BR-BW-KCL-YL','SL-RYL-KCL-YS','SL-RYL-KCL-YL','SL-SAV-BLU-KCL-YS','SL-SAV-BLU-KCL-YM','SL-SAV-BLU-KCL-YL','SL-TATCT-BG-KCL-YS','SL-TATCT-BG-KCL-YM','SL-TATCT-BG-KCL-YL','SL-TRY-BBW-KCL-YS','SL-TRY-BBW-KCL-YM','SL-TRY-BBW-KCL-YL','SL-TRY-RBW-KCL-YS','SL-TRY-RBW-KCL-YM','SL-TRY-RBW-KCL-YL','SL-TRY-USA-KCL-YS','SL-TRY-USA-KCL-YM','SL-TRY-USA-KCL-YL','SL-WHT-KCL-YS','SL-WHT-KCL-YM','SL-WHT-KCL-YL','SL-LION-WHT-KCL-YS','SL-LION-WHT-KCL-YM','SL-LION-WHT-KCL-YL','SL-OCWA-KCL-YS','SL-OCWA-KCL-YM','SL-OCWA-KCL-YL','SL-RUB-KCL-YS','SL-RUB-KCL-YM','SL-RUB-KCL-YL','SL-RBUFTBL-KCL-YS','SL-RBUFTBL-KCL-YM','SL-RBUFTBL-KCL-YL','SL-PSMK-KCL-YS','SL-PSMK-KCL-YM','SL-PSMK-KCL-YL','SL-FYBK-KCL-YS','SL-FYBK-KCL-YM','SL-FYBK-KCL-YL','SL-TIGMSK-KCL-YS','SL-TIGMSK-KCL-YM','SL-TIGMSK-KCL-YL','SL-TIGR-KCL-YS','SL-TIGR-KCL-YM','SL-TIGR-KCL-YL','SL-COROBB-KCL-YS','SL-COROBB-KCL-YM','SL-COROBB-KCL-YL','SL-CORBNY-KCL-YS','SL-CORBNY-KCL-YM','SL-CORBNY-KCL-YL','SL-TBLNCOR-KCL-YS','SL-TBLNCOR-KCL-YM','SL-TBLNCOR-KCL-YL','SL-CORBST-KCL-YS','SL-CORBST-KCL-YM','SL-CORBST-KCL-YL','SL-ANIMRD-KCL-YS','SL-ANIMRD-KCL-YM','SL-ANIMRD-KCL-YL','SL-VIBENAT-KCL-YS','SL-VIBENAT-KCL-YM','SL-VIBENAT-KCL-YL','SL-RDSCTCT-KCL-YS','SL-RDSCTCT-KCL-YM','SL-RDSCTCT-KCL-YL','SL-SAV2GRA-KCL-YS','SL-SAV2GRA-KCL-YM','SL-SAV2GRA-KCL-YL','SL-SHKMSK-KCL-YS','SL-SHKMSK-KCL-YM','SL-SHKMSK-KCL-YL','SL-DIGCAM-KCL-YS','SL-DIGCAM-KCL-YM','SL-DIGCAM-KCL-YL','SL-DIGCARBST-KCL-YS','SL-DIGCARBST-KCL-YM','SL-DIGCARBST-KCL-YL','SL-DIGULPR-KCL-YS','SL-DIGULPR-KCL-YM','SL-DIGULPR-KCL-YL','SL-DOMBKOP-KCL-YS','SL-DOMBKOP-KCL-YM','SL-DOMBKOP-KCL-YL','SL-RIPBYL-KCL-YS','SL-RIPBYL-KCL-YM','SL-RIPBYL-KCL-YL','SL-CORMHW-KCL-YS','SL-CORMHW-KCL-YM','SL-CORMHW-KCL-YL','SL-GRESMM-KCL-YS','SL-GRESMM-KCL-YM','SL-GRESMM-KCL-YL','SL-GRESMM-KCL-YS','SL-GRESMM-KCL-YM','SL-GRESMM-KCL-YL','SL-USAMFG-KCL-YS','SL-USAMFG-KCL-YM','SL-USAMFG-KCL-YL','SL-BLJICC-KCL-YS','SL-BLJICC-KCL-YM','SL-BLJICC-KCL-YL','SL-ICAMER-KCL-YS','SL-ICAMER-KCL-YM','SL-ICAMER-KCL-YL','SL-HTNOX-KCL-YS','SL-HTNOX-KCL-YM','SL-HTNOX-KCL-YL','SL-NAVSTRS-KCL-YS','SL-NAVSTRS-KCL-YM','SL-NAVSTRS-KCL-YL','SL-NEONG-KCL-YS','SL-NEONG-KCL-YM','SL-NEONG-KCL-YL','SL-GLDMARFY-KCL-YS','SL-GLDMARFY-KCL-YM','SL-GLDMARFY-KCL-YL','SL-GOARD-KCL-YS','SL-GOARD-KCL-YM','SL-GOARD-KCL-YL','SL-BIOMER-KCL-YS','SL-BIOMER-KCL-YM','SL-BIOMER-KCL-YL','SL-MONYBJM-KCL-YS','SL-MONYBJM-KCL-YM','SL-MONYBJM-KCL-YL','SL-GOABL-KCL-YS','SL-GOABL-KCL-YM','SL-GOABL-KCL-YL','SL-GOAWT-KCL-YS','SL-GOAWT-KCL-YM','SL-GOAWT-KCL-YL','SL-GOAWT-KCL-YS','SL-GOAWT-KCL-YM','SL-GOAWT-KCL-YL','SL-GOANV-KCL-YS','SL-GOANV-KCL-YM','SL-GOANV-KCL-YL','SL-GOANV-KCL-YS','SL-GOANV-KCL-YM','SL-GOANV-KCL-YL','SL-CBLT-KCL-YS','SL-CBLT-KCL-YM','SL-CBLT-KCL-YL','SL-GALGXY-KCL-YS','SL-GALGXY-KCL-YM','SL-GALGXY-KCL-YL','SL-BBPDTR-KCL-YS','SL-BBPDTR-KCL-YM','SL-BBPDTR-KCL-YL','SL-SPLTRD-KCL-YS','SL-SPLTRD-KCL-YM','SL-SPLTRD-KCL-YL','SL-TCTUSFBB-KCL-YS','SL-TCTUSFBB-KCL-YM','SL-TCTUSFBB-KCL-YL','SL-10EJIRD-KCL-YS','SL-10EJIRD-KCL-YM','SL-10EJIRD-KCL-YL','SL-10EJICK-KCL-YS','SL-10EJICK-KCL-YM','SL-10EJICK-KCL-YL','SL-SKLBKWH-KCL-YS','SL-SKLBKWH-KCL-YM','SL-SKLBKWH-KCL-YL','SL-SNPDSRC-KCL-YS','SL-SNPDSRC-KCL-YM','SL-SNPDSRC-KCL-YL','SL-PZZS-KCL-YS','SL-PZZS-KCL-YM','SL-PZZS-KCL-YL','SL-PLYBLK-KCL-YS','SL-PLYBLK-KCL-YM','SL-PLYBLK-KCL-YL','SL-ORGCRD-KCL-YS','SL-ORGCRD-KCL-YM','SL-ORGCRD-KCL-YL','SL-USAMCFBG-KCL-YS','SL-USAMCFBG-KCL-YM','SL-USAMCFBG-KCL-YL','SL-USAMCFBG-KCL-YS','SL-USAMCFBG-KCL-YM','SL-USAMCFBG-KCL-YL','SL-STLTCT-KCL-YS','SL-STLTCT-KCL-YM','SL-STLTCT-KCL-YL','SL-CRSVPBKY-KCL-YS','SL-CRSVPBKY-KCL-YM','SL-CRSVPBKY-KCL-YL','SL-CRSGBKRD-KCL-YS','SL-CRSGBKRD-KCL-YM','SL-CRSGBKRD-KCL-YL','SL-CRSVRBW-KCL-YS','SL-CRSVRBW-KCL-YM','SL-CRSVRBW-KCL-YL','SL-GRNSMLM-KCL-YS','SL-GRNSMLM-KCL-YM','SL-GRNSMLM-KCL-YL','SL-GRLLMS-KCL-YS','SL-GRLLMS-KCL-YM','SL-GRLLMS-KCL-YL','SL-INSPBLK-KCL-YS','SL-INSPBLK-KCL-YM','SL-INSPBLK-KCL-YL','SL-OCNWRG-KCL-YS','SL-OCNWRG-KCL-YM','SL-OCNWRG-KCL-YL','SL-HBDBNT-KCL-YS','SL-HBDBNT-KCL-YM','SL-HBDBNT-KCL-YL','SL-ASNBK-KCL-YS','SL-ASNBK-KCL-YM','SL-ASNBK-KCL-YL','SL-ASNRD-KCL-YS','SL-ASNRD-KCL-YM','SL-ASNRD-KCL-YL','SL-ASNBL-KCL-YS','SL-ASNBL-KCL-YM','SL-ASNBL-KCL-YL','SL-ASNBL-KCL-YS','SL-ASNBL-KCL-YM','SL-ASNBL-KCL-YL','SL-ASNPP-KCL-YS','SL-ASNPP-KCL-YM','SL-ASNPP-KCL-YL','SL-FRSOG-KCL-YS','SL-FRSOG-KCL-YM','SL-FRSOG-KCL-YL','SL-SNKSKB-KCL-YS','SL-SNKSKB-KCL-YM','SL-SNKSKB-KCL-YL','SL-SVGAME-KCL-YS','SL-SVGAME-KCL-YM','SL-SVGAME-KCL-YL','SL-ICGICA-KCL-YS','SL-ICGICA-KCL-YM','SL-ICGICA-KCL-YL','SL-ICGMCH-KCL-YS','SL-ICGMCH-KCL-YM','SL-ICGMCH-KCL-YL','SL-ICGSTC-KCL-YS','SL-ICGSTC-KCL-YM','SL-ICGSTC-KCL-YL','SL-ICGMLT-KCL-YS','SL-ICGMLT-KCL-YM','SL-ICGMLT-KCL-YL','SL-RPPWLF-KCL-YS','SL-RPPWLF-KCL-YM','SL-RPPWLF-KCL-YL','SL-OCNWRR-KCL-YS','SL-OCNWRR-KCL-YM','SL-OCNWRR-KCL-YL','SL-SKU000-KCL-YS','SL-SKU000-KCL-YM','SL-SKU000-KCL-YL','SL-HOT-PNK-CL-YL','SL-HOT-PNK-CL-S','SL-HOT-PNK-CL-M','SL-HOT-PNK-CL-L','SL-HOT-PNK-CL-XL','SL-HOT-PNK-CL-XXL','SL-HOT-PNK-CL-XXXL','SL-HOT-PNK-CL-YS','SL-HOT-PNK-CL-YM','SL-DUCKS-KCL-YS','SL-DUCKS-KCL-YM','SL-DUCKS-KCL-YL','SL-FIBO-KCL-YS','SL-FIBO-KCL-YM','SL-FIBO-KCL-YL','SL-WAUSFL-KCL-YS','SL-WAUSFL-KCL-YM','SL-WAUSFL-KCL-YL','SL-BRRENAUSFL-KCL-YS','SL-BRRENAUSFL-KCL-YM','SL-BRRENAUSFL-KCL-YL','SL-BOOM-KCL-YS','SL-BOOM-KCL-YM','SL-BOOM-KCL-YL','SL-KEOUBLYE-KCL-YS','SL-KEOUBLYE-KCL-YM','SL-KEOUBLYE-KCL-YL','SL-GOTOWACA-KCL-YS','SL-GOTOWACA-KCL-YM','SL-GOTOWACA-KCL-YL','SL-EMFA-KCL-YS','SL-EMFA-KCL-YM','SL-EMFA-KCL-YL','SL-DINO2-KCL-YS','SL-DINO2-KCL-YM','SL-DINO2-KCL-YL','SL-GRTU-KCL-YS','SL-GRTU-KCL-YM','SL-GRTU-KCL-YL','SL-UNBL-KCL-YS','SL-UNBL-KCL-YM','SL-UNBL-KCL-YL','SL-MAND-KCL-YS','SL-MAND-KCL-YM','SL-MAND-KCL-YL','SL-MOSIYERE-KCL-YS','SL-MOSIYERE-KCL-YM','SL-MOSIYERE-KCL-YL','SL-STWH-KCL-YS','SL-STWH-KCL-YM','SL-STWH-KCL-YL','SL-PASP-KCL-YS','SL-PASP-KCL-YM','SL-PASP-KCL-YL','SL-GOHERE-KCL-YS','SL-GOHERE-KCL-YM','SL-GOHERE-KCL-YL','SL-WTP-KCL-YS','SL-WTP-KCL-YM','SL-WTP-KCL-YL','SL-SVGCHYPP-KCL-YS','SL-SVGCHYPP-KCL-YM','SL-SVGCHYPP-KCL-YL','SL-SVGCHOBL-KCL-YS','SL-SVGCHOBL-KCL-YM','SL-SVGCHOBL-KCL-YL','SL-SVGCHBG-KCL-YS','SL-SVGCHBG-KCL-YM','SL-SVGCHBG-KCL-YL','SL-BDROS-KCL-YS','SL-BDROS-KCL-YM','SL-BDROS-KCL-YL','SL-GDFSTR-KCL-YS','SL-GDFSTR-KCL-YM','SL-GDFSTR-KCL-YL','SL-HAHAWHBL-KCL-YS','SL-HAHAWHBL-KCL-YM','SL-HAHAWHBL-KCL-YL','SL-SKANSN-KCL-YS','SL-SKANSN-KCL-YM','SL-SKANSN-KCL-YL','SL-HYBLBL-KCL-YS','SL-HYBLBL-KCL-YM','SL-HYBLBL-KCL-YL','SL-PIDO-KCL-YS','SL-PIDO-KCL-YM','SL-PIDO-KCL-YL','SL-VIBLJ-KCL-YS','SL-VIBLJ-KCL-YM','SL-VIBLJ-KCL-YL','SL-LIONST-KCL-YS','SL-LIONST-KCL-YM','SL-LIONST-KCL-YL','SL-CLDBSTFG-KCL-YS','SL-CLDBSTFG-KCL-YM','SL-CLDBSTFG-KCL-YL','SL-10EJICK-KCL-YM');

 		//2. Itera por todos los skus para determinar la cantidd de productos ordenados
 		$totalOrdered = 0;
 		for ($i=0;$i < count($skus);$i++){

 			echo "Probando para: ".$skus[$i]."\n";
 			

 		 	$orderItemsByProductType =  \DB::table('sh_purchaseorder_items')
                        ->leftJoin('sh_purchaseorders','sh_purchaseorder_items.idpo','=','sh_purchaseorders.id')
                        ->select('sh_purchaseorder_items.*')
                        ->whereRaw("(sh_purchaseorders.fulfillment_status != 'closed' and sh_purchaseorders.fulfillment_status != 'canceled') and sh_purchaseorder_items.sku='".$skus[$i]."' ")
                        ->get();
 		 	if ($orderItemsByProductType->count() > 0){
 		 		foreach ($orderItemsByProductType as $orderItem){
 		 			$totalOrdered += ($orderItem->qty_pending);
 		 		}
 		 		print_r($orderItemsByProductType);
 		 	}

 		 	echo "\n\n--------------------------\nTotal ordenado: ".$totalOrdered."\n";
 		}
		*/
 		$this->assertTrue(true);

 	}


 	public function testCreateInventoryReport(){
        $reportCreator = new ShipheroDailyInventoryReport();
        $report = $reportCreator->createReport(['graphqlUrl'=>'https://public-api.shiphero.com/graphql','authUrl'=>'https://public-api.shiphero.com/auth','qtyProducts'=>980,'tries' => 4,'available'=>false]);

        //print_r($report);
        $this->assertEquals(1,$report->inventoryReportItems->count());
        $this->assertEquals(0,$report->inventoryReportItems->get(0)->total_on_order);
 	}


 	public function testOrderingInventoryReportByInventoryQty(){

 		$invReport = InventoryReport::find(1);
 		//print_r($invReport->inventoryReportItems);


 		/*
 		echo "\nInventory Report antes de ordenamiento: \n";
 		foreach($invReport->inventoryReportItems as $invReportItem){

 			echo $invReportItem->label.": ".$invReportItem->total_inventory."\n";

 		}
 		*/

 		$this->assertEquals(128,$invReport->inventoryReportItems->get(0)->total_inventory);

 		//Ordenando los items del Invetory Report por cantidad del inventario
 		$invReport->inventoryReportItems = $invReport->inventoryReportItems()->orderBy('total_inventory')->get();


 		$this->assertEquals(2,$invReport->inventoryReportItems->get(0)->total_inventory);
 		/*
 		echo "\n\nInventory Report despues de ordenamiento: \n";
 		foreach($invReport->inventoryReportItems as $invReportItem){

 			echo $invReportItem->label.": ".$invReportItem->total_inventory."\n";

 		}
 		*/

 	}


	/* Preparing the Test */

	public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();
        return $app;
    }

     /**
     * Migrates the database and set the mailer to 'pretend'.
     * This will cause the tests to run quickly.
     */
    private function prepareForTests()
    {
		\Artisan::call('migrate');
     	// Adding data to database
     	//Product #1
     	array_push($this->products,new Product());
		$this->products[0]->idsp = "shpfy_6566584451165";
		$this->products[0]->title = 'Hot Orange Arm Sleeve';
		$this->products[0]->vendor = 'Sleefs';
		$this->products[0]->product_type = 'Sleeve';
		$this->products[0]->handle = 'hot-orange-arm-sleeve';
		$this->products[0]->save();

		array_push($this->variants,new Variant());
		$this->variants[0]->idsp = "shpfy_39319437836381";
		$this->variants[0]->sku = 'SL-HOTORG-AS-S-M';
		$this->variants[0]->title = 'S-M / Hot Orange';
		$this->variants[0]->idproduct = $this->products[0]->id;
		$this->variants[0]->price = 15.0;
		$this->variants[0]->save();

		array_push($this->variants,new Variant());
		$this->variants[1]->idsp = "shpfy_39319437803613";
		$this->variants[1]->sku = 'SL-HOTORG-AS-Y';
		$this->variants[1]->title = 'Y / Hot Orange';
		$this->variants[1]->idproduct = $this->products[0]->id;
		$this->variants[1]->price = 15.0;
		$this->variants[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[2]->idsp = "shpfy_39319437803616";
		$this->variants[2]->sku = 'SL-HOTORG-AS-L';
		$this->variants[2]->title = 'L / Hot Orange';
		$this->variants[2]->idproduct = $this->products[0]->id;
		$this->variants[2]->price = 15.0;
		$this->variants[2]->save();

		//Product #2
		array_push($this->products,new Product());
		$this->products[1]->idsp = "shpfy_311851773";
		$this->products[1]->title = 'Basic White Arm Sleeve';
		$this->products[1]->vendor = 'Sleefs';
		$this->products[1]->product_type = 'Sleeve';
		$this->products[1]->handle = 'white-arm-sleeves';
		$this->products[1]->save();

		array_push($this->variants,new Variant());
		$this->variants[3]->idsp = "shpfy_44590695434";
		$this->variants[3]->sku = 'SL-WHT-Y-1';
		$this->variants[3]->title = 'Y / white';
		$this->variants[3]->idproduct = $this->products[1]->id;
		$this->variants[3]->price = 15.0;
		$this->variants[3]->save();

		array_push($this->variants,new Variant());
		$this->variants[4]->idsp = "shpfy_44590695562";
		$this->variants[4]->sku = 'SL-WHT-XS-1';
		$this->variants[4]->title = 'XS / white';
		$this->variants[4]->idproduct = $this->products[1]->id;
		$this->variants[4]->price = 15.0;
		$this->variants[4]->save();

		array_push($this->variants,new Variant());
		$this->variants[5]->idsp = "shpfy_44590695690";
		$this->variants[5]->sku = 'SL-WHT-S-M-1';
		$this->variants[5]->title = 'S-M / white';
		$this->variants[5]->idproduct = $this->products[1]->id;
		$this->variants[5]->price = 15.0;
		$this->variants[5]->save();


		array_push($this->variants,new Variant());
		$this->variants[6]->idsp = "shpfy_44590695818";
		$this->variants[6]->sku = 'SL-WHT-L-1';
		$this->variants[6]->title = 'L / white';
		$this->variants[6]->idproduct = $this->products[1]->id;
		$this->variants[6]->price = 15.0;
		$this->variants[6]->save();

		//Product #3
		array_push($this->products,new Product());
		$this->products[2]->idsp = "shpfy_402045989";
		$this->products[2]->title = 'Basic Black Headband';
		$this->products[2]->vendor = 'Sleefs';
		$this->products[2]->product_type = 'Wide Headband';
		$this->products[2]->handle = 'black-wide-headband';
		$this->products[2]->save();

		array_push($this->variants,new Variant());
		$this->variants[7]->idsp = "shpfy_402045989";
		$this->variants[7]->sku = 'SL-BLK-WH';
		$this->variants[7]->title = 'ONE SIZE / Black';
		$this->variants[7]->idproduct = $this->products[2]->id;
		$this->variants[7]->price = 5.0;
		$this->variants[7]->save();

		//Product #4
		array_push($this->products,new Product());
		$this->products[3]->idsp = "shpfy_4579115073629";
		$this->products[3]->title = 'Basic White Leg Sleeve';
		$this->products[3]->vendor = 'Sleefs';
		$this->products[3]->product_type = 'Long Leg Sleeve';
		$this->products[3]->handle = 'basic-white-leg-sleeve';
		$this->products[3]->save();

		array_push($this->variants,new Variant());
		$this->variants[8]->idsp = "shpfy_32123436007517";
		$this->variants[8]->sku = 'SL-WHT-LG-S';
		$this->variants[8]->title = 'Slim / White';
		$this->variants[8]->idproduct = $this->products[3]->id;
		$this->variants[8]->price = 15.00;
		$this->variants[8]->save();

		array_push($this->variants,new Variant());
		$this->variants[9]->idsp = "shpfy_32123436040285";
		$this->variants[9]->sku = 'SL-WHT-LG-M';
		$this->variants[9]->title = 'Reg / White';
		$this->variants[9]->idproduct = $this->products[3]->id;
		$this->variants[9]->price = 15.00;
		$this->variants[9]->save();

		array_push($this->variants,new Variant());
		$this->variants[10]->idsp = "shpfy_32123436073053";
		$this->variants[10]->sku = 'SL-WHT-LG-L';
		$this->variants[10]->title = 'Big / White';
		$this->variants[10]->idproduct = $this->products[3]->id;
		$this->variants[10]->price = 15.00;
		$this->variants[10]->save();

		// Adding POs 

		//PO #1
		array_push($this->pos, new PurchaseOrder());
        $this->pos[0]->po_id = 515;
        $this->pos[0]->po_number = '2305-01 Sleeves';
        $this->pos[0]->po_date = '2023-04-05 10:21:00';
        $this->pos[0]->fulfillment_status = 'pending';
		$this->pos[0]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[0]->idpo = $this->pos[0]->id;
		$this->items[0]->sku = 'SL-HOTORG-AS-L';
		$this->items[0]->shid = '59dbc5830f969';
		$this->items[0]->quantity = 50;
		$this->items[0]->quantity_received = 5;
		$this->items[0]->qty_pending = 45;
		$this->items[0]->name = 'Hot Orange Arm Sleeve L / Hot Orange';
		$this->items[0]->idmd5 = md5('SL-HOTORG-AS-L'.'-'.'515');
		$this->items[0]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[1]->idpo = $this->pos[0]->id;
		$this->items[1]->sku = 'SL-HOTORG-SV-XL';
		$this->items[1]->shid = '59dbc5830fa20';
		$this->items[1]->quantity = 25;
		$this->items[1]->quantity_received = 0;
		$this->items[1]->qty_pending = 25;
		$this->items[1]->name = 'Hot Orange Forearm Compression Sleeve XL / Orange';
		$this->items[1]->idmd5 = md5('SL-HOTORG-SV-XL'.'-'.'515');
		$this->items[1]->save();


		//PO #2
		array_push($this->pos, new PurchaseOrder());
        $this->pos[1]->po_id = 516;
        $this->pos[1]->po_number = 'MMA PO 1';
        $this->pos[1]->po_date = '2017-12-30 21:29:00';
        $this->pos[1]->fulfillment_status = 'pending';
		$this->pos[1]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[2]->idpo = $this->pos[1]->id;
		$this->items[2]->sku = 'SL-10EJICK-KCL-YM';
		$this->items[2]->shid = '69d3c5830f969';
		$this->items[2]->quantity = 12;
		$this->items[2]->quantity_received = 3;
		$this->items[2]->qty_pending = 9;
		$this->items[2]->name = '100 Emoji Black Tights for Kids / YM / Black';
		$this->items[2]->idmd5 = md5('SL-10EJICK-KCL-YM'.'-'.'516');
		$this->items[2]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[3]->idpo = $this->pos[1]->id;
		$this->items[3]->sku = 'SL-AERIB-KS-YL';
		$this->items[3]->shid = '62c35a8302a86';
		$this->items[3]->quantity = 21;
		$this->items[3]->quantity_received = 21;
		$this->items[3]->qty_pending = 0;
		$this->items[3]->name = 'Aerial blue and navy arm sleeve / Y / Blue/navy';
		$this->items[3]->idmd5 = md5('SL-AERIB-KS-YL'.'-'.'516');
		$this->items[3]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[4]->idpo = $this->pos[1]->id;
		$this->items[4]->sku = 'SL-REDHAT';
		$this->items[4]->shid = '1aa8217bd792f';
		$this->items[4]->quantity = 23;
		$this->items[4]->quantity_received = 20;
		$this->items[4]->qty_pending = 3;
		$this->items[4]->name = 'SL-REDHAT';
		$this->items[4]->idmd5 = md5('SL-REDHAT'.'-'.'516');
		$this->items[4]->save();

		array_push($this->items,new PurchaseOrderItem());
		$this->items[5]->idpo = $this->pos[1]->id;
		$this->items[5]->sku = 'SL-ANIM-BEAR-L-1';
		$this->items[5]->shid = '3149adc003ed9';
		$this->items[5]->quantity = 5;
		$this->items[5]->quantity_received = 0;
		$this->items[5]->qty_pending = 5;
		$this->items[5]->name = 'Ripped Bear arm sleeve / L / Black/White';
		$this->items[5]->idmd5 = md5('SL-ANIM-BEAR-L-1'.'-'.'516');
		$this->items[5]->save();
		
		

		//---------------------------------------------------------------
		//Inventory Report Data
		//---------------------------------------------------------------

		array_push($this->inventoryReports, new \Sleefs\Models\Shiphero\InventoryReport());
		$this->inventoryReports[0]->save();

		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[0]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[0]->label = 'Sleeve';
		$this->inventoryReportsItems[0]->total_inventory = 128;
		$this->inventoryReportsItems[0]->total_on_order = 35;
		$this->inventoryReportsItems[0]->save();

		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[1]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[1]->label = 'Spats';
		$this->inventoryReportsItems[1]->total_inventory = 34;
		$this->inventoryReportsItems[1]->total_on_order = 0;
		$this->inventoryReportsItems[1]->save();

		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[2]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[2]->label = 'Kids Tights';
		$this->inventoryReportsItems[2]->total_inventory = 298;
		$this->inventoryReportsItems[2]->total_on_order = 191;
		$this->inventoryReportsItems[2]->save();

		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[3]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[3]->label = 'Jersey';
		$this->inventoryReportsItems[3]->total_inventory = 78;
		$this->inventoryReportsItems[3]->total_on_order = 0;
		$this->inventoryReportsItems[3]->save();

		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[4]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[4]->label = 'Tights';
		$this->inventoryReportsItems[4]->total_inventory = 2;
		$this->inventoryReportsItems[4]->total_on_order = 15;
		$this->inventoryReportsItems[4]->save();


		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[5]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[5]->label = 'Yoga Pants';
		$this->inventoryReportsItems[5]->total_inventory = 19;
		$this->inventoryReportsItems[5]->total_on_order = 5;
		$this->inventoryReportsItems[5]->save();


		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[6]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[6]->label = 'Kids Tights';
		$this->inventoryReportsItems[6]->total_inventory = 5690;
		$this->inventoryReportsItems[6]->total_on_order = 560;
		$this->inventoryReportsItems[6]->save();


		array_push($this->inventoryReportsItems, new InventoryReportItem());
		$this->inventoryReportsItems[7]->idreporte = $this->inventoryReports[0]->id;
		$this->inventoryReportsItems[7]->label = 'Hoodie';
		$this->inventoryReportsItems[7]->total_inventory = 45;
		$this->inventoryReportsItems[7]->total_on_order = 0;
		$this->inventoryReportsItems[7]->save();

    }

}