<?php

namespace Acrit\Import;

use Bitrix\Main\Loader,
	Bitrix\Main\Localization\Loc;

class ImportAdsapi extends Import
{
	private $source_login;
	private $source_token;

	const API_URI = 'https://ads-api.ru/main/';

	public static function getProfileParams()
	{
		$arParams                         = parent::getProfileParams();
		$arParams['SOURCE_LOGIN']['name'] = GetMessage("ACRIT_IMPORT_ADSAPI_FIELDS_SOURCE_LOGIN_NAME");
		$arParams['SOURCE_KEY']['name']   = GetMessage("ACRIT_IMPORT_ADSAPI_FIELDS_SOURCE_KEY_NAME");
		$arParams['SOURCE']['default']    = self::API_URI;
		$arParams['SOURCE']['display']    = false;
		$arParams['ENCODING']['default']  = 'UTF-8';
		$arParams['ENCODING']['display']  = false;
		return $arParams;
	}

	protected function fillProfile($profile_id = 0)
	{
		parent::fillProfile($profile_id);
		// Set default category
		$this->arProfile['SOURCE_PARAM_1'] = $this->arProfile['SOURCE_PARAM_1'] ? (int)$this->arProfile['SOURCE_PARAM_1'] : 1;
	}

	protected function setSource()
	{
		parent::setSource();
		// Check connection
		$query  = $this->getRequestUri('apigetparams') . "&category_id=1";
		$str    = file_get_contents($query);
		$arResp = json_decode($str, true);
		if ($arResp['error']) {
			throw new \Exception($arResp['error']);
		}
	}

	public function getRequestUri($method = 'api')
	{
		$query = self::API_URI . $method . "?user=" . urlencode($this->arProfile['SOURCE_LOGIN']) .
			"&token=" . urlencode($this->arProfile['SOURCE_KEY']);
		return $query;
	}

