<?php
global $DB;
CModule::AddAutoloadClasses('data.import', array('CDataImport' => 'classes/general/import.php', 'CDataImportCSV' => 'classes/general/import_csv.php', 'CDataImportXML' => 'classes/general/import_xml.php', 'CDataImportXLS' => 'classes/general/import_xls.php', 'CDataImportXLSX' => 'classes/general/import_xlsx.php', 'Data\Import\Format\ODS' => 'lib/format/ods.php', 'Data\Import\Format\JSON' => 'lib/format/json.php', 'CDataImportAgent' => 'classes/general/agent.php', 'CDataImportFilter' => 'classes/general/filter.php', 'CDataImportConversion' => 'classes/general/conversion.php', 'CDataImportFinishEvents' => 'classes/general/finish.php', 'CDataImportSection' => 'classes/general/section.php', 'CDataImportElement' => 'classes/general/element.php', 'CDataImportProperty' => 'classes/general/property.php', 'CDataImportHighload' => 'classes/general/highload.php', 'CDataImportUtils' => 'classes/general/utils.php', 'CDataImportLog' => 'classes/' . strtolower($DB->type) . '/log.php', 'CDataImportPlan' => 'classes/' . strtolower($DB->type) . '/plan.php', 'CDataImportPlanConnections' => 'classes/' . strtolower($DB->type) . '/connections.php', 'CDataImportPlanConnectionsFields' => 'classes/general/fields.php', 'CDataImportProcessingSettings' => 'classes/' . strtolower($DB->type) . '/processing_settings.php', 'CDataImportProcessingSettingsTypes' => 'classes/general/processing_types.php', 'CDataImportPlanSearchId' => 'classes/' . strtolower($DB->type) . '/search_id.php',));