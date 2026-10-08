<?php
declare(strict_types=1);
/**
 * Copyright © David Nimorwicz. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Nfourteen\AggregateProduct\ViewModel\Sales\Order\Items;

use InvalidArgumentException;
use Magento\Framework\ObjectManager\ResetAfterRequestInterface;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Sales\Api\Data\CreditmemoItemInterface;
use Magento\Sales\Api\Data\InvoiceItemInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Sales\Api\Data\ShipmentItemInterface;
use Magento\Sales\Model\Order\Item as OrderItem;
use Magento\Sales\Model\ResourceModel\Order\Item\CollectionFactory;
use Nfourteen\AggregateProduct\Service\LinkedProductFormatter;

class Renderer implements ArgumentInterface, ResetAfterRequestInterface
{
    /** @var array<int, array<int, OrderItem[]>> orderId => parent item id => child items */
    private array $childItemsByOrder = [];

    public function __construct(
        private readonly LinkedProductFormatter $linkedProductFormatter,
        private readonly SerializerInterface $json,
        private readonly CollectionFactory $itemCollectionFactory
    ) {
    }

    /**
     * Children of an aggregate parent order item. They are already linked when the order's whole
     * item collection was loaded (Order\Item\Collection::_afterLoad), but the customer account
     * order view pages its collection to parent rows only (Sales\Block\Order\Items::preparePager),
     * so fall back to loading the order's child items once and linking them to their parent.
     *
     * @param OrderItem $parentItem
     * @return OrderItem[]
     */
    public function getChildItems(OrderItem $parentItem): array
    {
        if ($parentItem->getChildrenItems()) {
            return $parentItem->getChildrenItems();
        }

        $children = $this->getChildItemsByParent((int)$parentItem->getOrderId())[(int)$parentItem->getItemId()] ?? [];
        foreach ($children as $child) {
            $child->setParentItem($parentItem);
        }

        return $parentItem->getChildrenItems();
    }

    public function _resetState(): void
    {
        $this->childItemsByOrder = [];
    }

    public function getOrderItemRowClassName(OrderItemInterface $item): string
    {
        return $item->getParentItem() ? 'item-options-container' : 'item-parent';
    }

    /**
     * Qty of a child to display against a document row: the document item's qty scaled by the
     * per-parent child qty captured in the snapshot. Guards the null/absent snapshot so templates
     * never dereference a missing 'qty'.
     *
     * @param InvoiceItemInterface|ShipmentItemInterface|CreditmemoItemInterface $parentItem
     * @param OrderItem $item
     * @return float
     */
    public function getChildDisplayQty(
        InvoiceItemInterface|ShipmentItemInterface|CreditmemoItemInterface $parentItem,
        OrderItem $item
    ): float {
        $config = $this->getConfigurationData($item);
        $childQty = is_array($config) ? (float)($config['qty'] ?? 0) : 0.0;

        return (float)$parentItem->getQty() * $childQty;
    }

    public function getValueHtml(OrderItem $item): string
    {
        $config = $this->getConfigurationData($item);
        if (is_array($config) && isset($config['qty'])) {
            return $this->linkedProductFormatter->formatFromData(
                (float)$config['qty'],
                (string)($config['name'] ?? $item->getName())
            );
        }

        return (string)$item->getName();
    }

    /**
     * @param OrderItem $item
     * @return array<string|int, mixed>|null
     */
    public function getConfigurationData(OrderItem $item): ?array
    {
        $options = $item->getProductOptions();

        if (!isset($options['aggregate_config'])) {
            return null;
        }

        // Per-child items store aggregate_config as a serialized JSON string ({qty, name}); the
        // parent order item stores it as an already-deserialized array ([{option_id, label,
        // value}]). Accept either rather than TypeError-ing on the array form.
        $config = $options['aggregate_config'];
        if (is_string($config)) {
            try {
                $config = $this->json->unserialize($config);
            } catch (InvalidArgumentException $e) {
                return null;
            }
        }

        return is_array($config) ? $config : null;
    }

    /**
     * @param int $orderId
     * @return array<int, OrderItem[]> parent item id => child items
     */
    private function getChildItemsByParent(int $orderId): array
    {
        if (!isset($this->childItemsByOrder[$orderId])) {
            $collection = $this->itemCollectionFactory->create();
            $collection->setOrderFilter($orderId);
            $collection->addFieldToFilter(OrderItemInterface::PARENT_ITEM_ID, ['notnull' => true]);

            $this->childItemsByOrder[$orderId] = [];
            foreach ($collection as $child) {
                $this->childItemsByOrder[$orderId][(int)$child->getParentItemId()][] = $child;
            }
        }

        return $this->childItemsByOrder[$orderId];
    }
}
