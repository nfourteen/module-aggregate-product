<?php
declare(strict_types=1);
/**
 * Copyright © David Nimorwicz. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Nfourteen\AggregateProduct\Test\Integration\Controller\Order;

use Magento\Catalog\Test\Fixture\Product as ProductFixture;
use Magento\Checkout\Test\Fixture\PlaceOrder as PlaceOrderFixture;
use Magento\Checkout\Test\Fixture\SetBillingAddress as SetBillingAddressFixture;
use Magento\Checkout\Test\Fixture\SetDeliveryMethod as SetDeliveryMethodFixture;
use Magento\Checkout\Test\Fixture\SetPaymentMethod as SetPaymentMethodFixture;
use Magento\Checkout\Test\Fixture\SetShippingAddress as SetShippingAddressFixture;
use Magento\Customer\Model\Session;
use Magento\Customer\Test\Fixture\Customer as CustomerFixture;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Quote\Test\Fixture\AddProductToCart as AddProductToCartFixture;
use Magento\Quote\Test\Fixture\CustomerCart as CustomerCartFixture;
use Magento\TestFramework\Fixture\Config;
use Magento\TestFramework\Fixture\DataFixture;
use Magento\TestFramework\Fixture\DataFixtureStorageManager;
use Magento\TestFramework\Fixture\DbIsolation;
use Magento\TestFramework\TestCase\AbstractController;
use Nfourteen\AggregateProduct\Test\Fixture\AggregateProduct as AggregateProductFixture;

/**
 * The customer account order view pages its item collection to parent rows only
 * (Sales\Block\Order\Items::preparePager), so the aggregate renderer must still find the
 * parent's children and list them under "Includes".
 */
class ViewTest extends AbstractController
{
    #[
        DbIsolation(false),
        Config('carriers/flatrate/active', 1, 'store', 'default'),
        Config('payment/checkmo/active', 1, 'store', 'default'),
        DataFixture(ProductFixture::class, [
            'sku' => 'agg-order-view-child-1',
            'name' => 'Agg Order View Child One',
            'price' => 10.0,
        ], as: 'child1'),
        DataFixture(ProductFixture::class, [
            'sku' => 'agg-order-view-child-2',
            'name' => 'Agg Order View Child Two',
            'price' => 7.5,
        ], as: 'child2'),
        DataFixture(AggregateProductFixture::class, [
            'sku' => 'agg-order-view-parent',
            'price' => 50.0,
            '_children' => [
                ['product_id' => '$child1.id$', 'qty' => 2],
                ['product_id' => '$child2.id$', 'qty' => 3],
            ],
        ], as: 'aggregate'),
        DataFixture(CustomerFixture::class, as: 'customer'),
        DataFixture(CustomerCartFixture::class, ['customer_id' => '$customer.id$'], as: 'cart'),
        DataFixture(AddProductToCartFixture::class, [
            'cart_id' => '$cart.id$',
            'product_id' => '$aggregate.id$',
            'qty' => 1,
        ]),
        DataFixture(SetBillingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetShippingAddressFixture::class, ['cart_id' => '$cart.id$']),
        DataFixture(SetDeliveryMethodFixture::class, [
            'cart_id' => '$cart.id$',
            'carrier_code' => 'flatrate',
            'method_code' => 'flatrate',
        ]),
        DataFixture(SetPaymentMethodFixture::class, ['cart_id' => '$cart.id$', 'method' => 'checkmo']),
        DataFixture(PlaceOrderFixture::class, ['cart_id' => '$cart.id$'], as: 'order'),
    ]
    public function testOrderViewListsAggregateChildren(): void
    {
        $fixtures = DataFixtureStorageManager::getStorage();
        $this->_objectManager->get(Session::class)->loginById((int)$fixtures->get('customer')->getId());

        $this->dispatch('sales/order/view/order_id/' . (int)$fixtures->get('order')->getId());
        /** @var HttpResponse $response */
        $response = $this->getResponse();
        $body = $response->getBody();

        $this->assertStringContainsString(
            'class="options-label"',
            $body,
            'Aggregate row must show its Includes label'
        );
        $this->assertMatchesRegularExpression('/2(\.0+)? x Agg Order View Child One/', $body);
        $this->assertMatchesRegularExpression('/3(\.0+)? x Agg Order View Child Two/', $body);
    }
}
