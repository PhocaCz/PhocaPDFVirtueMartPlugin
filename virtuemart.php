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
jimport( 'joomla.plugin.plugin' );
jimport('joomla.filesystem.file');

require_once(JPATH_ROOT.DS.'plugins'.DS.'phocapdf'.DS.'virtuemart'.DS.'virtuemartpdfoutput.php');

class plgPhocaPDFVirtueMart extends JPlugin
{
	public $pdfO;
	
	function plgPhocaPDFVirtueMart(& $subject, $config) {
		parent :: __construct($subject, $config);
	}
	
	function onBeforeCreatePDFVirtueMart(&$content, $staticData = array()) {
		
		$content->content = '';
		// load plugin params info
	 	$plugin =& JPluginHelper::getPlugin('phocapdf', 'virtuemart');
	 	$pluginP = new JParameter( $plugin->params );
		
		$content->margin_top		= $pluginP->get('margin_top', 108);
		$content->margin_left		= $pluginP->get('margin_left', 15);
		$content->margin_right		= $pluginP->get('margin_right', 15);
		$content->margin_bottom		= $pluginP->get('margin_bottom', 25);
		$content->site_font_color	= $pluginP->get('site_font_color', '#000000');
		$content->site_cell_height	= $pluginP->get('site_cell_height', 1.2);
		
		$content->font_type			= $pluginP->get('font_type', 'helvetica');
		$content->page_format		= $pluginP->get('page_format', 'A4');
		$content->page_orientation	= $pluginP->get('page_orientation', 'P');
		
		$content->header_data		= $pluginP->get('header_data', '');
		$content->header_font_type	= $pluginP->get('header_font_type', 'helvetica');
		$content->header_font_style	= $pluginP->get('header_font_style', '');
		$content->header_font_size	= $pluginP->get('header_font_size', 10);
		$content->header_margin		= $pluginP->get('header_margin', 5);
		
		$content->footer_font_type	= $pluginP->get('footer_font_type', 'helvetica');
		$content->footer_font_style	= $pluginP->get('footer_font_style', '');
		$content->footer_font_size	= $pluginP->get('footer_font_size', 10);
		$content->footer_margin		= $pluginP->get('footer_margin', 30);
		
		if (isset($staticData['filename'])) {
			if($staticData['filename'] != '') {
				$content->pdf_name = $staticData['filename'];
			} else {
				$content->pdf_name = 'Phoca PDF';
			}
		} else {
			$content->pdf_name			= $pluginP->get('pdf_name', '');
			if ($content->pdf_name == ''){
				$lang 			=& JFactory::getLanguage();
				$lang->load('plg_phocapdf_virtuemart', JPATH_ADMINISTRATOR, null, true);
				$d	= JRequest::get('request');
				
				if(isset($d['type']) && $d['type'] == 'invoice') {
					$content->pdf_name = JText::_('PLG_PHOCAPDF_VM_DELIVERY_INVOICE');
				} else if(isset($d['type']) && $d['type'] == 'receipt') {
					$content->pdf_name = JText::_('PLG_PHOCAPDF_VM_DELIVERY_RECEIPT');
				} else {
					$content->pdf_name = JText::_('PLG_PHOCAPDF_VM_DELIVERY_NOTE');
				}
			}
		}
		
		$content->pdf_destination	= $pluginP->get('pdf_destination', 'S');
		$content->image_scale		= $pluginP->get('image_scale', 1);
		$content->display_plugin	= $pluginP->get('display_plugin', 0);
		$content->display_image		= $pluginP->get('display_image', 1);
		$content->use_cache			= $pluginP->get('use_cache', 0);
		
		//Extra values
		if ((int)$content->site_cell_height > 3) {
			$content->site_cell_height = 3;
		}
		if ((int)$content->margin_top > 200) {
			$content->margin_top = 200;
		}
		if ((int)$content->margin_left > 50) {
			$content->margin_left = 50;
		}
		if ((int)$content->margin_right > 50) {
			$content->margin_right = 50;
		}
		if ((int)$content->margin_bottom > 150) {
			$content->margin_bottom = 150;
		}
		if ((int)$content->header_font_size > 30) {
			$content->header_font_size = 30;
		}
		if ((int)$content->footer_font_size > 30) {
			$content->footer_font_size = 30;
		}
		if ((int)$content->header_margin > 50) {
			$content->header_margin = 50;
		}
		if ((int)$content->footer_margin > 50) {
			$content->footer_margin = 50;
		}
		if ((int)$content->image_scale < 0.5) {
			$content->image_scale = 0.5;
		}
		
		return true;
	}
	
