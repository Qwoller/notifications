<?php
use Bitrix\Main\Loader;
Loader::includeModule("highloadblock");

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

$hlbl_ttt = 9;
$hlblock_ttt = HL\HighloadBlockTable::getById($hlbl_ttt)->fetch();

$entity_ttt = HL\HighloadBlockTable::compileEntity($hlblock_ttt);
$entity_data_class_ttt = $entity_ttt->getDataClass();
$rsData_ttt = $entity_data_class_ttt::getList(array(
    "select" => array("*"),
));
$html_edit = '';
while($arData_ttt = $rsData_ttt->Fetch()){
    $html_edit .= '<div class="link_edit">В описании статьи id: '.$arData_ttt['UF_ID_TEXT'].' найдены запрещенные слова '.$arData_ttt['UF_NAME_MAIN'].' без пометки '.$arData_ttt['UF_DECS_NAME'].'</div>';

}

?>
<?if($html_edit):?>
<style>
    .edit_block {
        margin: 10px 0;
    }
    .link_edit {
        padding-left: 15px;
        padding-right: 15px;
        font-size: 14px;
        padding-top: 5px;
        padding-bottom: 5px;
        background: #EE93A4;
        margin-bottom: 7px;
        font-weight: 700;
    }
    .link_edit:last-child {
        margin-bottom: 0px;
    }
</style>

<div class ="edit_block">
    <?
    echo $html_edit;
    ?>
</div>
<?endif;?>