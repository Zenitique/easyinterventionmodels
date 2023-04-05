<?php


require_once "ModelLineInter.php";

/**
 * This class provides additional actions for the Intervention module in Dolibarr.
 * It allows you to override parts of the internal Dolibarr code using the Hooks class.
 * To add, delete, or modify models, go to the "EasyInterventionModel" section in the module's admin interface
 * and use the provided CRUD interface to make changes.
 *
 * @author Nael Guenfoudi
 * @version 1.0
 * @package dolibarr_module
 */
class ActionsEasyInterventionModels
{


	/**
	 * Overrides the display of intervention lines in the Intervention module in Dolibarr.
	 * Adds a dropdown list of available line models and an "Apply" button to apply the selected model to the current intervention line.
	 * This method overrides the code in finchinter/card.php that manages intervention lines.
	 *
	 * @param array $parameters Array of parameters for the function.
	 * @param Object $object The Intervention object.
	 * @param string $action The action to perform.
	 * @param HookManager $hookmanager The HookManager object.
	 * @return 1 if override , else 0
	 */
	function alterLineInter($parameters, &$object, &$action, $hookmanager)
	{


		global $user, $db, $langs, $conf;

		//print "<h1>bonjour</h1>";
		$form = new Form($db);
		//add extraFields
		$extrafields = new ExtraFields($db);
		$extralabelsline = $extrafields->fetch_name_optionals_label($object->table_element_line);


		// Intervention lines
		print '<form action="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '" name="addinter" method="post">';
		print '<input type="hidden" name="token" value="' . newToken() . '">';
		print '<input type="hidden" name="id" value="' . $object->id . '">';
		if ($action == 'editline') {
			print '<input type="hidden" name="action" value="updateline">';
			print '<input type="hidden" name="line_id" value="' . GETPOST('line_id', 'int') . '">';
		} else {
			print '<input type="hidden" name="action" value="addline">';
		}
		$sql = 'SELECT ft.rowid, ft.description, ft.fk_fichinter, ft.duree, ft.rang,';
		$sql .= ' ft.date as date_intervention';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . 'fichinterdet as ft';
		$sql .= ' WHERE ft.fk_fichinter = ' . ((int)$object->id);
		if (!empty($conf->global->FICHINTER_HIDE_EMPTY_DURATION)) {
			$sql .= ' AND ft.duree <> 0';
		}
		$sql .= ' ORDER BY ft.rang ASC, ft.date ASC, ft.rowid';


		$resql = $db->query($sql);
		if ($resql) {
			$num = $db->num_rows($resql);
			$i = 0;

			if ($num) {
				print '<br>';
				print '<table class="noborder centpercent">';
				print '<tr class="liste_titre">';

				// No.
				if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
					print '<td width="5" class="center linecolnum"></td>';
				}

				print '<td class="liste_titre">' . $langs->trans('Description') . '</td>';
				print '<td class="liste_titre center">' . $langs->trans('Date') . '</td>';
				print '<td class="liste_titre right">' . (empty($conf->global->FICHINTER_WITHOUT_DURATION) ? $langs->trans('Duration') : '') . '</td>';
				print '<td class="liste_titre">&nbsp;</td>';
				print '<td class="liste_titre">&nbsp;</td>';
				print "</tr>\n";
			}
			while ($i < $num) {
				$objp = $db->fetch_object($resql);

				// Ligne en mode visu
				if ($action != 'editline' || GETPOST('line_id', 'int') != $objp->rowid) {
					print '<tr class="oddeven">';

					// No.
					if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
						print '<td class="center linecolnum">' . ($i + 1) . '</td>';
					}

					print '<td>';
					print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne


					$objectline = new FichinterLigne($db);
					$objectline->fetch($objp->rowid);
					$objectline->fetch_optionals();

					$extrafields->fetch_name_optionals_label($objectline->table_element);
					$objectline->fetch_optionals($object->lines[$i]->rowid, $extralabelsline);
					if (!empty($extrafields)) {
						$temps = $objectline->showOptionals($extrafields, 'view', array(), '', '', 1, 'line');
						if (!empty($temps)) {
							print '<div style="padding-top: 10px" id="extrafield_lines_area_' . $line->id . '" name="extrafield_lines_area_' . $line->id . '">';
							print $temps;
							print '</div>';
						}
					}
					print dol_htmlentitiesbr($objp->description);


					print '</td>';

					// Date
					print '<td class="center" width="150">' . (empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR) ? dol_print_date($db->jdate($objp->date_intervention), 'dayhour') : dol_print_date($db->jdate($objp->date_intervention), 'day')) . '</td>';

					// Duration
					print '<td class="right" width="150">' . (empty($conf->global->FICHINTER_WITHOUT_DURATION) ? convertSecondToTime($objp->duree) : '') . '</td>';

					print "</td>\n";

					// Icon to edit and delete
					if ($object->statut == 0 && $user->hasRight('ficheinter', 'creer')) {
						print '<td class="center">';
						print '<a class="editfielda marginrightonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=editline&token=' . newToken() . '&line_id=' . $objp->rowid . '#' . $objp->rowid . '">';
						print img_edit();
						print '</a>';
						print '<a class="marginleftonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=ask_deleteline&token=' . newToken() . '&line_id=' . $objp->rowid . '">';
						print img_delete();
						print '</a></td>';
						print '<td class="center">';
						if ($num > 1) {
							if ($i > 0) {
								print '<a class="marginleftonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=up&token=' . newToken() . '&line_id=' . $objp->rowid . '">';
								print img_up();
								print '</a>';
							}
							if ($i < $num - 1) {
								print '<a class="marginleftonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $object->id . '&action=down&token=' . newToken() . '&line_id=' . $objp->rowid . '">';
								print img_down();
								print '</a>';
							}
						}
						print '</td>';
					} else {
						print '<td colspan="2">&nbsp;</td>';
					}

					print '</tr>';
				}

