<?php  //steve for POSM and for debugging
/**
 * @package  Instant Search Plugin for Zen Cart
 * @author   marco-pm
 * @version  4.0.3
 * @see      https://github.com/marco-pm/zencart_instantsearch
 * @license  GNU Public License V2.0
 */

declare(strict_types=1);

namespace Zencart\Plugins\Catalog\InstantSearch\SearchEngineProviders;

class MysqlSearchEngineProvider extends \base implements SearchEngineProviderInterface
{
//steve for logfile
    protected bool $debugInstantSearch = false;
//
    /**
     * Array of product fields (keys) with the corresponding sql build method (values).
     *
     * @var array
     */
    protected const FIELDS_TO_BUILD_METHODS = [
        'category'         => ['buildSqlProductCategory'],
        'manufacturer'     => ['buildSqlProductManufacturer'],
        'meta-keywords'    => ['buildSqlProductMetaKeywords'],
//steve added POSM
        'model-broad'      => ['buildSqlProductModelBroadPOSM',
                               'buildSqlProductModelBroad'],
//eof
        'model-exact'      => ['buildSqlProductModelExact'],
        'name'             => ['buildSqlProductNameBegins',
                               'buildSqlProductNameWithoutDescription',
                               'buildSqlProductNameContains'],
        'name-description' => ['buildSqlProductNameBegins',
                               'buildSqlProductNameWithDescription',
                               'buildSqlProductNameContains'],
    ];

    /**
     * Use Query Expansion in the Full-Text searches.
     *
     * @var bool
     */
    protected bool $useQueryExpansion;

    /**
     * Alphabetical filter id.
     *
     * @var null|int
     */
    protected ?int $alphaFilter;

    /**
     * Array of search results.
     *
     * @var array
     */
    protected array $results;

    /**
     * Constructor.
     *
     * @param bool $useQueryExpansion
     */
    public function __construct(bool $useQueryExpansion = true)
    {
        $this->useQueryExpansion = $useQueryExpansion;
        $this->alphaFilter = null;
        $this->results = [];
//steve for logfile
        if ($this->debugInstantSearch) $this->logInstantSearch('', true);
//eof
    }

    /**
     * Searches for $queryText and returns the results.
     *
     * @param string $queryText
     * @param array $productFieldsList
     * @param int $productsLimit
     * @param int $categoriesLimit
     * @param int $manufacturersLimit
     * @param int|null $alphaFilter
     * @return array
     */
    public function search(
        string $queryText,
        array $productFieldsList,
        int $productsLimit,
        int $categoriesLimit = 0,
        int $manufacturersLimit = 0,
        int $alphaFilter = null
    ): array {
        $this->alphaFilter = $alphaFilter ?? 0;

        $sqlSequence = $this->buildSqlSequence($productFieldsList);

        // Run the sequence of database queries for products, until we have enough results
        foreach ($sqlSequence as $sql) {
            if (count($this->results) >= $productsLimit) {
                break;
            }
            $result = $this->searchProducts($sql, $queryText, $productsLimit - count($this->results));
            if (!empty($result)) {
                array_push($this->results, ...$result);
            }
        }

        if ($categoriesLimit > 0) {
            $result = $this->searchCategories($queryText, $categoriesLimit);
            if (!empty($result)) {
                array_push($this->results, ...$result);
            }
        }

        if ($manufacturersLimit > 0) {
            $result = $this->searchManufacturers($queryText, $manufacturersLimit);
            if (!empty($result)) {
                array_push($this->results, ...$result);
            }
        }

        return $this->results;
    }

    /**
     * Builds the sequence of database queries for products.
     * Note: validation of $productFieldsList is made by the InstantSearchConfigurationValidation class, therefore
     * we don't manage Exceptions while reading the list here.
     *
     * @param array $productFieldsList
     * @return array
     */
    protected function buildSqlSequence(array $productFieldsList): array
    {
        $sqlSequence = [];

        foreach ($productFieldsList as $field) {
            foreach (static::FIELDS_TO_BUILD_METHODS[$field] as $buildMethod) {
                $sqlSequence[] = $this->$buildMethod();
            }
        }

        return $sqlSequence;
    }

