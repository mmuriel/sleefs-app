<?php

namespace Sleefs\Helpers\ShopifyAPI;

/*
$pathToScriptFile = __FILE__;
$pathToScriptFile = preg_replace("/Shopify\.php$/","",$pathToScriptFile);
include_once($pathToScriptFile.'../curl/Curl.php');
*/

use Sleefs\Helpers\curl\Curl;

class Shopify {

    private $url;
    private $httpHeaders;

    public function __construct($apiUrl,$apiAccessToken) {

        /*
        $this->url = "https://" . Base::getConfigApp()->params['shopifyws']['SHP_TOKEN'];
        $this->url .= ":" . Base::getConfigApp()->params['shopifyws']['SHP_PWD'];
        $this->url .= "@" . Base::getConfigApp()->params['shopifyws']['SHP_APIURLBASE'];
         * 
         */
        $this->url = "https://" . $apiUrl;
        $this->httpHeaders = array('Content-Type: application/json','X-Shopify-Access-Token: '.$apiAccessToken);
    }

    public function getSingleOrder($id) {

        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }


        $url = $this->url . "orders/{$id}.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getAllOrders($options=null) {
        if ($options == null)
            $url = $this->url . "orders.json";
        else
            $url = $this->url . "orders.json?".$options;

        $response = Curl::urlGetWithResponseHeaders($url,$this->httpHeaders);
        $response['content'] = json_decode($response['content']);
        $response['headers'] = json_decode($response['headers']);
        //print_r($response['headers']);
        if (isset($response['headers']->link)){
            $response['headers']->link = $this->splitPaginationLinksInResponseHeader($response['headers']->link[0]);
            $response['headers']->links_params = array_map(function($link){
                return $this->getPaginationLinkParameters($link);
            },$response['headers']->link);
        }
        //$response = json_encode($response);
        return $response;
    }

    public function getAllProducts($options=null) {
        
        if ($options == null)
            $url = $this->url . "products.json";
        else
            $url = $this->url . "products.json?".$options;

        $response = Curl::urlGetWithResponseHeaders($url,$this->httpHeaders);
        $response['content'] = json_decode($response['content']);
        $response['headers'] = json_decode($response['headers']);
        //print_r($response['headers']);
        if (isset($response['headers']->link)){
            $response['headers']->link = $this->splitPaginationLinksInResponseHeader($response['headers']->link[0]);
            $response['headers']->links_params = array_map(function($link){
                return $this->getPaginationLinkParameters($link);
            },$response['headers']->link);
        }
        //$response = json_encode($response);
        return $response;
    }

    public function getAllProductsByCollection($id) {

        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }

        $url = $this->url . "products.json?collection_id={$id}";
        $response = Curl::urlGetWithResponseHeaders($url,$this->httpHeaders);
        $response['content'] = json_decode($response['content']);
        $response['headers'] = json_decode($response['headers']);
        //print_r($response['headers']);
        if (isset($response['headers']->link)){
            $response['headers']->link = $this->splitPaginationLinksInResponseHeader($response['headers']->link[0]);
            $response['headers']->links_params = array_map(function($link){
                return $this->getPaginationLinkParameters($link);
            },$response['headers']->link);
        }
        //$response = json_encode($response);
        return $response;
    }

    public function getCountProducts() {
        $url = $this->url . "products/count.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }


    public function getCountOrders($filter = null) {

        if ($filter == null)
            $url = $this->url . "products/count.json";
        else
            $url = $this->url . "products/count.json?".$filter;

        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }


    public function getSingleProduct($id) {

        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }
        $url = $this->url . "products/{$id}.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getAllImagesProduct($id) {

        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }

        $url = $this->url . "products/{$id}/images.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getSingleImageProduct($id_product, $id_image) {
        
        if (preg_match("/^shpfy_/",$id_product)){
            $id_product = preg_replace("/^shpfy_/","",$id_product);
        }

        if (preg_match("/^shpfy_/",$id_image)){
            $id_image = preg_replace("/^shpfy_/","",$id_image);
        }

        $url = $this->url . "products/{$id_product}/images/{$id_image}.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getSingleProductVariant($id_variant) {

        if (preg_match("/^shpfy_/",$id_variant)){
            $id_variant = preg_replace("/^shpfy_/","",$id_variant);
        }

        $url = $this->url . "/variants/{$id_variant}.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getAllProductVariants($id_product) {

        if (preg_match("/^shpfy_/",$id_product)){
            $id_product = preg_replace("/^shpfy_/","",$id_product);
        }
        $url = $this->url . "/products/{$id_product}/variants.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return json_decode($contents);
    }

    public function getWebHook() {
        $url = $this->url . "webhooks.json";
        $contents = Curl::urlGet($url,$this->httpHeaders);
        return $contents;
    }

    public function setNoteOrder($id, $note) {


        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }
        $data['order'] = array('id' => $id, 'note' => $note);
        $url = $this->url . "orders/{$id}.json";
        $contents = Curl::urlPUT($url, $data, $this->httpHeaders);
        return ($contents);
    }

    public function updateProductInventory ($productId,$variants){

        if (preg_match("/^shpfy_/",$productId)){
            $productId = preg_replace("/^shpfy_/","",$productId);
        }
        $data= array();
        $data['product'] = array('id' => $productId, 'variants' => $variants);
        $url = $this->url . "products/{$productId}.json";
        $contents = Curl::urlPUT($url,$data, $this->httpHeaders);
        return ($contents);
    }



    public function createAProduct($product){

        $url = $this->url . "products.json";
        //echo json_encode(["product" => (array)$product]);
        $contents = Curl::urlPost($url,["product" => (array)$product] ,$this->httpHeaders);
        return json_decode($contents);
    }




    /**
    * $data must be an associative array as this structure (it is not necessary to apply same fields):
    * 
    * $data = array('product'=>array(
    *       'id' => xxxxxxxxxxx,
    *       'title' => 'Products title',
    *       'hanlde' => 'products-title',
    *       .
    *       .
    *       .
    *   )
    * )
    */
    public function updateProduct ($productId,$data){
        if (preg_match("/^shpfy_/",$productId)){
            $productId = preg_replace("/^shpfy_/","",$productId);
        }
        $url = $this->url . "products/{$productId}.json";
        $contents = Curl::urlPUT($url,$data ,$this->httpHeaders);
        return json_decode($contents);
    }






    public function getCustomer($id) {
        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }
        $url = $this->url . "/customers/{$id}.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);
    }

    public function createTransaction($id,$amount) {

        if (preg_match("/^shpfy_/",$id)){
            $id = preg_replace("/^shpfy_/","",$id);
        }

        $data['transaction'] = array('amount' => $amount,"kind"=>"capture");
        $url = $this->url . "orders/{$id}/transactions.json";
        $contents = Curl::urlPost($url, $data);
        return ($contents);
    }

    public function getFulfillmentByOrder($order){

        $url = $this->url . "/orders/{$order}/fulfillments.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);

    }

    public function getOrderRiskLevel($order){

        $url = $this->url . "/orders/{$order}/risks.json";
        $contents = Curl::urlGet($url);
        return json_decode($contents);

    }

    public function setFulfillmentByOrder ($id,$tracking,$items){

        //$data['fulfillment'] = array('amount' => $amount,"kind"=>"capture");
        $data['fulfillment'] = array();
        $url = $this->url . "orders/{$id}/fulfillments.json";

        //Preparing data

        //Tracking numbers
        switch (count($tracking)){

            case 0:     $data['fulfillment']['tracking_number'] = null;
                        break;

            case 1:     $data['fulfillment']['tracking_number'] = $tracking[0];
                        break;

            default:    $data['fulfillment']['tracking_numbers'] = $tracking;
                        break;

        }

       
        //If there are multiple items
        if (count($items)>=1){

            $data['fulfillment']['line_items'] = array();
            foreach ($items as $item){

                $arrTmp = array("id" => $item);
                array_push($data['fulfillment']['line_items'],$arrTmp);

            }

        }

        $data['fulfillment']['notify_customer'] = true;
        //============================================================
        //Posting data
        //============================================================
        $contents = Curl::urlPost($url,$data);
        return ($contents);

    }


    public function setFulfillmentByOrderOnly ($idorder,$tracking,$notify=true){

        //$data['fulfillment'] = array('amount' => $amount,"kind"=>"capture");
        $data['fulfillment'] = array();
        $url = $this->url . "orders/{$idorder}/fulfillments.json";

        //Preparing data

        //Tracking numbers
        $data['fulfillment']['tracking_number'] = $tracking;
        //Notify user
        $data['fulfillment']['notify_customer'] = $notify;
        //============================================================
        //Posting data
        //============================================================
        $contents = Curl::urlPost($url,$data);
        return ($contents);

    }

    public function verifyWebHook($data, $hmac_header)
    {
      $calculated_hmac = base64_encode(hash_hmac('sha256', $data,SHP_SEC, true));
      return ($hmac_header == $calculated_hmac);
    }

    /**
     * Este método recupera los parámetros necesarios para construir
     * los links de paginación con el nuevo modelo de "cursor-based"
     * implementado por shopify para su API en cualquier listado.
     * 
     * @param string $urlString     String recuperado de los headers de respuesta de una petición 
     *                              previa hecha hacia el API de shopify, que retorna un listado
     *                              de elementos.
     * 
     * @return stdClass $linksParams    Un objeto tipo stdClass que incluye los siguientes atributos:
     * 
     *                                  - linkType
     *                                  - cursor
     *                                  - limit
     */

    public function getPaginationLinkParameters ($urlStr){

        $pregLinkType = "/rel=\"(next|previous)\"/";
        $pregCursor = "/page_info=([a-zA-Z0-9]{5,530})/";
        $pregLimit = "/limit=([0-9]{1,5})/";

        $linkParams = new \stdClass();
        $linkParams->linkType = '';
        $linkParams->cursor = '';
        $linkParams->limit = '';
        
        if (preg_match("/rel=\"(next|previous)\"/",$urlStr,$linkTypeMatches))
            $linkParams->linkType = $linkTypeMatches[1];

        if (preg_match("/page_info=([a-zA-Z0-9]{30,530})/",$urlStr,$cursorMatches))
            $linkParams->cursor = $cursorMatches[1];

        if (preg_match("/limit=([0-9]{1,5})/",$urlStr,$limitMatches))
            $linkParams->limit = $limitMatches[1];
        return $linkParams;
    }


    /**
     * Este método separa los links de paginación del nuevo modelo ("cursor-based")
     * implementado por shopify para su API en cualquier listado que requiera paginar,
     * de la cadena cruda que llega como valor en los headers que retorna shopify en la 
     * petición, la variable header es la: links
     * 
     * @param string $linksRawString        String recuperado de los headers de respuesta de una petición 
     *                                      previa hecha hacia el API de shopify, que retorna un listado
     *                                      de elementos. Los links son valores separados por comas.
     * 
     * @return array $links              Un arreglo de strings, donde cada elemento es un link de 
     *                                      paginación.
     */

    public function splitPaginationLinksInResponseHeader ($rawLinksString){

        $links = [];
        if ($rawLinksString == '' || !isset($rawLinksString))
            return $links;

        $links = preg_split("/\, \</",$rawLinksString);
        return $links;
    }


    /**
     * Este método localiza el valor del cursor (ID de paginación) que shopify retorna
     * en los headers de las peticiones.
     * 
     * @param array $linksInHeader          Arreglo de objetos tipo stdClass que contienen los
     *                                      datos de los links retornados en los headers
     *                                      de respuesta de la petición HTTP.
     * 
     * @param string $linkType              Valor del link a buscar, hastal el momento de la 
     *                                      generación de esta clase, solo se permiten los 
     *                                      valores: previous | next
     * 
     * @return string $cursor               Cadena de caracteres que representa el cursor (ID de
     *                                      paginación).
     */
    public function getPaginationCursorValue($linksInHeader,$linkType){
        $cursor = '';
        if (!is_array($linksInHeader) || count($linksInHeader)== 0)
            return $cursor;

        foreach ($linksInHeader as $link){
            if ($link->linkType == $linkType)
                return trim($link->cursor);
        }
        return $cursor; 
    }



}
?>