				// Line in update mode
				if ($object->statut == 0 && $action == 'editline' && $user->hasRight('ficheinter', 'creer') && GETPOST('line_id', 'int') == $objp->rowid) {

					//If you want modify models in update mode , uncomment
					/*//Models of intervention
					$modelsForFrom=ModelLineInter::search($db);
					print "<tr>";

					print"<table>";

					print "<tr>";
					print formForModels($modelsForFrom);
					print "</tr>";
					$modelSelected=$fieldsModel;
					//choose model from dropdown
					if(GETPOSTISSET('modeline')) {
						$modelSelected = ModelLineInter::findWithId(GETPOST('modeline'), $models);
					}*/


					print '<tr class="oddeven nohover">';

					// No.
					if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
						print '<td class="center linecolnum">' . ($i + 1) . '</td>';
					}

					print '<td>';
					print '<a name="' . $objp->rowid . '"></a>'; // ancre pour retourner sur la ligne


					$objectline = new FichinterLigne($db);
					$objectline->fetch($objp->rowid);
					$objectline->fetch_optionals();

					$extrafields->fetch_name_optionals_label($objectline->table_element);


					if (!empty($extrafields)) {
						$temps = $objectline->showOptionals($extrafields, 'edit', array(), '', '', 1, 'line');
						if (!empty($temps)) {
							print '<div style="padding-top: 10px" id="extrafield_lines_area_' . $line->id . '" name="extrafield_lines_area_' . $line->id . '">';
							print $temps;
							print '</div>';
						}
					}

					// Editeur wysiwyg
					require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
					$doleditor = new DolEditor('np_desc', $objp->description, '', 164, 'dolibarr_details', '', false, true, getDolGlobalInt('FCKEDITOR_ENABLE_DETAILS'), ROWS_2, '90%');
					$doleditor->Create();
					print '</td>';

