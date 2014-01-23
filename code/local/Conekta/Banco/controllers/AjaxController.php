<?php
class Conekta_Banco_AjaxController extends Mage_Core_Controller_Front_Action {

    public function indexAction() {
		$key=Mage::getStoreConfig('payment/banco/apikey');
		$quote = Mage::getSingleton('checkout/cart')->getQuote();
		$currency=Mage::getStoreConfig('payment/banco/currency');
		$grandTotal = $quote->getGrandTotal();
		$exploded_val=explode(".",$grandTotal);
		$exploded_val=$exploded_val['0']*100;
		require(dirname(__FILE__) . '/../../conekta-php/lib/Conekta.php');
		Conekta::setApiKey($key);
		$s_info = Mage::getSingleton('checkout/session')->getQuote()->getShippingAddress()->getData();
		$b_info = Mage::getSingleton('checkout/session')->getQuote()->getBillingAddress()->getData();
		$p_info = Mage::getSingleton('checkout/session')->getQuote()->getItemsCollection();
		$n_items = count($p_info->getColumnValues('sku'));
		$line_items = array();
		for ($i = 0; $i < $n_items; $i ++) {
			$name = $p_info->getColumnValues('name');
			$name = $name[$i];
			$sku = $p_info->getColumnValues('sku');
			$sku = $sku[$i];
			$price = $p_info->getColumnValues('price');
			$price = $price[$i];
			$description = $p_info->getColumnValues('description');
			$description = $description[$i];
			$product_type = $p_info->getColumnValues('product_type');
			$product_type = $product_type[$i];
			$line_items = array_merge($line_items, array(array(
				"name"=>$name,
				"sku"=>$sku,
				"unit_price"=> $price,
				"description"=>$description,
				"quantity"=> 1,
				"type"=>$product_type
			  ))
			);
		}
		$shipment = array();
		if ($s_info['grand_total'] > 0) {
			$shipment = array(
			  "carrier"=>"estafeta",
			  "service"=>"international",
			  "tracking_id"=>"XXYYZZ-9990000",
			  "price"=> $s_info['grand_total'],
			  "address"=> array(
				"street1"=>"250 Alexis St",
				"city"=>$s_info['city'],
				"state"=>$s_info['region'],
				"country"=>$s_info['country_id'],
				"zip"=>$s_info['postcode'],
			  )
			);
		}
		try {
			$charge = Conekta_Charge::create(array(
			  "description"=>"Compra en Magento de " . $b_info['email'],
			  "amount"=> $exploded_val,
			  "currency"=> $currency,
			  "bank"=>array(
				"type"=>"banorte"
			  ),
			  "details"=> array(
				"name"=> preg_replace('!\s+!', ' ', $b_info['firstname'] . ' ' . $b_info['middlename'] . ' ' . $b_info['firstname']),
				"email"=> $b_info['email'],
				"phone"=> $b_info['telephone'],
				"billing_address"=> array(
				  "company_name"=> $b_info['company'],
				  "street1"=> $b_info['street'],
				  "city"=>$b_info['city'],
				  "state"=>$b_info['region'],
				  "country"=>$b_info['country_id'],
				  "zip"=>$b_info['postcode'],
				  "phone"=>$b_info['telephone'],
				  "email"=>$b_info['email']
				),
				"line_items"=> $line_items
				),
				"shipment"=> $shipment
			  )
			);
			echo '{ "banco":"' . $charge->payment_method->type . '", "numero_servicio":"' . $charge->payment_method->service_number . '", "nombre_servicio":"' . $charge->payment_method->service_name . '", "referencia":"' . $charge->payment_method->reference . '" }';
		} catch (Conekta_Error $e) {
			echo '{"error":"' . $e->getMessage() . '"}';
		}
    }
}
?>
