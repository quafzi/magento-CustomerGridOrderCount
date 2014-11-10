<?php
/**
 * @package    Quafzi_CustomerGridOrderCount
 * @copyright  Copyright (c) 2013 Thomas Birke
 * @author     Thomas Birke <tbirke@netextreme.de>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Quafzi_CustomerGridOrderCount_Model_Observer
{
    public function beforeBlockToHtml(Varien_Event_Observer $observer) {
        $block = $observer->getEvent()->getBlock();
        if ($block instanceof Mage_Adminhtml_Block_Customer_Grid) {
            $this->_modifyCustomerGrid($block);
        }
    }

    protected function _modifyCustomerGrid(Mage_Adminhtml_Block_Customer_Grid $grid)
    {
        $this->_addOrderCountColumn($grid);

        // reinitialisiert die Spaltensortierung
        $grid->sortColumnsByOrder();
        // reinitialisiert die Sortierung und Filter der Collection
        $this->_callProtectedMethod($grid, '_prepareCollection');
    }

    /**
     * dirty hack...
     * @see http://www.webguys.de/magento/turchen-23-pimp-my-produktgrid/
     */
    protected function _callProtectedMethod($object, $methodName)
    {
        $reflection = new ReflectionClass($object);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invoke($object);
    }

    protected function _addOrderCountColumn($grid)
    {
        $grid->addColumnAfter('order_count', array(
            'header'    => Mage::helper('customer')->__('Order Count'),
            'align'     => 'center',
            'width'     => '80px',
            'type'      => 'number',
            'filter'    => false,
            'index'     => 'order_count'
        ), 'customer_since');
    }

    public function beforeCustomerCollectionLoad(Varien_Event_Observer $observer)
    {
        $collection = $observer->getEvent()->getCollection();
        if ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            $relationAlias = 'orders_to_count';

            $from = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
            if (false === array_key_exists($relationAlias, $from)) {
                // not yet joined
                $orderTableName = Mage::getSingleton('core/resource')
                    ->getTableName('sales/order');

                $collection
                    ->getSelect()
                    ->joinLeft(
                        array($relationAlias => $orderTableName),
                        $relationAlias . '.customer_id=e.entity_id',
                        array('order_count' => 'COUNT(' . $relationAlias . '.customer_id)')
                    );
                $collection->groupByAttribute('entity_id');
            }
        }
    }
}