					// Date d'intervention
					print '<td class="center nowrap">';
					if (!empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR)) {
						print $form->selectDate($db->jdate($objp->date_intervention), 'di', 0, 0, 0, "date_intervention");
					} else {
						print $form->selectDate($db->jdate($objp->date_intervention), 'di', 1, 1, 0, "date_intervention");
					}
					print '</td>';

					// Duration
					print '<td class="right">';
					if (empty($conf->global->FICHINTER_WITHOUT_DURATION)) {
						$selectmode = 'select';
						if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION)) {
							$selectmode = 'text';
						}
						$form->select_duration('duration', $objp->duree, 0, $selectmode);
					}
					print '</td>';

					print '<td class="center" colspan="5" valign="center">';
					print '<input type="submit" class="button buttongen marginbottomonly button-save" name="save" value="' . $langs->trans("Save") . '">';
					print '<input type="submit" class="button buttongen marginbottomonly button-cancel" name="cancel" value="' . $langs->trans("Cancel") . '"></td>';
					print '</tr>' . "\n";


				}

				$i++;
			}

			$db->free($resql);

			// Add new line
			if ($object->statut == 0 && $user->hasRight('ficheinter', 'creer') && $action <> 'editline' && empty($conf->global->FICHINTER_DISABLE_DETAILS)) {
				if (!$num) {
					print '<br>';
					print '<table class="noborder centpercent">';
					print '<tr class="liste_titre">';

					// No.
					if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
						print '<td width="5" class="center linecolnum"></td>';
					}

					print '<td>';
					print '<a name="add"></a>'; // ancre
					print $langs->trans('Description') . '</td>';
					print '<td class="center">' . $langs->trans('Date') . '</td>';
					print '<td class="right">' . (empty($conf->global->FICHINTER_WITHOUT_DURATION) ? $langs->trans('Duration') : '') . '</td>';
					print '<td colspan="3">&nbsp;</td>';
					print "</tr>\n";
				}


				$objectline = new FichinterLigne($db);
				$extralabel = $extrafields->fetch_name_optionals_label($objectline->table_element);

				//Models of intervention
				$modelsForFrom = ModelLineInter::getAllForm($db, array());


				//choose model from dropdown

				print '<tr class="oddeven nohover">' . "\n";

				// No.
				if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER)) {
					print '<td class="center linecolnum">' . ($i + 1) . '</td>';
				}


				if (is_object($objectline)) {
					$temps = $objectline->showOptionals($extrafields, 'create', array(), '', '', 1, 'line');


					if (!empty($temps)) {
						print '<td>';
						print '<div style="padding-top: 10px" id="extrafield_lines_area_create" name="extrafield_lines_area_create">';
						print $temps;
						print '</div>';
						print '</td>';
					}

				}


				//Editor
				print '<td colspan=2>';
				if (empty($conf->global->FICHINTER_EMPTY_LINE_DESC)) {

					print '<div class="left-side">';
					require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
					$doleditor = new DolEditor('np_desc', "", '', 100, 'dolibarr_details', '', true, true, true, ROWS_2, '90%');
					$doleditor->Create();
					print '</div>';

				}
				print '</td>';
				//Partie date , choix modele
				print '<td colspan=2>';
				print '<div class="right-side ">';
				//modele
				print formForModels($modelsForFrom);
				// Date intervention and button submit
				$now = dol_now();
				$timearray = dol_getdate($now);
				print '<div class="duration-bar">';
				if (!GETPOST('diday', 'int')) {
					$timewithnohour = dol_mktime(0, 0, 0, $timearray['mon'], $timearray['mday'], $timearray['year']);
				} else {
					$timewithnohour = dol_mktime(GETPOST('dihour', 'int'), GETPOST('dimin', 'int'), 0, GETPOST('dimonth', 'int'), GETPOST('diday', 'int'), GETPOST('diyear', 'int'));
				}
				print '<div class="center nowrap">';
				if (!empty($conf->global->FICHINTER_DATE_WITHOUT_HOUR)) {


					print $form->selectDate($timewithnohour, 'di', 0, 0, 0, "addinter");
				} else {
					print $form->selectDate($timewithnohour, 'di', 1, 1, 0, "addinter");
				}
				print '</div>';

				// Duration
				print '<div class="right">';
				if (empty($conf->global->FICHINTER_WITHOUT_DURATION)) {
					$selectmode = 'select';
					if (!empty($conf->global->INTERVENTION_ADDLINE_FREEDUREATION)) {
						$selectmode = 'text';
					}
					$form->select_duration('duration', (!GETPOST('durationhour', 'int') && !GETPOST('durationmin', 'int')) ? 3600 : (60 * 60 * GETPOST('durationhour', 'int') + 60 * GETPOST('durationmin', 'int')), 0, $selectmode);
				}
				print '</div>';
				print "<style>
