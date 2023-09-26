<?php
namespace Sleefs\Helpers\Google\SpreadSheets;

use Sleefs\Helpers\Misc\Response;
use Sleefs\Models\Shiphero\Vendor;

/**
 * Esta clase tiene como objetivo, convertir los datos de una
 * PO Extendida, recuperada vía API a un objeto con los datos
 * necesarios para registrar los datos en Google Spreadsheets
 * 
 * @param 	stdClass $shipheroPoExtended 	Objeto con los datos de una PO (Purchase Order) recuperado 
 * 											vía API desde el servicio shiphero.com, la estructura del 
 * 											objeto es el siguiente:
 * 
 * 											stdClass Object
 *											(
 *												[id] => UHVyY2hhc2VPcmRlcjoxNDkzNDgx
 *												[legacy_id] => 1493481
 *												[po_number] => 173892295001025491 Gloves
 *												[po_date] => 2023-08-14 00:00:00
 * 												[po_id] => 2830
 *												[account_id] => QWNjb3VudDoxMTU3
 *												[vendor_id] => ND
 *												[created_at] => 2023-05-04 15:45:08
 *												[fulfillment_status] => pending
 *												[po_note] => 5/8 Deposit $3,410.52 7/12 Balance $9,505.18
 *												[description] =>
 *												[subtotal] => 11573.7
 *												[shipping_price] => 1342.00
 *												[total_price] => 12915.7
 *												[line_items] => Array
 *												(
 *													[0] => stdClass Object
 *													(
 *														[node] => stdClass Object
 *														(
 *															[id] => UHVyY2hhc2VPcmRlckxpbmVJdGVtOjIxODc3MzA5
 *															[price] => 5.1300
 *															[po_id] => 1493481
 *															[account_id] => QWNjb3VudDoxMTU3
 *															[warehouse_id] => V2FyZWhvdXNlOjE2ODQ=
 *															[vendor_id] => VmVuZG9yOjQxNjQ3MA==
 *															[po_number] => Gloves
 *															[sku] => SL-NEOORG-RG-M
 *															[barcode] => SL-NEOORG-RG-M
 *															[note] =>
 *															[quantity] => 100
 *															[quantity_received] => 100
 *															[quantity_rejected] => 0
 *															[product_name] => Hot Orange Sticky Football Receiver Gloves M / Hot Orange
 *															[fulfillment_status] => pending
 *															[vendor] =>
 *															)
 *
 *													)
 *												)
 *
 * @return 	stdClass $poForGoogleSheet 		Objeto tipo stdClass con los datos necesarios para realizar un registro en 
 * 											Google Spreadsheets, el objeto debe tener la siguiente estructura
 * 											
 * 											stdClass Object
 *											(
 *											    [sh_id] => 57bcfb0a65c7b7c50780ef415d472fac
 *											    [id] => 2928
 *											    [po_number] => 185557482501025491 Pink Balaclav
 * 											    [status] => pending
 *											    [created_date] => 2023-08-31 12:19:00
 *											    [expected_date] => 2023-09-18
 *											    [vendor] => Wuxi Jieyu Microfiber Fabric Manufacturing
 *											    [product_cost] => 1450
 *											    [shipping_cost] => 1475
 *											    [total_cost] => 2925
 *											    [qty_of_skus] => 1
 *											    [qty_of_units] => 1000
 *											    [qty_of_units_received] => 0
 *											)
 *
 *  
 */ 

class ShipheroExtendedPoToGoogleSheetRecordConverter{

	public function convert (\stdClass $shipheroPoExtended): mixed
	{
		$poForGoogleSheet = new \stdClass();
		$poForGoogleSheet->sh_id = $shipheroPoExtended->id;
		$poForGoogleSheet->id = $shipheroPoExtended->po_id;
		$poForGoogleSheet->po_number = $shipheroPoExtended->po_number;
		$poForGoogleSheet->status = $shipheroPoExtended->fulfillment_status;
		$poForGoogleSheet->created_date = $shipheroPoExtended->created_at;
		$poForGoogleSheet->expected_date = $shipheroPoExtended->po_date;
		$poForGoogleSheet->vendor = 'ND';
		$poForGoogleSheet->product_cost = (float)($shipheroPoExtended->subtotal);
		$poForGoogleSheet->shipping_cost = (float)($shipheroPoExtended->shipping_price);
		$poForGoogleSheet->total_cost = (float)($shipheroPoExtended->total_price);
		$poForGoogleSheet->qty_of_skus = 0;
		$poForGoogleSheet->qty_of_units = 0;
		$poForGoogleSheet->qty_of_units_received = 0; 

		//Gets the Vendor Name
		$vendorDataResponse = $this->getVendorData($shipheroPoExtended);
		if ($vendorDataResponse->value != '')
		{
			$poForGoogleSheet->vendor = $vendorDataResponse->value->name;
		}	

		//Gets the quantity of skus, units and received units.
		$lineItemsQuantities = $this->iterateLineItems($shipheroPoExtended->line_items);
		$poForGoogleSheet->qty_of_skus = $lineItemsQuantities->qty_of_skus;
		$poForGoogleSheet->qty_of_units = $lineItemsQuantities->qty_of_units;
		$poForGoogleSheet->qty_of_units_received = $lineItemsQuantities->qty_of_units_received; 
		return $poForGoogleSheet;
	}


	private function getVendorData ($extendedPo): mixed
	{
		$response = new Response();
		if (isset($extendedPo->line_items) && is_array($extendedPo->line_items))
		{
			foreach ($extendedPo->line_items as $item)
			{
				if (isset($item->node->vendor_id))
				{
					$vendor = Vendor::whereRaw(" idsp='".$item->node->vendor_id."' ")->first();
					if ($vendor)
					{
						$response->value = $vendor;
						$response->status = 'ok';
						return $response;
					}
				}
			}
		}
		$response->value = '';
		$response->notes = 'Vendor not found';
		$response->status = 'ok';
		return $response;
	}


	private function iterateLineItems ($line_items): mixed
	{
		$reducedLineItemsValues = new \stdClass();
		$reducedLineItemsValues->qty_of_skus = 0;
		$reducedLineItemsValues->qty_of_units = 0;
		$reducedLineItemsValues->qty_of_units_received = 0;
		$reducedLineItemsValues = array_reduce($line_items,function ($reducedLineItemsValues,$item){
			$reducedLineItemsValues->qty_of_skus++;
			$reducedLineItemsValues->qty_of_units += $item->node->quantity;
			$reducedLineItemsValues->qty_of_units_received += $item->node->quantity_received;
			return $reducedLineItemsValues;
		},$reducedLineItemsValues);
		return $reducedLineItemsValues;
	}

}