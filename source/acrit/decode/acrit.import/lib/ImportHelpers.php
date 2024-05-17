<?php
namespace Acrit\Import;

use Bitrix\Main\Loader;
use JetBrains\PhpStorm\ExpectedValues;

trait ImportHelpers
{
	public function getTmpDir(): string
	{
		$dir_path = Loader::getDocumentRoot() . '/upload/acrit.import/' . $this->arProfile['ID'];
		if (!file_exists($dir_path)) {
			if (!mkdir($dir_path, 0777, true) && !is_dir($dir_path)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir_path));
			}
		}
		return $dir_path;
	}

	public function getTmpDirImg(): string
	{
		$dir_path = $this->getTmpDir() . '/img';
		if (!file_exists($dir_path)) {
			if (!mkdir($dir_path, 0777, true) && !is_dir($dir_path)) {
				throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir_path));
			}
		}
		return $dir_path;
	}


	private function copyImportFile(string $fileDest, int $historyCntFiles = 0): void
	{
		// save N files in dir to make diagnostic
		$files = (new \Bitrix\Main\IO\Directory($this->getTmpDir()))->getChildren();
		usort($files, static function (\Bitrix\Main\IO\FileSystemEntry $a, \Bitrix\Main\IO\FileSystemEntry $b) {
			return $b->getCreationTime() <=> $a->getCreationTime();
		});
		foreach ($files as $k => $file) {
			if (++$k > $historyCntFiles || $file->isDirectory()) {
				$file->delete();
			} else {
				$fileName = $this->getTmpDir() . '/' . date("Ymd_His_", $file->getCreationTime()) . $this->arProfile['ID'];
				$fileName .= $this->arTypeParams['file_ext'] ? '.' . $this->arTypeParams['file_ext'] : '';
				$file->rename($fileName);
			}
		}

		$copyContext = null;
		if (!empty($this->arProfile['SOURCE_LOGIN']) && !empty($this->arProfile['SOURCE_KEY'])) {
			$cred = sprintf('Authorization: Basic %s', base64_encode($this->arProfile['SOURCE_LOGIN'] . ":" . $this->arProfile['SOURCE_KEY']));
			$copyContext = stream_context_create([
				'http' => [
					'header'  => $cred
				]
			]);
		}
		if (! copy($this->arProfile['SOURCE_URL'], $fileDest, $copyContext)) {
			$error = error_get_last();
			if (!is_array($error) || !isset($error['message'])) {
				$error = ['message' => '-'];
			}
			throw new \Bitrix\Main\IO\IoException(
				sprintf('Cant download file %s to destination %s [%s]!', $this->arProfile['SOURCE_URL'], $fileDest, $error['message']),
				$this->arProfile['SOURCE_URL']
			);
		}
	}

	protected function convStrEncoding($value, $encoding = false)
	{
		$enc_to = 'UTF-8';
		if (LANG_CHARSET == 'windows-1251') {
			$enc_to = 'CP1251';
		}
		if ($encoding) {
			$enc_from = $encoding;
		} elseif ($this->arProfile['ENCODING']) {
			$enc_from = $this->arProfile['ENCODING'];
		} else {
			$enc_from = 'CP1251';
		}
		if ($enc_from != $enc_to) {
			$value = mb_convert_encoding($value, $enc_to, $enc_from);
		}
		return $value;
	}

	public function mb_ucfirst($str, $encoding = 'UTF-8')
	{
		$str = mb_ereg_replace('^[\ ]+', '', $str);
		$str = mb_strtoupper(mb_substr($str, 0, 1, $encoding), $encoding) . mb_substr($str, 1, mb_strlen($str), $encoding);
		return $str;
	}

	protected function clearStr($str)
	{
		return preg_replace("/[^\p{L},\s]/u", "", $str);
	}

	/**
	 * Get files from other server
	 * @param $url
	 * @param $alt_extension
	 *
	 * @return false|string
	 */
	protected function getServerFile($url, $alt_extension = false)
	{
		$file_name = false;
		$arUrl     = parse_url($url);
		if ($arUrl['scheme']) {
			$arPath       = pathinfo($arUrl['path']);
			$dir_img_name = $this->getTmpDirImg();
			$dir_profile  = $this->getTmpDir();
			$extension    = $alt_extension ?: $arPath['extension'];
			$file_name    = $dir_img_name . '/' . date('YmdHis') . random_int(10000, 99000) . '.' . $extension;
			$hasCurl      = function_exists('curl_version');
			if ($hasCurl) {
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				/** @noinspection CurlSslServerSpoofingInspection */
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_COOKIEJAR, $dir_profile . '/cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEFILE, $dir_profile . '/cookie.txt');
				curl_exec($ch);
				curl_close($ch);

				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
				/** @noinspection CurlSslServerSpoofingInspection */
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_COOKIEJAR, $dir_profile . '/cookie.txt');
				curl_setopt($ch, CURLOPT_COOKIEFILE, $dir_profile . '/cookie.txt');
				$f_data = curl_exec($ch);
				if ($f_data) {
					file_put_contents($file_name, $f_data);
				}
				curl_close($ch);
			} else {
				$strAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0';
				$arStream = [
					'http' => [
						'method' => "GET",
						'header' => "Accept-language: en\r\n" .
							"User-Agent: {$strAgent}\r\n",
						'follow_location' => false,
					]
				];
				file_get_contents($url, false, stream_context_create($arStream));
				$arCookies = [];
				foreach ($http_response_header as $strHeader) {
					if (preg_match('#^Set-Cookie: (.*?=.*?);#i', $strHeader, $arMatch)) {
						$arCookies[] = $arMatch[1];
					}
				}
				$strCookie                           = implode(';', $arCookies);
				$arStream['http']['header']          .= 'Cookie: ' . $strCookie . "\r\n";
				$arStream['http']['follow_location'] = true;
				$f_data                              = file_get_contents($url, false, stream_context_create($arStream));
				file_put_contents($file_name, $f_data);
			}
		}
		return $file_name;
	}

	protected function getElementCode($name)
	{
		$arParams = $this->getTranslitParams('CODE');
		return \CUtil::translit($name, "ru", $arParams);
	}

	protected function getSectionCode($name)
	{
		$arParams = $this->getTranslitParams('SECTION_CODE');
		return \CUtil::translit($name, "ru", $arParams);
	}

	final protected function getTranslitParams(
		#[ExpectedValues(['CODE', 'SECTION_CODE'])]
		string $iblockParamType = 'CODE'
	): array
	{
		$arParams = ["replace_space" => "-", "replace_other" => "-"];
		if (!empty($iblockParamType) && !empty($this->arIBlock) && !empty($this->arIBlock["FIELDS"][$iblockParamType]["DEFAULT_VALUE"])) {
			$arTranslit = $this->arIBlock["FIELDS"][$iblockParamType]["DEFAULT_VALUE"];
			$transKeys = [
				'TRANS_LEN'     => 'max_len',
				'TRANS_CASE'    => 'change_case',
				'TRANS_SPACE'   => 'replace_space',
				'TRANS_OTHER'   => 'replace_other',
				'TRANS_EAT'     => 'delete_repeat_replace',
				'USE_GOOGLE'    => 'use_google'     // deprecated
			];
			$transKeysToBool = [
				'TRANS_EAT',
				'USE_GOOGLE'
			];
			foreach ($arTranslit as $param => $value) {
				if (empty($transKeys[$param])) {
					continue;
				}
				if (in_array($param, $transKeysToBool, false)) {
					$value = ($value == 'Y');
				}
				$arParams[ $transKeys[$param] ] = $value;
			}
		}
		return $arParams;
	}
}