<?
$MESS["ELEMENT_EDIT_TITLE"] = "Редактирование настройки для обработки";
$MESS["ELEMENT_ADD_TITLE"] = "Добавление настройки для обработки";

$MESS["ELEMENTS_LIST"] = "Список";

$MESS["BTN_ACTIONS"] = "Действия";
$MESS["ADD_ELEMENT"] = "Добавить";
$MESS["COPY_ELEMENT"] = "Копировать";
$MESS["DEL_ELEMENT"] = "Удалить";
$MESS["DEL_ELEMENT_CONFIRM"] = "Вы действительно хотите удалить настройку?";

$MESS["ELEMENT_TAB"] = "Настройка обработки";
$MESS["ELEMENT_TAB_TITLE"] = "Основные настройки для обработки";

$MESS["GROUP_MAIN"] = "Основные";
$MESS["FIELD_ACTIVE"] = "Активность";
$MESS["FIELD_SORT"] = "Сортировка";
$MESS["FIELD_PROCESSING_TYPE"] = "Тип обработки";
$MESS["FIELD_PROCESSING_TYPE_CHOISE"] = "-- Выберите тип --";

$MESS["GROUP_PARAMS"] = "Параметры";
$MESS["FIELD_PARAMS_CHARACTER_MASK"] = "Удаляемые символы";
$MESS["FIELD_PARAMS_CHARACTER_MASK_DESCRIPTION"] = "Просто перечислите все символы, которые вы хотите удалить";
$MESS["FIELD_PARAMS_ALLOWABLE_TAGS"] = "Теги, которые не нужно удалять";
$MESS["FIELD_PARAMS_MODE"] = "Режим смены регистра";
$MESS["FIELD_PARAMS_ENCODING"] = "Кодировка";
$MESS["FIELD_PARAMS_DELIMITERS"] = "Разделители слов";
$MESS["FIELD_PARAMS_DELIMITER"] = "Разделитель";
$MESS["FIELD_PARAMS_LIMIT"] = "Ограничение";
$MESS["FIELD_PARAMS_PATTERN"] = "Шаблон";
$MESS["FIELD_PARAMS_FLAGS"] = "Флаги";
$MESS["FIELD_PARAMS_FORMAT"] = "Формат";
$MESS["FIELD_PARAMS_DECIMALS"] = "Число знаков после запятой";
$MESS["FIELD_PARAMS_DEC_POINT"] = "Разделитель дробной части";
$MESS["FIELD_PARAMS_THOUSANDS_SEP"] = "Разделитель тысяч";
$MESS["FIELD_PARAMS_SEARCH"] = "Искомое значение для замены";
$MESS["FIELD_PARAMS_REPLACE"] = "Значение замены";
$MESS["FIELD_PARAMS_REPLACEMENT_DESCRIPTION"] = "Используйте \${n}, где n - порядковый номер для вставки подмаски (от 0 до 99)";
$MESS["FIELD_PARAMS_NEEDLE"] = "Строка для начала вхождения";
$MESS["FIELD_PARAMS_BEFORE_NEEDLE"] = "Возвращать часть строки до вхождения";
$MESS["FIELD_PARAMS_OFFSET"] = "Смещение";
$MESS["FIELD_PARAMS_LENGTH"] = "Длина";
$MESS["FIELD_PARAMS_PRESERVE_KEYS"] = "Сохранить ключи";

$MESS["FIELD_PARAMS_PAD_LENGTH"] = "Длина результирующей строки";
$MESS["FIELD_PARAMS_PAD_LENGTH_NOTE"] = "Если значение отрицательно, меньше или равно длине входной строки, то дополнения не происходит и возвращается исходная строка";
$MESS["FIELD_PARAMS_PAD_STRING"] = "Строка для дополнения";
$MESS["FIELD_PARAMS_PAD_TYPE"] = "Граница для добавления";

$MESS["FIELD_PARAMS_ARITHMETIC_ADDITION"] = "Прибавляемое значение";
$MESS["FIELD_PARAMS_ARITHMETIC_SUBTRACTION"] = "Вычитаемое значение";
$MESS["FIELD_PARAMS_ARITHMETIC_MULTIPLICATION"] = "Множитель";
$MESS["FIELD_PARAMS_ARITHMETIC_DIVISION"] = "Делитель";
$MESS["FIELD_PARAMS_ARITHMETIC_MODULO"] = "Делитель";
$MESS["FIELD_PARAMS_ARITHMETIC_EXPONENTIATION"] = "Степень";

$MESS["FIELD_PARAMS_LANG"] = "Язык";
$MESS["FIELD_PARAMS_CHANGE_CASE"] = "Регистр";
$MESS["FIELD_PARAMS_REPLACE_SPACE"] = "Замена пробела";
$MESS["FIELD_PARAMS_REPLACE_OTHER"] = "Замена прочих символов";
$MESS["FIELD_PARAMS_DELETE_REPEAT_REPLACE"] = "Удалять повторяющиеся пробелы";
$MESS["FIELD_PARAMS_SAFE_CHARS"] = "Не производить замену следующих символов";

$MESS["FIELD_PARAMS_CODE"] = "Код";
$MESS["FIELD_PARAMS_CODE_ATTENTION"] = '
Языковая конструкция eval() может быть очень опасной, поскольку позволяет выполнить произвольный код. Не используйте эту обработку, если не уверены в том, что делаете.<br />
<br />
Доступные переменные:<br />
$field - значение для обработки<br />
$IMPORT_PARAMS - массив настроек импорта<br />
$ITEM_FIELDS - массив со значениями текущего объекта импорта<br />
$entities - массив с названиями сущностей<br />
<br />
Примеры:<br />
$field = ($field != "0"?"В наличии":"нет в наличии");<br />
if($field >= 100) $field = "Много"; elseif($field < 100 && $field > 10) $field = "Достаточно"; else $field = "Мало";<br />
$field = ($field == "Y"?"Да":"Нет");<br />
$field = ($field == "Да"?"Y":"N");<br />
$field = (string)500;<br />
';
$MESS["FIELD_PARAMS_CODE_NOTE"] = "Код не должен быть обернут открывающимся и закрывающимся тегами PHP. операторы должны быть разделены точкой с запятой (;) Для присвоения значения, используйте переменную \$field";

$MESS["MESSAGE_SAVE_ERROR"] = "Произошли ошибки при сохранении настройки";
$MESS["MESSAGE_ADD_BEFORE"] = "Добавьте для отображения";
$MESS["MESSAGE_ADD_BEFORE_DESCRIPTION"] = "Для отображения параметров, необходимо добавить настройку";
$MESS["MESSAGE_NO_PARAMS"] = "Нет доступных параметров";

$MESS["SAVE"] = "Сохранить";
$MESS["SAVE_TITLE"] = "Сохранить и вернуться";
$MESS["APPLY"] = "Применить";
$MESS["APPLY_TITLE"] = "Сохранить и остаться в форме";
$MESS["CANCEL"] = "Отменить";
$MESS["CANCEL_TITLE"] = "Не сохранять и вернуться";
?>