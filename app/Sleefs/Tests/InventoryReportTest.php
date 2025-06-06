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
 		
 		//Desde 2025-06-04 se detecta que el API tiene una limitación de 500 registros como limite máximo
 		//no aparece oficialmente en la documentación de shiphero, pero haciendo pruebas se detecta ese valor.
 		$prdsCollection = $shProductsGetter->getAllProducts(['graphqlUrl'=>'https://public-api.shiphero.com/graphql','authUrl'=>'https://public-api.shiphero.com/auth','qtyProducts'=>500,'tries' => 3],$prdsCollection);
 	
 		//Nota: Sleefs tiene a la fecha de 2025-06-04 más de 23.000 productos,
 		//por lo que esta prueba se hace imposible de ejecutar con la totalidad 
 		//de los productos, por lo tanto se limita a 2000 unds.
 		$this->assertGreaterThan(1499,$prdsCollection->count());
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


 	public function testCreateInventoryReport(){
        $reportCreator = new ShipheroDailyInventoryReport();
        $report = $reportCreator->createReport(['graphqlUrl'=>'https://public-api.shiphero.com/graphql','authUrl'=>'https://public-api.shiphero.com/auth','qtyProducts'=>500,'tries' => 25,'available'=>false]);

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
		$this->items[0]->barcode = 'bSL-HOTORG-AS-L';
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
		$this->items[1]->barcode = 'bSL-HOTORG-SV-XL';
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
		$this->items[2]->barcode = 'bSL-10EJICK-KCL-YM';
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
		$this->items[3]->barcode = 'bSL-AERIB-KS-YL';
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
		$this->items[4]->barcode = 'bSL-REDHAT';
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
		$this->items[5]->barcode = 'bSL-ANIM-BEAR-L-1';
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