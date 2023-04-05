<?php
/* Copyright (C) 2004-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2023 Naël Guenfoudi
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    easyinterventionmodels/admin/setup.php
 * \ingroup easyinterventionmodels
 * \brief   EasyInterventionModels setup page.
 */

// Load Dolibarr environment

$res = 0;
// Try main.inc.php into web root known defined into CONTEXT_DOCUMENT_ROOT (not always defined)
if (!$res && !empty($_SERVER["CONTEXT_DOCUMENT_ROOT"])) {
	$res = @include $_SERVER["CONTEXT_DOCUMENT_ROOT"]."/main.inc.php";
}
// Try main.inc.php into web root detected using web root calculated from SCRIPT_FILENAME
$tmp = empty($_SERVER['SCRIPT_FILENAME']) ? '' : $_SERVER['SCRIPT_FILENAME']; $tmp2 = realpath(__FILE__); $i = strlen($tmp) - 1; $j = strlen($tmp2) - 1;
while ($i > 0 && $j > 0 && isset($tmp[$i]) && isset($tmp2[$j]) && $tmp[$i] == $tmp2[$j]) {
	$i--; $j--;
}
if (!$res && $i > 0 && file_exists(substr($tmp, 0, ($i + 1))."/main.inc.php")) {
	$res = @include substr($tmp, 0, ($i + 1))."/main.inc.php";
}
if (!$res && $i > 0 && file_exists(dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php")) {
	$res = @include dirname(substr($tmp, 0, ($i + 1)))."/main.inc.php";
}
// Try main.inc.php using relative path
if (!$res && file_exists("../../main.inc.php")) {
	$res = @include "../../main.inc.php";
}
if (!$res && file_exists("../../../main.inc.php")) {
	$res = @include "../../../main.inc.php";
}
if (!$res) {
	die("Include of main fails");
}

global $langs, $user;

// Libraries
require_once DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php";
require_once '../lib/easyinterventionmodels.lib.php';
//require_once "../class/myclass.class.php";

// Translations
$langs->loadLangs(array("admin", "easyinterventionmodels@easyinterventionmodels"));

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('easyinterventionmodelssetup', 'globalsetup'));

// Access control
if (!$user->admin) {
	accessforbidden();
}

// Parameters
$action = GETPOST('action', 'aZ09');
$backtopage = GETPOST('backtopage', 'alpha');
$modulepart = GETPOST('modulepart', 'aZ09');	// Used by actions_setmoduleoptions.inc.php

$value = GETPOST('value', 'alpha');
$label = GETPOST('label', 'alpha');
$scandir = GETPOST('scan_dir', 'alpha');
$type = 'myobject';

$arrayofparameters = array();

$error = 0;
$setupnotempty = 0;

// Set this to 1 to use the factory to manage constants. Warning, the generated module will be compatible with version v15+ only
$useFormSetup = 0;
// Convert arrayofparameter into a formSetup object
if ($useFormSetup && (float) DOL_VERSION >= 15) {
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formsetup.class.php';
	$formSetup = new FormSetup($db);

	// you can use the param convertor
	$formSetup->addItemsFromParamsArray($arrayofparameters);

	// or use the new system see exemple as follow (or use both because you can ;-) )

	/*
	// Hôte
	$item = $formSetup->newItem('NO_PARAM_JUST_TEXT');
	$item->fieldOverride = (empty($_SERVER['HTTPS']) ? 'http://' : 'https://') . $_SERVER['HTTP_HOST'];
	$item->cssClass = 'minwidth500';

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM1 as a simple string input
	$item = $formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM1');

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM1 as a simple textarea input but we replace the text of field title
	$item = $formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM2');
	$item->nameText = $item->getNameText().' more html text ';

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM3
	$item = $formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM3');
	$item->setAsThirdpartyType();

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM4 : exemple of quick define write style
	$formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM4')->setAsYesNo();

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM5
	$formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM5')->setAsEmailTemplate('thirdparty');

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM6
	$formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM6')->setAsSecureKey()->enabled = 0; // disabled

	// Setup conf EASYINTERVENTIONMODELS_MYPARAM7
	$formSetup->newItem('EASYINTERVENTIONMODELS_MYPARAM7')->setAsProduct();
	*/

	$setupnotempty = count($formSetup->items);
}


