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


if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdfbrowser.php')) {
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdfbrowser.php');
} else {
	return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF Helper Browser failed');
}

if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'virtuemart.cfg.php')) {
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'virtuemart.cfg.php');
} else {
	return JError::raiseError('Error', 'VirtueMart Configuration file could not be found in system');
}

if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'currency'.DS.'class_currency_display.php')) {
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'currency'.DS.'class_currency_display.php');
} else {
	return JError::raiseError('Error', 'VirtueMart Class Currency Display file could not be found in system');
}



class PhocaPDFVirtueMartHelper {
	
	function renderPDFIcon($session, $url, $type, $page, $function, $orderId, $deliveryId){
	
		$lang 			=& JFactory::getLanguage();
		$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
	
	
		$browser 	= PhocaPDFHelperBrowser::browserDetection('browser');
		/*$href		= 'index.php?option=com_virtuemart&page='.$page.'&func='.$function
		.'&format=phocapdf&tmpl=component'
		.'&type='.$type.'&order_id='.(int)$orderId.'&delivery_id='.(int)$deliveryId;*/
		
		// Call abstract view to not load all virtuemart content and save memory
		$href		= 'index.php?option=com_phocapdf&view=pdf'
		.'&format=phocapdf&tmpl=component'
		.'&type='.$type.'&order_id='.(int)$orderId.'&delivery_id='.(int)$deliveryId;
		
		if ($browser == 'msie7' || $browser == 'msie8') {

			$onClick= '';
			$target	= 'target="_blank"';

		} else {
			
			$onClick= 'onclick="window.open(this.href,\'win2\',\'status=no,'
			.'toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,'
			.'width=640,height=480,directories=no,location=no\'); return false;"';
			$target	= '';
		}
		
		/*$link = '<a href="'. $session->url( $url . $href).'" '.$onClick.' '.$target.' title="'.JText::_('PLG_PHOCAPDF_VM_PDF').'" >'
		. JHtml::_('image', 'administrator/components/com_phocapdf/assets/images/icon-16-pdf.png', JText::_('PLG_PHOCAPDF_VM_PDF'))
		. '</a>';*/
		if(isset($_SESSION['ps_vendor_id'])) {
			$session 	=& JFactory::getSession();
			$session->set('ps_vendor_id', $_SESSION['ps_vendor_id']);
		}
		$link = '<a href="'. $href.'" '.$onClick.' '.$target.' title="'.JText::_('PLG_PHOCAPDF_VM_PDF').'" >'
		. JHtml::_('image', 'administrator/components/com_phocapdf/assets/images/icon-16-pdf.png', JText::_('PLG_PHOCAPDF_VM_PDF'))
		. '</a>';
		
		return $link;
	
	}
	
	function renderEmailIcon( $type, $orderId, $deliveryId){
	
		$link = '';
		return $link;
	
	}
	
	function renderPDFIconAccount($sess, $url, $order_id) {
		
		$var	= array('order_id' => $order_id);
		$dbd	= PhocaPDFVirtueMartHelper::dbQ('dbd', $var, '', 'objectlist');
		
		$linkPDFReceipt = $linkPDFInvoice = $linkPDFDeliveryNote = array();
		
		if (!empty($dbd)) {
			foreach ($dbd as $key => $value) {
				if (isset($value->delivery_id)) {
					$linkPDFDeliveryNote[] = PhocaPDFVirtueMartHelper::renderPDFIcon($sess, URL, 'deliverynote', 'order.order_print', 'createPDFDelivery', $order_id, $value->delivery_id);
				
					if ($value->is_invoice == '1') {
						$linkPDFInvoice[] = PhocaPDFVirtueMartHelper::renderPDFIcon($sess, URL, 'invoice', 'order.order_print', 'createPDFDelivery', $order_id, $value->delivery_id);
					} else {
						$linkPDFReceipt[] = PhocaPDFVirtueMartHelper::renderPDFIcon($sess, URL, 'receipt', 'order.order_print', 'createPDFDelivery', $order_id, $value->delivery_id);
					}
				}
			}
		
		
			$h = $i	= '<tr align="center">';
			if (!empty($linkPDFReceipt)) {
				$h .= '<td>'.JText::_('PLG_PHOCAPDF_VM_RECEIPT').'</td>';
				$i .= '<td>'.implode($linkPDFReceipt, '&nbsp;').'</td>';
			}
			if (!empty($linkPDFInvoice)) {
				$h .= '<td>'.JText::_('PLG_PHOCAPDF_VM_INVOICE').'</td>';
				$i .= '<td>'.implode($linkPDFInvoice, '&nbsp;').'</td>';
			}
			if (!empty($linkPDFDeliveryNote)) {
				$h .= '<td>'.JText::_('PLG_PHOCAPDF_VM_DELNOTE').'</td>';
				$i .= '<td>'.implode($linkPDFDeliveryNote, '&nbsp;').'</td>';
			}
			$h .= '</tr>';
			$i .= '</tr>';
			
			
			return '<table>'.$h.$i.'</table>';
		} else {
			return '';
		}
	}
	
