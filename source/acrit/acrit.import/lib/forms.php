<?php

namespace Acrit\Import;

use Bitrix\Main;

class Forms
{
    const MODULE_ID = "acrit.import";
    const ELEMENT_TYPE_TEXT = 'text';
    const ELEMENT_TYPE_CHECKBOX = 'checkbox';
    const ELEMENT_TYPE_RADIO = 'radio';
    const ELEMENT_TYPE_DATE = 'date';
    const ELEMENT_TYPE_SELECT = 'select';
	const ELEMENT_TYPE_TEXTBOX = 'textbox';

	public static function getElement($type, $name, $value, $params=[], $variants=[]) {
		$code = false;
		if ($type == self::ELEMENT_TYPE_TEXT) {
			$code = self::getInput($name, $value, $params);
		}
		elseif ($type == self::ELEMENT_TYPE_CHECKBOX) {
			$code = ''; //TODO
		}
		elseif ($type == self::ELEMENT_TYPE_RADIO) {
			$code = ''; //TODO
		}
		elseif ($type == self::ELEMENT_TYPE_SELECT) {
			$code = self::getSelect($name, $value, $params, $variants);
		}
		elseif ($type == self::ELEMENT_TYPE_TEXTBOX) {
			$code = self::getTextbox($name, $value, $params);
		}
		elseif ($type == self::ELEMENT_TYPE_DATE) {
			$code = ''; //TODO
		}
		return $code;
	}

	public static function getInput($name, $value, $params) {
		$code = '<input type="text" name="' . $name . '" value="' . $value . '"' . self::getParams($params) . ' />';
		return $code;
	}

	public static function getSelect($name, $value, $params, $variants) {
		$code = '<select name="' . $name . '" ' . self::getParams($params) . '>';
		foreach ($variants as $variant) {
			if ($variant['items']) {
				$code .= '<optgroup label="' . $variant['name'] . '">';
				foreach ($variant as $item) {
					$code .= '<option value="' . $item['value'] . '"' . ($variant['value']==$value?' selected':'') . '>' . $item['name'] . '</option>';
				}
				$code .= '</optgroup>';
			}
			else {
				$code .= '<option value="' . $variant['value'] . '"' . ($variant['value']==$value?' selected':'') . '>' . $variant['name'] . '</option>';
			}
		}
		$code .= '</select>';
		return $code;
	}

	public static function getTextbox($name, $value, $params) {
		$code = '<textbox name="' . $name . '" ' . self::getParams($params) . '>' . $value . '</textbox>';
		return $code;
	}

	public static function getParams($params) {
		$code = '';
		foreach ($params as $k => $value) {
			$code .= ' ' . $k . '="' . $value . '"';
		}
		return $code;
	}
}