	function onBeforeDisplayPDFVirtueMart(&$pdf, &$content, &$document, $staticData = '') {
		
		$documentOutput = PhocaPDFVirtueMartPdfOutput::getOutput($staticData);
		
		$pdf->SetTitle($documentOutput['title']);
		$pdf->SetSubject($documentOutput['subject']);
		$pdf->SetKeywords($documentOutput['keywords']);
		$pdf->SetAuthor('Phoca PDF');
		
		// Header
		if ($content->header_data != '') {
			$content->header_data = plgPhocaPDFVirtueMart::correctImgPath($content->header_data);
			$pdf->setHeaderData('' , 0, '', $content->header_data);
		} else {
			$pdf->setHeaderData('' , 0, '', '');
		}
		
		$pdf->setHeaderFont(array($content->header_font_type, $content->header_font_style, $content->header_font_size));
		
		$lang = &JFactory::getLanguage();
		$font = $content->font_type;
		$pdf->setRTL($lang->isRTL());

		$pdf->setFooterFont(array($content->footer_font_type, $content->footer_font_style, $content->footer_font_size));
		// Initialize PDF Document
		$pdf->AliasNbPages();
		$pdf->AddPage();
		
		if ($content->display_image == 0) {
			$documentOutput 	= preg_replace_callback('/<img(.*)>/Ui', array('phocaPDFVirtueMartCallbackImage', 'phocaPDFCallbackImage'), $documentOutput);
		}
		
		// Build the PDF Document string from the document buffer
		$pdf->writeHTML($documentOutput['output'], true, false, true, false, '');
		
		return true;
	}
	
	
	function phocaPDFVirtueMartCallbackImage ($matches) {
		$a				= $matches[0];
		$replacement 	= '';
		return $replacement;
	}

	public function correctImgPath($text, $pathOnly = 0) {
		if ($text != '') {
			// Try to correct images
			if ($pathOnly == 1) {
				$find 	= JPATH_ROOT;
				$to		= '';
			} else {
				$find 	= 'src="'.JPATH_ROOT;
				$to		= 'src="';
			}
			
			if (strpos($text, $find)) {
				$text = str_replace ($find, $to, $text);
			}
			
			if ($pathOnly == 1) {
				$text = $find .DS. $text;
				$text = str_replace ('/', DS, $text);
			} else {
				$text = str_replace ($to, $find . DS, $text);			
			}
			//$text = str_replace ('/', DS, $text);
		}
		return $text;
	}
	
}	
	

