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

class PhocaPDFVirtueMartDeliveryNote {
	
	/*
	 * $i ... item (product)
	 * $a ... all other items
	 */
	
	public function getOutput($a, $items) {
		
		$lang =& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		$o = '<style>'.$a['css'].'</style>'
.'<table class="tableitemstable" cellspacing="2" cellpadding="2">';

if ($a['obliterated'] == 1) {
	$o .= '<tr><td colspan="5" align="center" class="obliterated" >'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_OBLITERATED_LBL').'</td></tr>';
}

		foreach ($items as $key => $i) {

			$o .= ''
.'<tr class="tableitems">'
.'<td align="left" width="20%">'.$i['order_item_sku'].'</td>'
.'<td align="left" width="40%">'.$i['order_item_name'];
if ($i['product_attribute'] != '') {
	$o .= '<br /><span class="productattribute">'.$i['product_attribute'].'</span>';
}
$o .='</td>'
.'<td align="center" width="10%">'.$i['product_quantity'].'</td>'
.'<td align="right" width="15%">'.$i['product_quantity_delivered'].'</td>'
.'<td align="right" width="15%">'.$i['remaining'].'</td>'
.'</tr>';

		}
$o .= '</table>';
		
		return $o;
	
	}
	
	
	public function getOutputHeader($a) {
	
		$lang =& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		$o = '<style>'.$a['css'].'</style>';		
		
		$o .= '<p></p>';// Added because of tcpdf bug in 19490 undefined offset
		$o .= ''
.'<table>'
.'<tr width="100%">'
.'<td align="left" width="45%">'
  .'<div><img src="'.$a['logo'].'" alt="" /></div>'
.'</td>'
.'<td width="5%"></td>'
.'<td width="5%"></td>'
.'<td align="center" width="45%">'
  .'<div class="documenttitle">'.$a['title'].'</div>'
  
  .'<table>'
   .'<tr>'
    .'<td class="documenthead">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_ORDER_DATE_LBL').'</td>'
	.'<td class="documenthead">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_ORDER_NUMBER_LBL').'</td>'
	.'<td class="documenthead">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_DELNOTE_NUMBER_LBL').'</td>'
   .'</tr>'
   .'<tr>'
    .'<td>'.$a['order_date'].'</td>'
	.'<td>'.$a['order_id'].'</td>'
	.'<td>'.$a['delivery_id'].'</td>'
   .'</tr>'
   
   .'<tr>'
    .'<td class="documenthead"></td>'
	.'<td class="documenthead"></td>'
	.'<td class="documenthead"></td>'
   .'</tr>'
   .'<tr>'
    .'<td></td>'
	.'<td></td>'
	.'<td></td>'
   .'</tr>'
  .'</table>'
  
.'</td>'
.'</tr>'
.'</table>'

.'<div></div>'

.'<table cellpadding="5">'
.'<tr width="100%">'
.'<td align="left" width="45%" class="shipmentaddress">'

   .'<span class="shipmentaddresshead">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_SHIP_TO_LBL').'</span><br />';
	if ($a['s_company'] != '') {$o .= $a['s_company'].'<br />';}
	$o .= $a['s_first_name'].$a['s_middle_name'] .$a['s_last_name']. '<br />'	   
	.$a['s_address_1']. '<br />';   
	if ($a['s_address_2'] != '') {$o .= $a['s_address_2'].'<br />';}
	$o .= $a['s_zip'] .' '. $a['s_city'] . '<br />';
	if ($a['s_state'] != '') {$o .= $a['s_state'].'<br />';}
	$o .= $a['stcountry']

.'</td>'
.'<td width="5%"></td>'
.'<td width="5%"></td>'
.'<td align="left" width="45%" class="billingaddress">'
   .'<span class="billingaddresshead">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_BILL_TO_LBL').'</span><br />';
	if ($a['b_company'] != '') {$o .= $a['b_company'].'<br />';}
	$o .= $a['b_first_name'].$a['b_middle_name'] .$a['b_last_name']. '<br />';
	if ($a['extra_field_1'] != '') {
		$o .= '<span class="extrafield">'.$a['l_extra_field_1'] . ': '.$a['extra_field_1']. '</span><br />';
	}
	if ($a['extra_field_2'] != '') {
		$o .= '<span class="extrafield">'.$a['l_extra_field_2'] . ': '.$a['extra_field_2']. '</span><br />';
	}
	$o .= $a['b_address_1']. '<br />';   
	if ($a['b_address_2'] != '') {$o .= $a['b_address_2'].'<br />';}
	$o .= $a['b_zip'] .' '. $a['b_city'] . '<br />';
	if ($a['b_state'] != '') {$o .= $a['b_state'].'<br />';}
	$o .= $a['btcountry']
.'</td>'
.'</tr>'
.'</table>'

.'<div></div>'

.'<table class="terms">'
.'<tr width="100%">'
 .'<td width="20%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_SHIPPING_CARRIER_LBL').': </td><td width="35%">'.$a['details_1'].'</td>'
 .'<td width="20%"></td><td width="25%"></td>'
.'</tr>'

.'<tr width="100%">'
 .'<td width="20%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_SHIPPING_MODE_LBL').': </td><td width="35%">'.$a['details_2'].'</td>'
 .'<td width="20%"></td><td width="25%"></td>'
.'</tr>'
.'</table>';

$o .= '<table class="tableitemstable" cellspacing="2" cellpadding="2">'
.'<tr class="tableitemshead" width="100%">'
.'<td align="left" width="20%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_SKU_LBL').'</td>'
.'<td align="left" width="40%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_PRODUCT_LBL').'</td>'
.'<td align="center" width="10%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_QUANTITY_LBL').'</td>'
.'<td align="center" width="15%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_DELIVERED_LBL').'</td>'
.'<td align="center" width="15%">'.JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_REMAINING_LBL').'</td>'
.'</tr></table>';
		
		return $o;
	
	}
	
	public function getOutputFooter($a) {
	
		$lang =& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
		
		$o = '<style>'.$a['css'].'</style>';
		$o .= ''
.'<table class="vendorfooter" cellpadding="5">'
.'<tr width="100%" >'
 .'<td width="50%">'
 . $a['vendor_store_name'] . '<br />'
 . $a['vendor_address_1'] . '<br />'
 . $a['vendor_zip'] . ' ' . $a['vendor_city']
 .'</td>'
 .'<td width="50%">'
 . JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_VENDOR_PHONE_LBL') .': '. $a['contact_phone_1'] . '<br />'
 . JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_VENDOR_EMAIL_LBL') .': '. $a['contact_email'] . '<br />'
 . JText::_('PLG_PHOCAPDF_VM_DELIVERY_PRINT_VENDOR_URL_LBL') .': '. $a['internet']
 .'</td>'
.'</tr>'
.'</table>';
		return $o;
	}
}
?>