.right-side {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.choicemodel.top {
  margin-bottom: 30px;
}

.duration-bar {
  display: flex;
  justify-content: space-between;
  width: 100%;
}
</style>";
				print '<div class="center" valign="middle" colspan="3"><input type="submit" class="button button-add" value="' . $langs->trans('Add') . '" name="addline"></div>';
				print '</div>';//fin partie ligne date et boutton ajouter
				print '</div>';//fin partie droite
				print '</td>';
				print getCSS();

				print '</tr>';//fin ligne d'ajout
				if (!$num) {
					print '</table>';
				}
			}

			if ($num) {
				print '</table>';
			}
		} else {
			dol_print_error($db);
		}

		print '</form>' . "\n";
		return 1;
	}

	/**
	 * Overrides the `showOptionals` method of the Extrafields class to customize the appearance and display of custom fields.
	 *
	 * @param array $parameters An array of parameters passed to the hook.
	 * @param object $object The object being processed.
	 * @param string $action The action being performed.
	 * @param HookManager $hookmanager The hook manager object.
	 * @return string The HTML output for the custom fields.
	 */
	function showOptionals($parameters, &$object, &$action, $hookmanager)
	{
		global $user, $db, $langs, $conf;
		$out = "";

		extract($parameters);
		if (key_exists('label', $extrafields->attributes[$object->table_element]) && is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0) {
			$out .= "\n";
			$out .= '<!-- commonobject:showOptionals --> ';
			$out .= "\n";

			$extrafields_collapse_num = '';
			$e = 0;
			foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $label) {
				// Show only the key field in params
				if (is_array($params) && array_key_exists('onlykey', $params) && $key != $params['onlykey']) {
					continue;
				}

				// Test on 'enabled' ('enabled' is different than 'list' = 'visibility')
				$enabled = 1;
				if ($enabled && isset($extrafields->attributes[$object->table_element]['enabled'][$key])) {
					$enabled = dol_eval($extrafields->attributes[$object->table_element]['enabled'][$key], 1, 1, '1');
				}
				if (empty($enabled)) {
					continue;
				}

				$visibility = 1;
				if ($visibility && isset($extrafields->attributes[$object->table_element]['list'][$key])) {
					$visibility = dol_eval($extrafields->attributes[$object->table_element]['list'][$key], 1, 1, '1');
				}

				$perms = 1;
				if ($perms && isset($extrafields->attributes[$object->table_element]['perms'][$key])) {
					$perms = dol_eval($extrafields->attributes[$object->table_element]['perms'][$key], 1, 1, '1');
				}

				if (($mode == 'create') && abs($visibility) != 1 && abs($visibility) != 3) {
					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list
				} elseif (($mode == 'edit') && abs($visibility) != 1 && abs($visibility) != 3 && abs($visibility) != 4) {
					continue; // <> -1 and <> 1 and <> 3 = not visible on forms, only on list and <> 4 = not visible at the creation
				} elseif ($mode == 'view' && empty($visibility)) {
					continue;
				}
				if (empty($perms)) {
					continue;
				}
				// Load language if required
				if (!empty($extrafields->attributes[$object->table_element]['langfile'][$key])) {
					$langs->load($extrafields->attributes[$object->table_element]['langfile'][$key]);
				}

				$colspan = 0;
				if (is_array($params) && count($params) > 0 && $display_type == 'card') {
					if (array_key_exists('cols', $params)) {
						$colspan = $params['cols'];
					} elseif (array_key_exists('colspan', $params)) {    // For backward compatibility. Use cols instead now.
						$reg = array();
						if (preg_match('/colspan="(\d+)"/', $params['colspan'], $reg)) {
							$colspan = $reg[1];
						} else {
							$colspan = $params['colspan'];
						}
					}
				}
				$colspan = intval($colspan);

				switch ($mode) {
					case "view":
						$value = $object->array_options["options_" . $key . $keysuffix]; // Value may be clean or formated later
						break;
					case "create":
					case "edit":
						// We get the value of property found with GETPOST so it takes into account:
						// default values overwrite, restore back to list link, ... (but not 'default value in database' of field)
						$check = 'alphanohtml';
						if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('html', 'text'))) {
							$check = 'restricthtml';
						}
						$getposttemp = GETPOST($keyprefix . 'options_' . $key . $keysuffix, $check, 3); // GETPOST can get value from GET, POST or setup of default values overwrite.
						// GETPOST("options_" . $key) can be 'abc' or array(0=>'abc')
						if (is_array($getposttemp) || $getposttemp != '' || GETPOSTISSET($keyprefix . 'options_' . $key . $keysuffix)) {
							if (is_array($getposttemp)) {
								// $getposttemp is an array but following code expects a comma separated string
								$value = implode(",", $getposttemp);
							} else {
								$value = $getposttemp;
							}
						} else {
							$value = (!empty($object->array_options["options_" . $key]) ? $object->array_options["options_" . $key] : ''); // No GET, no POST, no default value, so we take value of object.
						}
						//var_dump($keyprefix.' - '.$key.' - '.$keysuffix.' - '.$keyprefix.'options_'.$key.$keysuffix.' - '.$object->array_options["options_".$key.$keysuffix].' - '.$getposttemp.' - '.$value);
						break;
				}

				// Output value of the current field
				if ($extrafields->attributes[$object->table_element]['type'][$key] == 'separate') {
					$extrafields_collapse_num = '';
					$extrafield_param = $extrafields->attributes[$object->table_element]['param'][$key];
					if (!empty($extrafield_param) && is_array($extrafield_param)) {
						$extrafield_param_list = array_keys($extrafield_param['options']);

						if (count($extrafield_param_list) > 0) {
							$extrafield_collapse_display_value = intval($extrafield_param_list[0]);

							if ($extrafield_collapse_display_value == 1 || $extrafield_collapse_display_value == 2) {
								$extrafields_collapse_num = $extrafields->attributes[$object->table_element]['pos'][$key];
							}
						}
					}

					// if colspan=0 or 1, the second column is not extended, so the separator must be on 2 columns
					$out .= $extrafields->showSeparator($key, $object, ($colspan ? $colspan + 1 : 2), $display_type);
				} else {
					$class = (!empty($extrafields->attributes[$object->table_element]['hidden'][$key]) ? 'hideobject ' : '');
					$csstyle = '';
					if (is_array($params) && count($params) > 0) {
						if (array_key_exists('class', $params)) {
							$class .= $params['class'] . ' ';
						}
						if (array_key_exists('style', $params)) {
							$csstyle = $params['style'];
						}
					}

					// add html5 elements
					$domData = ' data-element="extrafield"';
					$domData .= ' data-targetelement="' . $object->element . '"';
					$domData .= ' data-targetid="' . $object->id . '"';

					$html_id = (empty($object->id) ? '' : 'extrarow-' . $object->element . '_' . $key . '_' . $object->id);
					if ($display_type == 'card') {
						if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && ($e % 2) == 0) {
							$colspan = 0;
						}

						if ($action == 'selectlines') {
							$colspan++;
						}
					}

					// Convert date into timestamp format (value in memory must be a timestamp)
					if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('date'))) {
						$datenotinstring = $object->array_options['options_' . $key];
						if (!is_numeric($object->array_options['options_' . $key])) {    // For backward compatibility
							$datenotinstring = $object->db->jdate($datenotinstring);
						}
						$datekey = $keyprefix . 'options_' . $key . $keysuffix;
						$value = (GETPOSTISSET($datekey)) ? dol_mktime(12, 0, 0, GETPOST($datekey . 'month', 'int', 3), GETPOST($datekey . 'day', 'int', 3), GETPOST($datekey . 'year', 'int', 3)) : $datenotinstring;
					}
					if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('datetime'))) {
						$datenotinstring = $object->array_options['options_' . $key];
						if (!is_numeric($object->array_options['options_' . $key])) {    // For backward compatibility
							$datenotinstring = $object->db->jdate($datenotinstring);
						}
						$timekey = $keyprefix . 'options_' . $key . $keysuffix;
						$value = (GETPOSTISSET($timekey)) ? dol_mktime(GETPOST($timekey . 'hour', 'int', 3), GETPOST($timekey . 'min', 'int', 3), GETPOST($timekey . 'sec', 'int', 3), GETPOST($timekey . 'month', 'int', 3), GETPOST($timekey . 'day', 'int', 3), GETPOST($timekey . 'year', 'int', 3), 'tzuserrel') : $datenotinstring;
					}
					// Convert float submited string into real php numeric (value in memory must be a php numeric)
					if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('price', 'double'))) {
						$value = (GETPOSTISSET($keyprefix . 'options_' . $key . $keysuffix) || $value) ? price2num($value) : $object->array_options['options_' . $key];
					}

					// HTML, text, select, integer and varchar: take into account default value in database if in create mode
					if (in_array($extrafields->attributes[$object->table_element]['type'][$key], array('html', 'text', 'varchar', 'select', 'int', 'boolean'))) {
						if ($action == 'create') {
							$value = (GETPOSTISSET($keyprefix . 'options_' . $key . $keysuffix) || $value) ? $value : $extrafields->attributes[$object->table_element]['default'][$key];
						}
					}
					if (isset($value)) {
						$labeltoshow = $langs->trans($label);


						$helptoshow = $langs->trans($extrafields->attributes[$object->table_element]['help'][$key]);

						if ($display_type == 'card') {
							$out .= '<tr ' . ($html_id ? 'id="' . $html_id . '" ' : '') . $csstyle . ' class="valuefieldcreate ' . $class . $object->element . '_extras_' . $key . ' trextrafields_collapse' . $extrafields_collapse_num . (!empty($object->id) ? '_' . $object->id : '') . '" ' . $domData . ' >';
							if (!empty($conf->global->MAIN_VIEW_LINE_NUMBER) && ($action == 'view' || $action == 'valid' || $action == 'editline' || $action == 'confirm_valid' || $action == 'confirm_cancel')) {
								$out .= '<td></td>';
							}
							$out .= '<td class="wordbreak';
						} elseif ($display_type == 'line') {
							$out .= '<div ' . ($html_id ? 'id="' . $html_id . '" ' : '') . $csstyle . ' class="valuefieldlinecreate ' . $class . $object->element . '_extras_' . $key . ' trextrafields_collapse' . $extrafields_collapse_num . (!empty($object->id) ? '_' . $object->id : '') . '" ' . $domData . ' >';
							$out .= '<div style="display: inline-block; padding-right:4px" class="wordbreak';
						}

						//$out .= "titlefield";
						//if (GETPOST('action', 'restricthtml') == 'create') $out.='create';
						// BUG #11554 : For public page, use red dot for required fields, instead of bold label
						$tpl_context = isset($params["tpl_context"]) ? $params["tpl_context"] : "none";
						if ($tpl_context == "public") {    // Public page : red dot instead of fieldrequired characters
							$out .= '">';
							if (!empty($extrafields->attributes[$object->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
							if ($mode != 'view' && !empty($extrafields->attributes[$object->table_element]['required'][$key])) {
								$out .= '&nbsp;<span style="color: red">*</span>';
							}
						} else {
							if ($mode != 'view' && !empty($extrafields->attributes[$this->table_element]['required'][$key])) {
								$out .= ' fieldrequired';
							}
							$out .= '">';
							if (!empty($extrafields->attributes[$this->table_element]['help'][$key])) {
								$out .= $form->textwithpicto($labeltoshow, $helptoshow);
							} else {
								$out .= $labeltoshow;
							}
						}


						$out .= ($display_type == 'card' ? '</td>' : '</div>');

						$html_id = !empty($object->id) ? $object->element . '_extras_' . $key . '_' . $object->id : '';
						if ($display_type == 'card') {
							// a first td column was already output (and may be another on before if MAIN_VIEW_LINE_NUMBER set), so this td is the next one
							$out .= '<td ' . ($html_id ? 'id="' . $html_id . '" ' : '') . ' class="' . $object->element . '_extras_' . $key . '" ' . ($colspan ? ' colspan="' . $colspan . '"' : '') . '>';
						} elseif ($display_type == 'line') {
							$out .= '<div ' . ($html_id ? 'id="' . $html_id . '" ' : '') . ' style="display: inline-block" class="' . $object->element . '_extras_' . $key . ' extra_inline_' . $extrafields->attributes[$object->table_element]['type'][$key] . '">';
						}
					}

					switch ($mode) {
						case "view":


							$out .= $extrafields->showOutputField($key, $value, '', $object->table_element);


							break;
						case "create":
							$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $object->id, $object->table_element);
							break;
						case "edit":
							$out .= $extrafields->showInputField($key, $value, '', $keysuffix, '', 0, $object->id, $object->table_element);
							break;
					}

					$out .= ($display_type == 'card' ? '</td>' : '</div>');

					if (!empty($conf->global->MAIN_EXTRAFIELDS_USE_TWO_COLUMS) && (($e % 2) == 1)) {
						$out .= ($display_type == 'card' ? '</tr>' : '</div>');
					} else {
						$out .= ($display_type == 'card' ? '</tr>' : '</div>');
					}

					$e++;
				}
			}
			$out .= "\n";
			// Add code to manage list depending on others
			if (!empty($conf->use_javascript_ajax)) {
				$out .= $object->getJSListDependancies();
			}

			$out .= '<!-- /showOptionals --> ' . "\n";
		}

		$hookmanager->resPrint = $out;
		return 1;
	}


}


