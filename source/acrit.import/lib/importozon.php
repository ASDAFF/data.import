<?php

namespace Acrit\Import;

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::includeModule('acrit.import');

/**
 * Import from OZON
 */
class ImportOzon extends Import
{
	private $source_client;
	private $source_token;
	private $arApiCategories;
	private $arApiCategoriesPath;
	private static $arAttributeList;

	const API_URI = 'https://api-seller.ozon.ru';

	public static function getProfileParams()
	{
		$arParams                         = parent::getProfileParams();
		$arParams['SOURCE_LOGIN']['name'] = GetMessage("ACRIT_IMPORT_OZON_FIELDS_SOURCE_LOGIN_NAME");
		$arParams['SOURCE_KEY']['name']   = GetMessage("ACRIT_IMPORT_OZON_FIELDS_SOURCE_KEY_NAME");
		$arParams['SOURCE']['default']    = self::API_URI;
		$arParams['SOURCE']['display']    = false;
		$arParams['ENCODING']['default']  = 'UTF-8';
		$arParams['ENCODING']['display']  = false;
		return $arParams;
	}

	/**
	 * Populating class fields with authorization information from a Profile
	 * (auxiliary function of this class)
	 */
	public function fillAuthData()
	{
		if (!$this->arProfile['SOURCE_LOGIN']) {
			throw new \Exception(GetMessage("ACRIT_IMPORT_OZON_ERROR_CLIENTID"));
		}
		if (!$this->arProfile['SOURCE_KEY']) {
			throw new \Exception(GetMessage("ACRIT_IMPORT_OZON_ERROR_APIKEY"));
		}
		$this->source_client = $this->arProfile['SOURCE_LOGIN'];
		$this->source_token  = $this->arProfile['SOURCE_KEY'];
	}

	/**
	 * Obtaining subparameters (mandatory function of the module)
	 */
	public function fieldsPreParams()
	{
		$arFieldsParams                      = [
			'title' => GetMessage("ACRIT_IMPORT_OZON_FIELDSPRE_TITLE")
		];
		$arFieldsParams['fields']['section'] = [
			'DB_FIELD'    => 'PARAM_1',
			'TYPE'        => 'list',
			'LIST'        => $this->fieldsPreParamsCategs(),
			'DEFAULT'     => '',
			'LABEL'       => GetMessage("ACRIT_IMPORT_OZON_FIELDSPRE_PARAM_1"),
			'PLACEHOLDER' => '',
			'HINT'        => '',
		];
//        $arFieldsParams['fields']['limit'] = array(
//            'DB_FIELD' => 'PARAM_2',
//            'TYPE' => 'boolean',
//            'DEFAULT' => '0',
//            'LABEL' => GetMessage("ACRIT_IMPORT_OZON_FIELDSPRE_PARAM_2"),
//            'PLACEHOLDER' => '',
//            'HINT' => '',
//        );
		return $arFieldsParams;
	}

	public function fieldsPreParamsCategs()
	{
		$this->fillAuthData();
		$categs    = [
			''    => GetMessage("ACRIT_IMPORT_OZON_PREPARAMSCATEGS_NULL"),
			'all' => GetMessage("ACRIT_IMPORT_OZON_PREPARAMSCATEGS_ALL"),
		];
		$path_list = $this->getCategoriesPathList();
		foreach ($path_list as $categ_id => $path) {
			$titles = [];
			foreach ($path as $item) {
				$titles[] = $item['title'];
			}
			$categs[$categ_id] = implode(' / ', $titles);
		}
		asort($categs);
		return $categs;
	}