    /**
     * Search the products' fields.
     *
     * @param string $sql
     * @param string $queryText
     * @param int $limit
     * @return array
     */
    protected function searchProducts(string $sql, string $queryText, int $limit): array
    {
        global $db;

//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof
        $foundIds = implode(',', array_column($this->results, 'products_id'));

        $searchQueryPreg = preg_replace('/\s+/', ' ', preg_quote($queryText, '&'));
        $searchQueryRegexp = str_replace(' ', '|', $searchQueryPreg);

        // Remove all non-word characters and add wildcard operator for boolean mode search
        $searchBooleanQuery = str_replace(' ', '* ', trim(preg_replace('/[^\p{L}\p{N}_]+/u', ' ', $queryText))) . '*';

        // Prepare the sql
        $sql = $db->bindVars($sql, ':searchBooleanQuery', $searchBooleanQuery, 'string');
        $sql = $db->bindVars($sql, ':searchQuery', $queryText, 'string');
        $sql = $db->bindVars($sql, ':searchBeginsQuery', $queryText . '%', 'string');
        $sql = $db->bindVars($sql, ':regexpQuery', $searchQueryRegexp, 'string');
        $sql = $db->bindVars($sql, ':languageId', $_SESSION['languages_id'], 'integer');
        $sql = $db->bindVars($sql, ':foundIds', $foundIds ?? "''", 'inConstructInteger');
        $sql = $db->bindVars($sql, ':alphaFilter', chr($this->alphaFilter) . '%', 'string');
        $sql = $db->bindVars($sql, ':resultsLimit', $limit, 'integer');
//steve added
        $sql = $db->bindVars($sql, ':searchLikeQuery', '%' . $queryText . '%', 'string');
//eof

        $this->notify('NOTIFY_INSTANT_SEARCH_MYSQL_PRODUCTS_BEFORE_SQL', $queryText, $sql, $limit, $this->alphaFilter);
//steve for logfile
        if ($this->debugInstantSearch) {
            $debugInfo =
                '$queryText=' . $queryText . PHP_EOL .
                '$sql=' . $sql . PHP_EOL .
                '$limit=' . $limit . PHP_EOL .
                '$this->alphaFilter=' . $this->alphaFilter;
            $this->logInstantSearch($debugInfo);
        }
//eof

        // Run the sql
        $dbResults = $db->Execute($sql);
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch($dbResults->RecordCount() . ' results from query' . PHP_EOL . '-------------------------' . PHP_EOL);
        }
//eof

        // Save the results
        $results = [];
        foreach ($dbResults as $dbResult) {
//steve for logfile
            if ($this->debugInstantSearch) $this->logInstantSearch(print_r($dbResult, true) . PHP_EOL);
//eof
            $results[] = $dbResult;
        }

        return $results;
    }

    /**
     * Builds the Full-Text search sql on product name and (optionally) description.
     *
     * @param bool $includeDescription
     * @return string
     */
    protected function buildSqlProductNameDescription(bool $includeDescription = true): string
    {
        $queryExpansion = $this->useQueryExpansion === true ? ' WITH QUERY EXPANSION' : '';
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . ': $includeDescription=' . $includeDescription . ', $queryExpansion=' . $queryExpansion . ', '. PHP_EOL);
        }
//eof