/**
 * Generates a form to select a model for intervention lines.
 * The form includes a dropdown list of available models and an "Apply" button to apply the selected model to the current intervention line.
 *
 * @param array $models An array of available line models.
 * @return string The generated HTML form.
 */
function formForModels($models)
{
	global $langs, $db;

	// Create a new Dolibarr form object
	$form = new Form($db);

	// Generate the HTML for the form
	$out = '<div class="choicemodel top" style=" display: flex; align-items: center; margin-bottom: 30px">';

	// Get the currently selected model ID or use the default (1)


	// Generate the dropdown list of available models
	$id_selected = 2;
	$out .= "<p class='title-model' style='opacity:0.4'>Modèle  d'intervention :</p>";
	$out .= $form->selectarray('modeline', $models, $id_selected, 1, 0, 0, "", 0, 0, 0, 'minwidth100', 1, 0, 0, '', '', 0, '', 0, 0);

// Generate the "Apply" button
	$out .= '<input type="button" value="' . $langs->trans('Apply') . '" name="lineapply" id="lineapply" class="button  buttongen reposition nomargintop nomarginbottom" onclick="updateDoliEditorContent();" >';
	$out .= getCSSApply();
	$out .= updateDolEditorJs();

	$out .= getRefocusJs();

	// Close the form tag
	$out .= "</div>";

	// Return the generated HTML form
	return $out;
}

