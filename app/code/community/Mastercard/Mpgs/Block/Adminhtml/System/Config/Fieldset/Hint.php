<?php
/**
 * Copyright (c) 2018. On Tap Networks Limited.
 */
class Mastercard_Mpgs_Block_Adminhtml_System_Config_Fieldset_Hint
    extends Mage_Adminhtml_Block_Abstract
    implements Varien_Data_Form_Element_Renderer_Interface
{
    protected $_template = 'Mastercard/system/config/fieldset/hint.phtml';

    /**
     * Render fieldset html
     *
     * @param Varien_Data_Form_Element_Abstract $element
     * @return string
     */
    public function render( Varien_Data_Form_Element_Abstract $element ) 
    {
        return $this->toHtml();
    }
}
