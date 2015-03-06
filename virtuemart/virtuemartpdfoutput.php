<?php
/*
 * @package Joomla 1.5
 * @copyright Copyright (C) 2005 Open Source Matters. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 *
 * @component Phoca Plugin
 * @copyright Copyright (C) Jan Pavelka www.phoca.cz
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL  
 *  
 */
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.file');
require_once(JPATH_ROOT.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart'.DS.'virtuemarthelper.php');
require_once(JPATH_ROOT.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart'.DS.'tmpl.deliverynote.php');
require_once(JPATH_ROOT.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart'.DS.'tmpl.invoice.php');
require_once(JPATH_ROOT.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart'.DS.'tmpl.receipt.php');



class PhocaPDFVirtueMartPdfOutput {
	
	/*
	 * Outupt Items
	 */
	public function getOutput($staticData = array()) {
		
		
		$d			= JRequest::get('request');
		$plugin 	=&JPluginHelper::getPlugin('phocapdf', 'virtuemart');
	 	$pluginP 	= new JParameter( $plugin->params );
		$lang 		=& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		// Document is not rendered in browser, we need to get static data not set by request but by function
		if(isset($staticData['order_id']) && (int)$staticData['order_id'] > 0) {
			$d['order_id']	= (int)$staticData['order_id'];
		}
		if(isset($staticData['delivery_id']) && (int)$staticData['delivery_id'] > 0) {
			$d['delivery_id']	= (int)$staticData['delivery_id'];
		}
		if (isset($staticData['type']) && $staticData['type'] != '') {
			$d['type']	= $staticData['type'];
		}
		
		// Currency Info
		$dbv	= PhocaPDFVirtueMartHelper::dbQ('dbv');
		$var	= array('order_id' => $d['order_id']);
		$dbo	= PhocaPDFVirtueMartHelper::dbQ('dbo', $var);
		$c		= $dbo->order_currency;
		$user_id = $dbo->user_id;
		
		//if	(!empty($staticData)) {
			$CURRENCY_DISPLAY = PhocaPDFVirtueMartHelper::getCurrencyDisplayStyle($dbv->vendor_currency_display_style, $dbo->order_currency);
		//} else {
		//	global 	$CURRENCY_DISPLAY;
		//}
	
		$check_items = array( "Delivery ID" => "delivery_id", "Order ID" => "order_id" );
		if( !PhocaPDFVirtueMartPdfOutput::exists_and_is_numeric( $check_items, $d ) ) { return '<p>Error - Wrong delivery id or order id parameters</p>';}
		
		// Protecting displaying of documents - only users owned the documents can see them or the admins
		$userCurrent = &JFactory::getUser();
		$current_user_id 	= $userCurrent->get('id');
		$current_user_aid 	= $userCurrent->get('aid');
		if ($user_id == $current_user_id || (int)$current_user_aid >= 2) {
		
		} else {
			echo '<p>You are not allowed to see this document.</p>';
			exit;
		}

		$var	= array('order_id' => $d['order_id'], 'delivery_id' => $d['delivery_id']);
		$dbob	= PhocaPDFVirtueMartHelper::dbQ('dbob', $var);
		
		if(isset($dbob->obliterated)) {
			$a['obliterated'] = $dbob->obliterated;
		} else {
			$a['obliterated'] = 0;
		}
		
		/*
		$var	= array('order_id' => $dbo->order_id);
		$dbp	= PhocaPDFVirtueMartHelper::dbQ('dbp', $var);
		$a['payment_method']	= $dbp->payment_method_name;
		*/

		$var	= array('delivery_id' => $d['delivery_id']);
		$dbb	= PhocaPDFVirtueMartHelper::dbQ('dbb', $var);

		if($dbb->is_invoice == '1' && $d['type'] == 'invoice') {
			$type 		= 'invoice';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_INVOICE');
		} else if($dbb->is_invoice == '0' && $d['type'] == 'receipt') {
			$type 		= 'receipt';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_RECEIPT');
		} else {
			$type 		= 'deliverynote';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_DELNOTE');
		}
		
		
		// - - - - - - - - - - -
		// RENDER
		// - - - - - - - - - - - 
		// Rows
		$without_tax 				= array();
		$tax 						= array();
		$with_tax 					= array();
		$without_tax['item_total'] 	= 0;
		$with_tax['item_total']		= 0;
		$tax['item_total']			= 0;

		$var	= array('order_id' => $d['order_id'], 'delivery_id' => $d['delivery_id']);
		$dbi	= PhocaPDFVirtueMartHelper::dbQ('dbi', $var, '', 'objectlist');
		
		$i 	= array();
		$j	= 0;
		foreach ($dbi as $key => $value) {
			
			$var	= array('order_item_id' => $value->order_item_id);
			$dbv2	= PhocaPDFVirtueMartHelper::dbQ('dbv2', $var);

			$remaining = (int)$value->product_quantity - (int)$dbv2->delivered;

			$price_with_tax 	= array();
			$price_without_tax 	= array();
			$price_tax 			= array();
			//$displayed_price 	= array();
			
			if($type == 'invoice' || $type == 'receipt') {

				$price_with_tax['item']		= $value->product_final_price;
				$price_with_tax['sum'] 		= $value->product_final_price * $value->product_quantity_delivered;
				$price_without_tax['item'] 	= $value->product_item_price;
				$price_without_tax['sum'] 	= $value->product_item_price * $value->product_quantity_delivered;
				$price_tax['item'] 			= $price_with_tax['item'] - $price_without_tax['item'];
				$price_tax['sum'] 			= $price_with_tax['sum'] - $price_without_tax['sum'];

				$without_tax['item_total'] 	+= $price_without_tax['sum'];
				$with_tax['item_total'] 	+= $price_with_tax['sum'];
				$tax['item_total'] 			+= $price_tax['sum'];

				/*if( isset($auth["show_price_including_tax"]) ) {
					$displayed_price = $price_with_tax;
				} else {
					$displayed_price = $price_without_tax;
				}*/
			} 
			
			// SKU
			$i[$j]['order_item_sku'] 	= $value->order_item_sku;
			$i[$j]['order_item_name'] 	= $value->order_item_name;
			$i[$j]['product_quantity'] 	= $value->product_quantity;
			$i[$j]['product_quantity_delivered'] 	= $value->product_quantity_delivered;
			if($type == 'invoice' || $type == 'receipt') {
				$i[$j]['price_without_tax']['item']	= $CURRENCY_DISPLAY->getFullValue($price_without_tax['item'], '', $c);
				$i[$j]['price_without_tax']['sum']	= $CURRENCY_DISPLAY->getFullValue($price_without_tax['sum'], '', $c);
				$i[$j]['price_tax']['sum']			= $CURRENCY_DISPLAY->getFullValue($price_tax['sum'], '', $c);
				$i[$j]['price_with_tax']['sum']		= $CURRENCY_DISPLAY->getFullValue($price_with_tax['sum'], '', $c);
			} else {
				//$i[$j]['product_quantity_delivered'] 	= $value->product_quantity_delivered;
				$i[$j]['remaining'] 					= $remaining;
			}
			
			$attribute = $value->product_attribute;
			if(!empty($attribute)) {
				$i[$j]['product_attribute'] = $value->product_attribute;
			} else {
				$i[$j]['product_attribute'] = '';
			}
			
			$j++;
		} //end foreach products
		

		// Sumarize
		if($type == 'invoice' || $type == 'receipt') {

			$var	= array('order_id' => $d['order_id']);
			$dbmd	= PhocaPDFVirtueMartHelper::dbQ('dbmd', $var);

			// Add shipping costs to the fist delivery of the order
			if($dbmd->delivery_id == $d['delivery_id']) {
				$without_tax['shipping'] 	= $dbo->order_shipping;
				$tax['shipping'] 			= $dbo->order_shipping_tax;
				$with_tax['shipping'] 		= ($dbo->order_shipping + $dbo->order_shipping_tax);
				if($dbo->order_discount < 0) {
					$order_fee = abs($dbo->order_discount);
				} else {
					$order_fee = 0;
				}
			} else {
				$without_tax['shipping'] 	= 0;
				$tax['shipping'] 			= 0;
				$with_tax['shipping'] 		= 0;
				$order_fee 					= 0; 
			}

			// find out what procentage of the order this delivery is
			$var	= array('order_id' => $d['order_id']);
			$dbsd	= PhocaPDFVirtueMartHelper::dbQ('dbsd', $var);
			
			$order_value_procentage = $without_tax['item_total'] / $dbsd->order_subtotal;

			$order_discount = 0;
			if($dbo->order_discount > 0) {
				$order_discount = $dbo->order_discount * $order_value_procentage;
			}
			$coupon_discount = 0;
			if($dbo->coupon_discount > 0) {
				$coupon_discount = $dbo->coupon_discount * $order_value_procentage;
			}

			$without_tax['final_price'] = $without_tax['item_total'] - $coupon_discount - $order_discount + $order_fee;
			$tax_rate 					= abs($without_tax['item_total']/$with_tax['item_total']-1);
			$tax['final_price'] 		= ($with_tax['item_total'] - $order_discount - $coupon_discount + $order_fee) * $tax_rate;
			
			$tax['final_price_without_shipping']	= $tax['item_total'];
			$tax['final_price'] 					+= $tax['shipping'];
			$with_tax['final_price'] 				= $with_tax['item_total'] - $coupon_discount - $order_discount + $order_fee;;

			if($without_tax['final_price'] < 0) {$without_tax['final_price'] = 0;}
			if($with_tax['final_price'] < 0) 	{$with_tax['final_price'] = 0;}

			$without_tax['final_price'] 		+= $without_tax['shipping'];
			$with_tax['final_price'] 			+= $with_tax['shipping'];

			if($dbb->paid) {
				$without_tax['to_pay'] 	= 0;
				$with_tax['to_pay'] 	= 0;
			} else {
				$without_tax['to_pay'] 	= $without_tax['final_price'];
				$with_tax['to_pay'] 	= $with_tax['final_price'];
			}

			$a['item_total']					= $CURRENCY_DISPLAY->getFullValue($without_tax['item_total'], '', $c);
			$a['final_price_without_shipping']	= $CURRENCY_DISPLAY->getFullValue($tax['final_price_without_shipping'], '', $c);
			
			$a['with_tax']['shipping']			= $with_tax['shipping'];
			if($with_tax['shipping'] > 0) {
				$a['without_tax']['shipping']	= $CURRENCY_DISPLAY->getFullValue($without_tax['shipping'], '', $c);
				$a['tax']['shipping']			= $CURRENCY_DISPLAY->getFullValue($tax['shipping'], '', $c);
			}
			
			$a['coupon_discount']		= $coupon_discount;
			if($coupon_discount > 0) {
				$a['coupon_discount']	= $CURRENCY_DISPLAY->getFullValue($coupon_discount, '', $c);
			}
			
			$a['order_discount']		= $order_discount;
			if($order_discount > 0) {
				$a['order_discount']	= $CURRENCY_DISPLAY->getFullValue($order_discount, '', $c);
			}
			
			$a['order_fee']				= $order_fee;
			if($order_fee > 0) {
				$a['order_fee']			= $CURRENCY_DISPLAY->getFullValue($order_fee, '', $c);
			}
			
			$a['with_tax']['final_price']	= $CURRENCY_DISPLAY->getFullValue($with_tax['final_price'], '', $c);
			$a['with_tax']['to_pay']		= $CURRENCY_DISPLAY->getFullValue($with_tax['to_pay'], '', $c);

		}
		
		$a['css']						= $pluginP->get('css_site', '.tableitems{font-size:small;}
.topay{background-color: #f0f0f0;}
.productattribute{font-size:xx-small;}
.obliterated{font-size:30pt; font-weight:bold; color:#777777; background-color: #ffc835;}');
			
		if($dbb->is_invoice) {
		} else {
			$a['bill_id']				= sprintf("%08d", $dbb->bill_id);
		}
	
		if ($type == 'invoice') {
			$output = PhocaPDFVirtueMartInvoice::getOutput($a, $i);
		} else if ($type == 'receipt') {
			$output = PhocaPDFVirtueMartReceipt::getOutput($a, $i);
		} else {
			$output = PhocaPDFVirtueMartDeliveryNote::getOutput($a, $i);
		}
		
		$outputArray['output'] = $output;
		
		// PDF Document
		$outputArray['title'] 		= $typeLabel;
		$outputArray['subject']		= $typeLabel;
		$outputArray['keywords']	= $typeLabel;	

		return $outputArray;
	}
	
	/*
	 * Outupt Header
	 */
	
	public function getOutputHeader($pluginParams, $staticData = array()) {
		
		$lang 	=& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		$d		= JRequest::get('request');
		//Document is not rendered in browser, we need to get static data not set by request but by function
		if(isset($staticData['order_id']) && (int)$staticData['order_id'] > 0) {
			$d['order_id']	= (int)$staticData['order_id'];
		}
		if(isset($staticData['delivery_id']) && (int)$staticData['delivery_id'] > 0) {
			$d['delivery_id']	= (int)$staticData['delivery_id'];
		}
		if (isset($staticData['type']) && $staticData['type'] != '') {
			$d['type']	= $staticData['type'];
		}

		$check_items = array( "Delivery ID" => "delivery_id", "Order ID" => "order_id" );
		if( !PhocaPDFVirtueMartPdfOutput::exists_and_is_numeric( $check_items, $d ) ) { return '<p>Error - Wrong delivery id or order id parameters</p>';}		

		$dbv	= PhocaPDFVirtueMartHelper::dbQ('dbv');
		$var	= array('order_id' => $d['order_id']);
		$dbo	= PhocaPDFVirtueMartHelper::dbQ('dbo', $var);
		$user_id = $dbo->user_id;

		// Get names of extra fields
		$var	= array('extra_field' => 'extra_field_1');
		$dbuf1	= PhocaPDFVirtueMartHelper::dbQ('dbuf', $var);
		$var	= array('extra_field' => 'extra_field_2');;
		$dbuf2	= PhocaPDFVirtueMartHelper::dbQ('dbuf', $var);
		
		$var	= array('delivery_id' => $d['delivery_id']);
		$dbb	= PhocaPDFVirtueMartHelper::dbQ('dbb', $var);

		// Billing adress check state
		$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => 'BT');
		$dbstate= PhocaPDFVirtueMartHelper::dbQ('dbstate', $var);
		if ($dbstate->state == " - " OR $dbstate->state == "-" OR $dbstate->state == "") {
			// Billing adress without state;
			$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => 'BT');
			$dbbt	= PhocaPDFVirtueMartHelper::dbQ('dbt1', $var);					
		} else {
			//Billing adress with state
			$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => 'BT');
			$dbbt	= PhocaPDFVirtueMartHelper::dbQ('dbt2', $var);			
		}	   

		//Shipment address check addres type st
		$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => 'ST');
		$dbstateo= PhocaPDFVirtueMartHelper::dbQ('dbstateo', $var);
		
		if ((isset($dbstateo->order_info_id) && $dbstateo->order_info_id == "" ) || !isset($dbstateo->order_info_id))  {$chckst = "BT";} else {$chckst = "ST";}
		
		//Shipment address check state
		$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => $chckst);
		$dbstate= PhocaPDFVirtueMartHelper::dbQ('dbstate', $var);
		if ($dbstate->state == " - " OR $dbstate->state == "-" OR $dbstate->state == "") {
			// Shipment adress without state;			
			$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => $chckst);
			$dbst	= PhocaPDFVirtueMartHelper::dbQ('dbt1', $var);
	
		} else {
			// Shipment adress with state
			$var	= array('user_id' => $user_id, 'order_id' => $d['order_id'], 'address_type' => $chckst);
			$dbst	= PhocaPDFVirtueMartHelper::dbQ('dbt2', $var);
				
		}
		
		if($dbb->is_invoice == '1' && $d['type'] == 'invoice') {
			$type 		= 'invoice';
			$a['title']	= JText::_('PLG_PHOCAPDF_VM_INVOICE');
		} else if($dbb->is_invoice == '0' && $d['type'] == 'receipt') {
			$type 		= 'receipt';
			$a['title']	= JText::_('PLG_PHOCAPDF_VM_RECEIPT');
		} else {
			$type 		= 'deliverynote';
			$a['title']	= JText::_('PLG_PHOCAPDF_VM_DELNOTE');
		}
		
		// - - - - - - - - - - -
		// RENDER
		// - - - - - - - - - - -
	
		//$a['logo'] 	= JPATH_ROOT . DS . 'components' . DS . 'com_virtuemart' . DS
		//			  . 'shop_image' . DS . 'vendor' . DS . $dbv->vendor_full_image;
		
		$a['logo']	=  'components' . DS . 'com_virtuemart' . DS
					  . 'shop_image' . DS . 'vendor' . DS . $dbv->vendor_full_image;
 
		$details 	= explode( "|", $dbo->ship_method_id );

		
		$a['stcountry']	= '';
		if ($dbv->vendor_country != $dbst->country) {$a['stcountry']	= $dbst->country;}
		
		$a['btcountry']	= '';
		if($dbv->vendor_country != $dbbt->country) {$a['btcountry']	= $dbbt->country;}


		$a['bill_id']		= sprintf("%08d", $dbb->bill_id);
		$a['order_id']		= sprintf("%08d",$dbo->order_id);
		$a['delivery_id']	= sprintf("%08d",$d['delivery_id']);
		$a['bill_date']		= $dbb->bill_date;
		$a['order_date']	= $dbo->order_date;
		$a['bill_due']		= $dbb->bill_due;
		
		$a['s_company']		= $dbst->company;//IF
		$a['s_first_name']	= $dbst->first_name;
		$a['s_middle_name']	= ' '.$dbst->middle_name;
		$a['s_last_name']	= ' '.$dbst->last_name;
		$a['s_address_1']	= $dbst->address_1;
		$a['s_address_2']	= $dbst->address_2;//IF
		$a['s_zip']			= $dbst->zip;
		$a['s_city']		= $dbst->city;
		$a['s_state']		= $dbst->state;//IF
		
		$a['b_company']		= $dbbt->company;//IF
		$a['b_first_name']	= $dbbt->first_name;
		$a['b_middle_name']	= ' '.$dbbt->middle_name;
		$a['b_last_name']	= ' '.$dbbt->last_name;
		$a['b_address_1']	= $dbbt->address_1;
		$a['b_address_2']	= $dbbt->address_2;//IF
		$a['b_zip']			= $dbbt->zip;
		$a['b_city']		= $dbbt->city;
		$a['b_state']		= $dbbt->state;//IF
		
		// Maybe extra_field will be vm_extra_field in next versions of VM
		$a['l_extra_field_1'] = '';
		if (isset($dbuf1->title)) {$a['l_extra_field_1']	= $dbuf1->title;}
		$a['l_extra_field_2'] = '';
		if (isset($dbuf2->title)) {$a['l_extra_field_2']	= $dbuf2->title;}
		
		$a['extra_field_1']		= $dbbt->extra_field_1;
		$a['extra_field_2']		= $dbbt->extra_field_2;
		
		if ($a['l_extra_field_1'] == '') {
			$a['l_extra_field_1'] = JText::_('PLG_PHOCAPDF_VM_FORM_EXTRA_FIELD_1');
		} else {
			$a['l_extra_field_1'] = JText::_($a['l_extra_field_1']);
		}
		if ($a['l_extra_field_2'] == '') {
			$a['l_extra_field_2'] = JText::_('PLG_PHOCAPDF_VM_FORM_EXTRA_FIELD_2');
		} else {
			$a['l_extra_field_2'] = JText::_($a['l_extra_field_2']);
		}
		
		$a['details_1'] = '';
		if (isset($details[1])) {$a['details_1'] = $details[1];}
		$a['details_2'] = '';
		if (isset($details[2])) {$a['details_2'] = $details[2];}
		$a['due_date']			= $dbb->due_date;
		if ($dbb->paid == 0) {
			$a['delay_interest']	= $dbb->delay_interest . '% ';
		} else {
			$a['delay_interest']	= '-';
		}
		
		$a['delay_interest']	= $dbb->delay_interest;

		
		$a['css']				= $pluginParams->get('css_header', '.tableitemstable {}
.tableitemshead { font-size:6pt; border: 2px solid #ffffff; background-color: #f0f0f0;}
.documenttitle {font-size:xx-large;background-color: #f0f0f0;}
.documenthead{font-size:x-small;}
.shipmentaddress{background-color: #f0f0f0;}
.billingaddress{background-color: #f0f0f0;} 
.shipmentaddresshead{font-size:x-small;}
.billingaddresshead{font-size:x-small;}
.extrafield{font-size:small;}
.terms{font-size:small;}
.vendorfooter{background-color: #f0f0f0;}');

		
		if ($type == 'invoice') {
			$output = PhocaPDFVirtueMartInvoice::getOutputHeader($a);
		} else if ($type == 'receipt') {
			$output = PhocaPDFVirtueMartReceipt::getOutputHeader($a);
		} else {
			$output = PhocaPDFVirtueMartDeliveryNote::getOutputHeader($a);
		}
		return $output;
	
	}
	
	/*
	 * Outupt Footer
	 */
	
	public function getOutputFooter($pluginParams, $staticData = array()) {
		
		$lang 	=& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		$d		= JRequest::get('request');
		//Document is not rendered in browser, we need to get static data not set by request but by function
		if(isset($staticData['order_id']) && (int)$staticData['order_id'] > 0) {
			$d['order_id']	= (int)$staticData['order_id'];
		}
		if(isset($staticData['delivery_id']) && (int)$staticData['delivery_id'] > 0) {
			$d['delivery_id']	= (int)$staticData['delivery_id'];
		}
		if (isset($staticData['type']) && $staticData['type'] != '') {
			$d['type']	= $staticData['type'];
		}
		
		$var	= array('delivery_id' => $d['delivery_id']);
		$dbb	= PhocaPDFVirtueMartHelper::dbQ('dbb', $var);

		if($dbb->is_invoice == '1' && $d['type'] == 'invoice') {
			$type 		= 'invoice';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_INVOICE');
		} else if($dbb->is_invoice == '0' && $d['type'] == 'receipt') {
			$type 		= 'receipt';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_RECEIPT');
		} else {
			$type 		= 'deliverynote';
			$typeLabel	= JText::_('PLG_PHOCAPDF_VM_DELNOTE');
		}
		
		$dbv	= PhocaPDFVirtueMartHelper::dbQ('dbv');
		
		$a['vcountry']	= '';
		if($dbv->vendor_country) {$a['vcountry']= $dbv->vendor_country;}
		$a['vstate']	= '';
		if($dbv->vendor_state) {$a['vstate']	= $dbv->vendor_state;}		
		
		$a['vendor_store_name']	= $dbv->vendor_store_name;
		$a['contact_phone_1']	= $dbv->contact_phone_1;
		$a['vendor_address_1']	= $dbv->vendor_address_1;
		$a['contact_email']		= $dbv->contact_email;
		$a['vendor_zip']		= $dbv->vendor_zip;
		$a['vendor_city']		= $dbv->vendor_city;
		$a['internet']			= JURI::root();
		
		$a['css']				= $pluginParams->get('css_footer', '.vendorfooter {background-color: #f0f0f0;}');
		
		if ($type == 'invoice') {
			$output = PhocaPDFVirtueMartInvoice::getOutputFooter($a);
		} else if ($type == 'receipt') {
			$output = PhocaPDFVirtueMartReceipt::getOutputFooter($a);
		} else {
			$output = PhocaPDFVirtueMartDeliveryNote::getOutputFooter($a);
		}
		return $output;
	
	}
	
	/**************************************************************************
	 * name: exists_and_is_numeric
	 * created by: ingemar
	 * description: Checks an array of items if they exists and is numeric
	 * parameters: Array of names and keys, Data
	 * returns:
	 **************************************************************************/
	function exists_and_is_numeric( $check, &$d ) {
		foreach( $check as $key => $value ) {
			if( isset( $d[$value] ) && !is_numeric( $d[$value] ) ) {
				$d["error"] = 'The '.$key.' '.$d[$value].' is not valid.';
				return False;
			}
			elseif( !isset( $d[$value] ) ) {
				$d["error"] = 'Missing '.$key;
				return False;
			}
		}
		return True;
	}
}
?>
