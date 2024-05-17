<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/prolog.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/data.import/include.php");

IncludeModuleLangFile(__FILE__);

$module_id = 'data.import';
$moduleAccessLevel = $APPLICATION->GetGroupRight($module_id);

if ($moduleAccessLevel == "D")
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));

$IBLOCK_ID = IntVal($_REQUEST["IBLOCK_ID"]);
//$OBJECTS = explode(',', $_REQUEST["OBJECTS"]);
$OBJECTS = $_REQUEST["OBJECTS"];
$CODE = strip_tags($_REQUEST["CODE"]);
$VALUE = $_REQUEST["INPUT_VALUE"];
if (CheckSerializedData($VALUE)) {
   $arData = json_decode($VALUE);
}
if(!$arData)
	$arData = [];

$CFields = new CDataImportPlanConnectionsFields;
$SECTIONS = $CFields->GetFields("SECTION", $IBLOCK_ID, false, false);
$ELEMENTS = $CFields->GetFields("ELEMENT", $IBLOCK_ID, false, false);
$PROPERTIES = Array();
$propRes = CIBlockProperty::GetList(Array("ID"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>$IBLOCK_ID));
while($propFields = $propRes->GetNext())
{
	$PROPERTIES["PROPERTY_".$propFields["ID"]] = $propFields["NAME"];
}
$PRODUCTS = $CFields->GetFields("PRODUCT", $IBLOCK_ID, false, false);
$PRICES = $CFields->GetFields("PRICE", $IBLOCK_ID, false, false);
$STORES = $CFields->GetFields("STORE", $IBLOCK_ID, false, false);

$OBJECTS_VALUES = Array();
foreach($OBJECTS as $object):
	$OBJECTS_VALUES[$object] = $$object;
endforeach;