//steve removed boolean stuff from query, changed search term to searchLikeQuery which has % % around it
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                MATCH(pd.products_name) AGAINST(:searchQuery " . $queryExpansion . ") AS name_relevance_natural " .
                ($includeDescription === true ? ", MATCH(pd.products_description) AGAINST(:searchQuery " . $queryExpansion . ") AS description_relevance " : "") . "
            FROM
                " . TABLE_PRODUCTS_DESCRIPTION . " pd
                JOIN " . TABLE_PRODUCTS . " p ON (p.products_id = pd.products_id)
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : "") . "
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
                AND (
                    (pd.products_name LIKE :searchLikeQuery)" .
                    ($includeDescription === true ? " OR pd.products_description LIKE :searchLikeQuery " . $queryExpansion : "") . "
                )
            ORDER BY
                name_relevance_natural DESC,
                " . ($includeDescription === true ? " description_relevance DESC, " : "") . "
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Builds the Full-Text search sql on product name and description.
     *
     * @return string
     */
    protected function buildSqlProductNameWithDescription(): string
    {
        return $this->buildSqlProductNameDescription();
    }

    /**
     * Builds the Full-Text search sql on product name (no description).
     *
     * @return string
     */
    protected function buildSqlProductNameWithoutDescription(): string
    {
        return $this->buildSqlProductNameDescription(false);
    }

    /**
     * Builds the LIKE/REGEXP search sql on product name.
     *
     * @param bool $beginsWith
     * @return string
     */
    protected function buildSqlProductName(bool $beginsWith = true): string
    {
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . ': $beginsWith=' . $beginsWith . PHP_EOL);
        }
//eof
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                IFNULL(cpv.total_views, 0) AS total_views
            FROM
                " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id)
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
                LEFT JOIN (
                    SELECT
                        product_id,
                        SUM(views) AS total_views
                    FROM
                        " . TABLE_COUNT_PRODUCT_VIEWS . "
                    WHERE
                        language_id = :languageId
                    GROUP BY
                        product_id
                ) cpv ON (p.products_id = cpv.product_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : " ") . "
                AND pd.products_name " . ($beginsWith === true ? " LIKE :searchBeginsQuery " : " REGEXP :regexpQuery ") . "
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
            ORDER BY
                IFNULL(cpv.total_views, 0) DESC,
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Builds the LIKE search sql on product name.
     *
     * @return string
     */
    protected function buildSqlProductNameBegins(): string
    {
        return $this->buildSqlProductName();
    }

    /**
     * Builds the REGEXP search sql on product name.
     *
     * @return string
     */
    protected function buildSqlProductNameContains(): string
    {
        return $this->buildSqlProductName(false);
    }

    /**
     * Builds the LIKE/REGEXP search sql on product model.
     *
     * @param bool $exactMatch
     * @return string
     */
    protected function buildSqlProductModel(bool $exactMatch = true): string
    {
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . ': $exactMatch=' . $exactMatch . PHP_EOL);
        }
