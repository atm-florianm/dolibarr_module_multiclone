<?php

if (!class_exists('TObjetStd'))
{
	/**
	 * Needed if $form->showLinkedObjectBlock() is call
	 */
	define('INC_FROM_DOLIBARR', true);
	require_once dirname(__FILE__).'/../config.php';
}

class multiclone extends SeedObject
{

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Refused status
	 */
	const STATUS_REFUSED = 3;

	/**
	 * Accepted status
	 */
	const STATUS_ACCEPTED = 4;

	public static $TStatus = array(
		self::STATUS_DRAFT => 'Draft'
		, self::STATUS_VALIDATED => 'Validate'
		, self::STATUS_REFUSED => 'Refuse'
		, self::STATUS_ACCEPTED => 'Accept'
	);
	public $table_element = 'multiclone';
	public $element = 'multiclone';

	public function __construct($db)
	{
		global $conf, $langs;

		$this->db = $db;

		$this->fields = array(
			'ref' => array('type' => 'string', 'length' => 50, 'index' => true)
			, 'label' => array('type' => 'string')
			, 'status' => array('type' => 'integer', 'index' => true) // date, integer, string, float, array, text
			, 'entity' => array('type' => 'integer', 'index' => true)
		);

		$this->init();

		$this->status = self::STATUS_DRAFT;
		$this->entity = $conf->entity;
	}

	public function save($addprov = false)
	{
		global $user;

		if (!$this->getId())
			$this->fk_user_author = $user->id;

		$res = $this->id > 0 ? $this->updateCommon($user) : $this->createCommon($user);

		if ($addprov || !empty($this->is_clone))
		{
			$this->ref = '(PROV'.$this->getId().')';

			if (!empty($this->is_clone))
				$this->status = self::STATUS_DRAFT;

			$wc = $this->withChild;
			$this->withChild = false;
			$res = $this->id > 0 ? $this->updateCommon($user) : $this->createCommon($user);
			$this->withChild = $wc;
		}

		return $res;
	}

	public function loadBy($value, $field, $annexe = false)
	{
		$res = parent::loadBy($value, $field, $annexe);

		return $res;
	}

	public function load($id, $ref, $loadChild = true)
	{
		global $db;

		$res = parent::fetchCommon($id, $ref);

		if ($loadChild)
			$this->fetchObjectLinked();

		return $res;
	}

//	public function delete()
//	{
//		global $user;
//		
//		$this->generic->deleteObjectLinked();
//		
//		parent::deleteCommon($user);
//	}

	public function setDraft()
	{
		if ($this->status == self::STATUS_VALIDATED)
		{
			$this->status = self::STATUS_DRAFT;
			$this->withChild = false;

			return self::save();
		}

		return 0;
	}

	public function setValid()
	{
//		global $user;

		$this->ref = $this->getNumero();
		$this->status = self::STATUS_VALIDATED;

		return self::save();
	}

	public function getNumero()
	{
		if (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))
		{
			return $this->getNextNumero();
		}

		return $this->ref;
	}

	private function getNextNumero()
	{
		global $db, $conf;

		require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$mask = !empty($conf->global->MYMODULE_REF_MASK) ? $conf->global->MYMODULE_REF_MASK : 'MM{yy}{mm}-{0000}';
		$numero = get_next_value($db, $mask, 'multiclone', 'ref');

		return $numero;
	}

	public function setRefused()
	{
//		global $user;

		$this->status = self::STATUS_REFUSED;
		$this->withChild = false;

		return self::save();
	}

	public function setAccepted()
	{
//		global $user;

		$this->status = self::STATUS_ACCEPTED;
		$this->withChild = false;

		return self::save();
	}

	public function getNomUrl($withpicto = 0, $get_params = '')
	{
		global $langs;

		$result = '';
		$label = '<u>'.$langs->trans("Showmulticlone").'</u>';
		if (!empty($this->ref))
			$label .= '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;

		$linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		$link = '<a href="'.dol_buildpath('/multiclone/card.php', 1).'?id='.$this->getId().$get_params.$linkclose;

		$linkend = '</a>';

		$picto = 'generic';

		if ($withpicto)
			$result .= ($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
		if ($withpicto && $withpicto != 2)
			$result .= ' ';

		$result .= $link.$this->ref.$linkend;

		return $result;
	}

	public static function getStaticNomUrl($id, $withpicto = 0)
	{
		global $db;

		$object = new multiclone($db);
		$object->load($id, '', false);

		return $object->getNomUrl($withpicto);
	}

	public function getLibStatut($mode = 0)
	{
		return self::LibStatut($this->status, $mode);
	}

	public static function LibStatut($status, $mode)
	{
		global $langs;
		$langs->load('multiclone@multiclone');

		if ($status == self::STATUS_DRAFT)
		{
			$statustrans = 'statut0';
			$keytrans = 'multicloneStatusDraft';
			$shortkeytrans = 'Draft';
		}
		if ($status == self::STATUS_VALIDATED)
		{
			$statustrans = 'statut1';
			$keytrans = 'multicloneStatusValidated';
			$shortkeytrans = 'Validate';
		}
		if ($status == self::STATUS_REFUSED)
		{
			$statustrans = 'statut5';
			$keytrans = 'multicloneStatusRefused';
			$shortkeytrans = 'Refused';
		}
		if ($status == self::STATUS_ACCEPTED)
		{
			$statustrans = 'statut6';
			$keytrans = 'multicloneStatusAccepted';
			$shortkeytrans = 'Accepted';
		}


		if ($mode == 0)
			return img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 1)
			return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($keytrans);
		elseif ($mode == 2)
			return $langs->trans($keytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
		elseif ($mode == 3)
			return img_picto($langs->trans($keytrans), $statustrans).' '.$langs->trans($shortkeytrans);
		elseif ($mode == 4)
			return $langs->trans($shortkeytrans).' '.img_picto($langs->trans($keytrans), $statustrans);
	}

	public static function getFormConfirmClone($object)
	{
		dol_include_once('/core/class/html.form.class.php');
		global $langs, $db;
		$langs->load('multiclone@multiclone');
		$form = new Form($db);
//		if ($object->element == 'commande')
//		{
			$elem = "Order";
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
				// 1),
				array('type' => 'text', 'name' => 'cloneqty', 'label' => $langs->trans("CloneQty"), 'value' => 1),
				array('type' => 'text', 'name' => 'frequency', 'label' => $langs->trans("CloneFrequency"), 'value' => 0),
				array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company($object->socid, 'socid', '(s.client=1 OR s.client=3)', '', 0, 0, array(), 0, 'minwidth300')));
			// Paiement incomplet. On demande si motif = escompte ou autre
