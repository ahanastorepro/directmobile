<?php
/**
* Plugin Name: PriceSpy Products
* Plugin URI: https://storepro.io
* Author: StorePro
* Version: 1.0
* Author URI: https://storepro.io
* Description: Similar Products from PriceSpy
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Enqueue scripts and styles.
 */
add_action( 'wp_enqueue_scripts', 'sp_wp_enqueue_scripts' );
function sp_wp_enqueue_scripts() {
    wp_enqueue_style( 'pricespy-style', plugins_url('', __FILE__) . '/css/pricespy-style.css', false, '1.0.0' );
    wp_enqueue_script( 'pricespy-jquery', plugins_url('', __FILE__) . '/js/pricespy-jquery.js', array( 'jquery' ) );
}

//===== Get Access Token ===========
function getAccessToken() {
        $prspy_token_url = "https://api.schibsted.com/prisjakt/partner-search/token";
        $prspy_client_id = "40fd38daf4db4ebeb771acb2c97fa98d";
        $prspy_client_secret = "9B8CDfd800D6471EBC8A0e919D026Ef5";

	$content = "grant_type=client_credentials&scope=client&client_id=$prspy_client_id&client_secret=$prspy_client_secret";
//	$authorization = base64_encode("$prspy_client_id:$prspy_client_secret");
//      $header = array("Authorization: Basic {$authorization}","Content-Type: application/x-www-form-urlencoded");
	$header = array("Content-Type: application/x-www-form-urlencoded");
        
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $prspy_token_url,
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_POST => true,
		CURLOPT_POSTFIELDS => $content
	));
	$response = curl_exec($curl);
	curl_close($curl);

	return json_decode($response)->access_token;
}


//	step B - with the returned access_token we can make as many calls as we want
function getResource($access_token, $product_id) {
	$prspy_api_url = "https://api.schibsted.com/prisjakt/partner-search/products/$product_id?ref=60025&market=gb";
    error_log("API=>");
    //error_log($prspy_api_url );
	// $header = array("Authorization: Bearer {$access_token}");
    // Add the Authorization and X-Client-ID headers
    $header = array(
        "Authorization: Bearer {$access_token}",
        "client-id: 40fd38daf4db4ebeb771acb2c97fa98d"
    );
	$curl = curl_init();
	curl_setopt_array($curl, array(
		CURLOPT_URL => $prspy_api_url,
		CURLOPT_HTTPHEADER => $header,
		CURLOPT_SSL_VERIFYPEER => false,
		CURLOPT_RETURNTRANSFER => true
	));
	$response = curl_exec($curl);
    //error_log(print_r($response, true));
	curl_close($curl);

	return json_decode($response, true);
}


