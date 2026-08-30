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

class zcObserverInstantSearchObserver extends base
{
    public function __construct()
    {
        $this->attach($this, [
            'NOTIFY_FOOTER_END',
            'NOTIFY_MODULE_META_TAGS_UNSPECIFIEDPAGE'
        ]);
    }

    /**
     * Include JavaScript in the footer
     * $zco_notifier->notify('NOTIFY_FOOTER_END', $current_page);
     *
     * @param $class
     * @param $eventID
     * @param $paramsArray
     * @return void
     */
    public function updateNotifyFooterEnd(&$class, $eventID, $paramsArray): void
    {
        global $current_page_base;

        if (defined('INSTANT_SEARCH_DROPDOWN_ENABLED') && INSTANT_SEARCH_DROPDOWN_ENABLED === 'true') {
            echo "
                <script title='auto.instant_search_observer.php " . __LINE__ . "'>
                    const instantSearchSecurityToken          = '" . $_SESSION['securityToken'] . "';
                    const instantSearchDropdownInputWaitTime  = parseInt(" . INSTANT_SEARCH_DROPDOWN_INPUT_WAIT_TIME . ");
                    const instantSearchDropdownInputMinLength = parseInt(" . INSTANT_SEARCH_DROPDOWN_MIN_WORDSEARCH_LENGTH . ");
                    const instantSearchDropdownInputSelector  = '" . str_replace("'", "\'", INSTANT_SEARCH_DROPDOWN_INPUT_BOX_SELECTOR) . ":not([type=hidden])';
                </script>
                <script src=\"" . DIR_WS_TEMPLATE . "jscript/" . "instant_search_dropdown.min.js\"></script>
            ";
        }

        if ($current_page_base === FILENAME_INSTANT_SEARCH_RESULT) {
            echo "
                <script title='auto.instant_search_observer.php " . __LINE__ . "'>
                    const loadingResultsText = '" . TEXT_LOADING_RESULTS . "';
                    const noProductsFoundText = '" . TEXT_NO_PRODUCTS_FOUND . "';
                    const instantSearchResultSecurityToken = '" . $_SESSION['securityToken'] . "';
                </script>
            ";
            echo "<script src=\"" . DIR_WS_TEMPLATE . "jscript/" . "instant_search_results.min.js\"></script>";
        }

        if (defined('INSTANT_SEARCH_PAGE_ENABLED') && INSTANT_SEARCH_PAGE_ENABLED === 'true') {
            $instantSearchZcSearchResultPageName = zen_get_zcversion() >= '1.5.8' ? FILENAME_SEARCH_RESULT : FILENAME_ADVANCED_SEARCH_RESULT;
            $instantSearchFormSelector = "form[action*=$instantSearchZcSearchResultPageName]:not([name=search]):not([name=advanced_search])";

            // Replace the search forms' "action"
            echo "
                <script title='auto.instant_search_observer.php " . __LINE__ . "'>
                    document.addEventListener('DOMContentLoaded', () => {
                        // Replace the search forms' action
                        const instantSearchFormPageInputs = document.querySelectorAll(`$instantSearchFormSelector input[value=\"$instantSearchZcSearchResultPageName\"]`);
                        instantSearchFormPageInputs.forEach(input => input.value = \"" . FILENAME_INSTANT_SEARCH_RESULT . "\");

                        const instantSearchFormSearchDescrInputs = document.querySelectorAll(`$instantSearchFormSelector input[name=\"search_in_description\"]`);
                        instantSearchFormSearchDescrInputs.forEach(input => input.remove());

                        const instantSearchForms = document.querySelectorAll(`$instantSearchFormSelector`);
                        instantSearchForms.forEach(form => form.action = form.action.replace('$instantSearchZcSearchResultPageName', '" . FILENAME_INSTANT_SEARCH_RESULT . "'));
                    });
                </script>
            ";
        }
    }

    /**
     * If using the Instant Search results page, this modifies the browser title bar to include the keyword
     *
     * $zco_notifier->notify('NOTIFY_MODULE_META_TAGS_UNSPECIFIEDPAGE', $current_page_base, $metatag_page_name, $meta_tags_over_ride, $metatags_title, $metatags_description, $metatags_keywords);
     *
     * @param $class
     * @param $eventID
     * @param $p1 $current_page_base
     * @param $p2 $metatag_page_name
     * @param $p3 $meta_tags_over_ride
     * @param $p4 $metatags_title
     * @param $p5 $metatags_description
     * @param $p6 $metatags_keywords
     * @return void
     */
    public function updateNotifyModuleMetaTagsUnspecifiedpage(&$class, $eventID, $p1, &$p2, &$p3, &$p4, &$p5, &$p6): void
    {
        global $current_page_base;

        if ($current_page_base === FILENAME_INSTANT_SEARCH_RESULT && !empty($_GET['keyword'])) {
            $p3 = true;
            $p4 = NAVBAR_TITLE . ' -> ' . zen_output_string_protected($_GET['keyword']) . ' ' . PRIMARY_SECTION . TITLE . TAGLINE;
        }
    }
}