	/**
	 * Get the field list for the second step in the import profile
	 * (mandatory function of the module)
	 */
	public function fields()
	{
		$arSourceFields = [];
		$this->fillAuthData();
		// List of basic parameters
		$arAdsFields = [
			'id'                    => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ID"),
			'name'                  => GetMessage("ACRIT_IMPORT_OZON_FIELDS_NAME"),
			'offer_id'              => GetMessage("ACRIT_IMPORT_OZON_FIELDS_OFFER_ID"),
			'barcode'               => GetMessage("ACRIT_IMPORT_OZON_FIELDS_BARCODE"),
			'marketing_price'       => GetMessage("ACRIT_IMPORT_OZON_FIELDS_MARKETING_PRICE"),
			'old_price'             => GetMessage("ACRIT_IMPORT_OZON_FIELDS_OLD_PRICE"),
			'price'                 => GetMessage("ACRIT_IMPORT_OZON_FIELDS_PRICE"),
			'price_index'           => GetMessage("ACRIT_IMPORT_OZON_FIELDS_PRICE_INDEX"),
			'premium_price'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_PREMIUM_PRICE"),
			'buybox_price'          => GetMessage("ACRIT_IMPORT_OZON_FIELDS_BUYBOX_PRICE"),
			'min_ozon_price'        => GetMessage("ACRIT_IMPORT_OZON_FIELDS_MIN_OZON_PRICE"),
			'min_price'             => GetMessage("ACRIT_IMPORT_OZON_FIELDS_MIN_PRICE"),
			'recommended_price'     => GetMessage("ACRIT_IMPORT_OZON_FIELDS_RECOMMENDED_PRICE"),
			//	        'status' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_STATUS"),
			'category_id'           => GetMessage("ACRIT_IMPORT_OZON_FIELDS_CATEGORY_ID"),
			'category_name'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_CATEGORY_NAME"),
			'category_path'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_CATEGORY_PATH"),
			'color_image'           => GetMessage("ACRIT_IMPORT_OZON_FIELDS_COLOR_IMAGE"),
			//			'commissions' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_COMMISSIONS"),
			//			'created_at' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_CREATED_AT"),
			'images'                => GetMessage("ACRIT_IMPORT_OZON_FIELDS_IMAGES"),
			'primary_image'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_PRIMARY_IMAGE"),
			'images360'             => GetMessage("ACRIT_IMPORT_OZON_FIELDS_IMAGES360"),
			'is_prepayment'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_IS_PREPAYMENT"),
			'is_prepayment_allowed' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_IS_PREPAYMENT_ALLOWED"),
			//			'sources' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_SOURCES"),
			'stocks_coming'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_STOCKS_COMING"),
			'stocks_present'        => GetMessage("ACRIT_IMPORT_OZON_FIELDS_STOCKS_PRESENT"),
			'stocks_reserved'       => GetMessage("ACRIT_IMPORT_OZON_FIELDS_STOCKS_RESERVED"),
			'vat'                   => GetMessage("ACRIT_IMPORT_OZON_FIELDS_VAT"),
			//			'visibility_details' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_VISIBILITY_DETAILS"),
			'visible'               => GetMessage("ACRIT_IMPORT_OZON_FIELDS_VISIBLE"),
			'volume_weight'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_VOLUME_WEIGHT"),
			'attrib_height'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_HEIGHT"),
			'attrib_depth'          => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_DEPTH"),
			'attrib_width'          => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_WIDTH"),
			'attrib_dimension_unit' => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_DIMENSION_UNIT"),
			'attrib_weight'         => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_WEIGHT"),
			'attrib_weight_unit'    => GetMessage("ACRIT_IMPORT_OZON_FIELDS_ATTRIB_WEIGHT_UNIT"),
		];
		// Characteristics
		$category_id = $this->arProfile['SOURCE_PARAM_1'];
		if ($category_id) {
			if ($category_id == 'all') {
				$arAttribList = $this->getAllCategsAttributeList();
			} else {
				$arAttribList = $this->getAttributeList([$category_id]);
			}
			if (!empty($arAttribList)) {
				foreach ($arAttribList as $arItem) {
					$arAdsFields['attrib_' . $arItem['id']] = $arItem['name'];
				}
			}
		}
		// Sample data
		$arItemExample = $this->getExample();
		// List of linked fields
		$this->fillFieldsMap();
		// Fields output
		foreach ($arAdsFields as $k => $name) {
			$arSourceFields[$k] = [
				'ID'      => $k,
				'NAME'    => $name,
				'EXAMPLE' => $arItemExample[$k],
			];
			// Fields autocomplete
			if (strpos($k, 'attrib_') === 0 && !isset($this->arFieldsMap[$k])) {
				$attr_key = str_replace('attrib_', '', $k);
				if ($arAttribList[$attr_key]['is_collection']) {
					$arSourceFields[$k]['SAVED_FIELD'] = 'CREATE_PROP__STRING__MULT';
				} else {
					$arSourceFields[$k]['SAVED_FIELD'] = 'CREATE_PROP__STRING';
				}
			}
		}
		return $arSourceFields;
	}

	public function prepareItem($arItem)
	{
		$arItem['stocks_coming']   = $arItem['stocks']['coming'];
		$arItem['stocks_present']  = $arItem['stocks']['present'];
		$arItem['stocks_reserved'] = $arItem['stocks']['reserved'];
		$arItem['category_name']   = $this->getCategoryName($arItem['category_id']);
		$arCategPath               = $this->getCategoryPath($arItem['category_id']);
		$arCategPathNames          = [];
		foreach ($arCategPath as $arPathItem) {
			$arCategPathNames[] = $arPathItem['title'];
		}
		$arItem['category_path'] = $arCategPathNames;
		return $arItem;
	}

