<html>
<head>
<meta charset="utf-8">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0-beta.2/css/bootstrap.min.css" integrity="sha384-PsH8R72JQ3SOdhVi3uxftmaW6Vc51MKb0q5P2rRUpPvrszuE4W1povHYgTpBfshb" crossorigin="anonymous">
<link rel="stylesheet" href="bootstrap-sortable-master/Contents/bootstrap-sortable.css">
<link rel="stylesheet" href="bootstrap-chosen-master/bootstrap-chosen.css">
<script src="bootstrap-sortable-master/Scripts/bootstrap-sortable.js"></script>
<script src="bootstrap-sortable-master/Scripts/moment.min.js"></script>
<script src="chosen_v1.8.2/chosen.jquery.js"></script>
<script>
$(function() {
	$('.chosen-select').chosen();
	$('.chosen-select-deselect').chosen({ allow_single_deselect: true });
});

function filter() {
	var name_filter, subtype_filter, owned_filter, mana_filter, text_filter, names, types, manas, texts, owned;
	subtype_filter = $('#subtypes').val();
	name_filter = $('#card_name_filter').val().toUpperCase();
	owned_filter = $('#only_owned_filter').is(':checked');
	mana_filter = $('#mana_colour').val();
	text_filter = $('#card_text_filter').val().toUpperCase();

	names = $('.card_name');
	types = $('.card_type');
	manas = $('.manacost');
	texts = $('.card_text');
	owned = $('.num_owned');
	for (var i=0;i<names.length;i++) {
		var mana_show = false;
		for (var m=0;m<mana_filter.length;m++) {
			var mana_val = manas[i].getAttribute('data-value');
			if (mana_val.indexOf(mana_filter[m]) > -1) {
				mana_show = true;
			}
			if (mana_filter[m] == "NONE" && mana_val.search(/{[A-Z]}/) == -1) {
				mana_show = true;
			}
		}
		var subtype_show = false;
		for (var s=0;s<subtype_filter.length;s++) {
			var type_val = types[i].getAttribute('data-value');
			if (type_val.indexOf(subtype_filter[s]) > -1) {
				subtype_show = true;
			}
		}
		if (names[i].getAttribute('data-value').toUpperCase().indexOf(name_filter) > -1
			&& texts[i].getAttribute('data-value').toUpperCase().indexOf(text_filter) > -1 
			&& (mana_show || mana_filter.length == 0)
			&& (subtype_show || subtype_filter.length == 0)
			&& (!owned_filter || parseInt(owned[i].getAttribute('data-value')) > 0)
			) {
			$('#cards tr:eq('+(i+1)+')').show();
		} else {
			$('#cards tr:eq('+(i+1)+')').hide();
		}
	}
}

</script>
<style>
.common_card {
	background-color: #c7c3c3
}
.uncommon_card {
	background-color: #a4e8f3
}
.rare_card {
	background-color: #e2cc7a
}
.mythic_rare_card {
	background-color: #f19536
}

.table-striped>tbody>tr.common_card:nth-child(odd) {
	background-color:rgba(0,0,0,.05);
}
.table-striped>tbody>tr.uncommon_card:nth-child(odd) {
	background-color:rgba(164, 232, 243, 0.5);
}
.table-striped>tbody>tr.rare_card:nth-child(odd) {
	background-color:rgba(226, 204, 122, 0.5);
}
.table-striped>tbody>tr.mythic_rare_card:nth-child(odd) {
	background-color:rgba(243, 149, 53, 0.5);
}
/**
    position: relative;
    width: 100%;
    min-height: 1px;
    padding-right: 15px;
    padding-left: 15px;
*/
.col-25 {
    float: left;
    width: 15%;
    margin-top: 6px;
    padding-left: 15px;
}

.col-75 {
    float: left;
    width: 75%;
    margin-top: 6px;
    padding-left: 15px;
}
.formrow:after {
    content: "";
    display: table;
    clear: both;
}
@media (max-width: 600px) {
    .col-25, .col-75, input[type=submit] {
        width: 100%;
        margin-top: 0;
    }
}
</style>
</head>
<body>
<?php