$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);


/*
 * Actions
 */

include DOL_DOCUMENT_ROOT.'/core/actions_setmoduleoptions.inc.php';

if ($action == 'updateMask') {
	$maskconst = GETPOST('maskconst', 'alpha');
	$maskvalue = GETPOST('maskvalue', 'alpha');

	if ($maskconst) {
		$res = dolibarr_set_const($db, $maskconst, $maskvalue, 'chaine', 0, '', $conf->entity);
		if (!($res > 0)) {
			$error++;
		}
	}

	if (!$error) {
		setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
	} else {
		setEventMessages($langs->trans("Error"), null, 'errors');
	}
} elseif ($action == 'specimen') {
	$modele = GETPOST('module', 'alpha');
	$tmpobjectkey = GETPOST('object');

	$tmpobject = new $tmpobjectkey($db);
	$tmpobject->initAsSpecimen();

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir) {
		$file = dol_buildpath($reldir."core/modules/easyinterventionmodels/doc/pdf_".$modele."_".strtolower($tmpobjectkey).".modules.php", 0);
		if (file_exists($file)) {
			$filefound = 1;
			$classname = "pdf_".$modele."_".strtolower($tmpobjectkey);
			break;
		}
	}

	if ($filefound) {
		require_once $file;

		$module = new $classname($db);

		if ($module->write_file($tmpobject, $langs) > 0) {
			header("Location: ".DOL_URL_ROOT."/document.php?modulepart=easyinterventionmodels-".strtolower($tmpobjectkey)."&file=SPECIMEN.pdf");
			return;
		} else {
			setEventMessages($module->error, null, 'errors');
			dol_syslog($module->error, LOG_ERR);
		}
	} else {
		setEventMessages($langs->trans("ErrorModuleNotFound"), null, 'errors');
		dol_syslog($langs->trans("ErrorModuleNotFound"), LOG_ERR);
	}
} elseif ($action == 'setmod') {
	// TODO Check if numbering module chosen can be activated by calling method canBeActivated
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'EASYINTERVENTIONMODELS_'.strtoupper($tmpobjectkey)."_ADDON";
		dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity);
	}
} elseif ($action == 'set') {
	// Activate a model
	$ret = addDocumentModel($value, $type, $label, $scandir);
} elseif ($action == 'del') {
	$ret = delDocumentModel($value, $type);
	if ($ret > 0) {
		$tmpobjectkey = GETPOST('object');
		if (!empty($tmpobjectkey)) {
			$constforval = 'EASYINTERVENTIONMODELS_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
			if ($conf->global->$constforval == "$value") {
				dolibarr_del_const($db, $constforval, $conf->entity);
			}
		}
	}
} elseif ($action == 'setdoc') {
	// Set or unset default model
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'EASYINTERVENTIONMODELS_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		if (dolibarr_set_const($db, $constforval, $value, 'chaine', 0, '', $conf->entity)) {
			// The constant that was read before the new set
			// We therefore requires a variable to have a coherent view
			$conf->global->$constforval = $value;
		}

		// We disable/enable the document template (into llx_document_model table)
		$ret = delDocumentModel($value, $type);
		if ($ret > 0) {
			$ret = addDocumentModel($value, $type, $label, $scandir);
		}
	}
} elseif ($action == 'unsetdoc') {
	$tmpobjectkey = GETPOST('object');
	if (!empty($tmpobjectkey)) {
		$constforval = 'EASYINTERVENTIONMODELS_'.strtoupper($tmpobjectkey).'_ADDON_PDF';
		dolibarr_del_const($db, $constforval, $conf->entity);
	}
}



/*
 * View
 */

$form = new Form($db);
require_once '../class/ModelLineInter.php';
$formconfirm = '';