	/**
	 * List of fields examples
	 */
	public function getExample()
	{
		$arItemExample = [];
		$category_id   = $this->arProfile['SOURCE_PARAM_1'];
		$arRespProd    = $this->getProducts([], $category_id, 1);
		if ($arRespProd) {
			$arItem        = $arRespProd[0];
			$arItemExample = $this->prepareItem($arItem);
			foreach ($arItemExample as $k => $value) {
				if (is_array($value)) {
					$arItemExample[$k] = implode(' | ', $value);
				}
			}
		}
		return $arItemExample;
	}

	/**
	 * Calculate the total number of imported items
	 * (mandatory function of the module)
	 */
	public function count()
	{
		$this->fillAuthData();
		$count       = 0;
		$category_id = $this->arProfile['SOURCE_PARAM_1'];
		if ($category_id && $category_id != 'all') {
			$arProducts = $this->getProducts([], $category_id);
			if ($arProducts) {
				$count = count($arProducts);
			}
		} else {
			$arRes = $this->request('/v2/product/list', [
				'limit' => 1,
			]);
			if ($arRes['result']['total']) {
				$count = $arRes['result']['total'];
			}
		}
		return $count;
	}

	/**
	 * Import of the next batch of goods (one step of import)
	 * (mandatory function of the module)
	 * $limit - the number of items imported in a given step
	 * $next_item - the item you want to start the import with
	 */
	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		$this->initLog();
		\CModule::IncludeModule('iblock');
		$this->fillAuthData();
		$this->obLog->add('(import) next_item: ' . $next_item, \Acrit\Import\Log::TYPE_MESSAGE);
		$item_end = $next_item;
		$i        = 0;
		if ($type != self::STEP_NO && $limit) {
			$item_start = $next_item;
			if ($type == self::STEP_BY_TYME) {
				$step_time  = 250;
				$start_time = time();
			} elseif ($type == self::STEP_BY_COUNT) {
				$step_limit = $limit;
				$j          = 0;
			}
			$category_id = $this->arProfile['SOURCE_PARAM_1'];
			// Import data
			\CAcritImport::Log('(import) fill attributes list');
			$this->getAllCategsAttributeList();
			\CAcritImport::Log('(import) start get products');
			$arProducts = $this->getProducts([
				'limit' => 50,
			], $category_id);
			$this->obLog->add('(import) arProducts: ' . count($arProducts), \Acrit\Import\Log::TYPE_MESSAGE);
			if ($arProducts) {
				foreach ($arProducts as $arItem) {
					if ($i >= $item_start) {
						// Element import
						$arRow = $this->prepareItem($arItem);
						$this->saveIBData($arRow, $next_item);
						$j++;
					}
					$i++;
					if ($type == self::STEP_BY_TYME && $this->isRunoutTime($start_time, $step_time)) {
						break;
					} elseif ($type == self::STEP_BY_COUNT && $j > $step_limit) {
						break;
					}
				}
			}
			$item_end = $i;
		}
		$this->obLog->add('(import) item_end: ' . $item_end, \Acrit\Import\Log::TYPE_MESSAGE);
		return $item_end;
	}

	/**
	 * Time control
	 */
	public function isRunoutTime($start_time, $step_time)
	{
		$result    = false;
		$exec_time = time() - $start_time;
		if ($step_time && $exec_time > $step_time) {
			$result = true;
		}
		return $result;
	}

	/**
	 * Product List
	 */
	public function getProducts($arParams = [], $category_id = false, $limit = 0, $attribs = true)
	{
		$next_product = '';
		$arList       = [];
		$i            = 0;
		do {
			$arParamsDefault = [
				'limit'   => 50,
				'last_id' => $next_product,
			];
			$arProductsID    = $this->getProductsID(array_merge($arParamsDefault, $arParams), $next_product);
			/** @noinspection SlowArrayOperationsInLoopInspection */
			$arList = array_merge($arList, $this->getProductsInfo($arProductsID, $category_id, $limit, $attribs));
			$i++;
		} while ($arProductsID && !empty($arProductsID) && (!$limit || count($arList) < $limit) && $i < 1000);
		return $arList;
	}

	/**
	 * Product List (IDs)
	 *
	 * @param $arParams - API query parameters
	 * @param $next_product - ID of the last sample item
	 *
	 * @return false|mixed
	 */
	public function getProductsID($arParams = [], &$next_product = '')
	{
		$this->initLog();
		$arResult     = false;
		$arProductsID = [];
		$arResp       = $this->request('/v2/product/list', $arParams);
		if ($arResp['result']['items']) {
			foreach ($arResp['result']['items'] as $arItem) {
				$arProductsID[] = $arItem['product_id'];
			}
			$arResult     = $arProductsID;
			$next_product = $arResp['result']['last_id'];
		} else {
			if (!$arResp['result']) {
				$this->obLog->add('Get products IDs error: ' . $arResp['message'] . ' [' . $arResp['code'] . ']', \Acrit\Import\Log::TYPE_ERROR);
			}
		}
		return $arResult;
	}

	/**
	 * Product List (Data)
	 *
	 * @param $arProductsID - list of product identifiers
	 *
	 * @return false|mixed
	 */
	public function getProductsInfo($arProductsID = [], $category_id = false, $limit = 0, $attribs = true)
	{
		$arResult = [];
		if ($category_id == 'all') {
			$category_id = false;
		}
		if (is_array($arProductsID) && !empty($arProductsID)) {
			// Basic data
			$arResp = $this->request('/v2/product/info/list', [
				'product_id' => $arProductsID,
			]);
			if ($arResp['result']['items']) {
				$arProducts = [];
				foreach ($arResp['result']['items'] as $arItem) {
					$arProducts[$arItem['id']] = $arItem;
				}
				if ($attribs) {
					// Feature List (at once for all products of the category)
					if ($category_id) {
						$arAttribList = $this->getAttributeList([$category_id]);
					}
					// Feature data
					$arProdsAttribs = $this->getProductAttributes($arProductsID);
					foreach ($arProdsAttribs as $arProdAttribs) {
						$arProduct = $arProducts[$arProdAttribs['id']];
						// Feature List (for each product individually)
						if (!$category_id) {
							$arAttribList = $this->getAttributeList([$arProduct['category_id']]);
						}
						if (!$category_id || $arProduct['category_id'] == $category_id) {
							$arResProd = $arProduct;
							// Supplement the list of fields by the list of characteristics
							if (!empty($arAttribList)) {
								foreach ($arAttribList as $attrib_field) {
									$attrib_id                         = $attrib_field['id'];
									$arResProd['attrib_' . $attrib_id] = '';
								}
							}
							// Write in the fields these characteristics
							foreach ($arProdAttribs as $attrib_id => $attrib_value) {
								if (is_array($attrib_value) && count($attrib_value) == 1) {
									$attrib_value = $attrib_value[0];
								}
								$arResProd['attrib_' . $attrib_id] = $attrib_value;
							}
							$arResult[] = $arResProd;
							if ($limit && count($arResult) >= $limit) {
								break;
							}
						}
					}
				} else {
					$arResult = $arProducts;
				}
			} else {
				if (!$arResp['result']) {
					$this->obLog->add('Get products info error: ' . $arResp['message'] . ' [' . $arResp['code'] . ']', \Acrit\Import\Log::TYPE_ERROR);
				}
			}
		}
		return $arResult;
	}

	public function getCategoryName($category_id)
	{
		$result = false;
		if ($category_id) {
			if ($this->arApiCategories[$category_id]) {
				$result = $this->arApiCategories[$category_id];
			} else {
				$arResp = $this->request('/v2/category/tree', [
					'category_id' => $category_id,
				]);
				if ($arResp['result']) {
					$result                              = $arResp['result'][0]['title'];
					$this->arApiCategories[$category_id] = $result;
				}
			}
		}
		return $result;
	}


	/**
	 * Attributes list of category
	 */
	public function getAttributeList($category_ids)
	{
		$result = [];
		$step   = 20;

		if (self::$arAttributeList === null) {
			self::$arAttributeList = [];
		}
		\CAcritImport::Log('(getAttributeList) arAttributeList ' . (is_countable(self::$arAttributeList) ? count(self::$arAttributeList) : 0));

		for ($i = 0, $iMax = count($category_ids); $i < $iMax; $i += $step) {
			$categs_ids_part = array_slice($category_ids, $i, $step);
			// Attributes cache
			$categs_ids_part_empty = [];
			foreach ($categs_ids_part as $category_id) {
				if (isset(self::$arAttributeList[$category_id])) {
					/** @noinspection SlowArrayOperationsInLoopInspection */
					$result = array_merge($result, self::$arAttributeList[$category_id]);
				} else {
					$categs_ids_part_empty[] = $category_id;
				}
			}
			// Get attributes
			\CAcritImport::Log('(getAttributeList) attribs categs empty ' . count($categs_ids_part_empty));
			if (!empty($categs_ids_part_empty)) {
				$res = $this->request('/v3/category/attribute', [
					'category_id' => $categs_ids_part_empty,
				]);
				if ($res['result']) {
					foreach ($res['result'] as $category) {
						foreach ($category['attributes'] as $attribute) {
							self::$arAttributeList[$category['category_id']][$attribute['id']] = $attribute;
							$result[$attribute['id']]                                          = $attribute;
						}
					}
				}
			}
		}
		return $result;
	}


	/**
	 * Attributes list of all categories
	 */
	public function getAllCategsAttributeList()
	{
		// Categories containing products
		$products = $this->getProducts([
			'limit' => 100,
		], false, 0, false);

		$categs_list = [];
		foreach ($products as $product) {
			$categs_list[] = $product['category_id'];
		}
		$categs_list = array_unique($categs_list);
//		// List of categories
//		$categs_path = $this->getCategoriesPathList();
//		$categs_list = array_keys($categs_path);
//		$categs_list = array_slice($categs_list, 0, 50);
//		$categs_list[] = 17030803;
		// Category attributes
		$result = $this->getAttributeList($categs_list);
		return $result;
	}


	/**
	 * Attributes list of product
	 */
	public function getProductAttributes($arProductsID)
	{
		$result = false;
		$res    = $this->request('/v3/products/info/attributes', [
			'filter' => [
				'product_id' => $arProductsID,
			],
			'limit'  => 1000,
		]);
		if ($res['result']) {
			foreach ($res['result'] as $i => $product) {
				$result[$i] = $product;
				unset($result[$i]['attributes']);
				foreach ($product['attributes'] as $attribute) {
					$values = [];
					foreach ($attribute['values'] as $attr_value) {
						$values[] = $attr_value['value'];
					}
					$result[$i][$attribute['attribute_id']] = $values;
				}
			}
		}
		return $result;
	}


	/**
	 * Get a list of categories with paths from the root
	 */
	public function getCategoryPath($category_id)
	{
		$result      = false;
		$arCategPath = $this->getCategoriesPathList();
		if ($arCategPath[$category_id]) {
			$result = $arCategPath[$category_id];
		}
		return $result;
	}

	public function getCategoriesPathList()
	{
		if (!empty($this->arApiCategoriesPath)) {
			$result = $this->arApiCategoriesPath;
		} else {
			$res        = $this->request('/v2/category/tree');
			$categories = $res['result'];
			$result     = $this->getCategoriesPath([], $categories);
		}
		return $result;
	}

	public function getCategoriesPath($list, $categories, $path = [])
	{
		$level = count($path) - 1;
		foreach ($categories as $category) {
			if (empty($category['children'])) {
				$list[$category['category_id']] = array_merge($path, [$category]);
			} else {
				$path[$level] = [
					'category_id' => $category['category_id'],
					'title'       => $category['title'],
				];
				foreach ($category['children'] as $sub_category) {
					$sub_cat_item = [
						'category_id' => $sub_category['category_id'],
						'title'       => $sub_category['title'],
					];
					$list         = $this->getCategoriesPath($list, $sub_category['children'], array_merge($path, [$sub_cat_item]));
				}
			}
		}
		return $list;
	}

	/**
	 * API request
	 */
	public function request($method, $json = false, $type = 'post')
	{
		$curl        = curl_init();
		$url         = self::API_URI . $method;
		$headers[]   = 'Client-Id: ' . $this->source_client;
		$headers[]   = 'Api-Key: ' . $this->source_token;
		$headers[]   = 'Content-Type: application/json';
		/** @noinspection CurlSslServerSpoofingInspection */
		$curl_params = [
			CURLOPT_SSL_VERIFYPEER => false,
			CURLOPT_SSL_VERIFYHOST => false,
			CURLOPT_HEADER         => false,
			CURLOPT_CUSTOMREQUEST  => 'POST',
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL            => $url,
		];
		$query_data  = http_build_query([]);
		if ($type == 'post') {
			$curl_params[CURLOPT_POST]       = 1;
			$curl_params[CURLOPT_POSTFIELDS] = $query_data;
		} else {
			$curl_params[CURLOPT_URL] = $url . '?' . $query_data;
		}
		if ($json) {
			$curl_params[CURLOPT_POST]       = 1;
			$curl_params[CURLOPT_POSTFIELDS] = json_encode($json);
		}
		curl_setopt_array($curl, $curl_params);
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		$response = curl_exec($curl);
		curl_close($curl);
		$result = json_decode($response, true);
		usleep(100000);
		return $result;
	}
}
