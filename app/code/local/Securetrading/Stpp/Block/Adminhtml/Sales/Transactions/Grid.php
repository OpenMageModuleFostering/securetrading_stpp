<?php

class Securetrading_Stpp_Block_Adminhtml_Sales_Transactions_Grid extends Mage_Adminhtml_Block_Widget_Grid {
    public function __construct() {
        parent::__construct();
        $this->setId('securetrading_order_transactions');
        $this->setUseAjax(true);
        $this->setDefaultSort('transaction_id');
        $this->setDefaultDir('DESC');
        $this->setSaveParametersInSession(true);
    }
    
    protected function _prepareCollection() {
        $collection = Mage::getResourceModel('securetrading_stpp/transaction_collection');
        
        $order = Mage::registry('current_order');
        if ($order) {
            $collection->addFieldToFilter('order_id', $order->getId());
        }
        
        $this->setCollection($collection);
        parent::_prepareCollection();
    }
    
    protected function _prepareColumns() {
        $this->addColumn('transaction_id', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Transaction ID'),
            'index'     => 'transaction_id',
        ));
        $this->addColumn('transaction_reference', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Transaction Reference'),
            'index'     => 'transaction_reference',
        ));
        $this->addColumn('parent_transaction_reference', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Parent Transaction Reference'),
            'index'     => 'parent_transaction_reference',
            'renderer'  => 'securetrading_stpp/adminhtml_widget_grid_column_renderer_parenttransactionreference',
        ));
        $this->addColumn('request_type', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Request Type'),
            'index'     => 'request_type',
            'type'      => 'options',
            'options' => Mage::getModel('securetrading_stpp/transaction_types')->getCollection()->toSingleDimensionArray(),
        ));
        $this->addColumn('response_type', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Response Type'),
            'index'     => 'response_type',
            'type'      => 'options',
            'options'   => Mage::getModel('securetrading_stpp/transaction_types')->getCollection()->toSingleDimensionArray(),
        ));
        $this->addColumn('error_code', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Error Code'),
            'index'     => 'error_code',
            'type'      => 'number',
        ));
        $this->addColumn('last_updated_at', array(
            'header'    => Mage::helper('securetrading_stpp')->__('Last Updated At'),
            'index'     => 'last_updated_at',
            'type'      => 'datetime',
        ));
    }
    
    public function getGridUrl() {
        return $this->getUrl('*/*/grid', array('_current' => true));
    }
    
    public function getRowUrl($item) {
        return $this->getUrl('*/securetrading_transactions/single', array('transaction_id' => $item->getId()));
    }
}