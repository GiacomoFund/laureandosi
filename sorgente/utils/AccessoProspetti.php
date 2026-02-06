<?php
/**
 * @access public
 * @author franc
 */
class AccessoProspetti {
    private $file = 'data\pdf_generati\prospettoCommissione.pdf';
    public function fornisciAccesso(){
         return $this->file;
    }
}
?>