$help_url = '';
$page_name = "EasyInterventionModelsSetup";
// Confirm deletion of line
if ($action == 'ask_deletemodel') {
	$id = $_GET ['id'];
	$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"] . '?id=' . $id, $langs->trans('DeleteInterventionModel'), $langs->trans('ConfirmDeleteInterventionModel'), 'confirm_deletemodel', '', 0, 1);
}
//delete line
if ($action == 'confirm_deletemodel' && $_GET['confirm'] == 'yes') {
	$id = $_GET ['id'];
	$model = ModelLineInter::getById($id, $db);
	$result = $model->delete();
	header("Location: " . $_SERVER["PHP_SELF"]);
	exit;

}
//save line
if (GETPOSTISSET("save")) {
	$newName = filter_var($_POST["name-modify"], FILTER_SANITIZE_STRING);
	$newContent = $_POST["content-modify"];
	$idModelUpdate = filter_var($_POST["id"], FILTER_SANITIZE_NUMBER_INT);


	if (!empty($idModelUpdate)) {
		$modelForUpdate = ModelLineInter::getById($idModelUpdate, $db);

		if (!empty($modelForUpdate)) {

			if (!empty($newName)) {
				$modelForUpdate->name = $newName;
				$modelForUpdate->content = $newContent;

				$modelForUpdate->update();
			} else {
				setEventMessage('The model name cannot be empty.', 'errors');

			}
		}
	}
}
//add line
if (GETPOSTISSET('addmodel')) {
	//$newName =  filter_var($_POST["name-add"], FILTER_SANITIZE_STRING);
	$newName = $_POST["name-add"];
	$newContent = $_POST["content-add"];
	if (!empty($newName)) {
		$newModel = new ModelLineInter($db);
		$newModel->setModel($newName, $newContent);
		$newModel->create();
	} else {
		setEventMessage('The model name cannot be empty.', 'errors');
	}
}



llxHeader('', $langs->trans($page_name), $help_url);

print '<form action="' . $_SERVER["PHP_SELF"] . '" name="addinter" method="post">';
print $formconfirm;
print ModelLineInter::getAllHtml($db);
print getFormCreateModel();
print '</form>';

// Page end
print dol_get_fiche_end();

llxFooter();
$db->close();


function getFormCreateModel()
{
	global $langs;
	$html = '<table><tr class="titre">
            <td class="nobordernopadding widthpictotitle valignmiddle col-picto">
            <span class="fas fa-plus em080 infobox-contrat valignmiddle widthpictotitle pictotitle" style=""></span>
            <div class="titre inline-block">Créer modele de ligne d intervention</div></td></tr>';

	// Add form layout with titles for the input fields
	$html .= '<tr>';

	$html .= '<td style="width: 50%;">';
	$html .= '<div class="name-section">';
	$html .= '<label  class="input-label" style="display: block;">' . $langs->trans('Name') . '</label>';
	$html .= '<input type="text" id="name-add" name="name-add" style="width: 90%;" />';
	$html .= '</div>';
	$html .= '</td>';

	// Adding DolEditor for the input area of content
	require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
	$doleditor = new DolEditor('content-add', "", '', 100, 'dolibarr_details', '', true, true, true, ROWS_2, '90%');

	$html .= '<td style="width: 50%;">';
	$html .= '<div class="content-section">';
	$html .= '<label  class="input-label"  style="vertical-align: top;">' . $langs->trans('Content') . '</label>';
	$html .= $doleditor->Create(1);
	$html .= '</div>';
	$html .= '</td>';

	// Add submit button
	$html .= '<td style="width: 25%;">';
	$html .= '<div class="submit-section" style="text-align: center;">';
	$html .= '<input type="submit" class="button button-add" style="background: var(--butactionbg); color: #FFF !important; border-radius: 3px; border-collapse: collapse; border: none;" value="' . $langs->trans('Add') . '" name="addmodel" />';
	$html .= '</div>';
	$html .= '</td>';

	$html .= '</tr>';
	$html .= '</table>';

	return $html;
}