/*
 * Phoca PDF - extended TCPDF Class
 */
 
 if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdf.php')) {
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'helpers'.DS.'phocapdf.php');
} else {
	return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF library (Phoca PDF) failed');
}
if (JFile::exists(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'assets'.DS.'tcpdf'.DS.'tcpdf.php')) {
	require_once(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_phocapdf'.DS.'assets'.DS.'tcpdf'.DS.'tcpdf.php');
} else {
	return JError::raiseError('PDF ERROR', 'Document cannot be created - Loading of Phoca PDF library (TCPDF) failed');
}


class PhocaPDFVirtueMartTCPDF extends TCPDF
{
	protected $pluginP 	= null;
	public $staticData	= null;
	
	private function getPluginParameters() {
		if (empty($this->pluginP)) {
			$plugin 		= &JPluginHelper::getPlugin('phocapdf', 'virtuemart');
			$this->pluginP 	= new JParameter( $plugin->params );
		}
		return $this->pluginP;
	}
	
	public function setStaticData($staticData) {
		$this->staticData 	= $staticData;
	}
	
	public function getStaticData() {
		if (empty($this->staticData)) {
			$this->staticData = array();
		}
		return $this->staticData;
	}

	public function Header() {
		
		$pluginP	= $this->getPluginParameters();
		$ormargins 	= $this->getOriginalMargins();
		$headerfont = $this->getHeaderFont();
		$headerdata = $this->getHeaderData();
		$staticData	= $this->getStaticData();
		
		// Not used in VM
		$headerdata['title'] = '';
	
		// Params
		$params							= array();
		$params['header_display_line']	= $pluginP->get('header_display_line', 0);
		$params['header_display']	= $pluginP->get('header_display', 1);
		$params['header_font_color']	= $this->convertHTMLColorToDec($pluginP->get('header_font_color', '#000000'));
		$params['header_line_color']	= $this->convertHTMLColorToDec($pluginP->get('header_line_color', '#000000'));
		$params['header_bg_color']		= $pluginP->get('header_bg_color', '');
		$params['header_data']			= $pluginP->get('header_data', '');
		$params['header_data_align']	= $pluginP->get('header_data_align', 'L');
		$params['header_cell_height']	= $pluginP->get('header_cell_height', 1.2);
		
		//Extra values
		if ((int)$params['header_cell_height'] > 3) {
			$params['header_cell_height'] = 3;
		}
		
		$currentCHRH = $this->getCellHeightRatio();
		$this->setCellHeightRatio($params['header_cell_height']);
		
		$isHTML = false;
		if ($params['header_data'] != '') {
			$params['header_data'] = plgPhocaPDFVirtueMart::correctImgPath($params['header_data']);
			$isHTML = true;
		}
		
		if ($params['header_display'] == 1) {
			if (($headerdata['logo']) AND ($headerdata['logo'] != K_BLANK_IMAGE)) {
				$this->Image(K_PATH_IMAGES.$headerdata['logo'], $this->GetX(), $this->getHeaderMargin(), $headerdata['logo_width']);
				$imgy = $this->getImageRBY();
			} else {
				$imgy = $this->GetY();
			}
			$cell_height = round(($this->getCellHeightRatio() * $headerfont[2]) / $this->getScaleFactor(), 2);
			// set starting margin for text data cell
			if ($this->getRTL()) {
				$header_x = $ormargins['right'] + ($headerdata['logo_width'] * 1.1);
			} else {
				$header_x = $ormargins['left'] + ($headerdata['logo_width'] * 1.1);
			}
			$this->SetTextColor($params['header_font_color']['R'], $params['header_font_color']['G'], $params['header_font_color']['B']);
			// header title
			$this->SetFont($headerfont[0], 'B', $headerfont[2] + 1);
			$this->SetX($header_x);			
			$this->Cell(0, $cell_height, $headerdata['title'], 0, 1, '', 0, '', 0);
			// header string
			$this->SetFont($headerfont[0], $headerfont[1], $headerfont[2]);
			$this->SetX($header_x);
			
			$fill = 0;
			if ($params['header_bg_color'] != '') {
				$fillColor = $this->convertHTMLColorToDec($params['header_bg_color']);
				$this->SetFillColorArray(array($fillColor['R'],$fillColor['G'],$fillColor['B']));
				$fill = 1;
				
			}
			
			$this->MultiCell(0, $cell_height, $headerdata['string'], 0, $params['header_data_align'], $fill, 1, '', '', true, 0, $isHTML);
			// print an ending header line
			$border = 0;
			if ((int)$params['header_display_line'] == 1) {
				$this->SetLineStyle(array('width' => 0.85 / $this->getScaleFactor(), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array($params['header_line_color']['R'], $params['header_line_color']['G'], $params['header_line_color']['B'])));
				$this->SetY((2.835 / $this->getScaleFactor()) + max($imgy, $this->GetY()));
				$border = 'T';
			}
			if ($this->getRTL()) {
				$this->SetX($ormargins['right']);
			} else {
				$this->SetX($ormargins['left']);
			}
			$this->Cell(0, 0, '', $border, 0, 'C');
		}
		// Set it back
		$this->setCellHeightRatio($currentCHRH);
		
		$headerOutput = PhocaPDFVirtueMartPdfOutput::getOutputHeader($pluginP, $staticData);
		$headerOutput = plgPhocaPDFVirtueMart::correctImgPath($headerOutput);
		$this->writeHTML($headerOutput, true, false, false, false, '');
		
	}
	
	public function Footer() {
	
		$footerfont = $this->getFooterFont();

		$pluginP	= $this->getPluginParameters();
		$staticData	= $this->getStaticData();
	
		$footerOutput = PhocaPDFVirtueMartPdfOutput::getOutputFooter($pluginP, $staticData);
		$footerOutput = plgPhocaPDFVirtueMart::correctImgPath($footerOutput);
		$this->writeHTML($footerOutput, true, false, false, false, '');

		// Params
		$params								= array();
		$params['footer_display_line']		= $pluginP->get('footer_display_line', 0);
		$params['footer_font_color']		= $this->convertHTMLColorToDec($pluginP->get('footer_font_color', '#000000'));
		$params['footer_line_color']		= $this->convertHTMLColorToDec($pluginP->get('footer_line_color', '#000000'));
		$params['footer_bg_color']			= $pluginP->get('footer_bg_color', '');
		$params['footer_display']			= $pluginP->get('footer_display', 1);
		$params['footer_data']				= $pluginP->get('footer_data', '');
		$params['footer_display_pagination']= $pluginP->get('footer_display_pagination', 1);
		$params['footer_data_align']		= $pluginP->get('footer_data_align', 'C');
		$params['footer_margin']			= $pluginP->get('footer_margin', 28);
		$params['footer_cell_height']		= $pluginP->get('footer_cell_height', 1.2);
	
		//Extra values
		if ((int)$params['footer_cell_height'] > 3) {
			$params['footer_cell_height'] = 3;
		}
		
		if ((int)$params['footer_margin'] > 50) {
			$params['footer_margin'] = 50;
		}
		
		$currentCHRF = $this->getCellHeightRatio();
		$this->setCellHeightRatio($params['footer_cell_height']);
	
		$isHTML = false;
		if ($params['footer_data'] != '') {
			$params['footer_data'] = plgPhocaPDFVirtueMart::correctImgPath($params['footer_data']);
			$isHTML = true;
		}

		if ($params['footer_display'] == 1) {
			$cur_y = $this->GetY();
			$ormargins = $this->getOriginalMargins();
			$this->SetTextColor($params['footer_font_color']['R'], $params['footer_font_color']['G'], $params['footer_font_color']['B']);			
			//set style for cell border
			$border = 0;
			if ((int)$params['footer_display_line'] == 1) {
				$line_width = 0.85 / $this->getScaleFactor();
				$this->SetLineStyle(array('width' => $line_width, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => array($params['footer_line_color']['R'], $params['footer_line_color']['G'], $params['footer_line_color']['B'])));
				$border = 'T';
			}
			
			//print document barcode
			$barcode = $this->getBarcode();
			if (!empty($barcode)) {
				$this->Ln($line_width);
				$barcode_width = round(($this->getPageWidth() - $ormargins['left'] - $ormargins['right'])/3);
				$this->write1DBarcode($barcode, 'C128B', $this->GetX(), $cur_y + $line_width, $barcode_width, (($this->getFooterMargin() / 3) - $line_width), 0.3, '', '');	
			}
			if (empty($this->pagegroups)) {
				$pagenumtxt = $this->l['w_page'].' '.$this->getAliasNumPage().' / '.$this->getAliasNbPages();
			} else {
				$pagenumtxt = $this->l['w_page'].' '.$this->getPageNumGroupAlias().' / '.$this->getPageGroupAlias();
			}		
			$this->SetY($cur_y);
			
			
			// Pagination
			if ($params['footer_display_pagination'] == 0) {
				$pagenumtxt = '';
			}
			// Footer Data
			if ($params['footer_data'] != '') {
				$footertxt = str_replace('{phocapdfpagination}', $pagenumtxt, $params['footer_data']);
			
			} else {
				$footertxt = $pagenumtxt;
			}
			
			$cell_height = round(($this->getCellHeightRatio() * $footerfont[2]) / $this->getScaleFactor(), 2);
			
			$this->SetFont($footerfont[0], $footerfont[1], $footerfont[2]);
			if ($this->getRTL()) {
				$this->SetX($ormargins['right']);
			} else {
				$this->SetX($ormargins['left']);
			}
		
			$fill = 0;
			if ($params['footer_bg_color'] != '') {
				$fillColor = $this->convertHTMLColorToDec($params['footer_bg_color']);
				$this->SetFillColorArray(array($fillColor['R'],$fillColor['G'],$fillColor['B']));
				$fill = 1;
				
			}
		
			$this->MultiCell(0, $cell_height, $footertxt, $border, $params['footer_data_align'], $fill, 1, '', '', true, 0, $isHTML);
			
		}
		
		$posY	= $this->getPageHeight() -10;
		$this->writeHTMLCell(0, 0, 0, $posY, PhocaPDFHelper::getPhocaInfo(1), 0, 0, 0,true, 'R');
		
		// Set it back
		$this->setCellHeightRatio($currentCHRF);
		
	}

}
?>