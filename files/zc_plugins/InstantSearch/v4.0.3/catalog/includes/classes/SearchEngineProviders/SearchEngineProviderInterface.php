<?php

declare(strict_types=1);

/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * @updated  26/08/2026 torvista
 * @link     https://github.com/torvista/zencart_instantsearch
 */

namespace Zencart\Plugins\Catalog\InstantSearch\SearchEngineProviders;

interface SearchEngineProviderInterface
{
    /**
     * Searches for $queryText and returns the results.
     *
     * @param string $queryText
     * @param array $productFieldsList
     * @param int $productsLimit
     * @param int $categoriesLimit
     * @param int $manufacturersLimit
     * @param  int|null  $alphaFilter
     * @return array
     */
    public function search(
        string $queryText,
        array $productFieldsList,
        int $productsLimit,
        int $categoriesLimit = 0,
        int $manufacturersLimit = 0,
        ?int $alphaFilter = null
    ): array;
}