	function createDeliveryAndPDFandSendEmail($vmLogger, $VM_LANG, $classPath) {
		return true; // not used (true or false)
	}
	
	function translateDb($sql) {
		return str_replace('{vm}', VM_TABLEPREFIX, $sql);
	}
	
	function dbQ ($type, $var = array(), $query = '', $result = 'object') {
		
		switch($type) {
			case 'dbv':
				$query = 'SELECT * FROM #__{vm}_vendor'
						.' WHERE vendor_id = '.$_SESSION['ps_vendor_id'];
			break;
			case 'dbo':
				$query = "SELECT *, FROM_UNIXTIME(cdate, '%Y-%m-%d') as order_date "
						." FROM #__{vm}_orders WHERE order_id='".(int)$var['order_id']."' "
						." AND vendor_id = '".$_SESSION['ps_vendor_id']."'";
			break;
			case 'dbob':
				$query = "SELECT obliterated FROM #__{vm}_deliveries"
						." WHERE order_id = ".(int)$var['order_id']
						." AND vendor_id = ".$_SESSION['ps_vendor_id']
						." AND delivery_id = ".(int)$var['delivery_id'];
						
			break;
			case 'dbb':
				$query = "SELECT *, FROM_UNIXTIME(mdate, '%Y-%m-%d') as bill_date,"
						." FROM_UNIXTIME(mdate + (due_date * 86400), '%Y-%m-%d') as bill_due"
						." FROM #__{vm}_bills"
						." WHERE vendor_id = '".$_SESSION['ps_vendor_id']."'"
						." AND delivery_id = '".(int)$var['delivery_id']."'";
			break;
			case 'dbi':
				$query = "SELECT di.order_item_id, product_quantity_delivered, product_quantity,"
						." order_item_name, order_item_sku, product_final_price, product_item_price,"
						." product_attribute FROM #__{vm}_order_item AS oi, #__{vm}_delivery_item AS di"
						." WHERE oi.order_item_id = di.order_item_id"
						." AND oi.vendor_id = di.vendor_id"
						." AND oi.order_id = di.order_id"
						." AND oi.order_id = ".(int)$var['order_id']
						." AND oi.vendor_id = ".$_SESSION["ps_vendor_id"]
						." AND delivery_id = ".(int)$var['delivery_id'];
			break;
			case 'dbv2':
				$query = "SELECT SUM(product_quantity_delivered) as delivered FROM #__{vm}_delivery_item"
						." WHERE vendor_id = ".$_SESSION["ps_vendor_id"]
						." AND order_item_id = ".(int)$var['order_item_id']
						." AND obliterated = 0";
			break;
			case 'dbmd':
				$query = "SELECT MIN(delivery_id) as delivery_id FROM #__{vm}_deliveries"
						." WHERE order_id = ".(int)$var['order_id']
						." AND obliterated = 0"
						." AND vendor_id = ".$_SESSION['ps_vendor_id'];
			break;
			case 'dbsd':
				$query = "SELECT order_subtotal"
						." FROM #__{vm}_orders"
						." WHERE order_id = ".(int)$var['order_id']
						." AND vendor_id = ".$_SESSION['ps_vendor_id'];
			break;
			case 'dbuf':
				$query = "SELECT title from #__{vm}_userfield"
						." WHERE name = '".$var['extra_field']."' LIMIT 1";//Maybe without vm
			break;
			case 'dbstate':
				$query = "SELECT state FROM #__{vm}_order_user_info"
						." WHERE user_id='".(int)$var['user_id']."'"
						." AND order_id='".(int)$var['order_id']."'"
						." AND address_type = '".$var['address_type']."'";
					
			break;
			case 'dbstateo':
				$query = "SELECT order_info_id FROM #__{vm}_order_user_info"
						." WHERE user_id='".(int)$var['user_id']."'"
						." AND order_id='".(int)$var['order_id']."'"
						." AND address_type = '".$var['address_type']."'";
					
			break;
			case 'dbt1':
				$query = "SELECT a.*, a.state AS state, c.country_name AS country"
						." FROM #__{vm}_order_user_info AS a" 
						." INNER JOIN #__{vm}_country AS c ON a.country = c.country_3_code OR a.country = c.country_2_code"		
						." WHERE a.user_id = '".(int)$var['user_id']."'"
						." AND a.address_type = '".$var['address_type']."'"
						." AND a.order_id='".(int)$var['order_id']."'";
			break;
			case 'dbt2':
				$query = "SELECT a.*, s.state_name AS state, cc.country_name AS country"
						." FROM #__{vm}_order_user_info AS a"
						." INNER JOIN #__{vm}_state AS s ON a.state = s.state_2_code"
						." AND s.country_id=(SELECT c.country_id FROM #__{vm}_country AS c WHERE c.country_3_code = a.country OR c.country_2_code = a.country)"
						." INNER JOIN #__{vm}_country AS cc ON a.country = cc.country_3_code  OR a.country = cc.country_2_code"
						." WHERE a.user_id = '".(int)$var['user_id']."'"
						." AND a.address_type = '".$var['address_type']."'"
						." AND a.order_id='".(int)$var['order_id']."'";
			break;
			case 'dbu':
				$query = "SELECT * FROM #__{vm}_user_info"
						." WHERE user_id='".(int)$var['user_id']."'"
						." AND address_type='".$var['address_type']."'";
			break;
			
			case 'dbd':
				$query = "SELECT d.delivery_id, is_invoice"
						." FROM #__{vm}_deliveries AS d, #__{vm}_bills AS b"
						." WHERE d.obliterated = 0"
						." AND d.vendor_id = b.vendor_id"
						." AND d.order_id = b.order_id"
						." AND d.delivery_id = b.delivery_id"
						." AND d.vendor_id = ".$_SESSION["ps_vendor_id"]
						." AND d.order_id = ".(int)$var['order_id']
						." ORDER BY d.delivery_id DESC";
			break;
			case 'dbp':
				$query = "SELECT a.payment_method_id, p.payment_method_name FROM #__{vm}_order_payment AS a"
						." LEFT JOIN #__{vm}_payment_method AS p ON a.payment_method_id = p.payment_method_id"
						." WHERE order_id='".(int)$var['order_id']."'";
			break;
			
			default:
				return false;
			break;
		}
		
		$db		= JFactory::getDBO();
		$query	= str_replace('{vm}', VM_TABLEPREFIX, $query);// VM Config File is loaded
		//krumo($type, $query);
		$db->setQuery($query);
		//if (!$db->query()) {echo $this->setError($db->getErrorMsg()); return false;}
		if (!$db->query()) {echo '<div class="error" style="background:#fc5857;padding:10px">Error in SQL Query - Enable Debug Mode to get more info.</div>' /*$db->getErrorMsg()*/; return false;}
		if ($result == 'objectlist') {
			$result = $db->loadObjectList();
		} else {
			$result = $db->loadObject();
		}
		return $result;
	}
	
	function getCurrencyDisplayStyle($style, $orderCurrency) {
		$s = explode('|', $style);
		// VirtueMart Class
		$cD = new CurrencyDisplay();
		$cD->id				= $orderCurrency;
		$cD->symbol 		= $s[1]; 
		$cD->nbDecimal 		= $s[2]; 
		$cD->decimal 		= $s[3]; 
		$cD->thousands 		= $s[4]; 
		$cD->positivePos	= $s[5];
		$cD->negativePos	= $s[6];
		return $cD;
	}
	
	function getDeliveryData($d, $order_id, $delivery_id) {
		return '';
	}
}
