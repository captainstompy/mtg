<?php

// TODO - namespace? includes?

require_once(__DIR__ . "/db.php");

class Card {

	public $name;
	public $id;
	public $text;
	public $manacost;
	public $power;
	public $toughness;
	public $num_owned;
	public $type_str;
	public $flavour;
	public $types;
	public $set_id;
	public $rarity;
	public $associated_cards;
	public $refnum;
	public $layout;

	protected static $dbh;

	function __construct() {
		self::$dbh = DB::getDB();
	}

	public static function exists($refnum) {
		if (!isset(self::$dbh)) {
			self::$dbh = DB::getDB();
		}
		$query = "SELECT id FROM card WHERE refnum = :refnum";
		$result = self::$dbh->execQuery($query, array(':refnum'=>$refnum));
		return (count($result) > 0);
	}

	public function create() {
		if (self::exists($this->refnum)) {
			throw new Exception("Card ({$this->name}) already exists!");
		}
		$query = "INSERT INTO card 
			(name, refnum, text, flavour, manacost, power, toughness, type, num_own, rarity, set_id, layout)
			VALUES
			(:name, :refnum, :text, :flavour, :manacost, :power, :toughness, :type, :num_own, :rarity, :set_id, :layout)";
		$data = array(
			':name' => $this->name,
			':refnum' => $this->refnum,
			':text' => $this->text,
			':flavour' => $this->flavour,
			':manacost' => $this->manacost,
			':power' => $this->power,
			':toughness' => $this->toughness,
			':type' => $this->type_str,
			':num_own' => 0,
			':rarity' => $this->rarity,
			':set_id' => $this->set_id,
			':layout' => $this->layout
		);
		self::$dbh->execQuery($query, $data);
		$card_id = self::$dbh->lastInsertId();
		$this->id = $card_id;
		return $card_id;
	}

	public function update() {
		// TODO - gonna assume no updates on input...
		// except i messed up the mana...
		$query = "UPDATE card SET manacost = :cost WHERE id = :id";
		self::$dbh->execQuery($query, array(':cost'=>$this->manacost, ':id'=>$this->id));
	}

	public static function setNumOwned($card_id, $num_owned) {
		if (!isset(self::$dbh)) {
			self::$dbh = DB::getDB();
		}
		$query = "UPDATE card SET num_own = :num_own WHERE id = :id";
		self::$dbh->execQuery($query, array(':num_own'=>$num_owned, ':id'=>$card_id));
	}

	public function getById($card_id) {
		$query = "SELECT * FROM card WHERE id = :id";
		$result = self::$dbh->execQuery($query, array(':id'=>$card_id));
		if (count($result) === 0) {
			throw new Exception("Card $card_id not found");
		}
		$card_data = $result[0];
		$this->id = $card_data['id'];
		$this->name = $card_data['name'];
		$this->refnum = $card_data['refnum'];
		$this->manacost = $card_data['manacost'];
		$this->flavour = $card_data['flavour'];
		$this->text = $card_data['text'];
		$this->power = $card_data['power'];
		$this->toughness = $card_data['toughness'];
		$this->type_str = $card_data['type'];
		$this->num_own = $card_data['num_own'];
		$this->rarity = $card_data['rarity'];
		$this->set_id = $card_data['set_id'];

		$types_query = "SELECT type.id, type.name FROM type, card_types
			WHERE type.id = card_types.type_id AND card_types.card_id = :id";
		$types = self::$dbh->execQuery($types_query, array(':id' => $card_id));
		$this->types = $types;

		return $this;
	}

	public function getId() {
		if (!isset($this->refnum)) {
			throw new Exception("Must set 'refnum' to get ID");
		}
		$query = "SELECT id FROM card WHERE refnum = :ref";
		$result = self::$dbh->execQuery($query, array(':ref' => $this->refnum));
		if (count($result) === 0) {
			throw new Exception("ID not found for refnum {$this->refnum}");
		}
		$this->id = $result[0]['id'];
		return $this->id;
	}

	public static function getAll() {

	}

	public static function search(CardSearch $cs) {
		if (!isset(self::$dbh)) {
			self::$dbh = DB::getDB();
		}
		$tables = "sets, card";
		$data = array();
		if (isset($cs->main_type)) {
			$tables .= ", card_types, type ";
		}
		$where = "sets.id = card.set_id";
		if (isset($cs->main_type)) {
			$where .= " AND type.id = :id
				AND card_types.type_id = type.id
				AND card_types.card_id = card.id";
			$data[':id'] = $cs->main_type;
		}
		if (isset($cs->set)) {
			$where .= " AND sets.id = :set_id";
			$data[':set_id'] = $cs->set;
		}
		if (isset($cs->owned)) {
			$where .= " AND card.num_own > 0";
		}
		if (isset($cs->text)) {
			$where .= " AND card.text like :text";
			$data[':text'] = '%'.$cs->text.'%';
		}
		if (isset($cs->rarity)) {
			$where .= " AND card.rarity = :rarity";
			$data[':rarity'] = $cs->rarity;
		}
		$card_selection_query = "SELECT sets.name as set_name, card.name as card_name, card.text, card.manacost, card.type, card.power, card.toughness, card.rarity, card.num_own
			FROM $tables
			WHERE $where";
		$cards = self::$dbh->execQuery($card_selection_query, $data);

		return $cards;
	}

	public function createOrUpdate() {
		if (isset($this->id)) {
			$this->update();
		} else if (self::exists($this->refnum)) {
			$this->getId();
			$this->update();
		} else {
			$this->create();
		}
		return $this->id;
	}

	public function addType($type_id) {
		$query = "INSERT OR IGNORE INTO card_types (card_id, type_id) VALUES (:cid, :tid)";
		self::$dbh->execQuery($query, array(':cid'=>$this->id, ':tid'=>$type_id));
		// TODO - add type to type list?
	}

	public function addColour($colour_id) {
		$query = "INSERT OR IGNORE INTO card_colours (card_id, colour_id) VALUES (:cid, :tid)";
		self::$dbh->execQuery($query, array(':cid'=>$this->id, ':tid'=>$colour_id));
		// TODO - add type to type list?
	}
	
}

class CardSearch {

	public $set;
	public $owned;
	public $text;
	public $main_type;
	public $rarity;

}
