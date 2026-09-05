<?php
declare(strict_types=1);
/**
 * Copyright © Nfourteen. All Rights Reserved.
 * See COPYING.txt for license details.
 **/

namespace Nfourteen\AggregateProduct\Service;

use Nfourteen\AggregateProduct\Api\Data\LinkedProductInterface;

class LinkedProductFormatter
{
    public function format(LinkedProductInterface $linkedProduct): string
    {
        return $this->formatFromData($linkedProduct->getQty(), $linkedProduct->getProductName());
    }

    public function formatFromData(float $qty, string $name): string
    {
        return $qty . ' x ' . $name;
    }
}