	public function fillAuthData()
	{
		// Check auth data
		if (!$this->arProfile['SOURCE_LOGIN'] || !$this->arProfile['SOURCE_KEY']) {
			throw new \Exception(GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_EMPTY"));
		}
		// Fill data
		$this->source_login = $this->arProfile['SOURCE_LOGIN'];
		$this->source_token = $this->arProfile['SOURCE_KEY'];
	}

	public function fieldsPreParams()
	{
		$arFieldsParams                      = [
			'title' => GetMessage("ACRIT_IMPORT_ADSAPI_PARAMS_TITLE")
		];
		$arFieldsParams['fields']['section'] = [
			'DB_FIELD'    => 'PARAM_1',
			'TYPE'        => 'list',
			'LIST'        => $this->fieldsPreParamsCategs(),
			'DEFAULT'     => '1',
			'LABEL'       => GetMessage("ACRIT_IMPORT_ADSAPI_PARAMS_PARAM_1"),
			'PLACEHOLDER' => '',
			'HINT'        => '',
		];
		$arFieldsParams['fields']['limit']   = [
			'DB_FIELD'    => 'PARAM_2',
			'TYPE'        => 'number',
			'DEFAULT'     => '0',
			'LABEL'       => GetMessage("ACRIT_IMPORT_ADSAPI_PARAMS_PARAM_2"),
			'PLACEHOLDER' => '',
			'HINT'        => '',
		];
		$arFieldsParams['fields']['filter']  = [
			'DB_FIELD'    => 'PARAM_3',
			'TYPE'        => 'custom',
			'HTML'        => $this->fieldsPreParamsFilter(),
			'LABEL'       => '',
			'PLACEHOLDER' => '',
			'HINT'        => '',
		];
		return $arFieldsParams;
	}

	public function fieldsPreParamsCategs()
	{
		$categs = [
			[
				'1' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_1"),
				'2' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_2"),
				'3' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_3"),
				'4' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_4"),
				'5' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_5"),
				'6' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_6"),
				'7' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_7"),
				'8' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_8"),
			],
			[
				'9'  => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_9"),
				'10' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_10"),
			],
			[
				'12' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_12"),
				'13' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_13"),
				'14' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_14"),
			],
			[
				'21' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_21"),
				'22' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_22"),
				'23' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_23"),
				'24' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_24"),
				'25' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_25"),
			],
			[
				'27' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_27"),
				'28' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_28"),
				'29' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_29"),
				'30' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_30"),
				'31' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_31"),
				'32' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_32"),
				'33' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_33"),
			],
			[
				'34' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_34"),
				'35' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_35"),
				'36' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_36"),
				'37' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_37"),
				'38' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_38"),
				'39' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_39"),
				'40' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_40"),
				'41' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_41"),
				'42' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_42"),
				'43' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_43"),
			],
			[
				'44' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_44"),
				'45' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_45"),
				'46' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_46"),
				'47' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_47"),
				'48' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_48"),
				'49' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_49"),
				'50' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_50"),
				'51' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_51"),
			],
			[
				'52' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_52"),
				'53' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_53"),
				'54' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_54"),
				'55' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_55"),
				'56' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_56"),
				'57' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_57"),
				'58' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_58"),
			],
			[
				'59' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_59"),
				'60' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_60"),
				'61' => GetMessage("ACRIT_IMPORT_ADSAPI_CATEGS_61"),
			],
		];
		return $categs;
	}

	public function fieldsPreParamsFilter()
	{
		\Bitrix\Main\Page\Asset::getInstance()->addString("<style>
#adsapi_filter_form { width: 100%; background-color: #d7e3e7; margin: 5px 0; padding: 10px; }
#adsapi_filter_form td { width: 33.3%; padding: 0 10px 10px 0; vertical-align: top; }
#adsapi_filter_form label { display: block; margin-bottom: 3px; }
#adsapi_filter_form input { width: 40%; }
</style>");
		ob_start();
		?>
		<tr>
			<td colspan="2">
				<table id="adsapi_filter_form">
					<tr>
						<td>
							<div class="form-group">
								<label for="filter_nedvigimost_type"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_SELECT, 'PROFILE[SOURCE_PARAM_3][nedvigimost_type]', $this->arProfile['SOURCE_PARAM_3']['nedvigimost_type'], [
									'id' => 'filter_nedvigimost_type',
								], [
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LIST_0"), 'value' => ''],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LIST_1"), 'value' => '1'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LIST_2"), 'value' => '2'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LIST_3"), 'value' => '3'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_NEDVIGIMOST_TYPE_LIST_4"), 'value' => '4'],
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_q"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_Q_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][q]', $this->arProfile['SOURCE_PARAM_3']['q'], [
									'id'          => 'filter_q',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_Q_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_Q_TITLE"),
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_price1"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PRICE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][price1]', $this->arProfile['SOURCE_PARAM_3']['price1'], [
									'id'          => 'filter_price1',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PRICE1_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PRICE1_TITLE"),
								]);?>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][price2]', $this->arProfile['SOURCE_PARAM_3']['price2'], [
									'id'          => 'filter_price2',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PRICE2_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PRICE2_TITLE"),
								]);?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<div class="form-group">
								<label for="filter_date1"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_DATE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][date1]', $this->arProfile['SOURCE_PARAM_3']['date1'], [
									'id'          => 'filter_date1',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_DATE1_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_DATE1_TITLE"),
								]);?>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][date2]', $this->arProfile['SOURCE_PARAM_3']['date2'], [
									'id'          => 'filter_date2',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_DATE2_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_DATE2_TITLE"),
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_person_type"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_SELECT, 'PROFILE[SOURCE_PARAM_3][person_type]', $this->arProfile['SOURCE_PARAM_3']['person_type'], [
									'id'    => 'filter_person_type',
									'title' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_TITLE"),
								], [
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_LIST_0"), 'value' => ''],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_LIST_1"), 'value' => '1'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_LIST_2"), 'value' => '2'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PERSON_TYPE_LIST_3"), 'value' => '3'],
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_city"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_CITY_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][city]', $this->arProfile['SOURCE_PARAM_3']['city'], [
									'id'          => 'filter_city',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_CITY_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_CITY_TITLE"),
								]);?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<div class="form-group">
								<label for="filter_metro"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_METRO_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][metro]', $this->arProfile['SOURCE_PARAM_3']['metro'], [
									'id'          => 'filter_metro',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_METRO_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_METRO_TITLE"),
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_phone"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PHONE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_TEXT, 'PROFILE[SOURCE_PARAM_3][phone]', $this->arProfile['SOURCE_PARAM_3']['phone'], [
									'id'          => 'filter_phone',
									'placeholder' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PHONE_PLACEHOLDER"),
									'title'       => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_PHONE_TITLE"),
								]);?>
							</div>
						</td>
						<td>
							<div class="form-group">
								<label for="filter_source"><?=GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LABEL");?></label>
								<?=Forms::getElement(Forms::ELEMENT_TYPE_SELECT, 'PROFILE[SOURCE_PARAM_3][source]', $this->arProfile['SOURCE_PARAM_3']['source'], [
									'id' => 'filter_source',
								], [
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_0"), 'value' => ''],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_1"), 'value' => '1'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_2"), 'value' => '2'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_3"), 'value' => '3'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_4"), 'value' => '4'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_5"), 'value' => '5'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_6"), 'value' => '6'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_7"), 'value' => '7'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_8"), 'value' => '8'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_9"), 'value' => '9'],
									['name' => GetMessage("ACRIT_IMPORT_ADSAPI_FILTER_SOURCE_LIST_10"), 'value' => '10'],
								]);?>
							</div>
						</td>
					</tr>
				</table>
			</td>
		</tr>
		<?