//		}
		/*else
		{
			if($object->element == 'facture')$elem="Invoice";
			if($object->element == 'propal')$elem="Propal";
			$formquestion = array(
				// 'text' => $langs->trans("ConfirmClone"),
				// array('type' => 'checkbox', 'name' => 'clone_content', 'label' => $langs->trans("CloneMainAttributes"), 'value' =>
				// 1),
				array('type' => 'text', 'name' => 'cloneqty', 'label' => $langs->trans("CloneQty"), 'value' => 1),
				array('type' => 'other', 'name' => 'socid', 'label' => $langs->trans("SelectThirdParty"), 'value' => $form->select_company(GETPOST('socid', 'int'), 'socid', '(s.client=1 OR s.client=3)', '', 0, 0, array(), 0, 'minwidth300')));
		}*/
		// Paiement incomplet. On demande si motif = escompte ou autre
		$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id, $langs->trans("Clone$elem"), $langs->trans("ConfirmClone$elem", $object->ref), 'confirm_clone', $formquestion, 'yes', 1);

		return $formconfirm;
	}

	static function createFromCloneCustom($socid = 0, $object,$frequency=0)
	{
		global $user, $hookmanager,$conf;

		$error = 0;

		$object->context['createfromclone'] = 'createfromclone';

		$object->db->begin();

		// get extrafields so they will be clone
		foreach ($object->lines as $line)
			$line->fetch_optionals($line->rowid);

		// Load source object
		$objFrom = clone $object;

		// Change socid if needed
		if (!empty($socid) && $socid != $object->socid)
		{
			$objsoc = new Societe($object->db);

			if ($objsoc->fetch($socid) > 0)
			{
				$object->socid = $objsoc->id;
				$object->cond_reglement_id = (!empty($objsoc->cond_reglement_id) ? $objsoc->cond_reglement_id : 0);
				$object->mode_reglement_id = (!empty($objsoc->mode_reglement_id) ? $objsoc->mode_reglement_id : 0);
				$object->fk_project = '';
				$object->fk_delivery_address = '';
			}

			// TODO Change product price if multi-prices
		}

		$object->id = 0;
		$object->ref = '';
		$object->statut = self::STATUS_DRAFT;

		// Clear fields
		$object->user_author_id = $user->id;
		$object->user_valid = '';
		$object->date = dol_now();
		if($object->element == 'facture' && ! empty($frequency))$object->date = strtotime("+$frequency month", $objFrom->date);
		if($object->element == 'commande')$object->date_commande = dol_now();
		$object->date_creation = '';
		$object->date_validation = '';
		$object->ref_client = '';
		
		// Create clone
		$result = $object->create($user);
		$object->add_object_linked($object->element, $objFrom->id);

		if($object->element == 'facture' && $conf->global->MULTICLONE_VALIDATE_INVOICE) $object->validate($user);
		else if(($object->element == 'propal' && $conf->global->MULTICLONE_VALIDATE_PROPAL) || ($object->element == 'commande' && $conf->global->MULTICLONE_VALIDATE_ORDER)) $object->valid($user);
		
		if ($result < 0)
			$error++;



		unset($object->context['createfromclone']);

		// End
		if (!$error)
		{
			$object->db->commit();
			return $object->id;
		}
		else
		{
			$object->db->rollback();
			return -1;
		}
	}
	
	static function setFactureDate($objFrom,$object,$frequency)
	{
		global $db;
		$old_date_lim_reglement = $objFrom->date_lim_reglement;
		
	    $object->date=strtotime("+$frequency month", $objFrom->date);
		$new_date_lim_reglement = $object->calculate_date_lim_reglement();
		if ($new_date_lim_reglement > $old_date_lim_reglement) $object->date_lim_reglement = $new_date_lim_reglement;
		if ($object->date_lim_reglement < $object->date) $object->date_lim_reglement = $object->date;
		
		$sql = 'UPDATE '.MAIN_DB_PREFIX.'facture SET datef="'.$db->idate($object->date).'", date_lim_reglement="'. $db->idate($object->date_lim_reglement).'" WHERE rowid='.$object->id;
		$resql = $db->query($sql);
		
	}

}

/*
class multicloneDet extends TObjetStd
{
	public $table_element = 'multiclonedet';

	public $element = 'multiclonedet';
	
	public function __construct($db)
	{
		global $conf,$langs;
		
		$this->db = $db;
		
		$this->init();
		
		$this->user = null;
	}
	
	
}
*/