//eof
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                IFNULL(cpv.total_views, 0) AS total_views
            FROM
                " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id)
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
                LEFT JOIN (
                    SELECT
                        product_id,
                        SUM(views) AS total_views
                    FROM
                        " . TABLE_COUNT_PRODUCT_VIEWS . "
                    WHERE
                        language_id = :languageId
                    GROUP BY
                        product_id
                ) cpv ON (p.products_id = cpv.product_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : " ") . "
                AND p.products_model " . ($exactMatch === true ? "= :searchQuery" : "REGEXP :regexpQuery") . "
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
            ORDER BY
                IFNULL(cpv.total_views, 0) DESC,
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Builds the LIKE/REGEXP search sql on product model (exact match).
     *
     * @return string
     */
    protected function buildSqlProductModelExact(): string
    {
        return $this->buildSqlProductModel();
    }

    /**
     * Builds the LIKE/REGEXP search sql on product model (broad match).
     *
     * @return string
     */
    protected function buildSqlProductModelBroad(): string
    {
        return $this->buildSqlProductModel(false);
    }

    /**
     * Builds the REGEXP search sql on product meta keywords.
     *
     * @return string
     */
    protected function buildSqlProductMetaKeywords(): string
    {
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                IFNULL(cpv.total_views, 0) AS total_views
            FROM
                " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id)
                JOIN " . TABLE_META_TAGS_PRODUCTS_DESCRIPTION . " mtpd ON (
                    p.products_id = mtpd.products_id
                    AND mtpd.language_id = :languageId
                )
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
                LEFT JOIN (
                    SELECT
                        product_id,
                        SUM(views) AS total_views
                    FROM
                        " . TABLE_COUNT_PRODUCT_VIEWS . "
                    WHERE
                        language_id = :languageId
                    GROUP BY
                        product_id
                ) cpv ON (p.products_id = cpv.product_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : " ") . "
                AND (mtpd.metatags_keywords REGEXP :regexpQuery)
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
            ORDER BY
                IFNULL(cpv.total_views, 0) DESC,
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Builds the REGEXP search sql on product category (immediate parent category only).
     *
     * @return string
     */
    protected function buildSqlProductCategory(): string
    {
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof
        // recursive if mysql 8
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                IFNULL(cpv.total_views, 0) AS total_views
            FROM
                " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id)
                LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = p.master_categories_id
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
                LEFT JOIN (
                    SELECT
                        product_id,
                        SUM(views) AS total_views
                    FROM
                        " . TABLE_COUNT_PRODUCT_VIEWS . "
                    WHERE
                        language_id = :languageId
                    GROUP BY
                        product_id
                ) cpv ON (p.products_id = cpv.product_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : " ") . "
                AND (cd.categories_name REGEXP :regexpQuery)
                AND cd.language_id = :languageId
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
            ORDER BY
                IFNULL(cpv.total_views, 0) DESC,
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Builds the REGEXP search sql on product manufacturer.
     *
     * @return string
     */
    protected function buildSqlProductManufacturer(): string
    {
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof
        return "
            SELECT
                p.*,
                pd.products_name,
                m.manufacturers_name,
                IFNULL(cpv.total_views, 0) AS total_views
            FROM
                " . TABLE_PRODUCTS . " p
                JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (p.products_id = pd.products_id)
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
                LEFT JOIN (
                    SELECT
                        product_id,
                        SUM(views) AS total_views
                    FROM
                        " . TABLE_COUNT_PRODUCT_VIEWS . "
                    WHERE
                        language_id = :languageId
                    GROUP BY
                        product_id
                ) cpv ON (p.products_id = cpv.product_id)
            WHERE
                p.products_status <> 0 " .
                ($this->alphaFilter > 0 ? " AND pd.products_name LIKE :alphaFilter " : " ") . "
                AND (m.manufacturers_name REGEXP :regexpQuery)
                AND pd.language_id = :languageId
                AND p.products_id NOT IN (:foundIds)
            ORDER BY
                IFNULL(cpv.total_views, 0) DESC,
                p.products_sort_order,
                pd.products_name
            LIMIT
                :resultsLimit
        ";
    }

    /**
     * Search the categories' names.
     *
     * @param string $queryText
     * @param int $limit
     * @return array
     */
    protected function searchCategories(string $queryText, int $limit): array
    {
        global $db;
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof
        $searchQueryPreg = preg_replace('/\s+/', ' ', preg_quote($queryText, '&'));
        $searchQueryRegexp = str_replace(' ', '|', $searchQueryPreg);

        $sql = "
            SELECT
                c.categories_id,
                cd.categories_name,
                c.categories_image
            FROM
                " . TABLE_CATEGORIES . " c
                LEFT JOIN " . TABLE_CATEGORIES_DESCRIPTION . " cd ON cd.categories_id = c.categories_id
            WHERE
                c.categories_status <> 0
                AND (cd.categories_name REGEXP :regexpQuery)
                AND cd.language_id = :languageId
            ORDER BY
                c.sort_order,
                cd.categories_name
            LIMIT
                :resultsLimit
        ";

        $sql = $db->bindVars($sql, ':regexpQuery', $searchQueryRegexp, 'string');
        $sql = $db->bindVars($sql, ':languageId', $_SESSION['languages_id'], 'integer');
        $sql = $db->bindVars($sql, ':resultsLimit', $limit, 'integer');

        $this->notify('NOTIFY_INSTANT_SEARCH_MYSQL_CATEGORIES_BEFORE_SQL', $queryText, $sql, $limit);

        $dbResults = $db->Execute($sql);

        $results = [];
        foreach ($dbResults as $dbResult) {
            $results[] = $dbResult;
        }

        return $results;
    }

    /**
     * Search the manufacturers' names.
     *
     * @param string $queryText
     * @param int $limit
     * @return array
     */
    protected function searchManufacturers(string $queryText, int $limit): array
    {
        global $db;
//steve for logfile
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }
//eof

        $searchQueryPreg = preg_replace('/\s+/', ' ', preg_quote($queryText, '&'));
        $searchQueryRegexp = str_replace(' ', '|', $searchQueryPreg);

        $sql = "
            SELECT
                DISTINCT m.manufacturers_id,
                m.manufacturers_name,
                m.manufacturers_image
            FROM
                " . TABLE_PRODUCTS . " p
                LEFT JOIN " . TABLE_MANUFACTURERS . " m ON m.manufacturers_id = p.manufacturers_id
            WHERE
                p.products_status <> 0
                AND (m.manufacturers_name REGEXP :regexpQuery)
            ORDER BY
                m.manufacturers_name
            LIMIT
                :resultsLimit
        ";

        $sql = $db->bindVars($sql, ':regexpQuery', $searchQueryRegexp, 'string');
        $sql = $db->bindVars($sql, ':resultsLimit', $limit, 'integer');

        $this->notify('NOTIFY_INSTANT_SEARCH_MYSQL_MANUFACTURERS_BEFORE_SQL', $queryText, $sql, $limit);

        $dbResults = $db->Execute($sql);

        $results = [];
        foreach ($dbResults as $dbResult) {
            $results[] = $dbResult;
        }

        return $results;
    }
//steve
    /** debugging log
     * @param $message
     * @param bool $clearLog
     * @return string
     */
    protected function logInstantSearch($message, bool $clearLog = false): string
    {
        $logfilename = DIR_FS_LOGS . '/aaa InstantSearch_debug.log';
        $mode = $clearLog ? 'wb' : 'ab'; // wb: wipe file, binary mode. ab: append, binary mode
        date_default_timezone_set('Europe/Madrid');
        $fp = fopen($logfilename, $mode);
        if ($fp) {
            fwrite($fp, ($clearLog ? date('d/m/Y H:i:s') : $message) . "\n");
            fclose($fp);
        }
        return $logfilename;
    }
//search in POSM table for model
    protected function buildSqlProductModelBroadPOSM(): string
    {
        if ($this->debugInstantSearch) {
            $this->logInstantSearch(__LINE__ . ' fn ' . __FUNCTION__ . PHP_EOL);
        }

        if (!defined('TABLE_PRODUCTS_OPTIONS_STOCK'))
        {
            define('TABLE_PRODUCTS_OPTIONS_STOCK', DB_PREFIX . 'products_options_stock');
        }
        if (!defined('TABLE_PRODUCTS_OPTIONS_STOCK_ATTRIBUTES'))
        {
            define('TABLE_PRODUCTS_OPTIONS_STOCK_ATTRIBUTES', DB_PREFIX . 'products_options_stock_attributes');
        }
        if (!defined('TABLE_PRODUCTS_OPTIONS_STOCK_NAMES'))
        {
            define('TABLE_PRODUCTS_OPTIONS_STOCK_NAMES', DB_PREFIX . 'products_options_stock_names');
        }
        return "
        SELECT
            p.products_id, p.products_image, p.products_model, p.master_categories_id, p.manufacturers_id,
            pd.products_name,
            m.manufacturers_name,
            MAX(posm.pos_model) as pos_model
            FROM products p
             LEFT JOIN " . TABLE_MANUFACTURERS . " m ON (m.manufacturers_id = p.manufacturers_id)
             LEFT JOIN " . TABLE_PRODUCTS_OPTIONS_STOCK . " posm ON (posm.products_id = p.products_id)
             LEFT JOIN " . TABLE_PRODUCTS_DESCRIPTION . " pd ON (pd.products_id = p.products_id)
             WHERE
                pd.language_id = :languageId
             AND
                p.products_status = 1
             AND
                posm.pos_model LIKE :searchLikeQuery
             GROUP BY
             p.products_id, p.products_image, p.products_model, p.master_categories_id,p.manufacturers_id,
             pd.products_name,
             m.manufacturers_name
             ORDER BY posm.pos_model DESC
             LIMIT :resultsLimit
               ";
    }
}
