<?php

declare(strict_types=1);

/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * @link     https://github.com/torvista/zencart_instantsearch
 * @updated  26/08/2026 torvista
 */

/**
 * Returns the number of (enabled) products per manufacturer.
 * @param int $manufacturers_id Manufacturer's id
 * @return int Products count
 */
function isearch_count_products_for_manufacturer(int $manufacturers_id): int
{
    global $db;

    $products = $db->Execute("
        SELECT COUNT(products_id) AS total
        FROM " . TABLE_PRODUCTS . "
        WHERE manufacturers_id = " . $manufacturers_id . "
        AND products_status = 1
    ");

    return (int)$products->fields['total'];
}
