<?php
    if (!isset($_REQUEST["r"]))
        exit(1);

    require "settings.php";
    require "weblib.php";
    
    $title = "";
    $id = $_REQUEST['id'];
    switch ($id) {
        case 1:
            $title = "Smalltalk";
            break;
        case 2:
            $title = "For Sale";
            break;
        case 4:
            $title = "Tech'n'Cheats";
            break;
        case 6:
            $title = "OT";
            break;
        case 8:
            $title = "Online Gaming";
            break;
        case 19:
        	$title = "WM 2010";
        	break;
       	case 20:
        case 25:
        case 31:
       		$title = "E3";
       		break;
        case 30:
            $title = "EM";
            break;
        case 26:
            $title = "Kulturschmons";
            break;
    }
?>

<ul backHref="#boardlist" backText="Foren" class="board" title="<?php echo $title ?>" id="board:<?php echo $id ?>" refreshUrl="<?php echo $_SERVER['REQUEST_URI'] ?>">
<!--<li><a id="posttoboard" class="button" href="#post">Neuer Thread</a></li>-->
<?php
        echo showForum($id);
?>
</ul>