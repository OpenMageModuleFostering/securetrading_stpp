<?php

abstract class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Data_Abstract extends Mage_Adminhtml_Block_Widget_Grid {
    protected function _construct() {
        $this->setPagerVisibility(false);
        $this->setFilterVisibility(false);
    }
    
    protected function _getTransaction() {
        $transaction = Mage::registry('current_transaction');
        
        if ($transaction === null) {
            throw new Stpp_Exception(Mage::helper('securetrading_stpp')->__('A transaction has not been set.'));
        }
        return $transaction;
    }
    
    protected function _prepareCollection() {
        $collection = new Varien_Data_Collection();
        foreach ($this->_getGridData() as $key => $value) {
            $data = new Varien_Object(array('key' => $key, 'value' => $value));
            $collection->addItem($data);
        }

        $this->setCollection($collection);
        return parent::_prepareCollection();
    }
    
    protected function _prepareColumns() {
        $this->addColumn('key', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Key'),
            'index'     => 'key',
            'sortable'  => false,
            'type'      => 'text',
            'width'     => '50%'
        ));

        $this->addColumn('value', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Value'),
            'index'     => 'value',
            'sortable'  => false,
            'type'      => 'text',
            'escape'    => true
        ));
        return parent::_prepareColumns();
    }
    
    abstract protected function _getGridData();
}