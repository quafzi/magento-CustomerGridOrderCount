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
        if ($block instanceof Mage_Adminhtml_Block_Customer_Grid
            || $block instanceof Mage_Adminhtml_Block_Sales_Order_Grid
        ) {
            $after = ($block instanceof Mage_Adminhtml_Block_Customer_Grid)
                ? 'customer_since'
                : 'created_at';
            $this->_modifyGrid($block, $after);
        }
    }

    protected function _modifyGrid(Mage_Adminhtml_Block_Widget_Grid $grid, $after='customer_since')
    {
        $this->_addOrderCountColumn($grid, $after);
        // reinitialize column order
        $grid->sortColumnsByOrder();
        // reinitialize collection sort and filter
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

    protected function _addOrderCountColumn($grid, $after='customer_since')
    {
        $grid->addColumnAfter('order_count', array(
            'header'    => Mage::helper('customer')->__('Order Count'),
            'align'     => 'center',
            'width'     => '80px',
            'type'      => 'number',
            'filter'    => false,
            'index'     => 'order_count'
        ), $after);
    }

    public function beforeCollectionLoad($observer)
    {
        $collection = $observer->getEvent()->getCollection();
        if ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection) {
            $relationAlias = 'orders_to_count';
            $from = $collection->getSelect()->getPart(Zend_Db_Select::FROM);
            if (false === array_key_exists($relationAlias, $from)) {
                $this->_joinOrderCount($collection, $relationAlias);
            }
        }
        if ($collection instanceof Mage_Sales_Model_Resource_Order_Grid_Collection) {
            /* WTF? It's not that easy to join the count on the same table using Zend_Db */
            // create subquery
            $orderTable = Mage::getResourceModel('sales/order_collection');
            $orderTable->getSelect()->reset(Zend_Db_Select::COLUMNS);
            $orderTable
                 ->addExpressionFieldToSelect('order_count', 'COUNT(1)', 'order_count')
                 ->getSelect()->where('sub_table.customer_id = main_table.customer_id')
                 ;

            // change table alias, otherwise we would get "main_table" for both main and sub query
            $from = $orderTable->getSelect()->getPart(Zend_Db_Select::FROM);
            $from['sub_table'] = $from['main_table'];
            unset($from['main_table']);
            $from = $orderTable->getSelect()->setPart(Zend_Db_Select::FROM, $from);

            // add sub query
            $collection->addExpressionFieldToSelect(
                'order_count',
                new Zend_Db_Expr('(' . $orderTable->getSelect() . ') as order_count'),
                'order_count'
            );
        }
    }

    protected function _joinOrderCount($collection, $relationAlias)
    {
        $groupByAttribute = ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection)
            ? 'entity_id'
            : 'customer_id';
        $tableAlias = ($collection instanceof Mage_Customer_Model_Resource_Customer_Collection)
            ? 'e'
            : 'main_table';
        $orderTableName = Mage::getSingleton('core/resource')
            ->getTableName('sales/order');

        $collection
            ->getSelect()
            ->joinLeft(
                array($relationAlias => $orderTableName),
                $relationAlias . '.customer_id=' . $tableAlias . '.' . $groupByAttribute,
                array('order_count' => 'COUNT(' . $relationAlias . '.customer_id)')
            );
        if ($collection instanceof Mage_Eav_Model_Entity_Collection_Abstract) {
            $collection->groupByAttribute($groupByAttribute);
        } else {
            $collection->getSelect()->group(array($groupByAttribute));
        }
    }
}