// ===== PRICESPY PRODUCTS (SHORTCODE)  =====
add_shortcode( 'pricespy_products', 'sp_pricespy_products' );
function sp_pricespy_products($atts) {
    // Params extraction
    extract(
        shortcode_atts(
            array(
                'product_id' => ''
            ), 
            $atts
        )
    );
    
    ob_start();
    if(!empty($product_id)){
        $access_token = getAccessToken();
        //error_log("access token=>>");
        //error_log($access_token );
        $resource = getResource($access_token, $product_id);
        
        if(!empty($resource) && empty($resource['error'])){ ?>
            <div class="prspy-price-div">
                <table>
                    <thead>
                        <tr>
                            <th></th>
                            <th>Store</th>
                            <th>Product</th>
                            <!-- <th>Stock</th> -->
                            <th>Price</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
            <?php
            $i = 0;
            foreach ($resource['offers'] as $offer) {
                if($i < 10){
                    $i++;
                }else{
                    break;
                }
                ?>
                        <tr class="prspy-main-prices">
                            <td>
                                <span><a href="#!"><i class="prspy-arrow down"></i></a></span>
                            </td>
                            <td>
                                <div class="prspy-main-logo">
                                    <?php if(!empty($offer['shop']['logo']['small'])){ ?>
                                        <img src="<?=$offer['shop']['logo']['small']?>">
                                    <?php }else{
                                        echo '<span class="prspy-company-name">'.$offer['shop']['name'].'</span>';
                                    } ?>
                                    
                                </div>
                            </td>
                            <td>
                                <?=$resource['productName'] ?>
                            </td>
                            <td class="prspy-price">
                                <?=getCurrencySymbol($offer['price']['currency']).$offer['price']['value']?>
                                 <div class="prspy-price-delivery">
                                    <?=getDeliveryData($offer['price'])?>
                                </div>
                            </td>
                            <td class="prspy-btn">
                                <?php if(!empty($offer['url'])){ ?>
                                <a class="button" href="<?=$offer['url']?>" target="_blank">View in Shop</a>
                                <?php } ?>
                            </td>
                        </tr>
                        <?php 
                            if(!empty($offer['alternativePrices'])){
                                foreach ($offer['alternativePrices'] as $alt_price) { ?>
                                    <tr class="prspy-atv-prices">
                                        <td></td>
                                        <td colspan="2">
                                           <?=$alt_price['name']?>
                                        </td>
                                        <td class="prspy-price">
                                            <?=getCurrencySymbol($alt_price['price']['currency']).$alt_price['price']['value']?>
                                            <div class="prspy-price-delivery">
                                                <?=getDeliveryData($alt_price['price'])?>
                                            </div>
                                        </td>
                                        <td class="prspy-btn">
                                            <?php if(!empty($alt_price['url'])){ ?>
                                                <a class="button" href="<?=$alt_price['url']?>" target="_blank">View in Shop</a>
                                            <?php } ?>
                                        </td>
                                    </tr>
                                <?php }
                            }
                        ?>
            <?php } ?>
                    </tbody>
                </table>
            </div>
        <?php }else{ ?>
            <div class="prspy-error"><?= ($resource['error']) ? $resource['error']['message'] : 'Cannot fetch PriceSpy data!!' ?></div>
        <?php } 
    }else{ ?>
        <div class="prspy-error">Error!. No Product Id found.</div>
    <?php } 
    return ob_get_clean();
}


function getStockStatusText($stock) {
    $stock_status_text = '';
//    $img_stock_staus = plugins_url('', __FILE__).'/images/in-stock.png';
    if($stock['status_text'] == ''){
        if($stock['status'] == 'in_stock'){
            $stock_status_text = "In stock";
        }else if($stock['status'] == 'not_in_stock'){
            $stock_status_text = "Not in stock";
        }else if($stock['status'] == null){
            $stock_status_text = "Unknown stock";
        }
    }else{
        $stock_status_text = $stock['status_text'];
    }
	return $stock_status_text;
}

// function getStockStatusImage($stock) {
//     if($stock['status'] == 'in_stock'){
//         $img_stock_staus = plugins_url('', __FILE__).'/images/in-stock.png';
//     }else if($stock['status'] == 'not_in_stock'){
//         $img_stock_staus = plugins_url('', __FILE__).'/images/out-of-stock.png';
//     }else {
//         $img_stock_staus = plugins_url('', __FILE__).'/images/unknown-stock.png';
//     }
//     return $img_stock_staus;
// }

function getCurrencySymbol($currencyCode, $locale = 'en_US')
{
    $formatter = new \NumberFormatter($locale . '@currency=' . $currencyCode, \NumberFormatter::CURRENCY);
    return $formatter->getSymbol(\NumberFormatter::CURRENCY_SYMBOL);
}

function getDeliveryData($price_array) {
    // Initialize delivery data as 'Not Available' by default
    $delivery_data = '';

    // Check if 'includingShipping' exists and is not null
    if (isset($price_array['includingShipping']) && $price_array['includingShipping'] !== null) {
        // Check if 'value' exists and is equal to 'includingShipping'
        if (isset($price_array['value']) && $price_array['value'] == $price_array['includingShipping']) {
            $delivery_data = 'Free delivery';
        } else {
            // Use 'includingShipping' for delivery cost if different from 'value'
            $delivery_data = getCurrencySymbol($price_array['currency']) . $price_array['includingShipping'] . ' Incl. delivery';
        }
    }

    return $delivery_data;
}