<?
$MESS["MODULE_DEMO_EXPIRED"] = "data.import: Демо режим закончился, вы можете купить модуль в <a href=\"https://marketplace.1c-bitrix.ru/solutions/data.import/\" target=\"_blank\">Marketplace</a>";

$MESS["EVENT_START_PLAN"] = "Начат цикл выполнения плана импорта";
$MESS["EVENT_START_PARSE_FILE"] = "Информация из файла успешно получена";
$MESS["EVENT_PARSE_FILE"] = "Получена информация о количестве записей в файле в размере #COUNT# шт.";
$MESS["EVENT_GET_DATA_ARRAY"] = "Данные из файла были преобразованы в массив в количестве #COUNT# шт.";
$MESS["EVENT_GET_PLAN_ENTITY_CONNECTION"] = "Получены сопоставления для сущностей в количестве #COUNT# шт.";
$MESS["EVENT_SECTIONS_IS_IMPORTED"] = "Завершен импорт разделов";
$MESS["EVENT_ELEMENTS_IS_IMPORTED"] = "Завершен импорт элементов";
$MESS["EVENT_PROPERTIES_IS_IMPORTED"] = "Завершен импорт свойств для элементов";
$MESS["EVENT_PRODUCTS_IS_IMPORTED"] = "Завершен импорт товаров";
$MESS["EVENT_OFFERS_IS_IMPORTED"] = "Завершен импорт торговых предложений";
$MESS["EVENT_PRICES_IS_IMPORTED"] = "Завершен импорт цен";
$MESS["EVENT_STORE_AMOUNT_IS_IMPORTED"] = "Завершен импорт остатков по складам";
$MESS["EVENT_ENTITIES_IS_IMPORTED"] = "Завершен импорт сущностей";
$MESS["EVENT_FINISH_PLAN"] = "Завершен цикл выполнения плана импорта";
$MESS["EVENT_IMPORT_FILE_DELETE_OK"] = "Файл импорта удален";
$MESS["EVENT_IMPORT_FILE_DELETE_NO"] = "Не удалось удалить файл импорта";
$MESS["EVENT_CLEAR_DIR_OK"] = "Директория очищена успешно";
$MESS["EVENT_CLEAR_DIR_NO"] = "Не удалось очистить директорию";
$MESS["EVENT_CHANGE_OUT_SECTIONS"] = "Изменены разделы, которых не было в файле импорта";
$MESS["EVENT_CHANGE_OUT_ELEMENTS"] = "Изменены элементы, которых не было в файле импорта";
$MESS["EVENT_CHANGE_OUT_OFFERS"] = "Изменены торговые предложения, которых не было в файле импорта";
$MESS["EVENT_CHANGE_IN_SECTIONS"] = "Изменены неактивные разделы, которые были в файле импорта";
$MESS["EVENT_CHANGE_IN_ELEMENTS"] = "Изменены неактивные элементы, которые были в файле импорта";
$MESS["EVENT_CHANGE_IN_OFFERS"] = "Изменены неактивные торговые предложения, которые были в файле импорта";

$MESS["EVENT_GET_FILE"] = "Попытка поиска файла на сервере";
$MESS["EVENT_GET_FILE_SCAN"] = "Файл не найден, пробуем просканировать директорию на наличие файлов, начинающихся с такого же названия.";
$MESS["EVENT_GET_URL_FILE"] = "Попытка получения файла по url";
$MESS["ERROR_URL_NOT_VALIDE"] = "Ссылка не является валидной";

