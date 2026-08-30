<?php

declare(strict_types=1);

/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 * @updated  31/08/2026 torvista
 * @link     https://github.com/torvista/zencart_instantsearch
 */

$nameModelClass = '';
if (INSTANT_SEARCH_DROPDOWN_HIGHLIGHT_TEXT === 'query') {
    $nameModelClass = ' instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__nameModelWrapper--highlightQuery';
} elseif (INSTANT_SEARCH_DROPDOWN_HIGHLIGHT_TEXT === 'autocomplete') {
    $nameModelClass = ' instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__nameModelWrapper--highlightAutocomplete';
}

if (!empty($dropdownResults)) { ?>
    <ul role="listbox">
    <?php
    foreach ($dropdownResults as $result) {
        if (!empty($result['separator'])) { ?>
            </ul>
            <div class="instantSearchResultsDropdownContainer__separator"><?= $result['separator'] ?></div>
            <ul role="listbox">
            <?php
        } else { ?>
            <li role="option" tabindex="-1">
                <a href="<?= $result['link'] ?>" class="instantSearchResultsDropdownContainer__link">
                    <div class="instantSearchResultsDropdownContainer__resultWrapper">
                        <?php
                        if (!empty($result['img'])) { ?>
                            <div class="instantSearchResultsDropdownContainer__resultWrapper__img"><?= $result['img'] ?></div>
                            <?php
                        } ?>
                        <div class="instantSearchResultsDropdownContainer__resultWrapper__infoWrapper">
                            <div class="instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__nameModelWrapper<?= $nameModelClass ?>"><?= $result['name'] ?>
                                <?php
                                if (!empty($result['model'])) { ?>
                                    <div class="instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__nameModelWrapper__model"><?= $result['model'] ?></div>
                                    <?php
                                } ?>
                            </div>
                            <div class="instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__priceCountWrapper">
                                <?php
                                if (!empty($result['price'])) {
                                    echo $result['price'];
                                } elseif (!empty($result['count'])) { ?>
                                    <div class="instantSearchResultsDropdownContainer__resultWrapper__infoWrapper__priceCountWrapper__count"><?= $result['count'] . ' ' . TEXT_INSTANT_SEARCH_PRODUCTS_TEXT ?></div>
                                    <?php
                                } ?>
                            </div>
                        </div>
                    </div>
                </a>
            </li>
            <?php
        }
    } ?>
    </ul>
    <?php
}
