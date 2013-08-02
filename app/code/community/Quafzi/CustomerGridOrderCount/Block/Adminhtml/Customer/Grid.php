<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * @package    Quafzi_CustomerGridOrderCount
 * @copyright  Copyright (c) 2013 Thomas Birke
 * @author     Thomas Birke <tbirke@netextreme.de>
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Quafzi_CustomerGridOrderCount_Block_Adminhtml_Customer_Grid
    extends Quafzi_CustomerTypes_Block_Adminhtml_Customer_Grid
{
    public function setCollection($collection)
    {
        $orderTableName = Mage::getSingleton('core/resource')
            ->getTableName('sales/order');
        $collection
            ->getSelect()
            ->joinLeft(
                array('orders' => $orderTableName),
                'orders.customer_id=e.entity_id',
                array('order_count' => 'COUNT(customer_id)')
            );
        $collection
            ->groupByAttribute('entity_id');
        parent::setCollection($collection);
        return $this;
    }

    protected function _prepareColumns()
    {
        parent::_prepareColumns();
        $this->addColumnAfter('order_count', array(
            'header'    => Mage::helper('customer')->__('Order Count'),
            'align'     => 'center',
            'width'     => '80px',
            'type'      => 'number',
            'filter'    => false,
            'index'     => 'order_count'
        ), 'customer_since');
        $this->sortColumnsByOrder();
    }
}