require_once(__DIR__ . "/api/includes.php");

$card_search = new CardSearch();

if (isset($_GET['set']) && $_GET['set'] !== "all") {
	$card_search->set = $_GET['set'];
}
if (isset($_GET['restrict_to_owned'])) {
	$card_search->owned = $_GET['restrict_to_owned'];
}
if (isset($_GET['search_in_text']) && $_GET['search_in_text'] !== "") {
	$card_search->text = $_GET['search_in_text'];
}
if (isset($_GET['main_type']) && $_GET['main_type'] !== "all") {
	$card_search->main_type = $_GET['main_type'];
}
if (isset($_GET['rarity']) && $_GET['rarity'] !== "all") {
	$card_search->rarity = $_GET['rarity'];
}

$dir = 'sqlite:api/mtg.db';
$dbh  = new PDO($dir) or die("cannot open the database");

$card_selection = Card::search($card_search);

//$zombies = $dbh->query("select sets.name as set_name, card.name as card_name, card.text, card.manacost, card.type, card.power, card.toughness, card.rarity from sets, card, card_types, type where type.name = 'Zombie' and card_types.type_id = type.id and card_types.card_id = card.id and sets.id = card.set_id;");
$types = $dbh->query("select type.id, type.name from type order by type.name asc");
$subtypes = $dbh->query("select type.id, type.name from type order by type.name asc");
$all_sets = Set::getAll(true);
?>
<div class="main_search_type">
	<div class="page-header">
	  <h3 style="padding-left: 15px; padding-top: 15px;">BASE CARD SELECTION</h3>
	</div>
	<form method="GET" id="main_type_search">
		<div class="formrow">
			<div class="col-25">
				<label for="main_type_select">Main Type:</label>
			</div>
			<div class="col-75">
				<select id="main_type_select" name="main_type" class="chosen-select">
					<option value="all" <?= (isset($card_search->main_type) ? '' : 'selected="selected"') ?>>SHOW ALL TYPES</option>
					<?php foreach($types as $type) { ?>
						<option value="<?= $type['id'] ?>" <?= ((isset($_GET['main_type']) && $_GET['main_type'] == $type['id']) ? 'selected="selected"' : '') ?>><?= $type['name'] ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="formrow">
			<div class="col-25">
				<label for="sets_selection">Set:</label>
			</div>
			<div class="col-75">
				<select id="sets_selection" name="set" class="chosen-select">
					<option value="all" <?= (isset($card_search->set) ? '' : 'selected="selected"') ?>>SHOW ALL SETS</option>
					<?php foreach($all_sets as $set) { ?>
						<option value="<?= $set['id'] ?>"<?= ((isset($_GET['set']) && $_GET['set'] == $set['id']) ? 'selected="selected"' : '') ?>><?= $set['name'] . " (".$set['code'].")" ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="formrow">
			<div class="col-25">
				<label for="rarity_selection">Rarity:</label>
			</div>
			<div class="col-75">
				<select id="rarity_selection" name="rarity" class="chosen-select">
					<option value="all" <?= (isset($card_search->rarity) ? '' : 'selected="selected"') ?>>SHOW ALL RARITIES</option>
					<?php 
					$all_rarities = array('Common','Uncommon','Rare','Mythic Rare');
					foreach($all_rarities as $rarity) { ?>
						<option value="<?= $rarity ?>"<?= ((isset($_GET['rarity']) && $_GET['rarity'] == $rarity) ? 'selected="selected"' : '') ?>><?= $rarity ?></option>
					<?php } ?>
				</select>
			</div>
		</div>
		<div class="formrow">
			<div class="col-25">
				<label for="main_card_text">Search in card text:</label>
			</div>
			<div class="col-75">
				<input type="text" id="main_card_text" name="search_in_text" placeholder="Search in card text..." title="card text contains" <?= (isset($_GET['search_in_text']) ? "value=\"{$_GET['search_in_text']}\"" : '') ?>><br>
			</div>
		</div>
		<div class="formrow">
			<div class="col-25">
				<label for="only_owned_base">Show only owned cards</label>
			</div>
			<div class="col-75">
				<input type="checkbox" id="only_owned_base" name="restrict_to_owned" <?= (isset($_GET['restrict_to_owned']) ? 'checked' : '') ?>><br>
			</div>
		</div>
		<div class="formrow">
			<div class="col-25">
				<input type="submit" value="Set Base">
			</div>
		</div>
	</form>
</div>
<hr>
<div class="filters">
	<div class="page-header">
	  <h3 style="padding-left: 15px;">FURTHER FILTERING OPTIONS</h3>
	</div>
	<div class="formrow">
		<div class="col-25">
			<label for="card_name_filter">Search for card name:</label>
		</div>
		<div class="col-75">
			<input type="text" id="card_name_filter" onkeyup="filter()" placeholder="Search for card name..." title="card name"><br>
		</div>
	</div>
	<div class="formrow">
		<div class="col-25">
			<label for="card_text_filter">Search in card text:</label>
		</div>
		<div class="col-75">
			<input type="text" id="card_text_filter" onkeyup="filter()" placeholder="Search in card text..." title="card text contains"><br>
		</div>
	</div>
	<div class="formrow" <?= (isset($_GET['restrict_to_owned']) ? 'hidden' : '') ?>>
		<div class="col-25">
			<label for="only_owned_filter">Show only owned cards</label>
		</div>
		<div class="col-75">
			<input type="checkbox" id="only_owned_filter" onchange="filter()"><br>
		</div>
	</div>
	<div class="formrow">
		<div class="col-25">
			<label for="mana_colour">Mana colours:</label>
		</div>
		<div class="col-75">
			<select id="mana_colour" multiple class='chosen-select' onchange="filter()">
				<option value="B">Black</option>
				<option value="U">Blue</option>
				<option value="G">Green</option>
				<option value="R">Red</option>
				<option value="W">White</option>
				<option value="C">Colourless</option>
				<option value="NONE">None</option>
			</select>
		</div>
	</div>

	<div class="formrow">
		<div class="col-25">
			<label for="subtypes">Subtypes:</label>
		</div>
		<div class="col-75">
			<select id="subtypes" multiple class='chosen-select' onchange="filter()">
				<?php foreach($subtypes as $type) { 
					if (isset($_GET['main_type']) && $_GET['main_type'] == $type['id']) continue; // it's the main type, not a subtype
				?>
					<option value="<?= $type['name'] ?>"><?= $type['name'] ?></option>
				<?php } ?>
			</select>
		</div>
	</div>
<br>
</div>
<table class="table table-striped sortable" id="cards" style="width:90%">
<col style="width:5%">
<col style="width:18%">
<col style="width:37%">
<col style="width:10%">
<col style="width:20%">
<col style="width:5%">
<col style="width:5%">
<thead>
<tr>
<th>SET</th><th>CARD</th><th>TEXT</th><th>MANA</th><th>TYPE</th><th>P/T</th><th>NUM</th>
</tr>
</thead>
<tbody>
<?php
foreach($card_selection as $z) {
?>
	<tr class="<?= strtolower(str_replace(' ','_',$z['rarity'])) ?>_card">
		<td><?= $z['set_name'] ?></td>
		<td class="card_name"><?= $z['card_name'] ?></td>
		<td class="card_text"><?= str_replace("\n",'<br><br>',$z['text']) ?></td>
		<td class="manacost"><?= $z['manacost'] ?></td>
		<td class="card_type"><?= htmlspecialchars($z['type']) ?></td>
		<td><?= ($z['power'] !== "") ? $z['power']."/".$z['toughness'] : "" ?></td>
		<td class="num_owned"><?= $z['num_own'] ?></td>
	</tr>
<?php } ?>
</tbody>
</table>
</body>
</html>
