<?php
    if (!isset($_REQUEST["r"]))
        exit(1);

    require "settings.php";
    require "weblib.php";

    $id = $_REQUEST['id'];
    $thread = $_REQUEST['thread'];
    $closed = $_REQUEST['closed'];

    $title = "";
    switch ($id) {
        case 1:
            $title = "Smalltalk";
            break;
        case 2:
            $title = "PC";
            break;
        case 3:
            $title = "Konsole";
            break;
        case 4:
            $title = "Archiv";
            break;
        case 6:
            $title = "Online-Gaming";
            break;
        case 8:
            $title = "Flohmarkt";
            break;

    }

?>

<div backHref="board.php?id=<?php echo $id ?>" backText="<?php echo $title ?>" closed="<?php echo $closed ?>" class="thread" title="Thread" id="board:<?php echo $id ?>:thread:<?php echo $thread ?>" refreshUrl="<?php echo $_SERVER['REQUEST_URI'] ?>">
<?php
        echo showThread($id,$thread,$closed);
?>
</div>