$importFilter = new CDataImportFilter;
$compareList = $importFilter->getCompareList();
?>
<style>
<!--
#<?=$CODE?>_RESULTS table
{
	margin-bottom: 20px;
}
#<?=$CODE?>_RESULTS table.disabled
{
	display: none;
}
#<?=$CODE?>_RESULTS table thead th
{
	border-bottom: 1px solid #cecece;
}
#<?=$CODE?>_RESULTS table tbody tr td:last-of-type
{
	display: flex;
	align-items: center;
}
/*#<?=$CODE?>_OBJECT
{
	width: 70%
}
#<?=$CODE?>_Add
{
	width: 25%;
}*/
-->
</style>
<script>
function <?=$CODE?>_Add()
{
	var code = $("#<?=$CODE?>_OBJECT option:selected").val();
	var label = $("#<?=$CODE?>_OBJECT option:selected").text();
	var disabled = $("#<?=$CODE?>_OBJECT option:selected").attr("disabled");
	$("#<?=$CODE?>_OBJECT option:selected").attr("disabled", "disabled");
	//$("#<?=$CODE?>_OBJECT option:selected").remove();
	/*if($("#<?=$CODE?>_OBJECT option").length == 0)
	{
		$("#<?=$CODE?>_OBJECT").remove();
		$("#<?=$CODE?>_Add").remove();
	}*/
	if(code && label && disabled != "disabled")
	{
		$( "#<?=$CODE?>_RESULTS table" ).removeClass("disabled");
		$('#<?=$CODE?>_OBJECT option:selected').removeAttr("selected").next().attr('selected', 'selected');
		$( "#<?=$CODE?>_RESULTS table tbody" ).append(
			"<tr>" +
			"<td><input type='hidden' id='OBJECT' name='OBJECT' value='" + code +"' />" + label + ' [' + code + "]</td>" +
			"<td><select name='OBJECT_COMPARE'>" +
			<?foreach($compareList as $key => $option) {?>
			"<option value='<?=$key?>'><?=$option?></option>" +
			<? } ?>
			"</select>" + "</td>" +
			"<td class='flex'> <input type='text' name='OBJECT_VALUE' /> <span class='ui-button-icon ui-icon ui-icon-closethick delete' data-value='" + code + "'></span></td>" +
			"</tr>"
		);
	}
}
$(document).ready(function()
{
	$(document).on("click", "#<?=$CODE?>_RESULTS .delete", function(){
		var value = $(this).attr("data-value");
		if(value == undefined)
		{
			value = $(this).parents("tr").find('input[name="OBJECT"]').val();
		}
		$("#<?=$CODE?>_OBJECT option[value="+value+"]").removeAttr("disabled");
		$(this).parents("tr").remove();
	});
<?
foreach($OBJECTS_VALUES as $object):
	?>
	var <?=$object?>_values = [];
	var <?=$object?>_labels = [];
	<?
	foreach($object as $value => $label):
	?>
	<?=$object?>_values[<?=$object?>_values.length] = '<?=$value?>';
	<?=$object?>_labels[<?=$object?>_labels.length] = '<?=$label?>';
	<?
	endforeach;
	?>
	<?
endforeach;
?>
});
</script>
<form method="POST" name="<?=$CODE?>_RESULTS" id="<?=$CODE?>_RESULTS">
	<table class="ui-widget ui-widget-content<?if(!is_array($arData) && !count($arData)>0) echo ' disabled';?>" width="100%">
		<thead>
			<tr>
				<th><?=GetMessage("OBJECT");?></th>
				<th><?=GetMessage("COMPARE");?></th>
				<th><?=GetMessage("VALUE");?></th>
			</tr>
		</thead>
		<tbody>
		<?
		if(is_array($arData) && count($arData)>0)
		{
			$disabledData = Array();
			foreach($arData as $key => $data)
			{
				if($key%3 == 0)
					echo '<tr class="line">';
				echo '<td>';
				switch($data->name)
				{
					case("OBJECT"):
						foreach($OBJECTS_VALUES as $code => $object):
							foreach($object as $value => $label):
								if($value == str_replace($code."_", "", $data->value))
								{
									echo $label;
									$disabledData[$code][$value] = $label;
									//unset($OBJECTS_VALUES[$code][$value]);
								}
							endforeach;
						endforeach;
						echo "<input type='hidden' id='OBJECT' name='OBJECT' value='{$data->value}' /> [{$data->value}]";
						break;
					case("OBJECT_COMPARE"):
						echo "<select name='OBJECT_COMPARE'>";
						foreach($compareList as $key2 => $option) {
							if($data->value == $key2)
								echo "<option value='{$key2}' selected>{$option}</option>";
							else
								echo "<option value='{$key2}'>{$option}</option>";
						}
						echo "</select>";
						break;
					case("OBJECT_VALUE"):
						echo "<input type='text' name='OBJECT_VALUE' value='{$data->value}' /> <span class='ui-button-icon ui-icon ui-icon-closethick delete'></span>";
						break;
				}
				echo '</td>';
				if($key%3 == 2)
					echo '</tr>';
			}
		}
		?>
		</tbody>
	</table>
	<input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
</form>
<div style="display: flex;">
	<div class="ui-ctl ui-ctl-after-icon ui-ctl-dropdown">
		<div class="ui-ctl-after ui-ctl-icon-angle"></div>
		<select name="<?=$CODE?>_OBJECT" id="<?=$CODE?>_OBJECT" class="select-search typeselect ui-ctl-element">
		<?foreach($OBJECTS_VALUES as $code => $object):?>
			<optgroup label="<?=GetMessage("GROUP_".$code)?>">
			<?
			foreach($object as $value => $label):
			?>
			<option value="<?=$code.'_'.$value?>"<?if(isset($disabledData[$code][$value])){?> disabled<? } ?>><?=$label?></option>
			<?
			endforeach;
			?>
			</optgroup>
		<?
		endforeach;
		?>
		</select>
	</div>
	<button type="button" class="ui-btn ui-btn-icon-add ui-btn-light-border" class="button" id="<?=$CODE?>_Add" value="Y" onClick="<?=$CODE?>_Add();">
		<?=GetMessage("ADD");?>
	</button>
</div>
<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_popup_admin.php");?>