//		$add_fields = $this->fieldsPreParamsFilterAdditional();
		$html = ob_get_contents();
		ob_end_clean();
		return $html;
	}

	public function fieldsPreParamsFilterFields()
	{
		$list = [];
		return $list;
	}

	public function fieldsPreParamsFilterAdditional()
	{
		$list        = [];
		$category_id = (int)$this->arProfile['SOURCE_PARAM_1'];
		if (!$category_id) {
			$query = $this->getRequestUri('apigetparams') . '&category_id=' . $category_id;
			$str   = file_get_contents($query);
			$resp  = json_decode($str, true);
			if ($resp['error']) {
				throw new \Exception($resp['error'] . ' [' . $resp['code'] . ']');
			}
			if (!empty($resp['data'])) {
				foreach ($resp['data'] as $item) {
					$row    = $item;
					$list[] = $row;
				}
			}
		}
		return $list;
	}

	protected function fieldsGetParams($arParams, $arFieldsPath, &$arSourceFields)
	{
		$l = count($arFieldsPath);
		foreach ($arParams as $arParam) {
			$arFieldsPath[$l] = [
				'key'   => $arParam['param'],
				'title' => $this->convStrEncoding($arParam['title']),
			];
			// Add value into the profile fields array
			$arTitles = [];
			foreach ($arFieldsPath as $arLevel) {
				$arTitles[] = $arLevel['title'];
			}
			$k                  = $arFieldsPath[$l]['key'];
			$arSourceFields[$k] = [
				'ID'      => $k,
				'NAME'    => implode('. ', $arTitles),
				'EXAMPLE' => '',
			];
			// Process all of the sub parameters
			if (is_array($arParam['values'])) {
				foreach ($arParam['values'] as $arValue) {
					if ($arValue['subparams']) {
						$this->fieldsGetParams($arValue['subparams'], $arFieldsPath, $arSourceFields);
					}
				}
			}
		}
		return $arFieldsPath;
	}

	public function fields()
	{
		$arSourceFields = [];
		$this->fillAuthData();
		$category_id = (int)$this->arProfile['SOURCE_PARAM_1'];
		if ($category_id) {
			// List of main parameters
			$arAdsFields = [
				"id"                   => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_ID"),
				"url"                  => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_URL"),
				"title"                => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_TITLE"),
				"price"                => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PRICE"),
				"time"                 => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_TIME"),
				"phone"                => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PHONE"),
				"phone_operator"       => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PHONE_OPERATOR"),
				"person"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PERSON"),
				"contactname"          => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CONTACTNAME"),
				"person_type"          => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PERSON_TYPE"),
				"person_type_id"       => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PERSON_TYPE_ID"),
				"city"                 => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CITY"),
				"metro"                => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_METRO"),
				"address"              => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_ADDRESS"),
				"description"          => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_DESCRIPTION"),
				"nedvigimost_type"     => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_NEDVIGIMOST_TYPE"),
				"nedvigimost_type_id"  => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_NEDVIGIMOST_TYPE_ID"),
				"avitoid"              => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_AVITOID"),
				"source"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_SOURCE"),
				"source_id"            => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_SOURCE_ID"),
				"images"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_IMAGES"),
				"params"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PARAMS"),
				"cat1_id"              => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CAT1_ID"),
				"cat2_id"              => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CAT2_ID"),
				"cat1"                 => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CAT1"),
				"cat2"                 => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CAT2"),
				"coords"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_COORDS"),
				"region"               => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_REGION"),
				"city1"                => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_CITY1"),
				"param_xxx"            => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PARAM_XXX"),
				"count_ads_same_phone" => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_COUNT_ADS_SAME_PHONE"),
				"phone_protected"      => GetMessage("ACRIT_IMPORT_ADSAPI_SOURCE_FIELD_PHONE_PROTECTED"),
			];
			foreach ($arAdsFields as $k => $name) {
				$arSourceFields[$k] = [
					'ID'      => $k,
					'NAME'    => $name,
					'EXAMPLE' => '',
				];
			}
			// Get additional parameters
			$str          = file_get_contents($this->getRequestUri() . "?user=" . urlencode($this->source_login) .
				"&token=" . urlencode($this->source_token) .
				"&category_id=" . $category_id);
			$arResp       = json_decode($str, true);
			$arFieldsPath = [];
			$this->fieldsGetParams($arResp['data'], $arFieldsPath, $arSourceFields);
		}
		return $arSourceFields;
	}

	public function count()
	{
		$count = 0;
		$this->fillAuthData();
		$category_id = (int)$this->arProfile['SOURCE_PARAM_1'];
		if ($category_id) {
			$query  = $this->getRequestUri() . "?user=" . urlencode($this->source_login) .
				"&token=" . urlencode($this->source_token) .
				"&category_id=" . $category_id;
			$str    = file_get_contents($query);
			$arResp = json_decode($str, true);
			if ($arResp) {
				$count = count($arResp['data']);
			}
			sleep(5);
		}
		$load_limit = (int)$this->arProfile['SOURCE_PARAM_2'];
		if ($load_limit < $count) {
			$count = $load_limit;
		}
		return $count;
	}

	public function import($type = self::STEP_NO, $limit = 0, $next_item = 0)
	{
		parent::onBeforeImport($type, $limit, $next_item);

		$this->initLog();
		$load_limit = (int)$this->arProfile['SOURCE_PARAM_2'];
		if ($load_limit > 50 || $load_limit <= 0) {
			$limit = 50;
		} else {
			$limit = $load_limit;
		}
		\CModule::IncludeModule('iblock');
		$this->fillAuthData();
		$category_id = (int)$this->arProfile['SOURCE_PARAM_1'];
		if ($category_id) {
			// Create query
			$query = $this->getRequestUri() . "?user=" . urlencode($this->source_login) .
				"&token=" . urlencode($this->source_token) .
				"&category_id=" . $category_id;
			if (is_array($this->arProfile['SOURCE_PARAM_3']) && !empty($this->arProfile['SOURCE_PARAM_3'])) {
				$params = $this->arProfile['SOURCE_PARAM_3'];
				$params = array_filter($params);
				$query  .= "&" . http_build_query($params);
			}
			if ($next_item) {
				$query .= "&startid=" . $next_item;
			}
			if (($type == self::STEP_BY_COUNT || $type == self::STEP_BY_TYME) && $limit) {
				$query .= "&limit=" . ($limit + 1); // The last item only get for the first ID of the next iteration
			}
			// Get data
			$response_headers = [];
			$str              = \CAcritImport::networkRequest($query, $response_headers);
			$arResp           = json_decode($str, true);
			$i                = 0;
			if ($arResp['error']) {
				$this->obLog->add(' Error: ' . $arResp['error'] . ' [Code ' . $arResp['code'] . ']', \Acrit\Import\Log::TYPE_ERROR);
			}
			// Import data
			if (!empty($arResp['data'])) {
				foreach ($arResp['data'] as $arItem) {
					$arRow = $arItem;
					foreach ($arRow as $k => $value) {
						if (!in_array($k, ['imgurl', 'coords'])) {
							$arRow[$k] = $this->convStrEncoding($value);
						}
					}
					foreach ($arRow['images'] as $k => $arImage) {
						$arRow['images'][$k] = $arImage['imgurl'];
					}
					$next_item = $arRow['id'];
					if (($type == self::STEP_BY_COUNT || $type == self::STEP_BY_TYME) && $limit && $i >= $limit) {
						break;
					}
					// Send data of item to the iblock
					$this->saveIBData($arRow, $next_item);
					$i++;
				}
			} else if ($arResp['status'] != 'ok' || $arResp['code'] != 200) {
				$this->obLog->add(' Error: empty response (check php param allow_url_fopen): ' . serialize($str) . ' ' . serialize($response_headers), \Acrit\Import\Log::TYPE_ERROR);
			} else {
				$this->obLog->add(' Empty response (no goods provided by your filter?): ' . serialize($str) . ' ' . serialize($response_headers), \Acrit\Import\Log::TYPE_ERROR);
			}
			sleep(5);
		}
		return $next_item;
	}
}