/**
 * @return string css for the apply Button
 */
function getCSSApply()
{
	return "<style> #lineapply{
background: var(--butactionbg);
    color: #FFF !important;
    border-radius: 3px;
    border-collapse: collapse;
    border: none;
    margin-bottom: 3px;
    margin-top: 3px;
    margin-left: 5px;
    margin-right: 5px;
    font-family: arial,tahoma,verdana,helvetica;
    display: inline-block;
    padding: 8px 15px;
    min-width: 90px;
    text-align: center;
    cursor: pointer;
    text-decoration: none !important;
}</style>";
}


/**
 * Returns a JavaScript script to scroll the page to the "Apply" button when it is clicked.
 * The script also saves the clicked state in local storage to allow the page to remember if the button has been clicked before.
 *
 * @return string The generated JavaScript script.
 */
function getRefocusJs()
{
	return "<script>
  if (document.getElementById('lineapply')) {

  // Retrieves the value of the 'clicked' key from local storage
  var clicked = localStorage.getItem('clicked') === 'true';

  // If the value is true, scrolls the page to the button
  if (clicked) {
    var button = document.getElementById('lineapply');
    var buttonPosition = button.getBoundingClientRect().top;
    window.scrollTo({ top: buttonPosition, behavior: 'auto'});
    localStorage.setItem('clicked', 'false');

  }

  // Adds an event listener for the 'click' event on the button
  document.getElementById('lineapply').addEventListener('click', function(event) {

    localStorage.setItem('clicked', 'true'); // Saves the value 'true' to local storage
  });


}
</script>";
}

/**
 * Generates the JavaScript code needed to update the DoliEditor content based on the selected model.
 *
 * @return string The JavaScript code wrapped in a <script> tag.
 */
function updateDolEditorJs()
{
	global $db;
	// Convert the models to JSON
	$modelsJson = ModelLineInter::exportToJson($db);

	return "
<script>
// Add the models data as a JSON object

var modelsData = [];
var modelsData = $modelsJson;

function updateDoliEditorContent() {
    // Get the selected model ID
    const modelId = document.getElementById('modeline').value;

    // Find the selected model in the modelsData array
    const selectedModel = modelsData.find(model => model.id == modelId);

    // Update the DoliEditor content with the selected model's content
    if (selectedModel) {
        const editorInstance = CKEDITOR.instances['np_desc'];
        editorInstance.setData(selectedModel.content);
    }
}
</script>";
}

/**
 * @return string
 */
function getCSS()
{
	return "<style>

.dropdown {
  margin-bottom: 20px;
}

.duration-bar {
  display: flex;
  justify-content: space-between;
}
</style>";
}