$MESS["ERROR_NO_PLAN"] = "Не указан ID плана";
$MESS["ERROR_NO_IMPORT_FILE"] = "Не доступен файл для импорта";
$MESS["ERROR_NO_ACTIVE_CONNECTIONS"] = "Отсутствуют активные сопоставления";
$MESS["ERROR_ENTITIES_AND_NAMES_NOT_IDENTICAL"] = "Названия сущностей не совпадают с содержимым файла. Проверьте файл или настройки сопоставлений";
$MESS["ERROR_ENTITIES_AND_NAMES_NOT_IDENTICAL_NOTIFY"] = "План импорта: #PLAN_ID#. Названия сущностей не совпадают с содержимым файла. <a href='/bitrix/admin/data.import_plan_connections_edit.php?ID=#PLAN_ID#&lang=#LANG_ID#'>Проверьте файл или настройки сопоставлений</a>";
$MESS["ERROR_NO_FILE_DATA"] = "Файл не содержит информации";
$MESS["ERROR_CANNOT_GET_DATA_ARRAY"] = "Не удалось преобразовать данные в массив";
$MESS["ERROR_CANNOT_GET_PLAN_ENTITY_CONNECTION"] = "Не удалось получить сопоставления или они не заданы";
$MESS["ERROR_CANNOT_ADD_UPDATE_ITEM"] = "Не удалось добавить или обновить запись";
$MESS["ERROR_REQUIRED_FIELDS_NOT_FILLED"] = "Заполнены не все обязательные поля для #OBJECT#";
$MESS["ERROR_REQUIRED_EMPTY_FIELD"] = "Пустое значение для сущности импорта #ENTITY#";
$MESS["ERROR_NO_ELEMENT_ID"] = "Не указан элемент инфоблока";
$MESS["ERROR_NO_ELEMENT_ID_FOR_PRODUCT"] = "Не указан элемент инфоблока для создания товара";
$MESS["ERROR_NO_PRODUCT_ID_FOR_PRICE"] = "Не указан ID товара для создания цены";
$MESS["ERROR_NO_PRODUCT_ID_FOR_STORE_AMOUNT"] = "Не указан ID товара для изменения остатка на складе";
$MESS["ERROR_NO_ELEMENT_ID_FOR_OFFERS"] = "Не указан элемент инфоблока для создания торгового предложения";
$MESS["ERROR_NO_OFFER_ID_FOR_PRODUCT"] = "Не указано торговое предложение для создания товара";

$MESS["ERROR_GET_FILE"] = "Не удалось найти файл или файл отсутствует.";
$MESS["ERROR_GET_URL_FILE"] = "Не удалось получить файл. Ошибка #ERROR_CODE#";

$MESS["MESSAGE_CHECK_ENTITIES_NAMES_OK"] = "Названия сущностей совпадают";
$MESS["MESSAGE_IMPORT_ITEM_FILTER"] = "Для поиска #OBJECT# был использован фильтр";
$MESS["MESSAGE_IMPORT_ITEM_FILTER_NO_SEARCH"] = "Для поиска #OBJECT# не указаны условия";
$MESS["MESSAGE_IMPORT_ITEM_FILTER_RESULT"] = "В результате поиска #OBJECT# была получена следующая информация";
$MESS["MESSAGE_IMPORT_ITEM_FILTER_RESULT_COUNT"] = "В результате поиска #OBJECT# была получена информация в количестве #COUNT# шт.";
$MESS["MESSAGE_IMPORT_ITEM_FILTER_NO_RESULT"] = "Информация по #OBJECT# не найдена. Будет произведено добавление/обновление согласно настройкам";
$MESS["MESSAGE_IMPORT_ITEM_ADD_DISABLED"] = "Добавление #OBJECT# отключено в настройках плана импорта";
$MESS["MESSAGE_IMPORT_SKIP_IMPORT_WITHOUT_SECTION"] = "Обработка #OBJECT# прекращена, т.к. указана соотствествующая настройка для случая, если не найден раздел";

$MESS["MESSAGE_IMPORT_OBJECT_NEW"] = "Найден/добавлен/обновлен #OBJECT#";
$MESS["MESSAGE_IMPORT_OBJECT_UPDATE"] = "Обновлен #OBJECT#";
$MESS["MESSAGE_IMPORT_OBJECT_UPDATE_COUNT"] = "Обновлен #OBJECT# в количестве #COUNT# шт.";

$MESS["MESSAGE_FILE_OK"] = "Файл найден успешно";
$MESS["MESSAGE_FILES_OK"] = "Файлы найдены успешно: #COUNT# шт.";
$MESS["MESSAGE_URL_OK"] = "Файл получен успешно";

$MESS["LOGS_ARE_TOO_BIG"] = "data.import: Слишком большой объемов логов! Это может привести к падению БД. Очистите журнал событий. <a href=\"/bitrix/admin/data.import_logs.php?lang=".LANGUAGE_ID."\">перейти в журнал</a>";
$MESS["CURL_NOT_INCLUDED"] = "data.import: Не подключено расширение <a target=\"_blank\" href=\"http://php.net/manual/ru/book.curl.php\">CURL</a>";
$MESS["ZIP_NOT_INCLUDED"] = "data.import: Не подключено расширение <a target=\"_blank\" href=\"https://www.php.net/manual/ru/book.zip.php\">Zip</a>";
$MESS["ZLIB_NOT_INCLUDED"] = "data.import: Не подключено расширение <a target=\"_blank\" href=\"https://www.php.net/manual/ru/book.zlib.php\">Zlib</a>";
$MESS["RAR_NOT_INCLUDED"] = "data.import: Не подключено расширение <a target=\"_blank\" href=\"https://www.php.net/manual/ru/book.rar.php\">Rar</a>";
?>