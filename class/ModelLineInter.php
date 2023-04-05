<?php



/**
 * Class ModelLineInter
 *
 * Represents a model line of intervention.
 *
 * This file is part of Dolibarr ERP/CRM software.
 *
 * Copyrigth (C) 2023 Nael Guenfoudi <guenfmen@gmail.com>
 * @license GNU General Public License v3.0
 * @link https://www.dolibarr.org/
 */
class ModelLineInter
{

    protected $db;
    const table_name = 'modellineinter';
    public $id;
    public $name;
    public $content;

    /**
     * Constructor
     *
     * @param DoliDb $db Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     * Insert a model instance in DB
     * @return int -1 if error , else if model's inter
     */
    public function create()
    {

//		$this->name = filter_var($this->name, FILTER_SANITIZE_STRING);
        // Check if object already exists

        $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . self::table_name . " WHERE name ='" . $this->name . "'";


        $resql = $this->db->query($sql);
        if ($resql) {
            $row = $this->db->fetch_object($resql);
            if ($row) {
                // Object already exists, return -1 to indicate failure
                return -1;
            }
        }

        // Object does not exist, insert into database
        $name_escaped = $this->db->escape($this->name);
        $content_escaped = $this->db->escape($this->content);

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . self::table_name . " (name, content) VALUES ('$name_escaped', '$content_escaped')";
        $resql = $this->db->query($sql);

        if ($resql) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . self::table_name, 'rowid');
            return $this->id;
        } else {
            return -1;
        }
    }

    /**
     * Load a record from the database by its id
     *
     * @param int $id Id of the record to load
     * @return bool True if the record was loaded successfully, false otherwise
     */
    public function fetch($id)
    {
        $id = filter_id($id);
        $sql = "SELECT * FROM " . MAIN_DB_PREFIX . self::table_name . " WHERE id=$id";
        $resql = $this->db->query($sql, ['id' => $id]);
        if ($resql) {
            $record = $this->db->fetch_object($resql);
            if ($record) {
                $this->id = $record->id;
                $this->name = $record->name;
                $this->content = $record->content;
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * Update the record in the database
     *
     * @return bool True if the record was updated successfully, false otherwise
     */
    public function update()
    {

        $name_escaped = $this->db->escape($this->name);
        $content_escaped = $this->db->escape($this->content);
        $id_escaped = (int)$this->id;

        $sql = "UPDATE " . MAIN_DB_PREFIX . self::table_name . " SET name='$name_escaped', content='$content_escaped' WHERE rowid=$id_escaped";
        $resql = $this->db->query($sql);


        return $resql;
    }


    /**
     * Delete the record from the database
     *
     * @return bool True if the record was deleted successfully, false otherwise
     */
    public function delete()
    {
        $sql = "DELETE FROM " . MAIN_DB_PREFIX . self::table_name . " WHERE rowid=" . $this->id;
        $resql = $this->db->query($sql);

        if ($resql) {
            // Récupérer le plus grand identifiant utilisé dans la table
            $sql = "SELECT MAX(rowid) AS max_id FROM " . MAIN_DB_PREFIX . self::table_name;
            $resql = $this->db->query($sql);

            if ($resql) {
                $row = $resql->fetch_assoc();
                $new_id = $row['max_id'] + 1;

                // Réinitialiser la séquence d'auto-incrémentation
                $sql = "ALTER TABLE " . MAIN_DB_PREFIX . self::table_name . " AUTO_INCREMENT = $new_id";
                $resql = $this->db->query($sql);
            }

            // Réinitialiser les propriétés de l'objet
            $this->id = null;
            $this->name = null;
            $this->content = null;

            return true;
        } else {
            return false;
        }
    }


    public function setModel($name, $content)
    {
        $this->name = $name;
        $this->content = $content;
    }

    public function toHtml()
    {
        $html = "<div>\n";
        $html .= "<p>ID: {$this->id}</p>\n";
        $html .= "<p>Name: {$this->name}</p>\n";
        $html .= "<p>Content: {$this->content}</p>\n";
        $html .= "</div>\n";
        return $html;
    }


    /**
     * Retrieves a list of objects from the database that match the specified criteria.
     *
     * @param DoliDb $db The database handler.
     * @param array $criteria The search criteria.
     *
     * @return array An array of matching ModelLineInter objects.
     */
    public static function getAll($db, $criteria = [])
    {
        $sql = "SELECT rowid,name,content FROM " . MAIN_DB_PREFIX . self::table_name;
        $params = [];

        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                if (!empty($value)) {
                    $sql .= " AND $key = :$key";
                    $params[$key] = $value;
                }
            }
        }

        $resql = $db->query($sql, $params);
        $objects = [];

        if ($resql) {
            while ($record = $db->fetch_object($resql)) {
                $model = new self($db);
                $model->id = $record->rowid;
                $model->name = $record->name;
                $model->content = $record->content;
                $objects[] = $model;
            }
        }

        return $objects;
    }

    /**
     * Retrieves a list of objects from the database that match the specified criteria.
     *
     * @param DoliDb $db The database handler.
     * @param array $criteria The search criteria.
     *
     * @return array An array of matching objects in the format ($id => $name).
     */
    public static function getAllForm($db, $criteria = [])
    {
        $nameTable = MAIN_DB_PREFIX . self::table_name;
        $sql = "SELECT rowid, name FROM " . $nameTable;
        $params = [];

        if (!empty($criteria)) {
            foreach ($criteria as $key => $value) {
                if (!empty($value)) {
                    $sql .= " AND $key = :$key";
                    $params[$key] = $value;
                }
            }
        }
        $resql = $db->query($sql, $params);
        $objects = [];

        if ($resql) {
            while ($obj = $db->fetch_object($resql)) {
                $objects[$obj->rowid] = $obj->name;
            }
        }

        return $objects;
    }


    /**
     * Retrieves an object from the database by its ID.
     *
     * @param int $id The ID of the object to retrieve.
     * @param DoliDb $db The database handler.
     *
     * @return ModelLineInter|null The loaded object if it exists, null otherwise.
     */
    public static function getById($id, $db)
    {
        $id = filter_var($id, FILTER_VALIDATE_INT);

        $sql = "SELECT rowid,name,content FROM " . MAIN_DB_PREFIX . self::table_name . " WHERE rowid = " . $id;
        $resql = $db->query($sql);

        if ($resql) {
            $record = $db->fetch_object($resql);
            if ($record) {
                $model = new ModelLineInter($db);
                $model->id = $record->rowid;
                $model->name = $record->name;
                $model->content = $record->content;
                return $model;
            }
        }

        return null;
    }

    /**
     * Returns a HTML table with all the models of the intervention lines.
     *
     * @param DoliDB $db The Dolibarr database object
     * @return string      The HTML table
     */
    public static function getAllHtml($db)
    {
		global $langs;
        // Get all models
        $models = self::getAll($db);

        // Initialize the HTML table with a header row
        $html = '<div><tr class="titre"><td class="nobordernopadding widthpictotitle valignmiddle col-picto"><span class="fas fa-list em080 infobox-contrat valignmiddle widthpictotitle pictotitle" style=""></span></td><td class="nobordernopadding valignmiddle col-title"><div class="titre inline-block">Modifier ligne</div></td></tr>';
        $html .= "<table class='noborder centpercent'><thead><tr class='liste_titre'><th colspan='2'>Modèles des lignes d'intervention</th></tr></thead><tbody>";

        // Loop through each model and add a row to the HTML table
        foreach ($models as $model) {
            if (GETPOST("action") == "editmodel" && GETPOST("id") == $model->id) {
                $content = empty($model->content) ? "VIDE" : $model->content;

                // Adding DolEditor for the input area of name and content.
                require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
				$inputName=	 '<input type="text" id="name-modify" name="name-modify" style="width: 90%;" />';

				$doleditorContent = new DolEditor('content-modify', $model->content, '', 100, 'dolibarr_details', '', true, true, true, ROWS_2, '90%');

				// Add a hidden input field to send the 'id' value along with the POST request
                $html .= '<input type="hidden" name="id" value="' . $model->id . '">';

                // Add a row to the HTML table with a form for editing the model
                $html .= '<tr class="nowrap">

                       <td class="oddeven"><label class="input-label center" style="display: block;">' . $langs->trans('Name') . '</label>' . $inputName . '</td>

                        <td class="oddeven">' . $doleditorContent->Create(1) . '</td> ' . self::getSaveCancelButton() . '
                      </tr>';
            } else {
                $content = empty($model->content) ? "VIDE" : $model->content;

                // Add a row to the HTML table with the model name, content, and edit/delete buttons
                $html .= '<tr class="nowrap">
                        <td class="oddeven">' . htmlspecialchars($model->name) . '</td>
                        <td class="oddeven">' . $content . '</td> ' . self::getEditDeleteButtons($model->id) . '
                      </tr>';
            }
        }

        // Close the HTML table
        $html .= '</tbody></table></div>';

        // Return the HTML table
        return $html;
    }

    /**
     * Generates HTML buttons for editing and deleting a model.
     * @param int $id The ID of the model.
     * @return string The generated HTML buttons.
     */
    private static function getEditDeleteButtons($id)
    {

        $out = '<td class="center">';
        $out .= '<a class="editfielda marginrightonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=editmodel&token=' . newToken() . '&model_id=' . $id . '#' . $id . '">';
        $out .= img_edit();
        $out .= '</a>';
        if ($id != 1) {
            $out .= '<a class="marginleftonly" href="' . $_SERVER["PHP_SELF"] . '?id=' . $id . '&action=ask_deletemodel&token=' . newToken() . '&model_id=' . $id . '">';
            $out .= img_delete();
            $out .= '</a>';
        }
        $out .= '</td>';
        return $out;
    }

    /**
     * Generates HTML code for "Save" and "Cancel" buttons in a form.
     * @param string $saveButtonName The name of the "Save" button (default: "save").
     * @param string $cancelButtonName The name of the "Cancel" button (default: "cancel").
     * @return string The generated HTML code for the buttons.
     */
    private
    static function getSaveCancelButton($saveButtonName = 'save', $cancelButtonName = 'cancel')
    {
        global $langs;
        $html = '<td class="center" colspan="5" valign="center">';
        $html .= '<input type="submit" class="button buttongen marginbottomonly button-save" name="' . $saveButtonName . '" value="' . $langs->trans("Save") . '">';
        $html .= '<input type="submit" class="button buttongen marginbottomonly button-cancel" name="' . $cancelButtonName . '" value="' . $langs->trans("Cancel") . '">';
        $html .= '</td>';
        return $html;
    }
	/**
	 * Exports all models to a JSON string.
	 *
	 * @param object $db The instance of the database.
	 * @return string The JSON encoded models.
	 */
	public static function exportToJson($db)
	{
		// Get all models from the database
		$models = self::getAll($db);

		// Convert the models to an array
		$modelsArray = [];
		foreach ($models as $model) {
			$modelsArray[] = [
				'id' => $model->id,
				'name' => $model->name,
				'content' => $model->content,
			];
		}

		// Encode the array as JSON
		$json = json_encode($modelsArray, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

		// Return the JSON encoded models
		return $json;
	}



}
